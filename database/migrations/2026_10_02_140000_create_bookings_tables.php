<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bookings and their status history (plan.md §12, §30).
 *
 * Times are stored in UTC and rendered in Asia/Manila. Overlap protection is
 * enforced in BookCourt with a row lock plus an interval query — a unique
 * index cannot express "no two ranges may intersect".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('court_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable();

            // Human-facing reference, e.g. PB-2026-000123.
            $table->string('reference', 32)->unique();

            // Half-open interval [starts_at, ends_at): back-to-back bookings
            // are allowed, which is why the overlap test uses strict inequality.
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->unsignedSmallInteger('duration_minutes');

            $table->enum('status', [
                'pending',
                'confirmed',
                'checked_in',
                'completed',
                'cancelled',
                'no_show',
                'refunded',
            ])->default('pending')->index();

            // Money in pesos (plan.md §13, §32).
            $table->decimal('amount', 10, 2)->default(0);
            $table->char('currency', 3)->default('PHP');
            $table->string('price_type', 32)->nullable();
            $table->unsignedSmallInteger('players')->default(4);

            $table->text('notes')->nullable();

            // Lifecycle stamps, kept for reporting and audit (plan.md §12).
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('no_show_at')->nullable();
            $table->timestamp('refunded_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // The availability query filters by court and window, so this is
            // the index that decides whether a booking page feels fast.
            $table->index(['court_id', 'starts_at', 'ends_at']);
            $table->index(['organization_id', 'status', 'starts_at']);
            $table->index(['branch_id', 'starts_at']);
            $table->index(['user_id', 'starts_at']);
            $table->index('customer_id');
        });

        Schema::create('booking_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32);
            $table->text('note')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['booking_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_status_history');
        Schema::dropIfExists('bookings');
    }
};
