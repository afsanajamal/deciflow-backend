# DeciFlow Backend

Procurement / Budget Approval Support System - Laravel 11 REST API

## Features

- **Authentication**: Laravel Sanctum token-based auth
- **Role-Based Access Control**: 4 roles (super_admin, dept_admin, approver, requester)
- **Workflow Management**: State machine for request lifecycle
- **Approval Engine**: Multi-step approval based on amount thresholds
- **Audit Logging**: Immutable audit trail for all state transitions
- **Category Validation**: Business rules for different procurement categories

## Tech Stack

- **Framework**: Laravel 11
- **Database**: MySQL 8.0 (Docker)
- **Authentication**: Laravel Sanctum
- **Testing**: PHPUnit
- **CI/CD**: GitHub Actions

## Documentation

- **[API Documentation](docs/API_DOCUMENTATION.md)** - Complete API reference with all endpoints
- **[Architecture](docs/ARCHITECTURE.md)** - System architecture and design patterns
- **[Database Schema](docs/DATABASE_SCHEMA.md)** - Database structure and relationships
- **[Deployment Guide](docs/DEPLOYMENT.md)** - Production deployment instructions

## Requirements

- PHP 8.4+
- Composer 2.x
- Docker & Docker Compose

## Quick Start

```bash
# 1. Install dependencies
composer install

# 2. Setup environment
cp .env.example .env
php artisan key:generate

# 3. Start MySQL
docker-compose up -d

# 4. Run migrations and seed
php artisan migrate
php artisan db:seed

# 5. Start server
php artisan serve
```

API available at: `http://localhost:8000`

## Demo Accounts

Password: `password`

- **Super Admin**: superadmin@deciflow.com
- **Dept Admin**: deptadmin@deciflow.com
- **Approver**: approver@deciflow.com
- **Requester**: requester@deciflow.com

## API Documentation

### Auth
- `POST /api/v1/auth/login` - Login
- `POST /api/v1/auth/logout` - Logout
- `GET /api/v1/me` - Current user

### Requests
- `GET /api/v1/requests` - List (with filters)
- `POST /api/v1/requests` - Create draft
- `GET /api/v1/requests/{id}` - Details
- `PUT /api/v1/requests/{id}` - Update draft
- `POST /api/v1/requests/{id}/submit` - Submit
- `POST /api/v1/requests/{id}/cancel` - Cancel
- `POST /api/v1/requests/{id}/archive` - Archive

### Attachments
- `GET /api/v1/requests/{id}/attachments` - List attachments
- `POST /api/v1/requests/{id}/attachments` - Upload attachment (max 10MB)
- `GET /api/v1/attachments/{id}` - Download attachment
- `DELETE /api/v1/attachments/{id}` - Delete attachment

### Approvals
- `GET /api/v1/approvals/inbox` - Pending approvals
- `POST /api/v1/requests/{id}/approve` - Approve
- `POST /api/v1/requests/{id}/reject` - Reject
- `POST /api/v1/requests/{id}/return` - Return

### Rules & Audit
- `GET /api/v1/rules` - List rules
- `GET /api/v1/audit` - All logs (super_admin)
- `GET /api/v1/requests/{id}/audit` - Request timeline

## Approval Rules

- **≤ ¥100,000**: 1-step (approver)
- **¥100,001 - ¥500,000**: 2-step (approver → dept_admin)
- **> ¥500,000**: 3-step (approver → dept_admin → super_admin)

## Email Notifications

The system sends automatic email notifications for workflow events:

### Notification Types

1. **Request Submitted** - Sent to requester when they submit a request
2. **Approval Requested** - Sent to approvers when a request needs their review
3. **Request Approved** - Sent to requester when their request is fully approved
4. **Request Rejected** - Sent to requester when their request is rejected
5. **Request Returned** - Sent to requester when their request needs modifications

### Queue Configuration

Notifications are queued for better performance. To process them:

```bash
# Run the queue worker
php artisan queue:work

# Or use database queue for production
php artisan queue:table
php artisan migrate
```

### Mail Configuration

By default, emails are logged to `storage/logs/laravel.log` for development.

For production, update `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_FROM_ADDRESS=noreply@deciflow.com
MAIL_FROM_NAME=DeciFlow
FRONTEND_URL=https://your-frontend-url.com
```

## Testing

```bash
php artisan test
```

## License

MIT
