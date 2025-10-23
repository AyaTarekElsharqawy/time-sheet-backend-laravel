# Timesheets System (Laravel)

This repository contains the backend API for a small timesheet management system built with Laravel. It includes user authentication, timesheet CRUD, admin approval/rejection flows, and basic statistics.

## Table of contents
- Setup and running
- Design decisions
- Technology stack
- API documentation
- Assumptions and limitations
- Future improvements

## Setup and running

Requirements
- PHP 8.2+
- Composer
- Node.js + npm (optional, for asset building)
- SQLite (included for local development) or another database (MySQL/Postgres)

Quick start (Windows PowerShell)

1. Install PHP dependencies

    composer install

2. Copy environment and generate an app key

    copy .env.example .env; php artisan key:generate

3. Ensure the local SQLite database file exists and run migrations/seeds

    php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
    php artisan migrate --seed

4. (Optional) Install JS dependencies and build assets

    npm install; npm run build

5. Run the development server

    php artisan serve

The API base URL is: http://127.0.0.1:8000/api

## Design decisions

Problem domain
- Focus: a backend API for timesheet entry and approval suited for small teams.
- Core features implemented: authentication, timesheet create/read/update/delete, duplicate detection (prevent logging same project on same date per user), admin approve/reject, and basic stats.

Why this approach
- Laravel provides batteries-included features (auth, migrations, Eloquent) which accelerate building a reliable API.
- Sanctum tokens make it simple to secure the API for SPAs and mobile clients.

Roles and behavior
- Users have a `role` (string) that controls behavior: `employee` or `admin`.
- Employees can manage only their timesheets; admins can manage and approve/reject any timesheet.

## Technology stack

- PHP 8.2
- Laravel 12
- Laravel Sanctum (authentication)
- SQLite for local development (DB configurable in `.env`)
- Vite + npm for asset pipeline (minimal frontend assets included)
- PHPUnit for testing (project scaffold)

## API documentation

Base path: /api

Authentication
- Register
  - POST /api/register
  - Body: { name, email, password, password_confirmation }
  - Response: user object and token

- Login
  - POST /api/login
  - Body: { email, password }
  - Response: { token }

- Logout
  - POST /api/logout (auth required)

- Current user
  - GET /api/user (auth required)

Timesheets (requires auth:sanctum)
- List timesheets
  - GET /api/timesheets
  - Query params (optional): status, project (partial), date_from, date_to
  - Employees see their own records; admins see all.
  - Response: array of timesheet objects

- Create timesheet
  - POST /api/timesheets
  - Body: { project: string, hours_worked: number (1-12), date: YYYY-MM-DD, notes?: string }
  - Validations: project required, hours_worked between 1 and 12, date <= today
  - Duplicate rule: same user + same project + same date => 409 Conflict
  - Response: 201 with created timesheet

- Update timesheet
  - PUT /api/timesheets/{id}
  - Body: same as create
  - Only owner or admin can update. Updating resets status to `Pending` and clears approval fields.

- Delete timesheet
  - DELETE /api/timesheets/{id}
  - Only owner or admin can delete.

- Approve timesheet
  - PATCH /api/timesheets/{id}/approve
  - Admin-only. Sets status to `Approved`, records `approved_by` and `approved_at`.

- Reject timesheet
  - PATCH /api/timesheets/{id}/reject
  - Admin-only. Sets status to `Rejected`, records `approved_by` and `approved_at`.

- Stats
  - GET /api/timesheets/stats
  - Employees: scoped to their own records. Admins: global statistics.
  - Response: { total, approved, pending, rejected, total_hours, average_hours }

Timesheet response shape
```
{
  id: number,
  user_id: number,
  project: string,
  hours_worked: number,
  date: "YYYY-MM-DD",
  notes: string|null,
  status: "Pending"|"Approved"|"Rejected",
  approved_by: number|null,
  approved_at: datetime|null,
  created_at: datetime,
  updated_at: datetime
}
```

Errors
- Validation errors: 422 { errors: { field: [messages] } }
- Unauthorized: 401 (not authenticated) or 403 (authenticated but forbidden)
- Duplicate entry: 409 { error: 'This project is already logged for today.' }

## Assumptions and limitations

- Users table has a `role` string column containing `employee` or `admin`.
- Sanctum powers authentication and route protection via `auth:sanctum`.
- No pagination or sorting on list endpoints (may be needed for large data).
- No email notifications or external integrations for approvals.
- No audit logging; updates overwrite fields directly.

## Future improvements

If given more time, recommended enhancements:
- Add pagination, sorting, and more flexible filtering to `GET /api/timesheets`.
- Replace inline role checks with Laravel Policies/Gates for clearer authorization.
- Add feature tests (integration) covering happy paths and edge cases.
- Add soft deletes and an audit log for changes (who changed what and when).
- Add rate-limiting, input sanitization, and stricter validation rules.
- Add OpenAPI/Swagger documentation and example Postman collection.
- Add email/Slack notifications for approvals and rejections.


