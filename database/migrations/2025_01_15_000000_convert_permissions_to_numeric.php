<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Skip on fresh installs where the column does not exist
        if (!Schema::hasColumn('users', 'permissions')) {
            return;
        }

        $permissionMap = [
            'user' => 1,
            'account' => 2,
            'expense' => 3,
            'sms' => 4,
            'special_order' => 5,
            'manage_quantity' => 6,
            'tailor_order' => 7,
            'pos' => 8,
            'stock' => 9,
            'reports' => 10,
            'boutique' => 11,
            'tailor' => 12,
        ];

        $users = DB::table('users')
            ->whereNotNull('permissions')
            ->where('permissions', '!=', '[]')
            ->get();

        foreach ($users as $user) {
            $permissions = json_decode($user->permissions, true);

            if (is_array($permissions)) {
                $convertedPermissions = [];

                foreach ($permissions as $permission) {
                    if (is_numeric($permission)) {
                        $convertedPermissions[] = (int) $permission;
                        continue;
                    }

                    if (isset($permissionMap[$permission])) {
                        $convertedPermissions[] = $permissionMap[$permission];
                    }
                }

                DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'permissions' => json_encode($convertedPermissions)
                    ]);
            }
        }
    }

    public function down(): void
    {
        // Skip on fresh installs where the column does not exist
        if (!Schema::hasColumn('users', 'permissions')) {
            return;
        }

        $reversePermissionMap = [
            1 => 'user',
            2 => 'account',
            3 => 'expense',
            4 => 'sms',
            5 => 'special_order',
            6 => 'manage_quantity',
            7 => 'tailor_order',
            8 => 'pos',
            9 => 'stock',
            10 => 'reports',
            11 => 'boutique',
            12 => 'tailor',
        ];

        $users = DB::table('users')
            ->whereNotNull('permissions')
            ->where('permissions', '!=', '[]')
            ->get();

        foreach ($users as $user) {
            $permissions = json_decode($user->permissions, true);

            if (is_array($permissions)) {
                $convertedPermissions = [];

                foreach ($permissions as $permission) {
                    $permissionId = is_numeric($permission) ? (int) $permission : $permission;

                    if (isset($reversePermissionMap[$permissionId])) {
                        $convertedPermissions[] = $reversePermissionMap[$permissionId];
                    } elseif (!is_numeric($permission)) {
                        $convertedPermissions[] = $permission;
                    }
                }

                DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'permissions' => json_encode($convertedPermissions)
                    ]);
            }
        }
    }
};