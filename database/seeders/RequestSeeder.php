<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Request;
use App\Models\User;
use Illuminate\Database\Seeder;

class RequestSeeder extends Seeder
{
    public function run(): void
    {
        $requester = User::where('email', 'requester@deciflow.com')->first();
        $department = Department::where('name', 'HR')->first();

        $requests = [
            [
                'user_id' => $requester->id,
                'department_id' => $department->id,
                'title' => 'New Laptop Purchase',
                'description' => 'Need a new MacBook Pro for development work',
                'category' => 'EQUIPMENT',
                'amount' => 250000,
                'urgency' => 'NORMAL',
                'status' => 'DRAFT',
            ],
            [
                'user_id' => $requester->id,
                'department_id' => $department->id,
                'title' => 'Adobe Creative Cloud License',
                'description' => 'Annual license for design team',
                'category' => 'SOFTWARE',
                'amount' => 80000,
                'vendor_name' => 'Adobe Inc.',
                'urgency' => 'NORMAL',
                'status' => 'DRAFT',
            ],
            [
                'user_id' => $requester->id,
                'department_id' => $department->id,
                'title' => 'Office Cleaning Service',
                'description' => 'Monthly cleaning service contract',
                'category' => 'SERVICE',
                'amount' => 50000,
                'urgency' => 'NORMAL',
                'status' => 'SUBMITTED',
            ],
        ];

        foreach ($requests as $request) {
            Request::create($request);
        }
    }
}
