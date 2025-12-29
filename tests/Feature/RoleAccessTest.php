<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Department;
use App\Models\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    protected $requester;
    protected $approver;
    protected $deptAdmin;
    protected $superAdmin;
    protected $department;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        $requesterRole = Role::create(['name' => 'requester']);
        $approverRole = Role::create(['name' => 'approver']);
        $deptAdminRole = Role::create(['name' => 'dept_admin']);
        $superAdminRole = Role::create(['name' => 'super_admin']);
        
        // Create department
        $this->department = Department::create(['name' => 'IT']);
        
        // Create users
        $this->requester = User::create([
            'name' => 'Requester',
            'email' => 'requester@example.com',
            'password' => bcrypt('password'),
            'role_id' => $requesterRole->id,
            'department_id' => $this->department->id,
        ]);

        $this->approver = User::create([
            'name' => 'Approver',
            'email' => 'approver@example.com',
            'password' => bcrypt('password'),
            'role_id' => $approverRole->id,
            'department_id' => $this->department->id,
        ]);

        $this->deptAdmin = User::create([
            'name' => 'Dept Admin',
            'email' => 'deptadmin@example.com',
            'password' => bcrypt('password'),
            'role_id' => $deptAdminRole->id,
            'department_id' => $this->department->id,
        ]);

        $this->superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'password' => bcrypt('password'),
            'role_id' => $superAdminRole->id,
            'department_id' => $this->department->id,
        ]);
    }

    public function test_requester_can_view_own_requests()
    {
        $request = Request::create([
            'user_id' => $this->requester->id,
            'department_id' => $this->department->id,
            'title' => 'My Request',
            'description' => 'Test',
            'category' => 'EQUIPMENT',
            'amount' => 50000,
            'urgency' => 'NORMAL',
            'status' => 'DRAFT',
        ]);

        $token = $this->requester->createToken('test')->plainTextToken;
        
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson("/api/v1/requests/{$request->id}");

        $response->assertStatus(200);
    }

    public function test_requester_cannot_view_others_requests()
    {
        $otherRequester = User::create([
            'name' => 'Other Requester',
            'email' => 'other@example.com',
            'password' => bcrypt('password'),
            'role_id' => $this->requester->role_id,
            'department_id' => $this->department->id,
        ]);

        $request = Request::create([
            'user_id' => $otherRequester->id,
            'department_id' => $this->department->id,
            'title' => 'Other Request',
            'description' => 'Test',
            'category' => 'EQUIPMENT',
            'amount' => 50000,
            'urgency' => 'NORMAL',
            'status' => 'DRAFT',
        ]);

        $token = $this->requester->createToken('test')->plainTextToken;
        
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson("/api/v1/requests/{$request->id}");

        $response->assertStatus(403);
    }

    public function test_requester_can_only_update_own_draft_requests()
    {
        $request = Request::create([
            'user_id' => $this->requester->id,
            'department_id' => $this->department->id,
            'title' => 'Original',
            'description' => 'Test',
            'category' => 'EQUIPMENT',
            'amount' => 50000,
            'urgency' => 'NORMAL',
            'status' => 'DRAFT',
        ]);

        $token = $this->requester->createToken('test')->plainTextToken;
        
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/v1/requests/{$request->id}", [
                'title' => 'Updated',
            ]);

        $response->assertStatus(200);
    }

    public function test_requester_cannot_update_submitted_request()
    {
        $request = Request::create([
            'user_id' => $this->requester->id,
            'department_id' => $this->department->id,
            'title' => 'Original',
            'description' => 'Test',
            'category' => 'EQUIPMENT',
            'amount' => 50000,
            'urgency' => 'NORMAL',
            'status' => 'SUBMITTED',
        ]);

        $token = $this->requester->createToken('test')->plainTextToken;
        
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/v1/requests/{$request->id}", [
                'title' => 'Updated',
            ]);

        $response->assertStatus(403);
    }

    public function test_dept_admin_can_view_all_requests()
    {
        $request = Request::create([
            'user_id' => $this->requester->id,
            'department_id' => $this->department->id,
            'title' => 'Test Request',
            'description' => 'Test',
            'category' => 'EQUIPMENT',
            'amount' => 50000,
            'urgency' => 'NORMAL',
            'status' => 'DRAFT',
        ]);

        $token = $this->deptAdmin->createToken('test')->plainTextToken;
        
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson("/api/v1/requests/{$request->id}");

        $response->assertStatus(200);
    }

    public function test_super_admin_can_view_all_requests()
    {
        $request = Request::create([
            'user_id' => $this->requester->id,
            'department_id' => $this->department->id,
            'title' => 'Test Request',
            'description' => 'Test',
            'category' => 'EQUIPMENT',
            'amount' => 50000,
            'urgency' => 'NORMAL',
            'status' => 'DRAFT',
        ]);

        $token = $this->superAdmin->createToken('test')->plainTextToken;
        
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson("/api/v1/requests/{$request->id}");

        $response->assertStatus(200);
    }

    public function test_only_dept_admin_and_super_admin_can_access_rules()
    {
        // Requester cannot access
        $response = $this->actingAs($this->requester, 'sanctum')
            ->getJson('/api/v1/rules');
        $response->assertStatus(403);

        // Approver cannot access
        $response = $this->actingAs($this->approver, 'sanctum')
            ->getJson('/api/v1/rules');
        $response->assertStatus(403);

        // Dept Admin can access
        $response = $this->actingAs($this->deptAdmin, 'sanctum')
            ->getJson('/api/v1/rules');
        $response->assertStatus(200);

        // Super Admin can access
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/api/v1/rules');
        $response->assertStatus(200);
    }

    public function test_only_super_admin_can_archive_requests()
    {
        $request = Request::create([
            'user_id' => $this->requester->id,
            'department_id' => $this->department->id,
            'title' => 'Test Request',
            'description' => 'Test',
            'category' => 'EQUIPMENT',
            'amount' => 50000,
            'urgency' => 'NORMAL',
            'status' => 'APPROVED',
        ]);

        // Requester cannot archive
        $response = $this->actingAs($this->requester, 'sanctum')
            ->postJson("/api/v1/requests/{$request->id}/archive");
        $response->assertStatus(403);

        // Super Admin can archive
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson("/api/v1/requests/{$request->id}/archive");
        $response->assertStatus(200);
    }

    public function test_requester_can_only_cancel_own_requests()
    {
        $ownRequest = Request::create([
            'user_id' => $this->requester->id,
            'department_id' => $this->department->id,
            'title' => 'My Request',
            'description' => 'Test',
            'category' => 'EQUIPMENT',
            'amount' => 50000,
            'urgency' => 'NORMAL',
            'status' => 'SUBMITTED',
        ]);

        $token = $this->requester->createToken('test')->plainTextToken;
        
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/v1/requests/{$ownRequest->id}/cancel");

        $response->assertStatus(200);
        
        // Cannot cancel others' requests
        $otherRequest = Request::create([
            'user_id' => $this->approver->id,
            'department_id' => $this->department->id,
            'title' => 'Other Request',
            'description' => 'Test',
            'category' => 'EQUIPMENT',
            'amount' => 50000,
            'urgency' => 'NORMAL',
            'status' => 'SUBMITTED',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/v1/requests/{$otherRequest->id}/cancel");

        $response->assertStatus(403);
    }
}
