<?php

namespace App\Livewire;

use App\Models\NwsAlert;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class AlertsDashboard extends Component
{
    use WithPagination;

    /** @var int minutes */
    public int $periodMinutes = 60;

    /** @var array<int,string> */
    public array $periodOptions = [
        15 => 'Last 15 minutes',
        30 => 'Last 30 minutes',
        60 => 'Last 1 hour',
        360 => 'Last 6 hours',
        720 => 'Last 12 hours',
    ];

    public bool $showAlertModal = false;

    public ?string $selectedAlertId = null;

    public ?NwsAlert $selectedAlert = null;

    public function updatedPeriodMinutes(): void
    {
        // Reset pagination when changing the time window
        $this->resetPage();
    }

    public function openAlert(string $alertId): void
    {
        $this->selectedAlertId = $alertId;

        $this->selectedAlert = NwsAlert::query()
            ->with('counties')
            ->findOrFail($alertId);

        $this->showAlertModal = true;
    }

    public function closeAlertModal(): void
    {
        $this->showAlertModal = false;
        $this->selectedAlertId = null;
        $this->selectedAlert = null;
    }

    private function cutoffForPeriod(): Carbon
    {
        return now()->subMinutes($this->periodMinutes);
    }

    public function render(): View
    {
        $totalAlerts = NwsAlert::query()->count();

        $lastHourCount = NwsAlert::query()
            ->where('nws_updated_at', '>=', now()->subHour())
            ->count();

        $cutoff = $this->cutoffForPeriod();

        $alerts = NwsAlert::query()
            ->where('nws_updated_at', '>=', $cutoff)
            ->orderByDesc('nws_updated_at')
            ->paginate(25);

        return view('livewire.alerts-dashboard', [
            'totalAlerts' => $totalAlerts,
            'lastHourCount' => $lastHourCount,
            'cutoff' => $cutoff,
            'alerts' => $alerts,
        ])->layout('components.layouts.app');
    }
}
