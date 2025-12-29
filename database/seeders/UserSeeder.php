<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password');

        // Get roles and departments
        $superAdminRole = Role::where('name', 'super_admin')->first();
        $deptAdminRole = Role::where('name', 'dept_admin')->first();
        $approverRole = Role::where('name', 'approver')->first();
        $requesterRole = Role::where('name', 'requester')->first();

        $itDept = Department::where('name', 'IT')->first();
        $financeDept = Department::where('name', 'Finance')->first();
        $hrDept = Department::where('name', 'HR')->first();

        $users = [
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@deciflow.com',
                'password' => $password,
                'department_id' => $itDept->id,
                'role_id' => $superAdminRole->id,
            ],
            [
                'name' => 'IT Dept Admin',
                'email' => 'deptadmin@deciflow.com',
                'password' => $password,
                'department_id' => $itDept->id,
                'role_id' => $deptAdminRole->id,
            ],
            [
                'name' => 'John Approver',
                'email' => 'approver@deciflow.com',
                'password' => $password,
                'department_id' => $financeDept->id,
                'role_id' => $approverRole->id,
            ],
            [
                'name' => 'Alice Requester',
                'email' => 'requester@deciflow.com',
                'password' => $password,
                'department_id' => $hrDept->id,
                'role_id' => $requesterRole->id,
            ],
        ];

        foreach ($users as $user) {
            User::firstOrCreate(
                ['email' => $user['email']],
                $user
            );
        }
    }
}
