<?php

namespace Database\Seeders\Concerns;

use App\Enums\PriceType;
use App\Models\CourtPrice;

/**
 * Court rates in pesos, published per court.
 *
 * PriceResolver::candidates() filters on branch_id and accepts either a
 * court-scoped rule or a branch-wide default, so these rows must carry the
 * branch as well as the court or the booking engine resolves nothing and every
 * slot quotes zero.
 *
 * The windows are chosen so exactly one rule wins per hour, following
 * PriceType::priority(): off-peak is the daytime floor and peak outranks it.
 */
trait SeedsPricing
{
    /**
     * @param  array<int, int>  $courtIds
     */
    private function seedPricing(int $orgId, int $branchId, array $courtIds): void
    {
        foreach ($courtIds as $index => $courtId) {
            // Every third court is tournament-grade and carries a premium.
            $premium = $index % 3 === 0;

            // Off-peak: any hour before 17:00. Lowest priority, so it acts as
            // the daytime floor.
            CourtPrice::create([
                'organization_id' => $orgId,
                'branch_id' => $branchId,
                'court_id' => $courtId,
                'type' => PriceType::OFF_PEAK->value,
                'amount' => $premium ? 500.00 : 400.00,
                'min_minutes' => 60,
                'increment_minutes' => 60,
                'is_active' => true,
            ]);

            // Peak: 17:00-22:00. Outranks off-peak by priority.
            CourtPrice::create([
                'organization_id' => $orgId,
                'branch_id' => $branchId,
                'court_id' => $courtId,
                'type' => PriceType::PEAK->value,
                'amount' => $premium ? 800.00 : 650.00,
                'min_minutes' => 60,
                'increment_minutes' => 60,
                'starts_at_hour' => 17,
                'ends_at_hour' => 22,
                'is_active' => true,
            ]);

            // Weekend: Saturday and Sunday.
            CourtPrice::create([
                'organization_id' => $orgId,
                'branch_id' => $branchId,
                'court_id' => $courtId,
                'type' => PriceType::WEEKEND->value,
                'amount' => $premium ? 950.00 : 750.00,
                'min_minutes' => 60,
                'increment_minutes' => 60,
                'is_active' => true,
            ]);
        }
    }
}
