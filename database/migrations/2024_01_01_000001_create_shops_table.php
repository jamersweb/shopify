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
        Schema::create('shops', function (Blueprint $table) {
            $table->id();
            $table->string('shopify_domain')->unique();
            $table->text('shopify_token');
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('domain')->nullable();
            $table->string('province')->nullable();
            $table->string('country')->nullable();
            $table->string('address1')->nullable();
            $table->string('zip')->nullable();
            $table->string('city')->nullable();
            $table->string('source')->nullable();
            $table->string('phone')->nullable();
            $table->datetime('shopify_updated_at')->nullable();
            $table->datetime('shopify_created_at')->nullable();
            $table->string('country_code')->nullable();
            $table->string('country_name')->nullable();
            $table->string('currency')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('timezone')->nullable();
            $table->string('iana_timezone')->nullable();
            $table->string('shopify_plan_name')->nullable();
            $table->boolean('has_discounts')->default(false);
            $table->boolean('has_gift_cards')->default(false);
            $table->boolean('force_ssl')->default(false);
            $table->boolean('checkout_api_supported')->default(false);
            $table->boolean('multi_location_enabled')->default(false);
            $table->boolean('has_storefront')->default(false);
            $table->boolean('eligible_for_payments')->default(false);
            $table->boolean('eligible_for_card_reader_giveaway')->default(false);
            $table->boolean('finances')->default(false);
            $table->bigInteger('primary_location_id')->nullable();
            $table->string('cookie_consent_level')->nullable();
            $table->string('visitor_tracking_consent_preference')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shops');
    }
};
