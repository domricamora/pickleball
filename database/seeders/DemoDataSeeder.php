<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Branch;
use App\Models\Court;
use App\Models\CourtBlock;
use App\Models\CourtPrice;
use App\Models\CourtSchedule;
use App\Models\Customer;
use App\Models\Event;
use App\Models\Expense;
use App\Models\MembershipCreditUsage;
use App\Models\MembershipPlan;
use App\Models\MembershipSubscription;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\PaymentRefund;
use App\Models\Product;
use App\Models\Receipt;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Staff;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\Concerns\SeedsBookings;
use Database\Seeders\Concerns\SeedsCatalogue;
use Database\Seeders\Concerns\SeedsEvents;
use Database\Seeders\Concerns\SeedsMemberships;
use Database\Seeders\Concerns\SeedsOpeningHours;
use Database\Seeders\Concerns\SeedsPeople;
use Database\Seeders\Concerns\SeedsPricing;
use Database\Seeders\Concerns\SeedsTrading;
use Illuminate\Database\Seeder;

/**
 * Fills the existing tenant with enough history for the operator screens to
 * look like a club that has been trading.
 *
 * Two things make this read as real rather than as random rows:
 *
 *  - The reporting service derives revenue from payments, sales and membership
 *    subscriptions rather than from a report table (plan.md §20), so this writes
 *    those source rows and the dashboard figures follow from them.
 *  - History spans ninety days, because the dashboard defaults to a 30-day
 *    window; a same-day dataset reads as an empty business.
 *
 * Every row carries the tenant's organization_id. The tenant global scope is
 * the security boundary, so demo data without it would be invisible to the
 * very screens it exists to populate.
 *
 * No credentials are written (plan.md §2): demo players get no login, staff are
 * records without passwords, and the existing admin accounts are untouched, so
 * re-running this can never lock anyone out.
 */
class DemoDataSeeder extends Seeder
{
    use SeedsBookings;
    use SeedsCatalogue;
    use SeedsEvents;
    use SeedsMemberships;
    use SeedsOpeningHours;
    use SeedsPeople;
    use SeedsPricing;
    use SeedsTrading;

    /**
     * Reports progress when run from the console.
     *
     * The command is not guaranteed to be attached -- Seeder::run() can be
     * called directly (tests, other seeders), so every call goes through here
     * rather than assuming $this->command exists.
     */
    private function note(string $message): void
    {
        if ($this->command !== null) {
            $this->command->info($message);
        }
    }

    private function caution(string $message): void
    {
        if ($this->command !== null) {
            $this->command->warn($message);
        }
    }

    public function run(): void
    {
        $organization = Organization::query()->first();
        $branch = $organization
            ? Branch::query()->where('organization_id', $organization->id)->first()
            : null;

        $courts = $organization
            ? Court::query()->where('organization_id', $organization->id)->orderBy('id')->get()
            : collect();

        if ($organization === null || $branch === null || $courts->isEmpty()) {
            $this->caution('Needs an organisation, a branch and at least one court. Run app:install-admin first.');

            return;
        }

        // Bookings need a real account to be attributed to.
        $owners = User::query()->where('organization_id', $organization->id)->get();

        if ($owners->isEmpty()) {
            $this->caution('No tenant user found to own bookings. Seeding skipped.');

            return;
        }

        $orgId = $organization->id;
        $branchId = $branch->id;
        $courtIds = $courts->pluck('id')->all();

        $this->note(sprintf('Seeding demo data for "%s".', $organization->name));

        $this->clearOperationalData($orgId);

        $this->seedOpeningHours($orgId, $courtIds);
        $this->seedPricing($orgId, $branchId, $courtIds);

        $staff = $this->seedStaff($orgId, $branchId);
        $customers = $this->seedCustomers($orgId);

        $this->seedBookings($orgId, $branchId, $courtIds, $customers, $owners);

        $plans = $this->seedMembershipPlans($orgId, $branchId);
        $this->seedSubscriptions($orgId, $plans->pluck('id')->all(), $customers->pluck('id')->all());

        $products = $this->seedProducts($orgId, $branchId);
        $this->seedSales($orgId, $branchId, $products, $customers->pluck('id')->all(), $staff);
        $this->seedExpenses($orgId, $branchId, $owners);

        $this->seedEvents($orgId, $branchId, $courtIds[0]);

        $this->note('Demo data ready.');
    }

    /**
     * Clears this tenant's operational rows so the seeder can be re-run without
     * doubling every figure. Child rows go first to respect the foreign keys.
     */
    private function clearOperationalData(int $orgId): void
    {
        $saleIds = Sale::query()->withoutGlobalScope('organization')
            ->where('organization_id', $orgId)->pluck('id');

        $subscriptionIds = MembershipSubscription::query()->withoutGlobalScope('organization')
            ->where('organization_id', $orgId)->pluck('id');

        $bookingIds = Booking::query()->withoutGlobalScope('organization')
            ->where('organization_id', $orgId)->pluck('id');

        // Collected before the delete so refunds can be scoped through them:
        // payment_refunds has no organization_id of its own.
        $paymentIds = Payment::query()->withoutGlobalScope('organization')
            ->where('organization_id', $orgId)
            ->when($bookingIds->isNotEmpty(), fn ($q) => $q->orWhereIn('booking_id', $bookingIds))
            ->pluck('id');

        StockMovement::query()
            ->where(function ($query) use ($orgId, $saleIds): void {
                $query->where('organization_id', $orgId)
                    ->when($saleIds->isNotEmpty(), fn ($q) => $q->orWhereIn('sale_id', $saleIds));
            })
            ->delete();

        SaleItem::query()->whereIn('sale_id', $saleIds)->delete();

        // Receipt is tenant-scoped; payment_refunds are reachable only through
        // their payment, so the parent ids do the scoping.
        Receipt::query()->withoutGlobalScope('organization')->where('organization_id', $orgId)->delete();

        PaymentRefund::query()->whereIn('payment_id', $paymentIds)->delete();

        Payment::query()->withoutGlobalScope('organization')
            ->where('organization_id', $orgId)
            ->when($bookingIds->isNotEmpty(), fn ($q) => $q->orWhereIn('booking_id', $bookingIds))
            ->delete();

        Sale::query()->withoutGlobalScope('organization')->where('organization_id', $orgId)->delete();

        // These are soft-deleted, so they need forcing. Otherwise every re-run
        // would leave the previous set hidden behind a deleted_at and the
        // totals would quietly shrink.
        foreach ([Booking::class, Event::class, Customer::class, Product::class, Supplier::class, Expense::class, Staff::class] as $model) {
            $model::query()->withoutGlobalScope('organization')
                ->where('organization_id', $orgId)
                ->forceDelete();
        }

        // membership_credit_usages has no organization_id; scope it via its parent.
        MembershipCreditUsage::query()
            ->whereIn('membership_subscription_id', $subscriptionIds)
            ->delete();

        MembershipSubscription::query()->withoutGlobalScope('organization')->where('organization_id', $orgId)->delete();
        MembershipPlan::query()->withoutGlobalScope('organization')->where('organization_id', $orgId)->delete();

        CourtBlock::query()->withoutGlobalScope('organization')->where('organization_id', $orgId)->delete();
        CourtPrice::query()->withoutGlobalScope('organization')->where('organization_id', $orgId)->delete();
        CourtSchedule::query()->withoutGlobalScope('organization')->where('organization_id', $orgId)->delete();
    }
}
