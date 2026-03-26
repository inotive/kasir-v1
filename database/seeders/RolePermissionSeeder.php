<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $guardName = (string) config('auth.defaults.guard', 'web');

        // Reset cached roles and permissions
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Define Permissions
        $permissions = [
            // Dashboard
            'dashboard.access',

            // POS
            'pos.access',

            // Guides
            'guides.view',

            // Products
            'products.view',
            'products.create',
            'products.edit',
            'products.delete',

            // Categories
            'categories.view',
            'categories.create',
            'categories.edit',
            'categories.delete',

            // Transactions
            'transactions.view',
            'transactions.details',
            'transactions.pii.view',
            'transactions.print',
            'transactions.void',
            'transactions.refund',
            'transactions.void.approve',
            'transactions.refund.approve',

            // Members
            'members.view',
            'members.create',
            'members.edit',
            'members.delete',
            'members.pii.view',
            'members.regions.view',
            'members.regions.manage',

            // Reports
            'reports.view',
            'reports.sales',
            'reports.performance',
            'reports.expenses.manage',

            // Vouchers
            'vouchers.view',
            'vouchers.manage',

            // Manual Discounts
            'discounts.manual.apply',

            // Inventory
            'inventory.view',
            'inventory.manage',
            'inventory.ingredients.view',
            'inventory.ingredients.manage',
            'inventory.suppliers.view',
            'inventory.suppliers.manage',
            'inventory.movements.view',
            'inventory.movements.manage',
            'inventory.movements.create',
            'inventory.movements.delete',
            'inventory.purchases.view',
            'inventory.purchases.manage',
            'inventory.purchases.create',
            'inventory.purchases.edit',
            'inventory.purchases.receive',
            'inventory.purchases.cancel',
            'inventory.opnames.view',
            'inventory.opnames.manage',
            'inventory.opnames.create',
            'inventory.opnames.edit',
            'inventory.opnames.refresh_system_stocks',
            'inventory.opnames.post',
            'inventory.opnames.cancel',
            'inventory.reports.view',

            // User Management
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',

            // Role Management
            'roles.view',
            'roles.manage',

            // Dining Tables
            'dining_tables.view',
            'dining_tables.edit',

            // Settings
            'settings.view',
            'settings.edit',
            'settings.store.view',
            'settings.store.edit',
            'settings.printers.view',
            'settings.printers.edit',
            'settings.system.view',
            'settings.system.edit',
            'settings.points.view',
            'settings.points.edit',
            'settings.targets.view',
            'settings.targets.edit',
        ];

        $permissions = array_values(array_unique($permissions));

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, $guardName);
        }

        // Create Roles and Assign Permissions

        // Owner: All permissions
        $ownerRole = Role::findOrCreate('owner', $guardName);
        $ownerRole->syncPermissions($permissions);

        // Admin: Almost all, maybe restrict some critical owner stuff if needed
        $adminRole = Role::findOrCreate('admin', $guardName);
        $adminRole->syncPermissions($permissions);

        // Manager: Manage operations
        $managerRole = Role::findOrCreate('manager', $guardName);
        $managerRole->syncPermissions([
            'dashboard.access',
            'pos.access',
            'guides.view',
            'products.view', 'products.create', 'products.edit',
            'categories.view', 'categories.create', 'categories.edit',
            'transactions.view', 'transactions.details', 'transactions.pii.view', 'transactions.print', 'transactions.void', 'transactions.void.approve', 'transactions.refund.approve',
            'members.view', 'members.create', 'members.edit', 'members.delete', 'members.pii.view', 'members.regions.view', 'members.regions.manage',
            'reports.view', 'reports.sales', 'reports.performance',
            'reports.expenses.manage',
            'vouchers.view', 'vouchers.manage',
            'discounts.manual.apply',
            'inventory.view', 'inventory.manage',
            'inventory.ingredients.view', 'inventory.ingredients.manage',
            'inventory.suppliers.view', 'inventory.suppliers.manage',
            'inventory.movements.view', 'inventory.movements.manage', 'inventory.movements.create', 'inventory.movements.delete',
            'inventory.purchases.view', 'inventory.purchases.manage', 'inventory.purchases.create', 'inventory.purchases.edit', 'inventory.purchases.receive', 'inventory.purchases.cancel',
            'inventory.opnames.view', 'inventory.opnames.manage', 'inventory.opnames.create', 'inventory.opnames.edit', 'inventory.opnames.refresh_system_stocks', 'inventory.opnames.post', 'inventory.opnames.cancel',
            'inventory.reports.view',
            'dining_tables.view', 'dining_tables.edit',
            'users.view',
        ]);

        // Cashier: POS focused
        $cashierRole = Role::findOrCreate('cashier', $guardName);
        $cashierRole->syncPermissions([
            'dashboard.access',
            'pos.access',
            'guides.view',
            'products.view',
            'categories.view',
            'transactions.view', 'transactions.details', 'transactions.pii.view', 'transactions.print',
            'members.view', 'members.create', 'members.pii.view',
            'discounts.manual.apply',
        ]);

        // Inventory Staff
        $inventoryRole = Role::findOrCreate('inventory', $guardName);
        $inventoryRole->syncPermissions([
            'dashboard.access',
            'guides.view',
            'products.view',
            'categories.view',
            'inventory.view', 'inventory.manage',
            'inventory.ingredients.view', 'inventory.ingredients.manage',
            'inventory.suppliers.view', 'inventory.suppliers.manage',
            'inventory.movements.view', 'inventory.movements.manage', 'inventory.movements.create', 'inventory.movements.delete',
            'inventory.purchases.view', 'inventory.purchases.manage', 'inventory.purchases.create', 'inventory.purchases.edit', 'inventory.purchases.receive', 'inventory.purchases.cancel',
            'inventory.opnames.view', 'inventory.opnames.manage', 'inventory.opnames.create', 'inventory.opnames.edit', 'inventory.opnames.refresh_system_stocks', 'inventory.opnames.post', 'inventory.opnames.cancel',
            'inventory.reports.view',
        ]);

        // Accountant
        $accountantRole = Role::findOrCreate('accountant', $guardName);
        $accountantRole->syncPermissions([
            'dashboard.access',
            'guides.view',
            'transactions.view', 'transactions.details', 'transactions.pii.view', 'transactions.print',
            'reports.view', 'reports.sales', 'reports.performance',
            'reports.expenses.manage',
            'vouchers.view',
            'inventory.reports.view',
        ]);

        // Waiter
        $waiterRole = Role::findOrCreate('waiter', $guardName);
        $waiterRole->syncPermissions([
            'dashboard.access',
            'pos.access',
            'guides.view',
            'products.view',
            'categories.view',
        ]);

        // Assign Roles to Users based on existing 'role' column
        $users = User::all();
        foreach ($users as $user) {
            if ($user->role) {
                // Ensure the role string matches the enum value
                $roleName = (string) $user->role;

                // Check if role exists in Spatie (it should, we just created them)
                if (Role::query()->where('name', $roleName)->where('guard_name', $guardName)->exists()) {
                    $user->syncRoles([$roleName]);
                }
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
