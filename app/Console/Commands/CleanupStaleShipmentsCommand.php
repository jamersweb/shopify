<?php

namespace App\Console\Commands;

use App\Models\Shipment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupStaleShipmentsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'ecofreight:cleanup-stale {--threshold=48} {--dry-run}';

    /**
     * The console command description.
     */
    protected $description = 'Mark stale shipments and send alerts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $threshold = (int) $this->option('threshold'); // hours
        $dryRun = $this->option('dry-run');

        $this->info("Checking for stale shipments (threshold: {$threshold}h, dry run: " . ($dryRun ? 'yes' : 'no') . ")");

        // Find shipments that should be marked as stale
        $staleShipments = Shipment::active()
            ->whereNotNull('ecofreight_awb')
            ->where('stale_flag', false)
            ->where(function ($query) use ($threshold) {
                $query->whereNull('last_checked_at')
                      ->where('created_at', '<', now()->subHours($threshold))
                      ->orWhere('last_checked_at', '<', now()->subHours($threshold));
            })
            ->get();

        $this->info("Found {$staleShipments->count()} stale shipments");

        $marked = 0;
        $alertsSent = 0;

        foreach ($staleShipments as $shipment) {
            if (!$dryRun) {
                // Mark as stale
                $shipment->update(['stale_flag' => true]);

                // Send alert email if configured
                if ($shipment->shop->settings->alert_emails) {
                    $this->sendStaleAlert($shipment);
                    $alertsSent++;
                }

                $marked++;
            }

            $this->line("Shipment {$shipment->id} ({$shipment->shopify_order_name}) - AWB: {$shipment->ecofreight_awb}");
        }

        if ($dryRun) {
            $this->info("DRY RUN: Would mark {$staleShipments->count()} shipments as stale");
        } else {
            $this->info("Marked {$marked} shipments as stale, sent {$alertsSent} alerts");
        }

        // Log summary
        Log::info('Stale shipments cleanup completed', [
            'threshold_hours' => $threshold,
            'stale_count' => $staleShipments->count(),
            'marked' => $marked,
            'alerts_sent' => $alertsSent,
            'dry_run' => $dryRun,
        ]);

        return Command::SUCCESS;
    }

    /**
     * Send stale alert email.
     */
    protected function sendStaleAlert(Shipment $shipment)
    {
        try {
            $emails = explode(',', $shipment->shop->settings->alert_emails);
            $emails = array_map('trim', $emails);

            foreach ($emails as $email) {
                \Mail::send('emails.stale-shipment', [
                    'shipment' => $shipment,
                    'shop' => $shipment->shop,
                ], function ($message) use ($email, $shipment) {
                    $message->to($email)
                           ->subject("Stale Shipment Alert: {$shipment->shopify_order_name}");
                });
            }

            Log::info('Stale shipment alert sent', [
                'shipment_id' => $shipment->id,
                'awb' => $shipment->ecofreight_awb,
                'emails' => $emails,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send stale shipment alert', [
                'shipment_id' => $shipment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
