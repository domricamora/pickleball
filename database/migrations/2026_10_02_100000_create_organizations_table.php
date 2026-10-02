<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tenant root. Every other domain table hangs off an organization so a single
 * deployment can serve many facilities (plan.md §6, §30).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('status')->default('active')->index();

            // Philippine business details (plan.md §32)
            $table->string('business_name')->nullable();
            $table->string('tin', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 32)->nullable();

            $table->string('address_street')->nullable();
            $table->string('address_barangay')->nullable();
            $table->string('address_city')->nullable();
            $table->string('address_province')->nullable();
            $table->string('address_region')->nullable();
            $table->string('address_postal_code', 16)->nullable();
            $table->string('address_country', 2)->default('PH');

            // Operational settings stay administrative, never hard-coded.
            $table->string('timezone')->default('Asia/Manila');
            $table->char('currency', 3)->default('PHP');
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->boolean('tax_inclusive')->default(true);

            $table->json('settings')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('subscription_ends_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
