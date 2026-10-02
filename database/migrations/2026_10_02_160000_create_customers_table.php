<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The customer / player CRM (plan.md §14, §30).
 *
 * `bookings.customer_id` and `payments.customer_id` were declared without a
 * foreign key in Phase 4/5 precisely because this table did not exist yet;
 * the constraint is added here now that it does.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();

            // A customer may also have a login; the link is optional because
            // front desk staff routinely book for walk-ins.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->string('mobile', 24)->nullable();
            $table->date('birthday')->nullable();
            $table->enum('gender', ['male', 'female', 'other', 'prefer_not_to_say'])
                ->nullable();

            $table->string('address_line')->nullable();
            $table->string('address_barangay')->nullable();
            $table->string('address_city')->nullable();
            $table->string('address_province')->nullable();

            // Emergency contact (plan.md §14)
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_mobile', 24)->nullable();

            // How they play.
            $table->enum('skill_level', ['beginner', 'intermediate', 'advanced', 'competitive'])
                ->default('beginner');
            $table->enum('preferred_playing_time', ['morning', 'afternoon', 'evening', 'any'])
                ->default('any');
            $table->string('favorite_surface', 32)->nullable();

            $table->text('notes')->nullable();

            // Consent and preferences (plan.md §14)
            $table->boolean('consents_to_marketing')->default(false);
            $table->boolean('consents_to_sms')->default(false);
            $table->timestamp('consented_at')->nullable();

            $table->boolean('is_vip')->default(false);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            // Name lookups and email lookups are the two things staff do most.
            $table->index(['organization_id', 'last_name', 'first_name']);
            $table->index(['organization_id', 'email']);
            $table->index(['organization_id', 'mobile']);
            $table->index(['organization_id', 'is_vip']);
        });

        // Now that customers exist, the deferred references can be constrained.
        Schema::table('bookings', function (Blueprint $table) {
            $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
        });

        Schema::dropIfExists('customers');
    }
};
