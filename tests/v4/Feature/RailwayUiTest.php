<?php

use App\Livewire\LayoutPopups;
use App\Livewire\Railway\Canvas;
use App\Livewire\Railway\Logs as RailwayLogs;
use App\Livewire\Railway\Projects;
use App\Livewire\Railway\ServicePanel;
use App\Livewire\Railway\Settings;
use App\Models\Application;
use App\Models\Environment;
use App\Models\InstanceSettings;
use App\Models\Project;
use App\Models\Server;
use App\Models\StandaloneDocker;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Support\RailwayResourceMapper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    // `id` is not fillable, so seed record 0 unguarded so instanceSettings() (findOrFail(0)) resolves.
    InstanceSettings::unguarded(fn () => InstanceSettings::query()->create(['id' => 0]));
    Queue::fake();

    $this->user = User::factory()->create(['password' => Hash::make('test-password')]);
    $this->team = Team::factory()->create();
    $this->user->teams()->attach($this->team, ['role' => 'owner']);

    $this->server = Server::factory()->create(['team_id' => $this->team->id]);
    $this->destination = StandaloneDocker::factory()->create([
        'server_id' => $this->server->id,
        'network' => 'test-network-'.fake()->unique()->word(),
    ]);
    $this->project = Project::factory()->create(['team_id' => $this->team->id, 'name' => 'Mimic Internal']);
    $this->environment = Environment::factory()->create(['project_id' => $this->project->id]);

    $this->application = Application::factory()->create([
        'name' => 'staging-app',
        'status' => 'running:healthy',
        'environment_id' => $this->environment->id,
        'destination_id' => $this->destination->id,
        'destination_type' => $this->destination->getMorphClass(),
    ]);

    $this->actingAs($this->user);
    session(['currentTeam' => $this->team]);
});

it('renders the Railway projects dashboard with the project card', function () {
    // Railway's dashboard has no "Projects" heading: the workspace name is the
    // title and each card carries the project name plus its service count.
    Livewire::test(Projects::class)
        ->assertOk()
        ->assertSee('Mimic Internal')
        ->assertSee('1 service');
});

it('points each dashboard project card at an environment canvas', function () {
    // Railway's card is a whole-project entry that drops you straight into an
    // environment, so the card needs a resolved canvas target and a total
    // service count across every environment.
    $groups = Livewire::test(Projects::class)->assertOk()->viewData('groups');

    expect($groups)->toHaveCount(1);

    $card = $groups->first();

    // The count spans every environment, while the link defaults to
    // production the way Railway's dashboard does.
    $production = $this->project->environments()->where('name', 'production')->firstOrFail();

    expect($card['name'])->toBe('Mimic Internal')
        ->and($card['service_count'])->toBe(1)
        ->and($card['url'])->toBe(route('railway.canvas', [
            'project_uuid' => $this->project->uuid,
            'environment_uuid' => $production->uuid,
        ]));
});

it('renders the architecture canvas with the resource node', function () {
    Livewire::test(Canvas::class, [
        'project_uuid' => $this->project->uuid,
        'environment_uuid' => $this->environment->uuid,
    ])
        ->assertOk()
        ->assertSee('staging-app')
        ->assertSee('Online');
});

it('renders the section tabs in the header rather than a side rail', function () {
    // Railway has no left icon rail: Architecture / Observability / Logs /
    // Settings live in the project header, and the active one is underlined.
    $rendered = Livewire::test(Canvas::class, [
        'project_uuid' => $this->project->uuid,
        'environment_uuid' => $this->environment->uuid,
    ])->assertOk();

    foreach (['Architecture', 'Observability', 'Logs', 'Settings'] as $tab) {
        $rendered->assertSee($tab);
    }

    $rendered->assertSee('rw-tab-active-marker', escape: false)
        ->assertDontSee('rw-rail-item', escape: false);
});

it('opens the service panel for a selected resource', function () {
    Livewire::test(ServicePanel::class, [
        'environment' => $this->environment,
        'project' => $this->project,
    ])
        ->assertSet('open', false)
        ->dispatch('openServicePanel', uuid: $this->application->uuid, kind: 'application')
        ->assertSet('open', true)
        ->assertSet('kind', 'application')
        ->assertSee('staging-app')
        ->assertSee('Deployments');
});

it('persists a canvas node position', function () {
    Livewire::test(Canvas::class, [
        'project_uuid' => $this->project->uuid,
        'environment_uuid' => $this->environment->uuid,
    ])->call('savePosition', $this->application->uuid, 120, 240);

    $this->assertDatabaseHas('railway_canvas_positions', [
        'environment_id' => $this->environment->id,
        'resource_uuid' => $this->application->uuid,
        'x' => 120,
        'y' => 240,
    ]);
});

