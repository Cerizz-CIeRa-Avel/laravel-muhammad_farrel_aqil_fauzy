<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = ['Super Admin', 'Admin', 'Staff', 'Customer'];

        foreach ($roles as $role) {
            Role::create(['name' => $role, 'guard_name' => 'web']);
        }
    }
}