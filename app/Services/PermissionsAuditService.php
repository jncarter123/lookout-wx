<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionsAuditService
{
    public function sync(string $adminRoleName = 'Admin'): array
    {
        $guardName = config('auth.defaults.guard', 'web');

        $configuredPermissions = collect(config('auth_permissions.permissions', []))
            ->filter(fn ($permission) => is_string($permission) && trim($permission) !== '')
            ->map(fn ($permission) => trim($permission))
            ->unique()
            ->values();

        $existingPermissions = Permission::query()
            ->where('guard_name', $guardName)
            ->pluck('name');

        $toCreate = $configuredPermissions->diff($existingPermissions)->values();
        $toDelete = $existingPermissions->diff($configuredPermissions)->values();

        foreach ($toCreate as $permissionName) {
            Permission::create([
                'guard_name' => $guardName,
                'name' => $permissionName,
            ]);
        }

        if ($toDelete->isNotEmpty()) {
            Permission::query()
                ->where('guard_name', $guardName)
                ->whereIn('name', $toDelete->all())
                ->delete();
        }

        $adminRole = Role::query()
            ->where('guard_name', $guardName)
            ->where('name', $adminRoleName)
            ->first();

        if (!$adminRole) {
            $adminRole = Role::create([
                'guard_name' => $guardName,
                'name' => $adminRoleName,
            ]);
        }

        $adminRole->syncPermissions(
            Permission::query()->where('guard_name', $guardName)->pluck('name')->all()
        );

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return [
            'guard_name' => $guardName,
            'created' => $toCreate->all(),
            'deleted' => $toDelete->all(),
            'admin_role' => $adminRoleName,
        ];
    }
}