it('maps an environment\'s resources into node view-models', function () {
    $resources = RailwayResourceMapper::resourcesFor($this->environment);
    expect($resources)->toHaveCount(1);

    $node = RailwayResourceMapper::toNode($resources->first(), $this->project->uuid, $this->environment->uuid);
    expect($node['name'])->toBe('staging-app');
    expect($node['kind'])->toBe('application');
    expect($node['online'])->toBeTrue();
});

it('treats a non-running status as offline', function () {
    $this->application->update(['status' => 'exited']);

    expect(RailwayResourceMapper::isOnline($this->application->fresh()))->toBeFalse();
});

it('invites a new member from the Railway settings page', function () {
    Livewire::test(Settings::class, [
        'project_uuid' => $this->project->uuid,
        'environment_uuid' => $this->environment->uuid,
    ])
        ->set('section', 'members')
        ->set('inviteEmail', 'newbie@example.com')
        ->set('inviteRole', 'member')
        ->call('inviteMember', false)
        ->assertHasNoErrors()
        ->assertSet('generatedInviteLink', fn ($link) => filled($link));

    $this->assertDatabaseHas('team_invitations', [
        'team_id' => $this->team->id,
        'email' => 'newbie@example.com',
        'role' => 'member',
    ]);
    // A brand-new invitee also gets a user record with a forced password reset.
    $this->assertDatabaseHas('users', [
        'email' => 'newbie@example.com',
        'force_password_reset' => true,
    ]);
});

it('rejects an invalid invite email', function () {
    Livewire::test(Settings::class, [
        'project_uuid' => $this->project->uuid,
        'environment_uuid' => $this->environment->uuid,
    ])
        ->set('section', 'members')
        ->set('inviteEmail', 'not-an-email')
        ->call('inviteMember', false)
        ->assertHasErrors('inviteEmail');

    expect(TeamInvitation::count())->toBe(0);
});

it('prevents a member from inviting an owner (privilege escalation)', function () {
    $member = User::factory()->create(['password' => Hash::make('test-password')]);
    $member->teams()->attach($this->team, ['role' => 'member']);
    $this->actingAs($member);
    session(['currentTeam' => $this->team]);

    Livewire::test(Settings::class, [
        'project_uuid' => $this->project->uuid,
        'environment_uuid' => $this->environment->uuid,
    ])
        ->set('section', 'members')
        ->set('inviteEmail', 'boss@example.com')
        ->set('inviteRole', 'owner')
        ->call('inviteMember', false);

    expect(TeamInvitation::whereEmail('boss@example.com')->exists())->toBeFalse();
});

it('revokes a pending invitation', function () {
    $invitation = TeamInvitation::create([
        'team_id' => $this->team->id,
        'uuid' => new_public_id(32),
        'email' => 'pending@example.com',
        'role' => 'member',
        'link' => 'https://example.com/invite',
        'via' => 'link',
    ]);

    Livewire::test(Settings::class, [
        'project_uuid' => $this->project->uuid,
        'environment_uuid' => $this->environment->uuid,
    ])
        ->set('section', 'members')
        ->call('revokeInvitation', $invitation->id);

    $this->assertDatabaseMissing('team_invitations', ['id' => $invitation->id]);
});

it('adds a custom domain and surfaces the DNS records to configure', function () {
    $this->server->update(['ip' => '203.0.113.10']);

    Livewire::test(ServicePanel::class, [
        'environment' => $this->environment,
        'project' => $this->project,
    ])
        ->dispatch('openServicePanel', uuid: $this->application->uuid, kind: 'application')
        ->set('newDomain', 'app.example.com')
        ->call('addDomain')
        ->assertSet('domains', ['https://app.example.com'])
        ->assertSet('newDomain', '')
        ->assertSet('dnsDomain', 'app.example.com');

    expect($this->application->fresh()->fqdn)->toBe('https://app.example.com');
});

it('rejects an invalid custom domain without persisting it', function () {
    Livewire::test(ServicePanel::class, [
        'environment' => $this->environment,
        'project' => $this->project,
    ])
        ->dispatch('openServicePanel', uuid: $this->application->uuid, kind: 'application')
        ->set('newDomain', 'not-a-domain')
        ->call('addDomain')
        ->assertSet('domains', [])
        ->assertSet('dnsDomain', null);

    expect($this->application->fresh()->fqdn)->toBeNull();
});

it('removes a domain and clears the persisted fqdn', function () {
    $this->application->update(['fqdn' => 'https://app.example.com']);

    Livewire::test(ServicePanel::class, [
        'environment' => $this->environment,
        'project' => $this->project,
    ])
        ->dispatch('openServicePanel', uuid: $this->application->uuid, kind: 'application')
        ->assertSet('domains', ['https://app.example.com'])
        ->call('removeDomain', 0)
        ->assertSet('domains', []);

    expect($this->application->fresh()->fqdn)->toBeNull();
});

