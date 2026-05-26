<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Clear cached permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ── Create Permissions ────────────────────────────────────────────────
        $permissions = [
            'chat',           // Can use the chat
            'view_profiles',  // Can view other users' profiles
            'create_user',    // Can create new users
            'delete_user',    // Can delete users
            'manage_roles',   // Can promote/demote users
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'api']);
        }

        // ── Create Roles & assign permissions ─────────────────────────────────

        // User role — chat only
        $userRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'api']);
        $userRole->syncPermissions(['chat']);

        // Admin role — everything
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        $adminRole->syncPermissions($permissions);

        // ── Create default Admin user ─────────────────────────────────────────
        $admin = User::firstOrCreate(
            ['email' => 'admin@relayhub.com'],
            [
                'name'     => 'Admin',
                'password' => Hash::make('password'),
            ]
        );
        $admin->assignRole('admin');

        $this->command->info('✅ Roles, permissions and admin user created.');
        $this->command->info('   Admin: admin@relayhub.com / password');
    }
}