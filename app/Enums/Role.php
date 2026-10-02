<?php

namespace App\Enums;

/**
 * Platform and tenant roles (plan.md §7).
 *
 * Roles are stored as strings so they remain readable in the database and in
 * seeders. Permission names are derived from the role so a new role only has to
 * declare its abilities here.
 */
enum Role: string
{
    case SUPER_ADMIN = 'Super Admin';
    case FACILITY_OWNER = 'Facility Owner';
    case MANAGER = 'Manager';
    case FRONT_DESK = 'Front Desk';
    case CASHIER = 'Cashier';
    case STAFF = 'Staff';
    case COACH = 'Coach';
    case CUSTOMER = 'Customer';

    /**
     * Permissions granted to this role.
     *
     * @return array<int, string>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::SUPER_ADMIN => ['*'],

            self::FACILITY_OWNER => [
                'facilities.view', 'facilities.manage',
                'courts.view', 'courts.manage',
                'bookings.view', 'bookings.manage',
                'customers.view', 'customers.manage',
                'staff.view', 'staff.manage',
                'pos.use', 'pos.manage',
                'inventory.view', 'inventory.manage',
                'reports.view', 'reports.manage',
                'settings.manage',
            ],

            self::MANAGER => [
                'facilities.view', 'courts.view',
                'bookings.view', 'bookings.manage',
                'customers.view', 'customers.manage',
                'staff.view',
                'pos.use',
                'inventory.view',
                'reports.view',
            ],

            self::FRONT_DESK => [
                'facilities.view', 'courts.view',
                'bookings.view', 'bookings.manage',
                'customers.view', 'customers.manage',
                'pos.use',
            ],

            self::CASHIER => ['pos.use', 'inventory.view', 'customers.view'],

            self::STAFF => ['bookings.view', 'facilities.view', 'courts.view'],

            self::COACH => ['bookings.view', 'facilities.view', 'courts.view', 'customers.view'],

            self::CUSTOMER => ['booking.create-own', 'profile.view-own'],
        };
    }

    /**
     * Roles allowed inside a tenant. Super Admin belongs to the platform.
     *
     * @return array<int, self>
     */
    public static function tenantRoles(): array
    {
        return [
            self::FACILITY_OWNER,
            self::MANAGER,
            self::FRONT_DESK,
            self::CASHIER,
            self::STAFF,
            self::COACH,
            self::CUSTOMER,
        ];
    }

    public function label(): string
    {
        return $this->value;
    }
}
