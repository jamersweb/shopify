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
        Schema::create('shop_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->onDelete('cascade');
            
            // EcoFreight Connection
            $table->string('ecofreight_base_url')->default('https://app.ecofreight.ae/en');
            $table->text('ecofreight_username'); // encrypted
            $table->text('ecofreight_password'); // encrypted
            $table->timestamp('last_connection_test')->nullable();
            $table->boolean('connection_status')->default(false);
            
            // Ship-from (Origin) Settings
            $table->string('ship_from_company');
            $table->string('ship_from_contact');
            $table->string('ship_from_phone');
            $table->string('ship_from_email');
            $table->string('ship_from_address1');
            $table->string('ship_from_address2')->nullable();
            $table->string('ship_from_city');
            $table->string('ship_from_postcode')->nullable();
            $table->string('ship_from_country')->default('UAE');
            
            // Default Package Rules
            $table->decimal('default_weight', 8, 2)->default(1.0); // kg
            $table->decimal('default_length', 8, 2)->default(30); // cm
            $table->decimal('default_width', 8, 2)->default(20); // cm
            $table->decimal('default_height', 8, 2)->default(10); // cm
            $table->enum('packing_rule', ['per_order', 'per_item'])->default('per_order');
            
            // Services
            $table->boolean('use_standard_service')->default(true);
            $table->boolean('use_express_service')->default(true);
            
            // COD Settings
            $table->boolean('cod_enabled')->default(false);
            $table->decimal('cod_fee', 8, 2)->default(0);
            
            // Price Adjustments (internal reference only)
            $table->decimal('markup_percentage', 5, 2)->default(0);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            
            // Tracking Settings
            $table->string('tracking_url_template')->nullable();
            $table->boolean('auto_poll_tracking')->default(true);
            $table->integer('poll_interval_hours')->default(2);
            $table->integer('stop_after_days')->default(10);
            
            // Alert Settings
            $table->text('error_alert_emails')->nullable(); // comma-separated
            $table->boolean('include_awb_in_alerts')->default(true);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shop_settings');
    }
};
