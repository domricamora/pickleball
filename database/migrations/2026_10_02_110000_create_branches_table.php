<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A physical location operated by an organization (plan.md §30 `facilities`).
 * Phase 3 expands this with courts, schedules and pricing.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 32)->nullable();
            $table->string('status')->default('active')->index();

            $table->string('address_street')->nullable();
            $table->string('address_barangay')->nullable();
            $table->string('address_city')->nullable();
            $table->string('address_province')->nullable();
            $table->string('address_region')->nullable();
            $table->string('address_postal_code', 16)->nullable();
            $table->string('address_country', 2)->default('PH');

            $table->string('email')->nullable();
            $table->string('phone', 32)->nullable();

            // GPS coordinates (plan.md §11)
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
