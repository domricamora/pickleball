<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Notifications and marketing campaigns (plan.md §21, §30).
 *
 * Delivery is always queued (plan.md §21), so nothing here blocks a customer
 * request. A notification row is written synchronously as the record of
 * intent; the job does the sending and records the outcome on the channel
 * rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('membership_subscription_id')->nullable()
                ->constrained('membership_subscriptions')->nullOnDelete();

            $table->string('type', 48)->index();
            $table->string('channel', 16)->default('in_app');
            $table->string('subject')->nullable();
            $table->text('body');

            $table->enum('status', ['queued', 'sent', 'failed', 'read'])
                ->default('queued')->index();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'read_at']);
            $table->index(['organization_id', 'type']);
        });

        // Campaign membership, so a sequence knows exactly who it is talking
        // to and whether they have already had a step.
        Schema::create('campaign_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_key')->index();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('step', 32)->default('welcome');
            $table->timestamp('enrolled_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // One enrolment per customer per campaign step, so a repeated run
            // cannot send the same step twice.
            $table->unique(['campaign_key', 'customer_id', 'step'], 'campaign_recipient_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_recipients');
        Schema::dropIfExists('notifications');
    }
};
