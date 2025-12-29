<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['name' => 'IT'],
            ['name' => 'Finance'],
            ['name' => 'HR'],
            ['name' => 'Operations'],
        ];

        foreach ($departments as $department) {
            Department::firstOrCreate($department);
        }
    }
}
