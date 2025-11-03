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
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->onDelete('cascade');
            $table->bigInteger('shopify_order_id');
            $table->string('shopify_order_name');
            $table->string('ecofreight_awb')->nullable();
            $table->string('ecofreight_reference')->nullable();
            $table->enum('service_type', ['standard', 'express']);
            $table->enum('status', ['pending', 'created', 'label_generated', 'shipped', 'delivered', 'cancelled', 'error']);
            $table->text('error_message')->nullable();
            $table->json('shipment_data')->nullable(); // Store full EcoFreight response
            $table->json('label_data')->nullable(); // Store label file info
            $table->string('tracking_url')->nullable();
            $table->boolean('cod_enabled')->default(false);
            $table->decimal('cod_amount', 10, 2)->nullable();
            $table->timestamp('last_tracking_sync')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamp('next_retry_at')->nullable();
            $table->timestamps();
            
            $table->index(['shop_id', 'shopify_order_id']);
            $table->index('ecofreight_awb');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
