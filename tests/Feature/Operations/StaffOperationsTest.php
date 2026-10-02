<?php

namespace Tests\Feature\Operations;

use App\Enums\IncidentSeverity;
use App\Enums\LeaveStatus;
use App\Enums\Role;
use App\Models\Branch;
use App\Models\IncidentReport;
use App\Models\OperationsChecklist;
use App\Models\Organization;
use App\Models\Staff;
use App\Models\StaffTask;
use App\Services\Operations\AttendanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use RuntimeException;
use Tests\TestCase;

/**
 * Phase 11 acceptance checks: staff, attendance and daily operations
 * (plan.md §19).
 */
class StaffOperationsTest extends TestCase
{
    use RefreshDatabase;

    protected Staff $staff;

    protected AttendanceService $attendance;

    protected function setUp(): void
    {
        parent::setUp();

        $organization = Organization::factory()->create();
        $branch = Branch::factory()->for($organization)->create();

        $this->staff = Staff::factory()->create([
            'organization_id' => $organization->id,
            'branch_id' => $branch->id,
            'role' => Role::FRONT_DESK->value,
        ]);

        $this->attendance = app(AttendanceService::class);
    }

    public function test_a_staff_record_is_created(): void
    {
        $this->assertSame(Role::FRONT_DESK->value, $this->staff->role->value);
        $this->assertNotEmpty($this->staff->fullName());
    }

    public function test_commission_is_calculated_on_sales(): void
    {
        $cashier = Staff::factory()->create([
            'organization_id' => $this->staff->organization_id,
            'role' => Role::CASHIER->value,
            'commission_rate' => '5.00',
        ]);

        $this->assertSame(250.0, $cashier->commissionOn(5000.0));
        $this->assertSame(0.0, $this->staff->commissionOn(5000.0), 'No rate means no commission.');
    }

    public function test_punching_in_records_the_time(): void
    {
        $at = Carbon::parse('2026-03-04 08:00:00');

        $shift = $this->attendance->punchIn($this->staff, $at);

        $this->assertTrue($shift->time_in->eq($at));
        $this->assertNull($shift->time_out);
        $this->assertFalse($shift->isComplete());
    }

    public function test_a_double_punch_in_is_refused(): void
    {
        $at = Carbon::parse('2026-03-04 08:00:00');
        $this->attendance->punchIn($this->staff, $at);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('already punched in');

        $this->attendance->punchIn($this->staff, $at->copy()->addHour());
    }

    public function test_punching_out_closes_the_shift(): void
    {
        $in = Carbon::parse('2026-03-04 08:00:00');
        $this->attendance->punchIn($this->staff, $in);

        $shift = $this->attendance->punchOut($this->staff, $in->copy()->addHours(9), 60);

        $this->assertTrue($shift->isComplete());
        // 9 hours less a one hour break.
        $this->assertSame(8.0, $shift->workedHours());
    }

    public function test_punching_out_without_punching_in_is_refused(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('has not punched in');

        $this->attendance->punchOut($this->staff, Carbon::parse('2026-03-04 17:00:00'));
    }

    public function test_a_shift_cannot_end_before_it_starts(): void
    {
        $in = Carbon::parse('2026-03-04 08:00:00');
        $this->attendance->punchIn($this->staff, $in);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('cannot end before it began');

        $this->attendance->punchOut($this->staff, $in->copy()->subHour());
    }

    public function test_a_shift_cannot_be_closed_twice(): void
    {
        $in = Carbon::parse('2026-03-04 08:00:00');
        $this->attendance->punchIn($this->staff, $in);
        $this->attendance->punchOut($this->staff, $in->copy()->addHours(8));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('already been closed');

        $this->attendance->punchOut($this->staff, $in->copy()->addHours(9));
    }

    public function test_total_hours_are_summed_across_days(): void
    {
        foreach (['04', '05', '06'] as $day) {
            $in = Carbon::parse("2026-03-$day 08:00:00");
            $this->attendance->punchIn($this->staff, $in);
            $this->attendance->punchOut($this->staff, $in->copy()->addHours(6));
        }

        $hours = $this->attendance->totalHours(
            $this->staff,
            Carbon::parse('2026-03-01'),
            Carbon::parse('2026-03-31'),
        );

        $this->assertSame(18.0, $hours);
    }

    public function test_an_open_shift_counts_towards_nothing(): void
    {
        $this->attendance->punchIn($this->staff, Carbon::parse('2026-03-04 08:00:00'));

        $hours = $this->attendance->totalHours(
            $this->staff,
            Carbon::parse('2026-03-01'),
            Carbon::parse('2026-03-31'),
        );

        $this->assertSame(0.0, $hours, 'An unclosed shift must not silently count as zero hours worked.');
    }

