<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Request;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequestWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected $requester;

    protected $approver;

    protected $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        $requesterRole = Role::create(['name' => 'requester']);
        $approverRole = Role::create(['name' => 'approver']);

        // Create department
        $department = Department::create(['name' => 'IT']);

        // Create users
        $this->requester = User::create([
            'name' => 'Requester User',
            'email' => 'requester@example.com',
            'password' => bcrypt('password'),
            'role_id' => $requesterRole->id,
            'department_id' => $department->id,
        ]);

        $this->approver = User::create([
            'name' => 'Approver User',
            'email' => 'approver@example.com',
            'password' => bcrypt('password'),
            'role_id' => $approverRole->id,
            'department_id' => $department->id,
        ]);

        $this->token = $this->requester->createToken('test-token')->plainTextToken;
    }

    public function test_user_can_create_draft_request()
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/v1/requests', [
                'title' => 'Test Request',
                'description' => 'Test Description',
                'category' => 'EQUIPMENT',
                'amount' => 50000,
                'urgency' => 'NORMAL',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'title' => 'Test Request',
                'status' => 'DRAFT',
            ]);

        $this->assertDatabaseHas('requests', [
            'title' => 'Test Request',
            'status' => 'DRAFT',
        ]);
    }

    public function test_user_can_submit_draft_request()
    {
        $request = Request::create([
            'user_id' => $this->requester->id,
            'department_id' => $this->requester->department_id,
            'title' => 'Test Request',
            'description' => 'Test Description',
            'category' => 'EQUIPMENT',
            'amount' => 50000,
            'urgency' => 'NORMAL',
            'status' => 'DRAFT',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson("/api/v1/requests/{$request->id}/submit");

        $response->assertStatus(200);

        $request->refresh();
        $this->assertEquals('IN_REVIEW', $request->status);

        // Verify approval steps were created
        $this->assertDatabaseHas('approval_steps', [
            'request_id' => $request->id,
            'approver_role' => 'approver',
            'status' => 'pending',
        ]);
    }

    public function test_request_validation_fails_for_software_without_vendor()
    {
        $request = Request::create([
            'user_id' => $this->requester->id,
            'department_id' => $this->requester->department_id,
            'title' => 'Software Request',
            'description' => 'Test Description',
            'category' => 'SOFTWARE',
            'amount' => 50000,
            'urgency' => 'NORMAL',
            'status' => 'DRAFT',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson("/api/v1/requests/{$request->id}/submit");

        $response->assertStatus(422); // Should fail validation
    }

    public function test_user_can_cancel_request()
    {
        $request = Request::create([
            'user_id' => $this->requester->id,
            'department_id' => $this->requester->department_id,
            'title' => 'Test Request',
            'description' => 'Test Description',
            'category' => 'EQUIPMENT',
            'amount' => 50000,
            'urgency' => 'NORMAL',
            'status' => 'SUBMITTED',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson("/api/v1/requests/{$request->id}/cancel");

        $response->assertStatus(200);

        $request->refresh();
        $this->assertEquals('CANCELLED', $request->status);
    }

    public function test_user_can_only_update_own_draft_request()
    {
        $request = Request::create([
            'user_id' => $this->requester->id,
            'department_id' => $this->requester->department_id,
            'title' => 'Original Title',
            'description' => 'Test Description',
            'category' => 'EQUIPMENT',
            'amount' => 50000,
            'urgency' => 'NORMAL',
            'status' => 'DRAFT',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->putJson("/api/v1/requests/{$request->id}", [
                'title' => 'Updated Title',
            ]);

        $response->assertStatus(200);

        $request->refresh();
        $this->assertEquals('Updated Title', $request->title);
    }

    public function test_user_cannot_update_submitted_request()
    {
        $request = Request::create([
            'user_id' => $this->requester->id,
            'department_id' => $this->requester->department_id,
            'title' => 'Original Title',
            'description' => 'Test Description',
            'category' => 'EQUIPMENT',
            'amount' => 50000,
            'urgency' => 'NORMAL',
            'status' => 'SUBMITTED',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->putJson("/api/v1/requests/{$request->id}", [
                'title' => 'Updated Title',
            ]);

        $response->assertStatus(403);
    }
}
