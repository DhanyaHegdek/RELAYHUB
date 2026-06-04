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
        // clears old cached permissions so Laravel reloads fresh ones
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        //  Create Permissions
        $permissions = [
            'chat',          
            'view_profiles',  
            'create_user',    
            'delete_user',    
            'manage_roles',  
        ];

        //Insert Permissions into DB
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'api']);
        }

        //  Create Roles & assign permissions

        // User role — chat only
        //'guard_name' => 'api' tells Spatie: these permissions belong to API authentication.
        $userRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'api']);
        $userRole->syncPermissions(['chat']);

        // Admin role — everything
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        //syncPermissions() replaces existing permissions with given ones.
        $adminRole->syncPermissions($permissions);

        //  Create default Admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@relayhub.com'],
            [
                'name'     => 'Admin',
                'password' => Hash::make('password'),
            ]
        );
        $admin->assignRole('admin');

        $this->command->info(' Roles, permissions and admin user created.');
        $this->command->info('   Admin: admin@relayhub.com / password');
    }
}