    public function test_leave_can_be_requested_and_approved(): void
    {
        $request = $this->attendance->requestLeave(
            $this->staff,
            Carbon::parse('2026-04-01'),
            Carbon::parse('2026-04-03'),
            'Family event',
        );

        $this->assertSame(LeaveStatus::PENDING, $request->status);
        $this->assertSame(3, $request->days());

        $approved = $this->attendance->reviewLeave($request, true, null, 'Enjoy');

        $this->assertSame(LeaveStatus::APPROVED, $approved->status);
        $this->assertNotNull($approved->reviewed_at);
    }

    public function test_leave_can_be_rejected(): void
    {
        $request = $this->attendance->requestLeave(
            $this->staff,
            Carbon::parse('2026-04-01'),
            Carbon::parse('2026-04-01'),
        );

        $rejected = $this->attendance->reviewLeave($request, false, null, 'Too busy');

        $this->assertSame(LeaveStatus::REJECTED, $rejected->status);
    }

    public function test_leave_cannot_end_before_it_starts(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('cannot end before it starts');

        $this->attendance->requestLeave(
            $this->staff,
            Carbon::parse('2026-04-05'),
            Carbon::parse('2026-04-01'),
        );
    }

    public function test_a_decided_leave_cannot_be_decided_again(): void
    {
        $request = $this->attendance->requestLeave(
            $this->staff,
            Carbon::parse('2026-04-01'),
            Carbon::parse('2026-04-01'),
        );

        $this->attendance->reviewLeave($request, true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('was already Approved');

        $this->attendance->reviewLeave($request->fresh(), false);
    }

    public function test_a_checklist_reports_progress(): void
    {
        $checklist = OperationsChecklist::create([
            'organization_id' => $this->staff->organization_id,
            'branch_id' => $this->staff->branch_id,
            'type' => 'opening',
            'performed_on' => now()->toDateString(),
            'items' => [
                ['label' => 'Sweep courts', 'done' => true],
                ['label' => 'Check nets', 'done' => true],
                ['label' => 'Count balls', 'done' => false],
            ],
        ]);

        $this->assertSame(['done' => 2, 'total' => 3], $checklist->progress());
        $this->assertFalse($checklist->isComplete());
    }

    public function test_a_completed_checklist_is_marked(): void
    {
        $checklist = OperationsChecklist::create([
            'organization_id' => $this->staff->organization_id,
            'branch_id' => $this->staff->branch_id,
            'type' => 'closing',
            'performed_on' => now()->toDateString(),
            'items' => [['label' => 'Lock up', 'done' => true]],
            'completed_at' => now(),
        ]);

        $this->assertTrue($checklist->isComplete());
        $this->assertSame(['done' => 1, 'total' => 1], $checklist->progress());
    }

    public function test_a_critical_incident_demands_attention(): void
    {
        $incident = IncidentReport::create([
            'organization_id' => $this->staff->organization_id,
            'branch_id' => $this->staff->branch_id,
            'title' => 'Court surface damaged',
            'severity' => IncidentSeverity::CRITICAL->value,
        ]);

        $this->assertTrue($incident->severity->requiresImmediateAttention());
        $this->assertTrue($incident->isOpen());
        $this->assertFalse(
            IncidentReport::create([
                'organization_id' => $this->staff->organization_id,
                'title' => 'Ball lost',
                'severity' => IncidentSeverity::LOW->value,
            ])->severity->requiresImmediateAttention(),
        );
    }

    public function test_an_overdue_task_is_flagged(): void
    {
        $task = StaffTask::create([
            'organization_id' => $this->staff->organization_id,
            'assigned_to' => $this->staff->id,
            'title' => 'Replace net',
            'due_on' => now()->subDay()->toDateString(),
        ]);

        $this->assertTrue($task->isOverdue());
    }

    public function test_a_completed_task_is_not_overdue(): void
    {
        $task = StaffTask::create([
            'organization_id' => $this->staff->organization_id,
            'assigned_to' => $this->staff->id,
            'title' => 'Replace net',
            'due_on' => now()->subDay()->toDateString(),
            'status' => 'done',
        ]);

        $this->assertFalse($task->isOverdue());
    }

    public function test_a_task_with_no_due_date_is_not_overdue(): void
    {
        $task = StaffTask::create([
            'organization_id' => $this->staff->organization_id,
            'title' => 'Someday',
        ]);

        $this->assertFalse($task->isOverdue());
    }
}
