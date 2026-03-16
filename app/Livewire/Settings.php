<?php

namespace App\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Settings')]
class Settings extends Component
{
    public string $activeTab = 'api-keys';

    public function render()
    {
        return view('livewire.settings');
    }
}
