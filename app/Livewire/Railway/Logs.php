<?php

namespace App\Livewire\Railway;

use App\Livewire\Railway\Concerns\LoadsProjectContext;
use App\Models\Server;
use App\Support\RailwayResourceMapper;
use App\Support\ValidationPatterns;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Railway's environment-wide Log Explorer.
 *
 * Coolify has no aggregated log store — logs are read per container over SSH —
 * so this fans out across the environment's resources, tags each line with the
 * resource it came from, and interleaves them on the container timestamp.
 * Timestamps are therefore always requested: they are the only thing that lets
 * separate `docker logs` streams be merged into one ordered view.
 */
#[Layout('layouts.railway')]
class Logs extends Component
{
    use LoadsProjectContext;

    /** Cap the fan-out so one page load can't open dozens of SSH sessions. */
    private const MAX_CONTAINERS = 8;

    /** Lines pulled per container before filtering. */
    private const LINES_PER_CONTAINER = 200;

    /** Rows kept after merging, to bound the Livewire payload. */
    private const MAX_ROWS = 300;

    public string $filter = '';

    public string $range = '15m';

    /**
     * @var array<int, array{ts:string,sortKey:string,service:string,replica:string,message:string,level:string}>
     */
    public array $rows = [];

    public ?string $error = null;

    public function mount(string $project_uuid, string $environment_uuid): void
    {
        $this->loadProjectContext($project_uuid, $environment_uuid);
        $this->refreshLogs();
    }

    public function setRange(string $range): void
    {
        if (! array_key_exists($range, $this->ranges())) {
            return;
        }

        $this->range = $range;
        $this->refreshLogs();
    }

    public function refreshLogs(): void
    {
        $this->error = null;

        try {
            $this->rows = $this->collectRows();
        } catch (\Throwable $e) {
            $this->rows = [];
            $this->error = 'Could not read logs';
        }
    }

    /**
     * @return array<string, string>
     */
    public function ranges(): array
    {
        return [
            '15m' => 'Last 15 min',
            '1h' => 'Last 1 hour',
            '24h' => 'Last 24 hours',
            'all' => 'All Time',
        ];
    }

    public function render()
    {
        return view('livewire.railway.logs', [
            'ranges' => $this->ranges(),
            'visibleRows' => $this->visibleRows(),
        ]);
    }

    /**
     * Apply the free-text filter at render time so typing doesn't re-run SSH.
     *
     * @return array<int, array<string, string>>
     */
    protected function visibleRows(): array
    {
        $needle = trim($this->filter);
        if ($needle === '') {
            return $this->rows;
        }

        return array_values(array_filter(
            $this->rows,
            fn (array $row) => str_contains(mb_strtolower($row['message'].' '.$row['service']), mb_strtolower($needle))
        ));
    }

    /**
     * @return array<int, array<string, string>>
     */
    protected function collectRows(): array
    {
        $cutoff = $this->cutoff();
        $rows = [];
        $containersRead = 0;

        foreach (RailwayResourceMapper::resourcesFor($this->environment) as $resource) {
            if ($containersRead >= self::MAX_CONTAINERS) {
                break;
            }

            $server = $this->serverFor($resource);
            if (! $server || ! $server->isFunctional()) {
                continue;
            }

            // Same ownership guard the classic log viewer uses — never read a
            // container on a server this team does not own.
            if (! Server::ownedByCurrentTeam()->where('id', $server->id)->exists()) {
                continue;
            }

            foreach ($this->containersFor($resource) as $container) {
                if ($containersRead >= self::MAX_CONTAINERS) {
                    break;
                }
                if (! ValidationPatterns::isValidContainerName($container)) {
                    continue;
                }

                $containersRead++;

                try {
                    $output = getContainerLogs($server, $container, self::LINES_PER_CONTAINER, true);
                } catch (\Throwable $e) {
                    // A stopped or missing container is normal here; skip it
                    // rather than failing the whole explorer.
                    continue;
                }

                foreach ($this->parseLines($output, $resource->name ?? $container, $container, $cutoff) as $row) {
                    $rows[] = $row;
                }
            }
        }

        usort($rows, fn ($a, $b) => $a['sortKey'] <=> $b['sortKey']);

        return array_slice($rows, -self::MAX_ROWS);
    }

    /**
     * Split `docker logs -t` output into rows, dropping anything older than the
     * selected range. Docker prefixes each line with an RFC3339Nano timestamp.
     *
     * @return array<int, array<string, string>>
     */
    protected function parseLines(string $output, string $service, string $container, ?\DateTimeImmutable $cutoff): array
    {
        $replica = str($container)->limit(8, '')->toString();
        $rows = [];

        foreach (preg_split('/\r?\n/', $output) ?: [] as $line) {
            if (trim($line) === '') {
                continue;
            }

            $timestamp = null;
            $message = $line;

            if (preg_match('/^(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?Z?)\s(.*)$/s', $line, $matches)) {
                try {
                    $timestamp = new \DateTimeImmutable($matches[1]);
                    $message = $matches[2];
                } catch (\Throwable $e) {
                    $timestamp = null;
                }
            }

            if ($cutoff && $timestamp && $timestamp < $cutoff) {
                continue;
            }

            $rows[] = [
                'ts' => $timestamp?->format('M d H:i:s') ?? '',
                'sortKey' => $timestamp?->format('Y-m-d\TH:i:s.u') ?? '',
                'service' => $service,
                'replica' => $replica,
                'message' => $message,
                'level' => $this->levelFor($message),
            ];
        }

        return $rows;
    }

    /**
     * Railway tints error lines; everything else renders as normal output.
     */
    protected function levelFor(string $message): string
    {
        return preg_match('/\b(error|fatal|exception|panic|failed)\b/i', $message) === 1 ? 'error' : 'info';
    }

    protected function cutoff(): ?\DateTimeImmutable
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        return match ($this->range) {
            '15m' => $now->modify('-15 minutes'),
            '1h' => $now->modify('-1 hour'),
            '24h' => $now->modify('-24 hours'),
            default => null,
        };
    }

    protected function serverFor(Model $resource): ?Server
    {
        return optional($resource->destination ?? null)->server
            ?? ($resource->server ?? null);
    }

    /**
     * Resolve container names per resource kind. Applications are discovered at
     * runtime by label; databases are named after their uuid; a service's
     * containers are `{subname}-{service uuid}`.
     *
     * @return array<int, string>
     */
    protected function containersFor(Model $resource): array
    {
        $kind = RailwayResourceMapper::kind($resource);

        try {
            if ($kind === 'application') {
                $server = $this->serverFor($resource);
                if (! $server) {
                    return [];
                }

                return collect(getCurrentApplicationContainerStatus($server, $resource->id))
                    ->pluck('Names')
                    ->filter()
                    ->take(2)
                    ->values()
                    ->all();
            }

            if ($kind === 'service') {
                return collect($resource->applications()->get())
                    ->concat($resource->databases()->get())
                    ->map(fn ($sub) => data_get($sub, 'name').'-'.$resource->uuid)
                    ->filter()
                    ->take(4)
                    ->values()
                    ->all();
            }

            return array_filter([$resource->uuid ?? null]);
        } catch (\Throwable $e) {
            return [];
        }
    }
}
