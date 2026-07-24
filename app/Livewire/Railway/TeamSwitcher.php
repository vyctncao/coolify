<?php

namespace App\Livewire\Railway;

use App\Models\Team;
use Livewire\Component;

/**
 * Native Railway workspace/team switcher (the "Root Team / PRO" header).
 * Lists the user's teams and switches the current team via refreshSession().
 */
class TeamSwitcher extends Component
{
    public function switchTo(int $teamId)
    {
        $user = auth()->user();
        if (! $user->teams->contains($teamId)) {
            return null;
        }

        $team = Team::find($teamId);
        if (! $team) {
            return null;
        }

        refreshSession($team);

        return redirect()->route('dashboard');
    }

    public function render()
    {
        return view('livewire.railway.team-switcher', [
            'teams' => auth()->user()->teams()->get(),
            'current' => currentTeam(),
        ]);
    }
}