it('changes a member role and removes a member', function () {
    $member = User::factory()->create(['password' => Hash::make('test-password')]);
    $member->teams()->attach($this->team, ['role' => 'member']);

    $component = Livewire::test(Settings::class, [
        'project_uuid' => $this->project->uuid,
        'environment_uuid' => $this->environment->uuid,
    ])->set('section', 'members');

    $component->call('changeRole', $member->id, 'admin');
    $this->assertDatabaseHas('team_user', [
        'team_id' => $this->team->id,
        'user_id' => $member->id,
        'role' => 'admin',
    ]);

    $component->call('removeMember', $member->id);
    $this->assertDatabaseMissing('team_user', [
        'team_id' => $this->team->id,
        'user_id' => $member->id,
    ]);
});

it('suppresses the Coolify promo popups on Railway pages', function () {
    // The sponsorship and notification popups are Coolify-branded and break
    // the skin, so the Railway layout opts out. The realtime-connection
    // warning is deliberately unaffected — it reports a real fault.
    Livewire::test(LayoutPopups::class, ['showPromos' => false])
        ->assertOk()
        ->assertDontSee('Love Coolify?');

    Livewire::test(LayoutPopups::class)
        ->assertOk()
        ->assertSet('showPromos', true);
});

it('lists service variables at Railway density with values masked', function () {
    $this->application->environment_variables()->createMany([
        ['key' => 'API_TOKEN', 'value' => 'super-secret-value'],
        ['key' => 'SERVICE_FQDN_APP', 'value' => 'https://example.test'],
    ]);

    $panel = Livewire::test(ServicePanel::class, [
        'environment' => $this->environment,
        'project' => $this->project,
    ])->dispatch('openServicePanel', uuid: $this->application->uuid, kind: 'application');

    $variables = collect($panel->get('variables'));

    // Railway masks every value with a fixed-width run of dots, so a value's
    // length can't be inferred from the row.
    expect($variables->firstWhere('key', 'API_TOKEN'))
        ->toMatchArray(['masked' => '•••••••', 'provided' => false]);

    // SERVICE_-prefixed variables are Coolify-provided and fold into the
    // collapsed group, mirroring Railway's "provided variables" row.
    expect($variables->firstWhere('key', 'SERVICE_FQDN_APP')['provided'])->toBeTrue();

    $panel->assertDontSee('super-secret-value');
});

it('adds and removes a service variable from the Railway list', function () {
    $panel = Livewire::test(ServicePanel::class, [
        'environment' => $this->environment,
        'project' => $this->project,
    ])
        ->dispatch('openServicePanel', uuid: $this->application->uuid, kind: 'application')
        ->set('newVariableKey', 'FEATURE_FLAG')
        ->set('newVariableValue', 'on')
        ->call('addVariable')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('environment_variables', [
        'key' => 'FEATURE_FLAG',
        'resourceable_id' => $this->application->id,
    ]);

    $created = $this->application->environment_variables()->where('key', 'FEATURE_FLAG')->firstOrFail();

    $panel->call('deleteVariable', $created->id);

    $this->assertDatabaseMissing('environment_variables', ['id' => $created->id]);
});

it('rejects a variable key that is not a valid environment name', function () {
    Livewire::test(ServicePanel::class, [
        'environment' => $this->environment,
        'project' => $this->project,
    ])
        ->dispatch('openServicePanel', uuid: $this->application->uuid, kind: 'application')
        ->set('newVariableKey', '9-not valid')
        ->call('addVariable')
        ->assertHasErrors('newVariableKey');

    expect($this->application->environment_variables()->where('key', '9-not valid')->exists())->toBeFalse();
});

it('never reveals a shown-once variable', function () {
    $secret = $this->application->environment_variables()->create([
        'key' => 'ONE_TIME_TOKEN',
        'value' => 'do-not-display',
        'is_shown_once' => true,
    ]);

    Livewire::test(ServicePanel::class, [
        'environment' => $this->environment,
        'project' => $this->project,
    ])
        ->dispatch('openServicePanel', uuid: $this->application->uuid, kind: 'application')
        ->call('revealVariable', $secret->id)
        ->assertSet('revealedVariables', [])
        ->assertDontSee('do-not-display');
});

