<?php

namespace Tests\Unit;

use App\Models\CourtBlock;
use App\Models\CourtSchedule;
use Carbon\Carbon;
use Tests\TestCase;

/**
 * Pure domain rules for schedules, blocks and pricing (plan.md §11).
 */
class CourtRulesTest extends TestCase
{
    public function test_a_schedule_covers_times_inside_its_window(): void
    {
        $schedule = new CourtSchedule([
            'opens_at' => '06:00:00',
            'closes_at' => '22:00:00',
            'is_closed' => false,
        ]);

        $this->assertTrue($schedule->covers('06:00'));
        $this->assertTrue($schedule->covers('13:30'));
        $this->assertTrue($schedule->covers('21:59'));
    }

    public function test_a_schedule_excludes_times_outside_its_window(): void
    {
        $schedule = new CourtSchedule([
            'opens_at' => '06:00:00',
            'closes_at' => '22:00:00',
            'is_closed' => false,
        ]);

        // The closing time itself is exclusive.
        $this->assertFalse($schedule->covers('05:59'));
        $this->assertFalse($schedule->covers('22:00'));
    }

    public function test_a_closed_schedule_covers_nothing(): void
    {
        $schedule = new CourtSchedule([
            'opens_at' => '06:00:00',
            'closes_at' => '22:00:00',
            'is_closed' => true,
        ]);

        $this->assertFalse($schedule->covers('12:00'));
    }

    public function test_a_schedule_can_run_past_midnight(): void
    {
        $schedule = new CourtSchedule([
            'opens_at' => '20:00:00',
            'closes_at' => '02:00:00',
            'is_closed' => false,
        ]);

        $this->assertTrue($schedule->covers('21:00'));
        $this->assertTrue($schedule->covers('23:59'));
        $this->assertTrue($schedule->covers('01:30'));
        $this->assertFalse($schedule->covers('12:00'));
    }

    public function test_an_all_day_block_blocks_every_slot(): void
    {
        $block = new CourtBlock([
            'starts_on' => Carbon::parse('2026-12-25'),
            'ends_on' => Carbon::parse('2026-12-25'),
            'starts_at' => null,
            'ends_at' => null,
        ]);

        $this->assertTrue($block->blocksSlot(Carbon::parse('2026-12-25'), '09:00'));
        $this->assertTrue($block->blocksSlot(Carbon::parse('2026-12-25'), '20:00'));
        $this->assertFalse($block->blocksSlot(Carbon::parse('2026-12-26'), '09:00'));
    }

    public function test_a_timed_block_only_blocks_its_window(): void
    {
        $block = new CourtBlock([
            'starts_on' => Carbon::parse('2026-03-02'),
            'ends_on' => Carbon::parse('2026-03-02'),
            'starts_at' => '08:00:00',
            'ends_at' => '12:00:00',
        ]);

        $this->assertTrue($block->blocksSlot(Carbon::parse('2026-03-02'), '08:00'));
        $this->assertTrue($block->blocksSlot(Carbon::parse('2026-03-02'), '11:59'));
        $this->assertFalse($block->blocksSlot(Carbon::parse('2026-03-02'), '12:00'));
        $this->assertFalse($block->blocksSlot(Carbon::parse('2026-03-02'), '15:00'));
    }

    public function test_a_block_spanning_days_covers_both(): void
    {
        $block = new CourtBlock([
            'starts_on' => Carbon::parse('2026-04-01'),
            'ends_on' => Carbon::parse('2026-04-03'),
            'starts_at' => null,
            'ends_at' => null,
        ]);

        $this->assertTrue($block->blocksSlot(Carbon::parse('2026-04-01'), '10:00'));
        $this->assertTrue($block->blocksSlot(Carbon::parse('2026-04-02'), '10:00'));
        $this->assertTrue($block->blocksSlot(Carbon::parse('2026-04-03'), '10:00'));
        $this->assertFalse($block->blocksSlot(Carbon::parse('2026-04-04'), '10:00'));
    }
}
