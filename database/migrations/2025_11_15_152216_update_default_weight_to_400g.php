<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update existing records with default weight of 1.0 kg to 0.4 kg (400g)
        // This updates all shop settings that have the old default value
        DB::table('shop_settings')
            ->where('default_weight', 1.0)
            ->update(['default_weight' => 0.4]);
        
        // Also update NULL values to 0.4
        DB::table('shop_settings')
            ->whereNull('default_weight')
            ->update(['default_weight' => 0.4]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert records that were updated from 1.0 to 0.4 back to 1.0
        // Note: This only reverts records that were exactly 1.0 before
        // Records that were manually set to 0.4 will remain 0.4
        DB::table('shop_settings')
            ->where('default_weight', 0.4)
            ->update(['default_weight' => 1.0]);
    }
};
