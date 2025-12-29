<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Department;
use App\Models\Request;
use App\Models\ApprovalStep;
use App\Services\RuleEngineService;
use App\Services\ApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalRulesTest extends TestCase
{
    use RefreshDatabase;

    protected $requester;
    protected $approver;
    protected $deptAdmin;
    protected $superAdmin;
    protected $ruleEngine;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        $requesterRole = Role::create(['name' => 'requester']);
        $approverRole = Role::create(['name' => 'approver']);
        $deptAdminRole = Role::create(['name' => 'dept_admin']);
        $superAdminRole = Role::create(['name' => 'super_admin']);
        
        // Create department
        $department = Department::create(['name' => 'IT']);
        
        // Create users
        $this->requester = User::create([
            'name' => 'Requester',
            'email' => 'requester@example.com',
            'password' => bcrypt('password'),
            'role_id' => $requesterRole->id,
            'department_id' => $department->id,
        ]);

        $this->approver = User::create([
            'name' => 'Approver',
            'email' => 'approver@example.com',
            'password' => bcrypt('password'),
            'role_id' => $approverRole->id,
            'department_id' => $department->id,
        ]);

        $this->deptAdmin = User::create([
            'name' => 'Dept Admin',
            'email' => 'deptadmin@example.com',
            'password' => bcrypt('password'),
            'role_id' => $deptAdminRole->id,
            'department_id' => $department->id,
        ]);

        $this->superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'password' => bcrypt('password'),
            'role_id' => $superAdminRole->id,
            'department_id' => $department->id,
        ]);

        $this->ruleEngine = new RuleEngineService();
    }

    public function test_small_amount_requires_one_approval_step()
    {
        $request = new Request([
            'amount' => 50000, // <= 100,000
        ]);

        $steps = $this->ruleEngine->determineApprovalSteps($request);

        $this->assertCount(1, $steps);
        $this->assertEquals(['approver'], $steps);
    }

    public function test_medium_amount_requires_two_approval_steps()
    {
        $request = new Request([
            'amount' => 300000, // 100,001 - 500,000
        ]);

        $steps = $this->ruleEngine->determineApprovalSteps($request);

        $this->assertCount(2, $steps);
        $this->assertEquals(['approver', 'dept_admin'], $steps);
    }

    public function test_large_amount_requires_three_approval_steps()
    {
        $request = new Request([
            'amount' => 600000, // > 500,000
        ]);

        $steps = $this->ruleEngine->determineApprovalSteps($request);

        $this->assertCount(3, $steps);
        $this->assertEquals(['approver', 'dept_admin', 'super_admin'], $steps);
    }

    public function test_approval_steps_are_created_on_submit()
    {
        $request = Request::create([
            'user_id' => $this->requester->id,
            'department_id' => $this->requester->department_id,
            'title' => 'Medium Request',
            'description' => 'Test',
            'category' => 'EQUIPMENT',
            'amount' => 300000,
            'urgency' => 'NORMAL',
            'status' => 'DRAFT',
        ]);

        $approvalService = app(ApprovalService::class);
        $approvalService->submitRequest($request, $this->requester);

        $request->refresh();
        
        $this->assertEquals('IN_REVIEW', $request->status);
        $this->assertEquals(2, $request->approvalSteps()->count());
        
        $this->assertDatabaseHas('approval_steps', [
            'request_id' => $request->id,
            'step_number' => 1,
            'approver_role' => 'approver',
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('approval_steps', [
            'request_id' => $request->id,
            'step_number' => 2,
            'approver_role' => 'dept_admin',
            'status' => 'pending',
        ]);
    }

    public function test_approver_can_approve_their_step()
    {
        $request = Request::create([
            'user_id' => $this->requester->id,
            'department_id' => $this->requester->department_id,
            'title' => 'Test Request',
            'description' => 'Test',
            'category' => 'EQUIPMENT',
            'amount' => 50000,
            'urgency' => 'NORMAL',
            'status' => 'IN_REVIEW',
        ]);

        ApprovalStep::create([
            'request_id' => $request->id,
            'step_number' => 1,
            'approver_role' => 'approver',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->approver, 'sanctum')
            ->postJson("/api/v1/requests/{$request->id}/approve", [
                'comment' => 'Looks good',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('approval_steps', [
            'request_id' => $request->id,
            'approver_role' => 'approver',
            'status' => 'approved',
            'comment' => 'Looks good',
        ]);

        $request->refresh();
        $this->assertEquals('APPROVED', $request->status);
    }

    public function test_multi_step_approval_requires_all_steps()
    {
        $request = Request::create([
            'user_id' => $this->requester->id,
            'department_id' => $this->requester->department_id,
            'title' => 'Large Request',
            'description' => 'Test',
            'category' => 'EQUIPMENT',
            'amount' => 600000,
            'urgency' => 'NORMAL',
            'status' => 'IN_REVIEW',
        ]);

        ApprovalStep::create([
            'request_id' => $request->id,
            'step_number' => 1,
            'approver_role' => 'approver',
            'status' => 'pending',
        ]);

        ApprovalStep::create([
            'request_id' => $request->id,
            'step_number' => 2,
            'approver_role' => 'dept_admin',
            'status' => 'pending',
        ]);

        ApprovalStep::create([
            'request_id' => $request->id,
            'step_number' => 3,
            'approver_role' => 'super_admin',
            'status' => 'pending',
        ]);

        // First approval
        $response = $this->actingAs($this->approver, 'sanctum')
            ->postJson("/api/v1/requests/{$request->id}/approve", [
                'comment' => 'Approved step 1',
            ]);

        $response->assertStatus(200);
        $request->refresh();
        $this->assertEquals('IN_REVIEW', $request->status); // Still in review

        // Second approval
        $response = $this->actingAs($this->deptAdmin, 'sanctum')
            ->postJson("/api/v1/requests/{$request->id}/approve", [
                'comment' => 'Approved step 2',
            ]);

        $response->assertStatus(200);
        $request->refresh();
        $this->assertEquals('IN_REVIEW', $request->status); // Still in review

        // Third and final approval
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson("/api/v1/requests/{$request->id}/approve", [
                'comment' => 'Approved step 3',
            ]);

        $response->assertStatus(200);
        $request->refresh();
        $this->assertEquals('APPROVED', $request->status); // Now approved
    }
}