it('renders the Railway log explorer toolbar and column header', function () {
    $logs = Livewire::test(RailwayLogs::class, [
        'project_uuid' => $this->project->uuid,
        'environment_uuid' => $this->environment->uuid,
    ])
        ->assertOk()
        ->assertSet('range', '15m')
        ->assertSee('Last 15 min');

    // The Timestamp / Service / Replica / Message header belongs to the table,
    // so it only appears once there is something to tabulate.
    $logs->set('rows', [[
        'ts' => 'Aug 28 10:00:00',
        'sortKey' => '2026-08-28T10:00:00.000000',
        'service' => 'staging-app',
        'replica' => 'abc12345',
        'message' => 'Listening on port 3000',
        'level' => 'info',
    ]])
        ->assertSee('Timestamp')
        ->assertSee('Service')
        ->assertSee('Replica')
        ->assertSee('Message')
        ->assertSee('staging-app');
});

it('changes the log time range and ignores an unknown one', function () {
    $logs = Livewire::test(RailwayLogs::class, [
        'project_uuid' => $this->project->uuid,
        'environment_uuid' => $this->environment->uuid,
    ]);

    $logs->call('setRange', '1h')->assertSet('range', '1h');

    // An unrecognised range must not be written through to the component.
    $logs->call('setRange', 'last-decade')->assertSet('range', '1h');
});

it('filters log rows without re-reading the containers', function () {
    // The filter runs over already-fetched rows at render time; typing must not
    // trigger another SSH round-trip.
    $rows = [
        ['ts' => 'Aug 28 10:00:00', 'sortKey' => '2026-08-28T10:00:00.000000', 'service' => 'staging-app', 'replica' => 'abc12345', 'message' => 'Listening on port 3000', 'level' => 'info'],
        ['ts' => 'Aug 28 10:00:01', 'sortKey' => '2026-08-28T10:00:01.000000', 'service' => 'staging-app', 'replica' => 'abc12345', 'message' => 'Error: connection refused', 'level' => 'error'],
    ];

    Livewire::test(RailwayLogs::class, [
        'project_uuid' => $this->project->uuid,
        'environment_uuid' => $this->environment->uuid,
    ])
        ->set('rows', $rows)
        ->assertSee('Listening on port 3000')
        ->set('filter', 'connection refused')
        ->assertSee('Error: connection refused')
        ->assertDontSee('Listening on port 3000');
});

it('shows the empty state when no container produced a line', function () {
    // The seeded server is not functional, so the fan-out yields nothing —
    // which is the ordinary case, not an error.
    Livewire::test(RailwayLogs::class, [
        'project_uuid' => $this->project->uuid,
        'environment_uuid' => $this->environment->uuid,
    ])
        ->assertOk()
        ->assertSet('rows', [])
        ->assertSet('error', null)
        ->assertSee('No logs in this time range');
});

it('renders canvas nodes at Railway\'s measured card geometry', function () {
    // These numbers were measured off Railway's own rendered service card
    // (their template deploy page mounts the real component): 288x144, 12px
    // radius, 24px padding, 16px/600 title. Locked in so a future tidy-up
    // can't quietly drift the node off-spec.
    Livewire::test(Canvas::class, [
        'project_uuid' => $this->project->uuid,
        'environment_uuid' => $this->environment->uuid,
    ])
        ->assertOk()
        ->assertSee('w-[288px]', escape: false)
        ->assertSee('min-h-[144px]', escape: false)
        // Not `text-base`: Coolify's --color-base token makes Tailwind emit
        // .text-base as a *colour* utility, which silently shadows the
        // font-size one. An explicit value sidesteps the collision.
        ->assertSee('text-[16px]', escape: false)
        ->assertDontSee('text-base', escape: false);
});

it('fuses attached volumes to the bottom of a canvas node', function () {
    // Railway draws a resource's persistent volumes as a darker sub-card
    // welded to the bottom of the service node, labelled by mount path.
    $this->application->persistentStorages()->create([
        'name' => 'app-data',
        'mount_path' => '/var/lib/data',
    ]);

    $node = RailwayResourceMapper::toNode(
        $this->application->fresh(),
        $this->project->uuid,
        $this->environment->uuid
    );

    expect($node['volumes'])->toBe([['name' => 'app-data', 'mount' => '/var/lib/data']]);

    Livewire::test(Canvas::class, [
        'project_uuid' => $this->project->uuid,
        'environment_uuid' => $this->environment->uuid,
    ])
        ->assertOk()
        ->assertSee('/var/lib/data')
        // The node's own bottom corners square off so the two read as one card.
        ->assertSee('!rounded-b-none', escape: false);
});

it('reports no volumes for a resource that has none', function () {
    expect(RailwayResourceMapper::volumes($this->application))->toBe([]);

    Livewire::test(Canvas::class, [
        'project_uuid' => $this->project->uuid,
        'environment_uuid' => $this->environment->uuid,
    ])
        ->assertOk()
        ->assertDontSee('!rounded-b-none', escape: false);
});
