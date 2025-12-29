# Database Schema Documentation

## Overview

DeciFlow uses MySQL 8.0 with the following database structure.

**Database Name:** `deciflow`
**Port:** 3307 (Docker mapped to 3306 internally)

## Entity Relationship Diagram

```
┌─────────────┐
│    roles    │
└──────┬──────┘
       │
       │ 1:N
       │
┌──────▼──────────────┐        ┌─────────────────┐
│       users         │────────│   departments   │
└──────┬──────────────┘   1:N  └─────────────────┘
       │
       │ 1:N
       │
┌──────▼──────────────┐
│     requests        │
└──────┬──────────────┘
       │
       ├──────────┬──────────┬──────────┐
       │ 1:N      │ 1:N      │ 1:N      │
       │          │          │          │
┌──────▼────┐ ┌──▼────────┐ ┌▼─────────┐
│ approval  │ │  request  │ │   audit  │
│  _steps   │ │ _attachm  │ │   _logs  │
└───────────┘ └───────────┘ └──────────┘

┌─────────────┐
│    rules    │  (Standalone - defines approval logic)
└─────────────┘
```

## Tables

### 1. **users**

Stores system users.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Primary key |
| name | VARCHAR(255) | NO | - | User's full name |
| email | VARCHAR(255) | NO | - | Unique email address |
| email_verified_at | TIMESTAMP | YES | NULL | Email verification timestamp |
| password | VARCHAR(255) | NO | - | Bcrypt hashed password |
| remember_token | VARCHAR(100) | YES | NULL | Remember me token |
| department_id | BIGINT UNSIGNED | YES | NULL | FK to departments |
| role_id | BIGINT UNSIGNED | YES | NULL | FK to roles |
| created_at | TIMESTAMP | YES | NULL | Record creation time |
| updated_at | TIMESTAMP | YES | NULL | Last update time |

**Indexes:**
- PRIMARY KEY (`id`)
- UNIQUE KEY (`email`)
- INDEX (`department_id`)
- INDEX (`role_id`)

**Foreign Keys:**
- `department_id` → `departments.id` ON DELETE SET NULL
- `role_id` → `roles.id` ON DELETE SET NULL

---

### 2. **roles**

Stores user roles for RBAC.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Primary key |
| name | VARCHAR(255) | NO | - | Role name (super_admin, dept_admin, approver, requester) |
| created_at | TIMESTAMP | YES | NULL | Record creation time |
| updated_at | TIMESTAMP | YES | NULL | Last update time |

**Indexes:**
- PRIMARY KEY (`id`)
- UNIQUE KEY (`name`)

**Seeded Data:**
- `super_admin` - Full system access
- `dept_admin` - Department administrator
- `approver` - Can approve requests
- `requester` - Can create requests

---

### 3. **departments**

Stores organizational departments.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Primary key |
| name | VARCHAR(255) | NO | - | Department name |
| created_at | TIMESTAMP | YES | NULL | Record creation time |
| updated_at | TIMESTAMP | YES | NULL | Last update time |

**Indexes:**
- PRIMARY KEY (`id`)

**Seeded Data:**
- IT
- Finance
- HR
- Operations

---

### 4. **requests**

Stores purchase/budget requests.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Primary key |
| user_id | BIGINT UNSIGNED | NO | - | FK to users (requester) |
| department_id | BIGINT UNSIGNED | NO | - | FK to departments |
| title | VARCHAR(255) | NO | - | Request title |
| description | TEXT | NO | - | Detailed description |
| category | ENUM | NO | - | EQUIPMENT, SOFTWARE, SERVICE, TRAVEL |
| amount | INTEGER | NO | - | Amount in JPY (Japanese Yen) |
| vendor_name | VARCHAR(255) | YES | NULL | Vendor name (required for SOFTWARE) |
| urgency | ENUM | NO | NORMAL | NORMAL, URGENT |
| urgency_reason | TEXT | YES | NULL | Reason if urgent |
| travel_start_date | DATE | YES | NULL | Travel start (required for TRAVEL) |
| travel_end_date | DATE | YES | NULL | Travel end (required for TRAVEL) |
| status | ENUM | NO | DRAFT | DRAFT, SUBMITTED, IN_REVIEW, RETURNED, APPROVED, REJECTED, CANCELLED, ARCHIVED |
| created_at | TIMESTAMP | YES | NULL | Record creation time |
| updated_at | TIMESTAMP | YES | NULL | Last update time |

**Indexes:**
- PRIMARY KEY (`id`)
- INDEX (`user_id`)
- INDEX (`department_id`)
- INDEX (`status`)
- INDEX (`created_at`)

**Foreign Keys:**
- `user_id` → `users.id` ON DELETE CASCADE
- `department_id` → `departments.id` ON DELETE CASCADE

**Status Flow:**
```
DRAFT → SUBMITTED → IN_REVIEW → APPROVED
              ↓           ↓
           CANCELLED   RETURNED → (back to DRAFT or IN_REVIEW)
                          ↓
                      REJECTED
                          ↓
                      ARCHIVED
```

