<?php

namespace App\Support;

final class SalonPermission
{
    public const DASHBOARD = 14;

    public const BOOKINGS_LIST = 15;

    public const BOOKING_PAGE = 16;

    public const VIEW_BOOKINGS = 17;

    public const BOOKING_MANAGEMENT = 18;

    public const TEAM = 19;

    public const STAFF = 20;

    public const TOOLS = 21;

    public const EXPENSE_CATEGORY = 22;

    public const EXPENSE = 23;

    public const EXPENSE_REPORT = 24;

    public const SERVICE = 25;

    public const CUSTOMER = 26;

    public const MONTHLY_INCOME_REPORT = 27;

    public const INCOME_EXPENSE_REPORT = 28;

    /** @return int[] */
    public static function allIds(): array
    {
        return range(self::DASHBOARD, self::INCOME_EXPENSE_REPORT);
    }

    /**
     * @param  array<int|string>  $userPermissions
     * @param  int[]  $requiredAny
     */
    public static function userHasAny(array $userPermissions, array $requiredAny): bool
    {
        $userPermissions = array_map('intval', $userPermissions);

        return (bool) array_intersect($userPermissions, $requiredAny);
    }
}
