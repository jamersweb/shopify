<?php

namespace App\Console\Commands;

use App\Models\Shipment;
use App\Jobs\CreateShipmentJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessPendingShipments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shipments:process {--id= : Process specific shipment ID} {--all : Process all pending shipments}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manually process pending shipments and send them to EcoFreight';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $shipmentId = $this->option('id');
        $processAll = $this->option('all');

        if ($shipmentId) {
            // Process specific shipment
            $shipment = Shipment::find($shipmentId);
            
            if (!$shipment) {
                $this->error("Shipment ID {$shipmentId} not found");
                return 1;
            }
            
            if ($shipment->ecofreight_awb) {
                $this->warn("Shipment {$shipmentId} already has AWB: {$shipment->ecofreight_awb}");
                return 0;
            }
            
            $this->info("Processing shipment ID: {$shipmentId} (Order: {$shipment->shopify_order_name})");
            $this->processShipment($shipment);
            
        } elseif ($processAll) {
            // Process all pending shipments
            $shipments = Shipment::where('status', 'pending')
                ->whereNull('ecofreight_awb')
                ->get();
            
            if ($shipments->isEmpty()) {
                $this->info('No pending shipments found');
                return 0;
            }
            
            $this->info("Found {$shipments->count()} pending shipment(s)");
            $this->newLine();
            
            foreach ($shipments as $shipment) {
                $this->info("Processing shipment ID: {$shipment->id} (Order: {$shipment->shopify_order_name})");
                $this->processShipment($shipment);
                $this->newLine();
            }
            
        } else {
            // Show pending shipments and ask which to process
            $shipments = Shipment::where('status', 'pending')
                ->whereNull('ecofreight_awb')
                ->get(['id', 'shopify_order_name', 'created_at']);
            
            if ($shipments->isEmpty()) {
                $this->info('No pending shipments found');
                return 0;
            }
            
            $this->info("Found {$shipments->count()} pending shipment(s):");
            $this->newLine();
            
            $headers = ['ID', 'Order', 'Created'];
            $rows = $shipments->map(function ($s) {
                return [$s->id, $s->shopify_order_name, $s->created_at->format('Y-m-d H:i:s')];
            })->toArray();
            
            $this->table($headers, $rows);
            $this->newLine();
            
            $id = $this->ask('Enter shipment ID to process (or press Enter to process all)', 'all');
            
            if ($id === 'all') {
                foreach ($shipments as $shipment) {
                    $this->info("Processing shipment ID: {$shipment->id}");
                    $this->processShipment($shipment);
                    $this->newLine();
                }
            } else {
                $shipment = Shipment::find($id);
                if ($shipment) {
                    $this->processShipment($shipment);
                } else {
                    $this->error("Shipment ID {$id} not found");
                    return 1;
                }
            }
        }
        
        return 0;
    }
    
    protected function processShipment(Shipment $shipment)
    {
        try {
            // Check if shop settings exist
            if (!$shipment->shop || !$shipment->shop->settings) {
                $this->error("   ❌ Shop settings not configured for shipment {$shipment->id}");
                $shipment->update([
                    'status' => 'error',
                    'error_message' => 'Shop settings not configured'
                ]);
                return;
            }
            
            // Dispatch the job
            $requestId = uniqid('manual_', true);
            $this->line("   📤 Dispatching job with request ID: {$requestId}");
            
            // Since queue is sync, job will run immediately
            CreateShipmentJob::dispatch($shipment->shop_id, $shipment->id, $requestId);
            
            // Wait a moment for sync queue to process
            sleep(1);
            
            // Refresh shipment to check status
            $shipment->refresh();
            
            if ($shipment->ecofreight_awb) {
                $this->info("   ✅ Success! AWB: {$shipment->ecofreight_awb}");
            } elseif ($shipment->status === 'error') {
                $this->error("   ❌ Failed: {$shipment->error_message}");
            } else {
                $this->warn("   ⚠️  Still pending. Check logs for details.");
            }
            
        } catch (\Exception $e) {
            $this->error("   ❌ Exception: {$e->getMessage()}");
            Log::error('ProcessPendingShipments failed', [
                'shipment_id' => $shipment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
