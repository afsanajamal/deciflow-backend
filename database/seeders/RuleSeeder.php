<?php

namespace Database\Seeders;

use App\Models\Rule;
use Illuminate\Database\Seeder;

class RuleSeeder extends Seeder
{
    public function run(): void
    {
        $rules = [
            [
                'name' => 'Small Amount Approval',
                'min_amount' => 0,
                'max_amount' => 100000,
                'approval_steps_json' => json_encode(['approver']),
                'category' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Medium Amount Approval',
                'min_amount' => 100001,
                'max_amount' => 500000,
                'approval_steps_json' => json_encode(['approver', 'dept_admin']),
                'category' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Large Amount Approval',
                'min_amount' => 500001,
                'max_amount' => null,
                'approval_steps_json' => json_encode(['approver', 'dept_admin', 'super_admin']),
                'category' => null,
                'is_active' => true,
            ],
        ];

        foreach ($rules as $rule) {
            Rule::firstOrCreate(
                ['name' => $rule['name']],
                $rule
            );
        }
    }
}
