<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Mapping of string permissions to numeric IDs
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

        // Get all users with permissions
        $users = DB::table('users')
            ->whereNotNull('permissions')
            ->where('permissions', '!=', '[]')
            ->get();

        foreach ($users as $user) {
            $permissions = json_decode($user->permissions, true);
            
            if (is_array($permissions)) {
                $convertedPermissions = [];
                
                foreach ($permissions as $permission) {
                    // Skip if already numeric (already converted)
                    if (is_numeric($permission)) {
                        $convertedPermissions[] = (int)$permission;
                        continue;
                    }
                    
                    // Convert string to numeric ID
                    if (isset($permissionMap[$permission])) {
                        $convertedPermissions[] = $permissionMap[$permission];
                    }
                }
                
                // Update user permissions
                DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'permissions' => json_encode($convertedPermissions)
                    ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Mapping of numeric IDs back to string permissions
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

        // Get all users with permissions
        $users = DB::table('users')
            ->whereNotNull('permissions')
            ->where('permissions', '!=', '[]')
            ->get();

        foreach ($users as $user) {
            $permissions = json_decode($user->permissions, true);
            
            if (is_array($permissions)) {
                $convertedPermissions = [];
                
                foreach ($permissions as $permission) {
                    // Convert numeric ID back to string
                    $permissionId = is_numeric($permission) ? (int)$permission : $permission;
                    
                    if (isset($reversePermissionMap[$permissionId])) {
                        $convertedPermissions[] = $reversePermissionMap[$permissionId];
                    } elseif (!is_numeric($permission)) {
                        // If it's already a string, keep it
                        $convertedPermissions[] = $permission;
                    }
                }
                
                // Update user permissions
                DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'permissions' => json_encode($convertedPermissions)
                    ]);
            }
        }
    }
};

