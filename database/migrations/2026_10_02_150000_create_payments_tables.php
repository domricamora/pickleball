<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Philippine payments, refunds and receipts (plan.md §13, §30).
 *
 * Amounts are pesos. Card details are NEVER stored here — only the gateway's
 * own opaque token and the last four digits, which is all a receipt needs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('reference', 32)->unique();

            $table->enum('method', [
                'gcash',
                'maya',
                'card',
                'bank_transfer',
                'cash',
                'pos',
            ])->index();

            $table->enum('status', [
                'pending',
                'processing',
                'paid',
                'failed',
                'partially_refunded',
                'refunded',
                'expired',
            ])->default('pending')->index();

            // Pesos, matching court_prices and bookings (plan.md §32).
            $table->decimal('amount', 10, 2);
            $table->decimal('refunded_amount', 10, 2)->default(0);
            $table->char('currency', 3)->default('PHP');

            // Opaque gateway identifiers only. No PAN, no CVV, ever.
            $table->string('gateway', 32)->nullable();
            $table->string('gateway_payment_id')->nullable();
            $table->string('gateway_reference', 64)->nullable();
            $table->string('card_last_four', 4)->nullable();

            $table->text('failure_reason')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('refunded_at')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            // The booking ledger is read far more often than it is written.
            $table->index(['booking_id', 'status']);
            $table->index(['organization_id', 'created_at']);
            // A gateway callback may arrive more than once; this is what makes
            // handling it idempotent.
            $table->unique(['gateway', 'gateway_payment_id'], 'payments_gateway_unique');
        });

        Schema::create('payment_refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reference', 32)->unique();
            $table->decimal('amount', 10, 2);
            $table->char('currency', 3)->default('PHP');
            $table->string('gateway_refund_id')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index('payment_id');
        });

        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number', 32)->unique();
            $table->decimal('subtotal', 10, 2);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->char('currency', 3)->default('PHP');
            $table->jsonb('line_items')->nullable();
            $table->timestamp('issued_at');
            $table->timestamps();

            $table->index('booking_id');
            $table->index(['organization_id', 'issued_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipts');
        Schema::dropIfExists('payment_refunds');
        Schema::dropIfExists('payments');
    }
};
