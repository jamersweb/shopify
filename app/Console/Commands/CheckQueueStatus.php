<?php

namespace App\Console\Commands;

use App\Models\Shipment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

class CheckQueueStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:status {--detailed : Show detailed information}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check queue status and shipment processing status';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('📊 Queue & Shipment Status Check');
        $this->info('================================');
        $this->newLine();

        // Check queue connection
        $queueConnection = config('queue.default', 'sync');
        $this->info("Queue Connection: <fg=cyan>{$queueConnection}</>");
        
        if ($queueConnection === 'sync') {
            $this->warn('⚠️  Queue is set to "sync" - jobs run immediately, no queue worker needed');
            $this->warn('   Note: For production, use "database" or "redis" connection');
        } else {
            $this->info('ℹ️  Queue worker must be running: php artisan queue:work');
            
            // Check if queue worker might be running (basic check)
            if (function_exists('exec')) {
                $processes = [];
                if (PHP_OS_FAMILY === 'Windows') {
                    exec('tasklist /FI "IMAGENAME eq php.exe" 2>NUL', $processes);
                } else {
                    exec('ps aux | grep "queue:work" | grep -v grep', $processes);
                }
                
                if (!empty($processes)) {
                    $this->info('✅ Queue worker process detected');
                } else {
                    $this->warn('⚠️  No queue worker process detected - jobs may not be processing');
                }
            }
        }
        
        $this->newLine();

        // Check pending shipments
        $pendingShipments = Shipment::where('status', 'pending')
            ->whereNull('ecofreight_awb')
            ->count();
        
        $this->info("📦 Pending Shipments (not sent to EcoFreight): <fg=yellow>{$pendingShipments}</>");

        // Check created shipments
        $createdShipments = Shipment::where('status', 'created')
            ->whereNotNull('ecofreight_awb')
            ->count();
        
        $this->info("✅ Created Shipments (sent to EcoFreight): <fg=green>{$createdShipments}</>");

        // Check error shipments
        $errorShipments = Shipment::where('status', 'error')
            ->count();
        
        if ($errorShipments > 0) {
            $this->error("❌ Failed Shipments: {$errorShipments}");
        } else {
            $this->info("❌ Failed Shipments: <fg=green>0</>");
        }

        // Check recent shipments
        $recentShipments = Shipment::where('created_at', '>=', now()->subHours(24))
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get(['id', 'shopify_order_name', 'status', 'ecofreight_awb', 'created_at', 'updated_at']);

        $this->newLine();
        $this->info('📋 Recent Shipments (Last 24 Hours):');
        $this->newLine();

        if ($recentShipments->isEmpty()) {
            $this->warn('No shipments in the last 24 hours');
        } else {
            $headers = ['ID', 'Order', 'Status', 'AWB', 'Created', 'Updated'];
            $rows = [];
            
            foreach ($recentShipments as $shipment) {
                $statusColor = match($shipment->status) {
                    'created' => 'green',
                    'pending' => 'yellow',
                    'error' => 'red',
                    default => 'white',
                };
                
                $rows[] = [
                    $shipment->id,
                    $shipment->shopify_order_name,
                    "<fg={$statusColor}>{$shipment->status}</>",
                    $shipment->ecofreight_awb ?? 'N/A',
                    $shipment->created_at->format('Y-m-d H:i:s'),
                    $shipment->updated_at->format('Y-m-d H:i:s'),
                ];
            }
            
            $this->table($headers, $rows);
        }

        // Detailed information
        if ($this->option('detailed')) {
            $this->newLine();
            $this->info('🔍 Detailed Information:');
            $this->newLine();

            // Check for stuck shipments (pending for more than 1 hour)
            $stuckShipments = Shipment::where('status', 'pending')
                ->where('created_at', '<', now()->subHour())
                ->whereNull('ecofreight_awb')
                ->get();

            if ($stuckShipments->isNotEmpty()) {
                $this->warn("⚠️  Found {$stuckShipments->count()} stuck shipment(s) (pending > 1 hour):");
                foreach ($stuckShipments as $shipment) {
                    $this->line("   - Order: {$shipment->shopify_order_name} (ID: {$shipment->id})");
                    $this->line("     Created: {$shipment->created_at->format('Y-m-d H:i:s')}");
                }
                $this->newLine();
            }

            // Check queue size (if using database queue)
            if ($queueConnection === 'database') {
                $queueSize = DB::table('jobs')->count();
                $failedJobs = DB::table('failed_jobs')->count();
                
                $this->info("Queue Size: <fg=cyan>{$queueSize}</> jobs");
                if ($failedJobs > 0) {
                    $this->error("Failed Jobs: {$failedJobs}");
                    $this->line("   Run: php artisan queue:failed");
                } else {
                    $this->info("Failed Jobs: <fg=green>0</>");
                }
            }
        }

        $this->newLine();
        $this->info('💡 Tips:');
        $this->line('   - Start queue worker: php artisan queue:work');
        $this->line('   - View logs: tail -f storage/logs/laravel.log');
        $this->line('   - Check failed jobs: php artisan queue:failed');
        $this->line('   - Retry failed jobs: php artisan queue:retry all');
        
        return 0;
    }
}
