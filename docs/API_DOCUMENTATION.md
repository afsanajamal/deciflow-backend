# DeciFlow API Documentation

## Base URL
```
http://localhost:8000/api/v1
```

## Authentication
All protected endpoints require Bearer token authentication.

**Header:**
```
Authorization: Bearer {your_token_here}
```

---

## Authentication Endpoints

### Login
**POST** `/auth/login`

**Request Body:**
```json
{
  "email": "requester@deciflow.com",
  "password": "password"
}
```

**Response (200):**
```json
{
  "user": {
    "id": 1,
    "name": "Alice Requester",
    "email": "requester@deciflow.com",
    "role": {
      "id": 4,
      "name": "requester"
    },
    "department": {
      "id": 3,
      "name": "HR"
    }
  },
  "token": "1|abc123..."
}
```

### Logout
**POST** `/auth/logout`  
**Auth:** Required

**Response (200):**
```json
{
  "message": "Logged out successfully"
}
```

### Get Current User
**GET** `/me`  
**Auth:** Required

**Response (200):**
```json
{
  "id": 1,
  "name": "Alice Requester",
  "email": "requester@deciflow.com",
  "role": {...},
  "department": {...}
}
```

---

## Request Endpoints

### List Requests
**GET** `/requests`  
**Auth:** Required

**Query Parameters:**
- `status` - Filter by status (DRAFT, SUBMITTED, IN_REVIEW, etc.)
- `category` - Filter by category (EQUIPMENT, SOFTWARE, SERVICE, TRAVEL)
- `department_id` - Filter by department
- `amount_min` - Minimum amount
- `amount_max` - Maximum amount
- `date_from` - Start date (YYYY-MM-DD)
- `date_to` - End date (YYYY-MM-DD)

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "title": "New Laptop Purchase",
      "description": "Need a new MacBook Pro",
      "category": "EQUIPMENT",
      "amount": 250000,
      "status": "DRAFT",
      "urgency": "NORMAL",
      "user": {...},
      "department": {...},
      "created_at": "2024-12-28T10:00:00Z",
      "updated_at": "2024-12-28T10:00:00Z"
    }
  ],
  "current_page": 1,
  "total": 20
}
```

### Create Request
**POST** `/requests`  
**Auth:** Required

**Request Body:**
```json
{
  "title": "New Laptop",
  "description": "MacBook Pro for development",
  "category": "EQUIPMENT",
  "amount": 250000,
  "urgency": "NORMAL",
  "vendor_name": "Apple Inc.",
  "urgency_reason": null,
  "travel_start_date": null,
  "travel_end_date": null
}
```

**Response (201):**
```json
{
  "id": 5,
  "title": "New Laptop",
  "status": "DRAFT",
  ...
}
```

### Get Request Details
**GET** `/requests/{id}`  
**Auth:** Required

**Response (200):**
```json
{
  "id": 1,
  "title": "New Laptop Purchase",
  ...
  "approval_steps": [...],
  "attachments": [...]
}
```

### Update Request
**PUT** `/requests/{id}`  
**Auth:** Required (Owner only, DRAFT status only)

**Request Body:** (same as Create)

**Response (200):** (updated request object)

### Submit Request
**POST** `/requests/{id}/submit`  
**Auth:** Required (Owner only)

Validates and submits request for approval. Creates approval steps based on amount.

**Response (200):**
```json
{
  "id": 1,
  "status": "IN_REVIEW",
  "approval_steps": [
    {
      "step_number": 1,
      "approver_role": "approver",
      "status": "pending"
    }
  ]
}
```

### Cancel Request
**POST** `/requests/{id}/cancel`  
**Auth:** Required (Owner only)

**Response (200):**
```json
{
  "id": 1,
  "status": "CANCELLED"
}
```

### Archive Request
**POST** `/requests/{id}/archive`  
**Auth:** Required (Super Admin only)

**Response (200):**
```json
{
  "id": 1,
  "status": "ARCHIVED"
}
```

---

## Approval Endpoints

### Get Approval Inbox
**GET** `/approvals/inbox`  
**Auth:** Required (Approver, Dept Admin, Super Admin)

Returns requests pending approval by current user's role.

**Response (200):**
```json
[
  {
    "id": 5,
    "title": "Office Supplies",
    "amount": 50000,
    "status": "IN_REVIEW",
    "approval_steps": [
      {
        "step_number": 1,
        "approver_role": "approver",
        "status": "pending"
      }
    ]
  }
]
```

### Approve Request
**POST** `/requests/{id}/approve`  
**Auth:** Required

**Request Body:**
```json
{
  "comment": "Approved. Looks good."
}
```

**Response (200):**
```json
{
  "id": 5,
  "status": "APPROVED",
  ...
}
```

### Reject Request
**POST** `/requests/{id}/reject`  
**Auth:** Required

**Request Body:**
```json
{
  "comment": "Budget exceeded for this quarter."
}
```

**Response (200):**
```json
{
  "id": 5,
  "status": "REJECTED",
  ...
}
```

### Return Request
**POST** `/requests/{id}/return`  
**Auth:** Required

**Request Body:**
```json
{
  "comment": "Please provide more details."
}
```

**Response (200):**
```json
{
  "id": 5,
  "status": "RETURNED",
  ...
}
```

---

## Attachment Endpoints

### List Attachments
**GET** `/requests/{id}/attachments`  
**Auth:** Required

**Response (200):**
```json
[
  {
    "id": 1,
    "file_name": "invoice.pdf",
    "mime_type": "application/pdf",
    "created_at": "2024-12-28T10:00:00Z"
  }
]
```

### Upload Attachment
**POST** `/requests/{id}/attachments`  
**Auth:** Required  
**Content-Type:** `multipart/form-data`

**Request Body:**
```
file: [binary file data, max 10MB]
```

**Response (201):**
```json
{
  "id": 2,
  "request_id": 5,
  "file_name": "invoice.pdf",
  "file_path": "attachments/abc123_1234567890.pdf",
  "mime_type": "application/pdf"
}
```

### Download Attachment
**GET** `/attachments/{id}`  
**Auth:** Required

**Response (200):** Binary file download

### Delete Attachment
**DELETE** `/attachments/{id}`  
**Auth:** Required

**Response (200):**
```json
{
  "message": "Attachment deleted successfully"
}
```

---

## Rule Endpoints

### List Rules
**GET** `/rules`  
**Auth:** Required (Dept Admin, Super Admin)

**Response (200):**
```json
[
  {
    "id": 1,
    "name": "Small Amount Approval",
    "min_amount": 0,
    "max_amount": 100000,
    "approval_steps_json": ["approver"],
    "category": null,
    "is_active": true
  }
]
```

### Create Rule
**POST** `/rules`  
**Auth:** Required (Dept Admin, Super Admin)

**Request Body:**
```json
{
  "name": "Special Equipment Rule",
  "min_amount": 0,
  "max_amount": 50000,
  "approval_steps_json": ["approver"],
  "category": "EQUIPMENT",
  "is_active": true
}
```

### Update Rule
**PUT** `/rules/{id}`  
**Auth:** Required (Dept Admin, Super Admin)

**Request Body:** (same as Create)

### Delete Rule
**DELETE** `/rules/{id}`  
**Auth:** Required (Dept Admin, Super Admin)

**Response (200):**
```json
{
  "message": "Rule deleted successfully"
}
```

---

## Audit Endpoints

### Get All Audit Logs
**GET** `/audit`  
**Auth:** Required (Super Admin only)

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "request_id": 5,
      "user_id": 1,
      "action": "transition",
      "from_status": "DRAFT",
      "to_status": "SUBMITTED",
      "meta": {
        "comment": "Submitting for approval"
      },
      "created_at": "2024-12-28T10:00:00Z",
      "user": {...},
      "request": {...}
    }
  ]
}
```

