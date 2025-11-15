<?php

namespace App\Console\Commands;

use App\Models\ShopSetting;
use Illuminate\Console\Command;

class SetEcoFreightProductionToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ecofreight:set-production-token {token} {--shop-id= : Set token for specific shop only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set the EcoFreight production bearer token for all shops or a specific shop';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $token = $this->argument('token');
        $shopId = $this->option('shop-id');

        if (!$token) {
            $this->error('Token is required');
            return 1;
        }

        $query = ShopSetting::query();
        
        if ($shopId) {
            $query->where('shop_id', $shopId);
            $this->info("Setting production token for shop ID: {$shopId}");
        } else {
            $this->info('Setting production token for all shops...');
        }

        $settings = $query->get();
        
        if ($settings->isEmpty()) {
            $this->warn('No shop settings found');
            return 1;
        }

        $updated = 0;
        foreach ($settings as $setting) {
            $setting->ecofreight_bearer_token = $token;
            $setting->connection_status = true;
            $setting->last_connection_test = now();
            $setting->save();
            $updated++;
        }

        $this->info("✅ Successfully updated {$updated} shop setting(s) with production token");
        return 0;
    }
}
