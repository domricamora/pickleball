        Schema::create('operations_checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->enum('type', ['opening', 'closing'])->index();
            $table->date('performed_on')->index();
            $table->json('items')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'type', 'performed_on']);
        });<?php

use Illuminate\Database\Migrations\Migration;
        use Illuminate\Database\Schema\Blueprint;
        use Illuminate\Support\Facades\Schema;

        /**
         * Staff records, attendance, leave and daily operations (plan.md §19, §30).
         *
         * Attendance is stored as a punch pair (time in, time out) rather than an
         * accumulated number of hours, so a missing or corrected punch stays visible
         * instead of being absorbed into a total.
         */
        return new class extends Migration
        {
            public function up(): void
            {
                Schema::create('staff', function (Blueprint $table) {
                    $table->id();
                    $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
                    $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                    $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();

                    $table->string('employee_number', 32)->nullable();
                    $table->string('first_name');
                    $table->string('last_name');
                    $table->string('phone', 24)->nullable();
                    $table->date('hired_on')->nullable();
                    $table->enum('role', [
                        'Facility Owner',
                        'Manager',
                        'Front Desk',
                        'Cashier',
                        'Staff',
                        'Coach',
                    ])->index();

                    // Commission is a rate on sales, where the role earns one.
                    $table->decimal('commission_rate', 5, 2)->default(0);
                    $table->decimal('base_salary', 10, 2)->default(0);
                    $table->boolean('is_active')->default(true);
                    $table->timestamps();
                    $table->softDeletes();

                    $table->unique(['organization_id', 'employee_number'], 'staff_org_number_unique');
                    $table->index(['organization_id', 'is_active']);
                });

                Schema::create('staff_attendances', function (Blueprint $table) {
                    $table->id();
                    $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
                    $table->foreignId('staff_id')->constrained()->cascadeOnDelete();
                    $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();

                    $table->date('work_date');
                    $table->timestamp('time_in')->nullable();
                    $table->timestamp('time_out')->nullable();
                    $table->unsignedSmallInteger('break_minutes')->default(0);
                    $table->text('notes')->nullable();
                    $table->timestamps();

                    // One shift per person per day: a double punch-in is impossible.
                    $table->unique(['staff_id', 'work_date'], 'attendance_staff_date_unique');
                    $table->index(['organization_id', 'work_date']);
                });

                Schema::create('staff_leave_requests', function (Blueprint $table) {
                    $table->id();
                    $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
                    $table->foreignId('staff_id')->constrained()->cascadeOnDelete();
                    $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();

                    $table->date('starts_on');
                    $table->date('ends_on');
                    $table->text('reason')->nullable();
                    $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])
                        ->default('pending')->index();
                    $table->text('review_note')->nullable();
                    $table->timestamp('reviewed_at')->nullable();
                    $table->timestamps();

                    $table->index(['organization_id', 'status']);
                });
                // Opening and closing checklists share one table: same shape, two uses.
                Schema::create('operations_checklists', function (Blueprint $table) {
                    $table->id();
                    $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
                    $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
                    $table->foreignId('staff_id')->nullable()->constrained('staff')->nullOnDelete();
                    $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();

                    $table->enum('type', ['opening', 'closing'])->index();
                    $table->date('performed_on')->index();
                    $table->json('items')->nullable();
                    $table->text('notes')->nullable();
                    $table->timestamp('completed_at')->nullable();
                    $table->timestamps();

                    $table->index(['branch_id', 'type', 'performed_on']);
                });

                Schema::create('incident_reports', function (Blueprint $table) {
                    $table->id();
                    $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
                    $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
                    $table->foreignId('court_id')->nullable()->constrained()->nullOnDelete();
                    $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
                    $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();

                    $table->string('title');
                    $table->text('description')->nullable();
                    $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('low')->index();
                    $table->enum('status', ['open', 'investigating', 'resolved', 'closed'])
                        ->default('open')->index();
                    $table->timestamp('resolved_at')->nullable();
                    $table->text('resolution')->nullable();
                    $table->timestamps();

                    $table->index(['organization_id', 'status']);
                });

                Schema::create('staff_tasks', function (Blueprint $table) {
                    $table->id();
                    $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
                    $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
                    $table->foreignId('assigned_to')->nullable()->constrained('staff')->nullOnDelete();
                    $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();

                    $table->string('title');
                    $table->text('description')->nullable();
                    $table->enum('type', ['maintenance', 'admin', 'other'])->default('other')->index();
                    $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal')->index();
                    $table->enum('status', ['open', 'in_progress', 'done', 'cancelled'])
                        ->default('open')->index();
                    $table->date('due_on')->nullable();
                    $table->timestamp('completed_at')->nullable();
                    $table->timestamps();

                    $table->index(['organization_id', 'status']);
                });
            }

            public function down(): void
            {
                Schema::dropIfExists('staff_tasks');
                Schema::dropIfExists('incident_reports');
                Schema::dropIfExists('operations_checklists');
                Schema::dropIfExists('staff_leave_requests');
                Schema::dropIfExists('staff_attendances');
                Schema::dropIfExists('staff');
            }
        };