---

### 5. **approval_steps**

Stores individual approval steps for each request.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Primary key |
| request_id | BIGINT UNSIGNED | NO | - | FK to requests |
| step_number | INTEGER | NO | - | Sequential step number (1, 2, 3...) |
| approver_role | VARCHAR(255) | NO | - | Role required to approve |
| approver_id | BIGINT UNSIGNED | YES | NULL | FK to users (actual approver) |
| status | ENUM | NO | pending | pending, approved, rejected, returned |
| comment | TEXT | YES | NULL | Approver's comment |
| approved_at | TIMESTAMP | YES | NULL | Approval timestamp |
| created_at | TIMESTAMP | YES | NULL | Record creation time |
| updated_at | TIMESTAMP | YES | NULL | Last update time |

**Indexes:**
- PRIMARY KEY (`id`)
- INDEX (`request_id`)
- INDEX (`approver_id`)
- INDEX (`approver_role`, `status`)

**Foreign Keys:**
- `request_id` → `requests.id` ON DELETE CASCADE
- `approver_id` → `users.id` ON DELETE SET NULL

**Example:**
For a ¥300,000 request (medium amount):
```
step_number | approver_role | status
------------|---------------|--------
1           | approver      | approved
2           | dept_admin    | pending
```

---

### 6. **request_attachments**

Stores file attachments for requests.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Primary key |
| request_id | BIGINT UNSIGNED | NO | - | FK to requests |
| file_name | VARCHAR(255) | NO | - | Original filename |
| file_path | VARCHAR(255) | NO | - | Storage path |
| mime_type | VARCHAR(255) | NO | - | File MIME type |
| created_at | TIMESTAMP | YES | NULL | Upload time |
| updated_at | TIMESTAMP | YES | NULL | Last update time |

**Indexes:**
- PRIMARY KEY (`id`)
- INDEX (`request_id`)

**Foreign Keys:**
- `request_id` → `requests.id` ON DELETE CASCADE

**Storage:**
- Location: `storage/app/attachments/`
- Max size: 10MB
- Naming: `{unique_id}_{timestamp}.{extension}`

---

### 7. **rules**

Stores approval rules configuration.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Primary key |
| name | VARCHAR(255) | NO | - | Rule name/description |
| min_amount | INTEGER | NO | 0 | Minimum amount (inclusive) |
| max_amount | INTEGER | YES | NULL | Maximum amount (exclusive, NULL = infinity) |
| approval_steps_json | JSON | NO | - | Array of role names |
| category | VARCHAR(255) | YES | NULL | Optional category filter |
| is_active | BOOLEAN | NO | TRUE | Rule is active/enabled |
| created_at | TIMESTAMP | YES | NULL | Record creation time |
| updated_at | TIMESTAMP | YES | NULL | Last update time |

**Indexes:**
- PRIMARY KEY (`id`)
- INDEX (`min_amount`, `max_amount`)
- INDEX (`is_active`)

**Seeded Rules:**

| Name | Min Amount | Max Amount | Approval Steps | Category |
|------|------------|------------|----------------|----------|
| Small Amount | 0 | 100,000 | ["approver"] | NULL |
| Medium Amount | 100,001 | 500,000 | ["approver", "dept_admin"] | NULL |
| Large Amount | 500,001 | NULL | ["approver", "dept_admin", "super_admin"] | NULL |

**approval_steps_json Example:**
```json
["approver", "dept_admin", "super_admin"]
```

---

### 8. **audit_logs**

Immutable audit trail for all state transitions.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Primary key |
| request_id | BIGINT UNSIGNED | NO | - | FK to requests |
| user_id | BIGINT UNSIGNED | NO | - | FK to users (actor) |
| action | VARCHAR(255) | NO | - | Action type (created, submitted, approved, etc.) |
| from_status | VARCHAR(255) | YES | NULL | Previous status |
| to_status | VARCHAR(255) | YES | NULL | New status |
| meta | JSON | YES | NULL | Additional metadata |
| created_at | TIMESTAMP | NO | - | Timestamp (immutable) |

**Note:** No `updated_at` - audit logs are immutable!

**Indexes:**
- PRIMARY KEY (`id`)
- INDEX (`request_id`)
- INDEX (`user_id`)
- INDEX (`created_at`)

**Foreign Keys:**
- `request_id` → `requests.id` ON DELETE CASCADE
- `user_id` → `users.id` ON DELETE CASCADE

**Example:**
```
action      | from_status | to_status  | user    | created_at
------------|-------------|------------|---------|-------------------
created     | NULL        | DRAFT      | Alice   | 2024-12-28 10:00
submitted   | DRAFT       | SUBMITTED  | Alice   | 2024-12-28 10:05
transition  | SUBMITTED   | IN_REVIEW  | System  | 2024-12-28 10:05
approved    | IN_REVIEW   | APPROVED   | John    | 2024-12-28 10:30
```

