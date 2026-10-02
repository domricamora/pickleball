<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Memberships, packages and credit tracking (plan.md §15, §30).
 *
 * Credits are never decremented in place. Every use is an immutable row in
 * membership_credit_usages, and the balance is derived from them. That makes
 * the history auditable and stops a lost update from silently minting credit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membership_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();

            $table->enum('billing_cycle', ['monthly', 'quarterly', 'annual', 'custom'])
                ->default('monthly');
            $table->unsignedSmallInteger('custom_days')->nullable(); // only for 'custom'

            // Recurring fee in pesos. Null means the plan is a package only.
            $table->decimal('price', 10, 2)->nullable();
            $table->char('currency', 3)->default('PHP');

            // Included sessions per cycle. 0 = none, null = unlimited.
            $table->unsignedSmallInteger('sessions_included')->nullable();

            // Benefits (plan.md §15)
            $table->boolean('priority_booking')->default(false);
            $table->boolean('member_only_events')->default(false);
            $table->unsignedTinyInteger('guest_passes')->default(0);
            // Percentage off, e.g. 10.00 for 10% off member rates.
            $table->decimal('discount_percent', 5, 2)->default(0);

            $table->json('benefits')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['organization_id', 'is_active']);
        });

        /*
         * Prepaid session packs: 5 sessions, 10 sessions, off-peak only, and
         * so on. A package is bought once and consumed, not billed.
         */
        Schema::create('membership_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('membership_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('sessions');
            $table->decimal('price', 10, 2);
            $table->char('currency', 3)->default('PHP');
            $table->unsignedSmallInteger('valid_days')->default(90);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['organization_id', 'is_active']);
        });

        Schema::create('membership_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('membership_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->enum('status', ['active', 'paused', 'expired', 'cancelled'])
                ->default('active')->index();

            $table->date('starts_on');
            $table->date('ends_on');
            $table->date('cancelled_at')->nullable();

            // Credits granted this cycle, and how many are left.
            $table->unsignedSmallInteger('credits_granted')->default(0);
            $table->unsignedSmallInteger('credits_remaining')->default(0);
            // null on the plan means unlimited, so 0 here would be a lie.
            $table->boolean('is_unlimited')->default(false);

            $table->timestamps();

            $table->index(['customer_id', 'status']);
            $table->index(['organization_id', 'status']);
        });

        Schema::create('membership_credit_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('membership_subscription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('credits')->default(1);
            $table->timestamp('used_at');
            $table->timestamps();

            // One credit can only be spent once per booking.
            $table->unique(['membership_subscription_id', 'booking_id'], 'credit_usage_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_credit_usages');
        Schema::dropIfExists('membership_subscriptions');
        Schema::dropIfExists('membership_packages');
        Schema::dropIfExists('membership_plans');
    }
};
