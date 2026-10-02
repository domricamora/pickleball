<?php

namespace App\Enums;

/**
 * The marketing funnel (plan.md §23).
 *
 * Visitor -> Facility View -> Court View -> Booking -> Payment ->
 * Completed Booking -> Repeat Booking.
 *
 * The order matters: conversion is measured between consecutive steps, so the
 * enum order is the funnel order and must not be rearranged casually.
 */
enum FunnelStep: string
{
    case VISITOR = 'visitor';
    case FACILITY_VIEW = 'facility_view';
    case COURT_VIEW = 'court_view';
    case BOOKING_STARTED = 'booking_started';
    case BOOKING_COMPLETED = 'booking_completed';
    case REPEAT_BOOKING = 'repeat_booking';

    /**
     * The step that follows this one, or null at the end of the funnel.
     */
    public function next(): ?self
    {
        $all = self::cases();
        $index = array_search($this, $all, true);

        return $index === false || $index === array_key_last($all) ? null : $all[$index + 1];
    }

    public function label(): string
    {
        return match ($this) {
            self::VISITOR => 'Visitor',
            self::FACILITY_VIEW => 'Facility view',
            self::COURT_VIEW => 'Court view',
            self::BOOKING_STARTED => 'Booking started',
            self::BOOKING_COMPLETED => 'Completed booking',
            self::REPEAT_BOOKING => 'Repeat booking',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