### Get Request Audit Timeline
**GET** `/requests/{id}/audit`  
**Auth:** Required

**Response (200):**
```json
[
  {
    "id": 1,
    "action": "transition",
    "from_status": "DRAFT",
    "to_status": "SUBMITTED",
    "user": {
      "name": "Alice Requester"
    },
    "created_at": "2024-12-28T10:00:00Z"
  }
]
```

---

## Error Responses

### 401 Unauthorized
```json
{
  "message": "Unauthenticated."
}
```

### 403 Forbidden
```json
{
  "error": {
    "code": "FORBIDDEN",
    "message": "Insufficient permissions"
  }
}
```

### 404 Not Found
```json
{
  "message": "Resource not found"
}
```

### 422 Validation Error
```json
{
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Validation failed: vendor_name is required for SOFTWARE category"
  }
}
```

### 500 Server Error
```json
{
  "error": {
    "code": "SERVER_ERROR",
    "message": "Internal server error"
  }
}
```

---

## Rate Limiting
- **Limit:** 60 requests per minute per IP
- **Headers:**
  - `X-RateLimit-Limit: 60`
  - `X-RateLimit-Remaining: 45`

When exceeded:
```json
{
  "message": "Too Many Attempts."
}
```

---

## Approval Rules

Approval steps are determined by request amount:

| Amount (JPY) | Approval Steps |
|--------------|----------------|
| ≤ ¥100,000 | 1-step (approver) |
| ¥100,001 - ¥500,000 | 2-step (approver → dept_admin) |
| > ¥500,000 | 3-step (approver → dept_admin → super_admin) |

---

## Category Validation Rules

- **SOFTWARE**: `vendor_name` required
- **TRAVEL**: `travel_start_date` and `travel_end_date` required
- **URGENT**: `urgency_reason` required

---

## Testing

### Demo Accounts
All passwords: `password`

- **Super Admin**: superadmin@deciflow.com
- **Dept Admin**: deptadmin@deciflow.com
- **Approver**: approver@deciflow.com
- **Requester**: requester@deciflow.com
