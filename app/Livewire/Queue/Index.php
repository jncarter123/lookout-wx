<?php

namespace App\Livewire\Queue;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Livewire\Component;

class Index extends Component
{
    public function mount(): void
    {
        $this->authorize('queue.monitor');
    }

    public function retryJob(string $uuid): void
    {
        $this->authorize('queue.monitor');
        Artisan::call('queue:retry', ['id' => [$uuid]]);
    }

    public function deleteJob(string $uuid): void
    {
        $this->authorize('queue.monitor');
        Artisan::call('queue:forget', ['id' => $uuid]);
    }

    public function retryAll(): void
    {
        $this->authorize('queue.monitor');
        Artisan::call('queue:retry', ['id' => ['all']]);
    }

    public function render()
    {
        $pending = DB::table('jobs')->count();
        $failedCount = DB::table('failed_jobs')->count();

        try {
            $masters = app(MasterSupervisorRepository::class)->all();
            $processes = collect($masters)->sum(
                fn ($m) => collect($m->supervisors)->sum('processes')
            );
            $status = count($masters) > 0 ? 'running' : 'inactive';
        } catch (\Throwable) {
            $processes = null;
            $status = 'unknown';
        }

        $failedJobs = DB::table('failed_jobs')
            ->orderByDesc('failed_at')
            ->limit(100)
            ->get()
            ->map(function ($row) {
                $payload = json_decode($row->payload, true);
                $exceptionLines = explode("\n", $row->exception);

                return (object) [
                    'uuid' => $row->uuid,
                    'display_name' => class_basename($payload['displayName'] ?? 'Unknown'),
                    'full_name' => $payload['displayName'] ?? 'Unknown',
                    'queue' => $row->queue,
                    'failed_at' => Carbon::parse($row->failed_at),
                    'exception_msg' => $exceptionLines[0] ?? '',
                    'exception' => $row->exception,
                ];
            });

        return view('livewire.queue.index', [
            'pending' => $pending,
            'failedCount' => $failedCount,
            'processes' => $processes,
            'status' => $status,
            'failedJobs' => $failedJobs,
        ]);
    }
}
