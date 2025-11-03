<?php

namespace App\Console\Commands;

use App\Models\Shipment;
use App\Jobs\TrackSyncJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ScheduleTrackingSyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'ecofreight:schedule-tracking-sync {--interval=2} {--max-age=10}';

    /**
     * The console command description.
     */
    protected $description = 'Schedule tracking sync jobs for active shipments';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $interval = (int) $this->option('interval'); // hours
        $maxAge = (int) $this->option('max-age'); // days

        $this->info("Scheduling tracking sync for shipments (interval: {$interval}h, max age: {$maxAge} days)");

        // Find shipments that need tracking sync
        $shipments = Shipment::active()
            ->whereNotNull('ecofreight_awb')
            ->where(function ($query) use ($interval) {
                $query->whereNull('last_checked_at')
                      ->orWhere('last_checked_at', '<', now()->subHours($interval));
            })
            ->where('created_at', '>', now()->subDays($maxAge))
            ->get();

        $this->info("Found {$shipments->count()} shipments needing sync");

        $scheduled = 0;
        $skipped = 0;

        foreach ($shipments as $shipment) {
            // Skip if already scheduled recently
            if ($shipment->next_retry_at && $shipment->next_retry_at->isFuture()) {
                $skipped++;
                continue;
            }

            // Skip if too many sync attempts
            if ($shipment->sync_attempts >= 10) {
                $this->warn("Skipping shipment {$shipment->id} - too many sync attempts ({$shipment->sync_attempts})");
                $skipped++;
                continue;
            }

            // Schedule tracking sync
            TrackSyncJob::dispatch($shipment->id, false)
                ->delay(now()->addMinutes(rand(1, 30))); // Random delay to spread load

            $scheduled++;
        }

        $this->info("Scheduled {$scheduled} jobs, skipped {$skipped} shipments");

        // Log summary
        Log::info('Tracking sync scheduling completed', [
            'scheduled' => $scheduled,
            'skipped' => $skipped,
            'interval_hours' => $interval,
            'max_age_days' => $maxAge,
        ]);

        return Command::SUCCESS;
    }
}
