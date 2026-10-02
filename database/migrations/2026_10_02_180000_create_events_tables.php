<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Events, registrations, brackets, divisions, matches and results
 * (plan.md §16, §30).
 *
 * Built to accept singles, doubles and mixed doubles from the start: a match
 * references two *registrations*, each of which may name a player or a team, so
 * a format change later is data rather than a migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', [
                'open_play',
                'beginner_session',
                'clinic',
                'coaching',
                'league',
                'tournament',
                'community',
            ])->index();

            $table->enum('status', [
                'draft',
                'open',
                'full',
                'ongoing',
                'completed',
                'cancelled',
            ])->default('draft')->index();

            $table->timestamp('starts_at');
            $table->timestamp('ends_at');

            // Where it happens: a court, or a branch when the event roams.
            $table->foreignId('court_id')->nullable()->constrained()->nullOnDelete();

            $table->unsignedSmallInteger('capacity')->nullable();
            $table->unsignedSmallInteger('fee')->default(0);
            $table->char('currency', 3)->default('PHP');

            // Format, so a division can be read later without guessing.
            $table->enum('format', ['singles', 'doubles', 'mixed_doubles'])
                ->default('doubles');
            $table->boolean('is_members_only')->default(false);
            $table->unsignedSmallInteger('max_team_size')->default(2);

            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'status', 'starts_at']);
            $table->index(['branch_id', 'starts_at']);
        });

        Schema::create('event_divisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('name');                 // e.g. "3.0 Mixed"
            $table->decimal('min_rating', 4, 2)->nullable();
            $table->decimal('max_rating', 4, 2)->nullable();
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->timestamps();

            $table->index('event_id');
        });

        Schema::create('event_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_division_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('partner_name')->nullable();   // doubles partner
            $table->string('team_name')->nullable();
            $table->enum('status', ['registered', 'waitlisted', 'withdrawn', 'disqualified'])
                ->default('registered')->index();
            $table->unsignedSmallInteger('amount_paid')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            // One player enters an event once.
            $table->unique(['event_id', 'customer_id'], 'event_registration_unique');
            $table->index(['event_id', 'status']);
        });

        /*
         * Bracket matches. A bye is a real state: a 5-player round robin has
         * one, and it must not be played.
         */
        Schema::create('event_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_division_id')->nullable()->constrained()->nullOnDelete();

            // Links within a bracket: a winner advances into a later match.
            $table->foreignId('winner_of_match_id')->nullable()
                ->constrained('event_matches')->nullOnDelete();
            $table->unsignedTinyInteger('round')->default(1);
            $table->unsignedSmallInteger('position')->default(0);

            $table->foreignId('team_a_registration_id')->nullable()
                ->constrained('event_registrations')->nullOnDelete();
            $table->foreignId('team_b_registration_id')->nullable()
                ->constrained('event_registrations')->nullOnDelete();

            $table->unsignedTinyInteger('team_a_score')->nullable();
            $table->unsignedTinyInteger('team_b_score')->nullable();

            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'bye', 'walkover'])
                ->default('scheduled')->index();

            $table->unsignedSmallInteger('best_of')->default(3);
            $table->foreignId('court_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('scheduled_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['event_division_id', 'round']);
            $table->index(['event_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_matches');
        Schema::dropIfExists('event_registrations');
        Schema::dropIfExists('event_divisions');
        Schema::dropIfExists('events');
    }
};