---

### 9. **personal_access_tokens**

Laravel Sanctum tokens for API authentication.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Primary key |
| tokenable_type | VARCHAR(255) | NO | - | Model type (User) |
| tokenable_id | BIGINT UNSIGNED | NO | - | User ID |
| name | VARCHAR(255) | NO | - | Token name |
| token | VARCHAR(64) | NO | - | Hashed token |
| abilities | TEXT | YES | NULL | Token permissions |
| last_used_at | TIMESTAMP | YES | NULL | Last usage time |
| expires_at | TIMESTAMP | YES | NULL | Expiration time |
| created_at | TIMESTAMP | YES | NULL | Creation time |
| updated_at | TIMESTAMP | YES | NULL | Last update time |

**Indexes:**
- PRIMARY KEY (`id`)
- UNIQUE KEY (`token`)
- INDEX (`tokenable_type`, `tokenable_id`)

---

### 10. **sessions** (Laravel Framework)

Session storage (database driver).

---

### 11. **cache**, **cache_locks** (Laravel Framework)

Cache storage (database driver).

---

### 12. **jobs**, **failed_jobs**, **job_batches** (Laravel Framework)

Queue system tables.

---

### 13. **password_reset_tokens** (Laravel Framework)

Password reset tokens.

---

### 14. **migrations** (Laravel Framework)

Migration version tracking.

---

## Data Relationships

### User → Request (1:N)
- One user can create multiple requests
- Request tracks creator via `user_id`

### Department → Request (1:N)
- One department has multiple requests
- Request belongs to requester's department

### Department → User (1:N)
- One department has multiple users
- User belongs to one department

### Role → User (1:N)
- One role assigned to multiple users
- User has one role

### Request → ApprovalStep (1:N)
- One request has multiple approval steps
- Steps created based on amount + rules

### Request → RequestAttachment (1:N)
- One request can have multiple attachments
- Attachments deleted when request is deleted

### Request → AuditLog (1:N)
- One request has multiple audit logs
- Complete history of state changes

### User → ApprovalStep (1:N)
- One user (approver) can have multiple steps assigned
- Optional - step may not be assigned yet

### User → AuditLog (1:N)
- One user performs multiple actions
- Tracks who did what

## Indexes Strategy

### Performance Optimization
- **Foreign keys**: All foreign keys indexed
- **Frequently filtered**: status, role, dates
- **Search queries**: email (unique), names

### Query Patterns
```sql
-- Get user's requests (indexed on user_id)
SELECT * FROM requests WHERE user_id = ?

-- Get pending approvals for role (compound index)
SELECT * FROM approval_steps
WHERE approver_role = ? AND status = 'pending'

-- Get requests by status (indexed)
SELECT * FROM requests WHERE status = 'IN_REVIEW'

-- Get audit timeline (indexed on request_id)
SELECT * FROM audit_logs
WHERE request_id = ?
ORDER BY created_at ASC
```

## Data Integrity

### Constraints
- **Foreign keys with CASCADE**: Request deletion removes steps, attachments, audits
- **ENUM types**: Ensures valid status/category values
- **NOT NULL**: Required fields enforced at DB level
- **UNIQUE**: Email uniqueness enforced

### Validation Layers
1. **Database**: Column types, constraints
2. **Model**: Eloquent $fillable/$guarded
3. **Request class**: Form request validation
4. **Business logic**: Service layer validation

## Backup & Migration

### Backup Strategy
```bash
# Backup database
docker exec deciflow-mysql mysqldump -uroot -ppassword deciflow > backup.sql

# Restore database
docker exec -i deciflow-mysql mysql -uroot -ppassword deciflow < backup.sql
```

### Migration Management
```bash
# Check migration status
php artisan migrate:status

# Rollback last migration
php artisan migrate:rollback

# Fresh migration (dev only!)
php artisan migrate:fresh --seed
```

## Seeded Data Overview

### Users (4)
- Super Admin (IT, super_admin)
- IT Dept Admin (IT, dept_admin)
- John Approver (Finance, approver)
- Alice Requester (HR, requester)

### Roles (4)
- super_admin, dept_admin, approver, requester

### Departments (4)
- IT, Finance, HR, Operations

### Rules (3)
- Small: ≤¥100,000 → 1 step
- Medium: ¥100,001-¥500,000 → 2 steps
- Large: >¥500,000 → 3 steps

### Sample Requests (3)
- Laptop ¥250,000 (DRAFT)
- Adobe License ¥80,000 (DRAFT)
- Cleaning Service ¥50,000 (SUBMITTED)

## Database Configuration

**Connection details:**
```
Host: 127.0.0.1
Port: 3307
Database: deciflow
Username: root
Password: password
```

**Laravel .env:**
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3307
DB_DATABASE=deciflow
DB_USERNAME=root
DB_PASSWORD=password
```
