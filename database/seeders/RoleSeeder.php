<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create Permissions based on policies and actions

        // Project permissions
        Permission::create(['name' => 'view projects']);
        Permission::create(['name' => 'create projects']);
        Permission::create(['name' => 'update projects']);
        Permission::create(['name' => 'delete projects']);

        // Task permissions
        Permission::create(['name' => 'view tasks']);
        Permission::create(['name' => 'create tasks']);
        Permission::create(['name' => 'update tasks']);
        Permission::create(['name' => 'update any task']);
        Permission::create(['name' => 'delete tasks']);
        Permission::create(['name' => 'delete any task']);

        // Workspace permissions
        Permission::create(['name' => 'view workspaces']);
        Permission::create(['name' => 'view any workspace']);
        Permission::create(['name' => 'create workspaces']);
        Permission::create(['name' => 'update workspaces']);
        Permission::create(['name' => 'delete workspaces']);

        // User permissions
        Permission::create(['name' => 'delete users']);

        // Other permissions
        Permission::create(['name' => 'view billing']);

        // 2. Create Roles and Assign Permissions

        // Admin Role - Can do everything
        $admin = Role::create(['name' => 'admin']);
        $admin->givePermissionTo(Permission::all());

        // Manager Role - Can manage projects and tasks within workspace
        $manager = Role::create(['name' => 'manager']);
        $manager->givePermissionTo([
            'view projects',
            'create projects',
            'update projects',
            'delete projects',
            'view tasks',
            'create tasks',
            'update tasks',
            'delete tasks',
            'view workspaces',
            'update workspaces',
            'delete workspaces',
        ]);

        // Employee Role - Limited permissions for assigned work
        $employee = Role::create(['name' => 'employee']);
        $employee->givePermissionTo([
            'view projects',
            'view tasks',
            'update tasks',
            'view workspaces',
        ]);
    }
}
