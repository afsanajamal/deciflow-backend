<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'super_admin'],
            ['name' => 'dept_admin'],
            ['name' => 'approver'],
            ['name' => 'requester'],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate($role);
        }
    }
}
