<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            // Tracking fields
            $table->string('last_status')->nullable()->after('status');
            $table->timestamp('last_checked_at')->nullable()->after('last_tracking_sync');
            $table->timestamp('delivered_at')->nullable()->after('last_checked_at');
            $table->boolean('stale_flag')->default(false)->after('delivered_at');
            $table->integer('sync_attempts')->default(0)->after('retry_count');
            
            // Webhook fields (for future use)
            $table->boolean('webhook_opt_in')->default(false)->after('stale_flag');
            $table->timestamp('webhook_last_seen_at')->nullable()->after('webhook_opt_in');
            
            // Performance metrics
            $table->timestamp('first_scan_at')->nullable()->after('webhook_last_seen_at');
            $table->timestamp('label_generated_at')->nullable()->after('first_scan_at');
            
            // Additional indexes
            $table->index(['last_status', 'stale_flag']);
            $table->index(['delivered_at']);
            $table->index(['last_checked_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropIndex(['last_status', 'stale_flag']);
            $table->dropIndex(['delivered_at']);
            $table->dropIndex(['last_checked_at']);
            
            $table->dropColumn([
                'last_status',
                'last_checked_at',
                'delivered_at',
                'stale_flag',
                'sync_attempts',
                'webhook_opt_in',
                'webhook_last_seen_at',
                'first_scan_at',
                'label_generated_at',
            ]);
        });
    }
};
