<?php

namespace App\Livewire;

use Livewire\Component;

class LayoutPopups extends Component
{
    /**
     * Whether to render the sponsorship and notification promos.
     *
     * The Railway layout opts out: those popups are Coolify-branded and break
     * the skin. The realtime-connection warning is always rendered, because
     * it reports an actual fault the user needs to see.
     */
    public bool $showPromos = true;

    public function getListeners()
    {
        $teamId = auth()->user()->currentTeam()->id;

        return [
            "echo-private:team.{$teamId},TestEvent" => 'testEvent',
        ];
    }

    public function testEvent()
    {
        $this->dispatch('success', 'Realtime events configured!');
    }

    public function render()
    {
        return view('livewire.layout-popups');
    }
}
