<?php

namespace App\Livewire\Railway;

use App\Models\Environment;
use App\Models\Project;
use Livewire\Component;

/**
 * Inline "New Environment" control for the Railway environment switcher.
 * Creates a genuinely separate environment on the current project (it does NOT
 * rename/delete the current one) and navigates to the new environment's canvas.
 */
class NewEnvironment extends Component
{
    public Project $project;

    public string $name = '';

    public function mount(Project $project): void
    {
        $this->project = $project;
    }

    public function create()
    {
        $this->validate([
            'name' => ['required', 'string', 'min:1', 'max:255', 'regex:/^[a-zA-Z0-9._-]+$/'],
        ], [
            'name.regex' => 'Use letters, numbers, dots, dashes or underscores.',
        ]);

        try {
            $this->authorize('create', Environment::class);

            if ($this->project->environments()->where('name', $this->name)->exists()) {
                $this->addError('name', 'An environment named "'.$this->name.'" already exists.');

                return null;
            }

            $environment = Environment::create([
                'name' => $this->name,
                'project_id' => $this->project->id,
                'uuid' => new_public_id(),
            ]);

            return redirect()->route('railway.canvas', [
                'project_uuid' => $this->project->uuid,
                'environment_uuid' => $environment->uuid,
            ]);
        } catch (\Throwable $e) {
            $this->addError('name', 'You are not allowed to create an environment here.');

            return null;
        }
    }

    public function render()
    {
        return view('livewire.railway.new-environment');
    }
}
