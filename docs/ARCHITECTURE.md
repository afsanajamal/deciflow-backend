# DeciFlow Architecture Documentation

## System Overview

DeciFlow is a procurement/budget approval system built with Laravel 11, providing a robust REST API for managing purchase requests with multi-level approval workflows.

## Architecture Diagram

```
┌─────────────┐
│   Frontend  │ (Vue/Nuxt.js - http://localhost:3000)
│  (Not here) │
└──────┬──────┘
       │ HTTP/REST
       │
┌──────▼──────────────────────────────────────────┐
│           Laravel Backend API                    │
│         (http://localhost:8000/api/v1)          │
│                                                  │
│  ┌────────────────────────────────────────┐    │
│  │  Controllers (API V1)                  │    │
│  │  • AuthController                      │    │
│  │  • RequestController                   │    │
│  │  • ApprovalController                  │    │
│  │  • AttachmentController                │    │
│  │  • RuleController                      │    │
│  │  • AuditController                     │    │
│  └────────┬───────────────────────────────┘    │
│           │                                      │
│  ┌────────▼───────────────────────────────┐    │
│  │  Middleware                             │    │
│  │  • Sanctum Auth                         │    │
│  │  • CORS                                 │    │
│  │  • Role-based Access Control            │    │
│  └────────┬───────────────────────────────┘    │
│           │                                      │
│  ┌────────▼───────────────────────────────┐    │
│  │  Business Logic Layer                   │    │
│  │  • ApprovalService                      │    │
│  │  • StateMachineService                  │    │
│  │  • RuleEngineService                    │    │
│  └────────┬───────────────────────────────┘    │
│           │                                      │
│  ┌────────▼───────────────────────────────┐    │
│  │  Models (Eloquent ORM)                  │    │
│  │  • User, Role, Department               │    │
│  │  • Request, ApprovalStep                │    │
│  │  • Rule, AuditLog                       │    │
│  └────────┬───────────────────────────────┘    │
│           │                                      │
└───────────┼──────────────────────────────────────┘
            │
┌───────────▼──────────────────────────────────────┐
│         MySQL Database (Docker)                   │
│              Port: 3307                           │
└───────────────────────────────────────────────────┘
```

## Technology Stack

### Backend Framework
- **Laravel 11** - PHP web framework
- **PHP 8.5.1** - Programming language
- **Composer** - Dependency management

### Database
- **MySQL 8.0** - Relational database (Dockerized)
- **Eloquent ORM** - Database abstraction layer

### Authentication
- **Laravel Sanctum** - API token authentication
- **Bearer tokens** - Stateless authentication

### API Documentation
- **L5-Swagger** - OpenAPI/Swagger documentation
- **Swagger UI** - Interactive API explorer

### Testing
- **PHPUnit** - Unit and feature testing
- **Laravel Testing** - Built-in testing utilities

### Development Tools
- **Docker & Docker Compose** - Containerization
- **MySQL Workbench** - Database visualization
- **VS Code** - Code editor with Intelephense

## Layer Architecture

### 1. Presentation Layer (Controllers)

**Location:** `app/Http/Controllers/Api/V1/`

Handles HTTP requests and responses:
- Input validation
- Response formatting
- HTTP status codes

**Key Controllers:**
- `AuthController` - Login/logout, user info
- `RequestController` - CRUD for requests
- `ApprovalController` - Approval workflow actions
- `AttachmentController` - File upload/download
- `RuleController` - Approval rules management
- `AuditController` - Audit log retrieval

### 2. Business Logic Layer (Services)

**Location:** `app/Services/`

Core business logic and workflows:

#### ApprovalService
- Manages approval workflow
- Creates approval steps
- Processes approve/reject/return actions
- Sends notifications

#### StateMachineService
- Manages request state transitions
- Validates state changes
- Creates audit logs
- Enforces business rules

#### RuleEngineService
- Determines approval steps based on:
  - Request amount
  - Category
  - Custom rules
- Validates business rules

### 3. Data Layer (Models)

**Location:** `app/Models/`

Eloquent ORM models for database access:
- `User` - System users
- `Role` - User roles (RBAC)
- `Department` - Organization departments
- `Request` - Purchase requests
- `ApprovalStep` - Individual approval steps
- `RequestAttachment` - File attachments
- `Rule` - Approval rules configuration
- `AuditLog` - Immutable audit trail

### 4. Authorization Layer (Policies)

