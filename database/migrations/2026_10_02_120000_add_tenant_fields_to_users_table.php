<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Links users to tenants and records which staff member they are
 * (plan.md §30 `organization_users`, §11 staff).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Null for platform (SaaS) staff who operate across tenants.
            $table->foreignId('organization_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->after('organization_id')->constrained()->nullOnDelete();

            // Customer-facing profile fields (plan.md §16).
            $table->string('phone', 32)->nullable()->after('email');
            $table->enum('skill_level', ['beginner', 'intermediate', 'advanced', 'competitive'])
                ->default('beginner')
                ->after('phone');
            $table->boolean('is_active')->default(true)->after('skill_level');
        });

        Schema::create('organization_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 64)->default('customer');
            $table->boolean('is_primary')->default(false);
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'user_id', 'role']);
            $table->index(['user_id', 'is_primary']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_users');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
            $table->dropConstrainedForeignId('organization_id');
            $table->dropColumn(['phone', 'skill_level', 'is_active']);
        });
    }
};
