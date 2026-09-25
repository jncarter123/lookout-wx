<?php

namespace App\Livewire;

use App\Models\NwsAlert;
use App\Support\MarineAreas;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class ActiveAlertsDashboard extends Component
{
    use WithPagination;

    public bool $showAlertModal = false;

    public ?string $selectedAlertId = null;

    public ?NwsAlert $selectedAlert = null;

    public string $marineArea = '';


    public function updatedMarineArea(): void
    {
        $this->resetPage();
    }

    public function clearMarineArea(): void
    {
        $this->marineArea = '';
        $this->resetPage();
    }

    public function openAlert(string $alertId): void
    {
        $this->selectedAlertId = $alertId;

        $this->selectedAlert = NwsAlert::query()
            ->with('counties')
            ->with('zones')
            ->findOrFail($alertId);

        $this->showAlertModal = true;
    }

    public function closeAlertModal(): void
    {
        $this->showAlertModal = false;
        $this->selectedAlertId = null;
        $this->selectedAlert = null;
    }

    public function render(): View
    {
        $zonePrefixes = MarineAreas::zonePrefixes($this->marineArea);

        $alerts = NwsAlert::query()
            ->inActiveFeed()
            ->where('status', 'Actual')
            ->whereNotNull('expires')
            ->where('expires', '>', now())
            ->when(
                $zonePrefixes !== [],
                function ($query) use ($zonePrefixes) {
                    $query->whereHas('zones', function ($q) use ($zonePrefixes) {
                        $q->where(function ($q2) use ($zonePrefixes) {
                            foreach ($zonePrefixes as $prefix) {
                                $q2->orWhere('zone_id', 'like', $prefix . '%');
                            }
                        });
                    });
                }
            )
            ->orderBy('expires')
            ->paginate(25);

        return view('livewire.active-alerts-dashboard', [
            'alerts' => $alerts,
            'marineAreaOptions' => MarineAreas::options(),
        ])->layout('components.layouts.app');
    }
}