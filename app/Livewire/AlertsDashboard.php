<?php

namespace App\Livewire;

use App\Models\NwsAlert;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
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

    /** Matches event, headline, area description, county UGC, or zone ID within the time window. */
    #[Url(as: 'q', except: '')]
    public string $search = '';

    public bool $showAlertModal = false;

    public ?string $selectedAlertId = null;

    public ?NwsAlert $selectedAlert = null;

    public function updatedPeriodMinutes(): void
    {
        // Reset pagination when changing the time window
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
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

    private function applySearch(Builder $query): void
    {
        $term = trim($this->search);

        if ($term === '') {
            return;
        }

        // Case-insensitive on both drivers: JSON strings compare with a binary collation on MySQL.
        // '!' is the LIKE escape because SQLite has no default one and MySQL's is a backslash.
        $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_strtolower($term)).'%';
        $matches = fn (string $column) => ["lower($column) like ? escape '!'", [$like]];

        // areaDesc lives only in the raw JSON; the grammar wraps the path for MySQL or SQLite.
        $areaDesc = $query->getQuery()->getGrammar()->wrap('raw->properties->areaDesc');

        $query->where(function (Builder $q) use ($matches, $areaDesc) {
            $q->whereRaw(...$matches('event'))
                ->orWhereRaw(...$matches('headline'))
                ->orWhereRaw(...$matches($areaDesc))
                ->orWhereHas('counties', fn (Builder $q) => $q->whereRaw(...$matches('county_ugc')))
                ->orWhereHas('zones', fn (Builder $q) => $q->whereRaw(...$matches('zone_id')));
        });
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
            ->tap(fn (Builder $q) => $this->applySearch($q))
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
