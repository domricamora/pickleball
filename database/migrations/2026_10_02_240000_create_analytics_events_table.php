<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Product analytics (plan.md §23).
 *
 * Public traffic is tracked without an organization_id, because a visitor
 * exists before they have chosen a facility. Everything is nullable for that
 * reason, and a platform-wide view is an explicit Super Admin concern rather
 * than the default.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            // A string, not foreignId(): this is a generated analytics id, not
            // a key into another table. foreignId() would create a BIGINT.
            $table->string('session_id', 64)->nullable()->index();

            // The funnel step: facility_view, court_view, booking_started,
            // booking_completed, repeat_booking, and so on.
            $table->string('name', 48)->index();
            $table->json('properties')->nullable();

            // Coarse device and source, never anything identifying.
            $table->string('device', 24)->nullable();
            $table->string('referrer', 128)->nullable();
            $table->string('ip_hash', 64)->nullable();

            $table->timestamp('occurred_at')->index();
            $table->timestamps();

            $table->index(['organization_id', 'name', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_events');
    }
};