**Location:** `app/Policies/`

- `RequestPolicy` - Request authorization logic
  - Who can view/edit/submit/cancel requests
  - Owner-based permissions
  - Role-based access control

### 5. Middleware Layer

**Location:** `app/Http/Middleware/`

- `EnsureRole` - Role verification
- Sanctum authentication (built-in)
- CORS configuration (built-in)

## Data Flow

### Example: Creating and Approving a Request

```
1. User logs in
   POST /api/v1/auth/login
   ↓
   Returns bearer token

2. User creates draft request
   POST /api/v1/requests
   ↓
   RequestController → Request model → Database
   ↓
   Returns created request (status: DRAFT)

3. User submits request
   POST /api/v1/requests/{id}/submit
   ↓
   RequestController → ApprovalService
   ↓
   RuleEngineService (determines approval steps based on amount)
   ↓
   Creates ApprovalStep records
   ↓
   StateMachineService (transitions to IN_REVIEW)
   ↓
   Creates AuditLog
   ↓
   Sends notification to approvers

4. Approver reviews inbox
   GET /api/v1/approvals/inbox
   ↓
   Returns pending requests for their role

5. Approver approves
   POST /api/v1/requests/{id}/approve
   ↓
   ApprovalController → ApprovalService
   ↓
   Updates ApprovalStep (status: approved)
   ↓
   Checks if more steps needed
   ↓
   If last step → StateMachineService (transition to APPROVED)
   ↓
   Creates AuditLog
   ↓
   Sends notification to requester
```

## Database Schema

See [DATABASE_SCHEMA.md](DATABASE_SCHEMA.md) for detailed schema documentation.

## Security Model

### Authentication
- **Token-based**: Sanctum personal access tokens
- **Stateless**: No server-side sessions
- **Token storage**: Client-side (localStorage/cookie)

### Authorization (RBAC)
- **Roles**: super_admin, dept_admin, approver, requester
- **Policy-based**: Laravel policies for fine-grained control
- **Middleware**: Role verification on protected routes

### Data Protection
- **Password hashing**: bcrypt (12 rounds)
- **SQL injection**: Eloquent ORM protection
- **XSS prevention**: Laravel's automatic escaping
- **CSRF protection**: Sanctum CSRF cookies (for SPA)
- **Rate limiting**: 60 requests/minute

### File Upload Security
- **Max size**: 10MB
- **Validation**: File type verification
- **Storage**: Isolated from public directory
- **Access control**: Policy-based download authorization

## Scalability Considerations

### Current Architecture
- Single server deployment
- MySQL database
- Synchronous processing

### Potential Optimizations
1. **Database**
   - Read replicas for scaling reads
   - Database connection pooling
   - Query optimization with indexes

2. **Caching**
   - Redis for session/cache storage
   - API response caching
   - Database query caching

3. **Asynchronous Processing**
   - Queue workers for emails
   - Background jobs for heavy operations
   - Event-driven architecture

4. **Load Balancing**
   - Multiple app servers
   - Session affinity or shared sessions
   - Database connection pooling

5. **File Storage**
   - Cloud storage (S3, etc.)
   - CDN for static assets
   - Distributed file system

## Monitoring & Logging

### Current Logging
- **Application logs**: `storage/logs/laravel.log`
- **Audit logs**: Database (immutable)
- **Email logs**: Log driver (development)

### Production Recommendations
- **Application Performance Monitoring (APM)**
  - New Relic, DataDog, or Laravel Telescope
- **Error tracking**
  - Sentry, Bugsnag, or Rollbar
- **Log aggregation**
  - ELK Stack, Splunk, or CloudWatch
- **Uptime monitoring**
  - Pingdom, UptimeRobot, or StatusCake

## API Versioning

**Current version:** v1 (`/api/v1/*`)

**Strategy:**
- URL-based versioning
- Backward compatibility maintained within version
- Major changes require new version (v2, v3, etc.)

## Deployment Architecture

See [DEPLOYMENT.md](DEPLOYMENT.md) for production deployment guide.

## Related Documentation

- [README.md](../README.md) - Quick start guide
- [API_DOCUMENTATION.md](API_DOCUMENTATION.md) - Complete API reference
- [DATABASE_SCHEMA.md](DATABASE_SCHEMA.md) - Database structure
- [DEPLOYMENT.md](DEPLOYMENT.md) - Production deployment
