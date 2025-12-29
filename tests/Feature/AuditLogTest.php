<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Department;
use App\Models\Request;
use App\Models\AuditLog;
use App\Services\StateMachineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $stateMachine;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create role and department
        $role = Role::create(['name' => 'requester']);
        $department = Department::create(['name' => 'IT']);
        
        // Create user
        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'department_id' => $department->id,
        ]);

        $this->stateMachine = app(StateMachineService::class);
    }

    public function test_audit_log_created_on_status_transition()
    {
        $request = Request::create([
            'user_id' => $this->user->id,
            'department_id' => $this->user->department_id,
            'title' => 'Test Request',
            'description' => 'Test',
            'category' => 'EQUIPMENT',
            'amount' => 50000,
            'urgency' => 'NORMAL',
            'status' => 'DRAFT',
        ]);

        $initialLogCount = AuditLog::count();

        $this->stateMachine->transition($request, 'SUBMITTED', $this->user);

        $this->assertEquals($initialLogCount + 1, AuditLog::count());
        
        $this->assertDatabaseHas('audit_logs', [
            'request_id' => $request->id,
            'user_id' => $this->user->id,
            'from_status' => 'DRAFT',
            'to_status' => 'SUBMITTED',
        ]);
    }

    public function test_audit_log_contains_metadata()
    {
        $request = Request::create([
            'user_id' => $this->user->id,
            'department_id' => $this->user->department_id,
            'title' => 'Test Request',
            'description' => 'Test',
            'category' => 'EQUIPMENT',
            'amount' => 50000,
            'urgency' => 'NORMAL',
            'status' => 'DRAFT',
        ]);

        $this->stateMachine->transition($request, 'SUBMITTED', $this->user, [
            'comment' => 'Submitting for approval',
        ]);

        $log = AuditLog::latest()->first();
        
        $this->assertNotNull($log->meta);
        $this->assertEquals('Submitting for approval', $log->meta['comment']);
    }

    public function test_audit_logs_are_immutable()
    {
        $request = Request::create([
            'user_id' => $this->user->id,
            'department_id' => $this->user->department_id,
            'title' => 'Test Request',
            'description' => 'Test',
            'category' => 'EQUIPMENT',
            'amount' => 50000,
            'urgency' => 'NORMAL',
            'status' => 'DRAFT',
        ]);

        $this->stateMachine->transition($request, 'SUBMITTED', $this->user);

        $log = AuditLog::latest()->first();
        $createdAt = $log->created_at;

        // Simulate time passing
        sleep(1);

        // Try to update (should not change created_at or have updated_at)
        $log->action = 'MODIFIED';
        $log->save();

        $log->refresh();
        
        // created_at should not change
        $this->assertEquals($createdAt->timestamp, $log->created_at->timestamp);
        
        // updated_at should not exist on the model
        $this->assertNull($log->updated_at);
    }

    public function test_request_audit_timeline_returns_all_logs_in_order()
    {
        $request = Request::create([
            'user_id' => $this->user->id,
            'department_id' => $this->user->department_id,
            'title' => 'Test Request',
            'description' => 'Test',
            'category' => 'EQUIPMENT',
            'amount' => 50000,
            'urgency' => 'NORMAL',
            'status' => 'DRAFT',
        ]);

        // Create multiple transitions
        $this->stateMachine->transition($request, 'SUBMITTED', $this->user);
        $this->stateMachine->transition($request, 'IN_REVIEW', $this->user);
        $this->stateMachine->transition($request, 'APPROVED', $this->user);

        $token = $this->user->createToken('test')->plainTextToken;
        
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson("/api/v1/requests/{$request->id}/audit");

        $response->assertStatus(200);
        
        $logs = $response->json();
        
        $this->assertCount(3, $logs);
        
        // Verify order (oldest first)
        $this->assertEquals('DRAFT', $logs[0]['from_status']);
        $this->assertEquals('SUBMITTED', $logs[0]['to_status']);
        
        $this->assertEquals('SUBMITTED', $logs[1]['from_status']);
        $this->assertEquals('IN_REVIEW', $logs[1]['to_status']);
        
        $this->assertEquals('IN_REVIEW', $logs[2]['from_status']);
        $this->assertEquals('APPROVED', $logs[2]['to_status']);
    }

    public function test_super_admin_can_view_all_audit_logs()
    {
        $superAdminRole = Role::create(['name' => 'super_admin']);
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'password' => bcrypt('password'),
            'role_id' => $superAdminRole->id,
            'department_id' => $this->user->department_id,
        ]);

        $request1 = Request::create([
            'user_id' => $this->user->id,
            'department_id' => $this->user->department_id,
            'title' => 'Request 1',
            'description' => 'Test',
            'category' => 'EQUIPMENT',
            'amount' => 50000,
            'urgency' => 'NORMAL',
            'status' => 'DRAFT',
        ]);

        $request2 = Request::create([
            'user_id' => $this->user->id,
            'department_id' => $this->user->department_id,
            'title' => 'Request 2',
            'description' => 'Test',
            'category' => 'EQUIPMENT',
            'amount' => 60000,
            'urgency' => 'NORMAL',
            'status' => 'DRAFT',
        ]);

        $this->stateMachine->transition($request1, 'SUBMITTED', $this->user);
        $this->stateMachine->transition($request2, 'SUBMITTED', $this->user);

        $token = $superAdmin->createToken('test')->plainTextToken;
        
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/audit');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertGreaterThanOrEqual(2, count($data));
    }

    public function test_non_super_admin_cannot_view_all_audit_logs()
    {
        $token = $this->user->createToken('test')->plainTextToken;
        
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/audit');

        $response->assertStatus(403);
    }
}
