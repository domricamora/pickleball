<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Courts, their weekly schedules, ad-hoc blocks and pricing (plan.md §11,
 * §30). Everything here is tenant-owned and therefore carries organization_id
 * so the global scope can isolate it.
 */
return new class extends Migration
{
    public function up(): void
    {
        /*
         * Extra facility detail that was deferred from the branches table.
         */
        Schema::table('branches', function (Blueprint $table) {
            $table->text('description')->nullable()->after('status');
            $table->json('opening_hours')->nullable()->after('description');
        });

        Schema::create('courts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('number', 32)->nullable();

            // Playing surface (plan.md §11)
            $table->enum('surface', ['hard', 'soft', 'acrylic', 'cushioned', 'other'])->default('hard');
            $table->enum('type', ['standard', 'dedicated', 'tournament'])->default('standard');
            $table->enum('setting', ['indoor', 'outdoor'])->default('indoor');

            $table->enum('status', ['available', 'maintenance', 'blocked', 'retired'])
                ->default('available')
                ->index();
            $table->unsignedSmallInteger('capacity')->default(4);

            $table->json('amenities')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // A court number is unique within its facility, not platform-wide.
            $table->unique(['branch_id', 'number']);
            $table->index(['organization_id', 'status']);
            $table->index(['branch_id', 'status']);
        });

        /*
         * Recurring weekly opening hours. 0 = Sunday through 6 = Saturday,
         * matching PHP's day-of-week numbering.
         */
        Schema::create('court_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('court_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday');          // 0 (Sun) - 6 (Sat)
            $table->time('opens_at');
            $table->time('closes_at');
            $table->boolean('is_closed')->default(false);
            $table->timestamps();

            $table->index(['court_id', 'weekday']);
        });

        /*
         * Ad-hoc closures: maintenance, a holiday, an event reservation or a
         * private block. These override the weekly schedule for their window.
         */
        Schema::create('court_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('court_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->cascadeOnDelete();
            $table->enum('type', ['maintenance', 'holiday', 'event', 'private', 'other'])->default('other');
            $table->string('reason')->nullable();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->time('starts_at')->nullable();   // null = all day
            $table->time('ends_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'starts_on', 'ends_on']);
            $table->index(['court_id', 'starts_on', 'ends_on']);
        });

        /*
         * Pricing in pesos (plan.md §13, §32). Tax treatment stays
         * administrative on the organization, so prices are stored gross of
         * any VAT the facility chooses to add.
         */
        Schema::create('court_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('court_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->enum('type', [
                'weekday',
                'weekend',
                'peak',
                'off_peak',
                'holiday',
                'member',
                'guest',
            ]);
            $table->decimal('amount', 10, 2);
            $table->unsignedSmallInteger('min_minutes')->default(60);
            $table->unsignedSmallInteger('increment_minutes')->default(60);
            $table->unsignedTinyInteger('starts_at_hour')->nullable();  // peak window
            $table->unsignedTinyInteger('ends_at_hour')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['organization_id', 'is_active']);
            $table->index(['court_id', 'type']);
        });

        Schema::create('branch_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('alt_text')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['branch_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_photos');
        Schema::dropIfExists('court_prices');
        Schema::dropIfExists('court_blocks');
        Schema::dropIfExists('court_schedules');
        Schema::dropIfExists('courts');

        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn(['description', 'opening_hours']);
        });
    }
};
