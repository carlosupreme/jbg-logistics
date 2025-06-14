<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

         $permissions = [
            // User management
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            'users.activate',
            'users.deactivate',
            'users.reset-password',

            // Store management
            'stores.view',
            'stores.create',
            'stores.edit',
            'stores.delete',

            // Zone management
            'zones.view',
            'zones.create',
            'zones.edit',
            'zones.delete',

            // Role and permission management
            'roles.view',
            'roles.create',
            'roles.edit',
            'roles.delete',
            'permissions.view',
            'permissions.assign',

            // Reports and analytics
            'reports.view',
            'reports.export',
            'analytics.view',

            // System settings
            'settings.view',
            'settings.edit',
            'system.maintenance',

            // Audit logs
            'logs.view',
            'logs.export',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions

        // Super Admin - has all permissions
        $superAdmin = Role::create(['name' => 'Super Administrador']);
        $superAdmin->givePermissionTo(Permission::all());

        // Admin - has most permissions except system maintenance
        $admin = Role::create(['name' => 'Administrador']);
        $admin->givePermissionTo([
            'users.view', 'users.create', 'users.edit', 'users.activate', 'users.deactivate',
            'stores.view', 'stores.create', 'stores.edit',
            'zones.view', 'zones.create', 'zones.edit',
            'roles.view', 'permissions.view',
            'reports.view', 'reports.export',
            'analytics.view',
            'settings.view', 'settings.edit',
            'logs.view',
        ]);

        // Store Manager - can manage their store and users
        $storeManager = Role::create(['name' => 'Gerente de Tienda']);
        $storeManager->givePermissionTo([
            'users.view', 'users.create', 'users.edit',
            'stores.view', 'stores.edit',
            'reports.view',
            'analytics.view',
        ]);

        // Supervisor - can view and basic management
        $supervisor = Role::create(['name' => 'Supervisor']);
        $supervisor->givePermissionTo([
            'users.view',
            'stores.view',
            'reports.view',
            'analytics.view',
        ]);

        // Employee - basic access
        $employee = Role::create(['name' => 'Empleado']);
        $employee->givePermissionTo([
            'users.view',
            'stores.view',
        ]);

        // System Administrator - technical role
        $sysAdmin = Role::create(['name' => 'Administrador del Sistema']);
        $sysAdmin->givePermissionTo([
            'users.view', 'users.create', 'users.edit', 'users.delete',
            'users.activate', 'users.deactivate', 'users.reset-password',
            'stores.view', 'stores.create', 'stores.edit', 'stores.delete',
            'zones.view', 'zones.create', 'zones.edit', 'zones.delete',
            'roles.view', 'roles.create', 'roles.edit', 'roles.delete',
            'permissions.view', 'permissions.assign',
            'settings.view', 'settings.edit',
            'system.maintenance',
            'logs.view', 'logs.export',
        ]);

        $this->command->info('Roles and permissions created successfully!');
    }
}
