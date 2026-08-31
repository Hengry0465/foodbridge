# Pickup Scheduling & Tracking Module

**Author:** Prem A/L Murugiah  
**Student ID:** 2113456  
**Educational Reference Implementation**

## Overview

This module manages the pickup process after a successful food match has been created. It allows recipients and donors to coordinate pickup dates and times, update pickup status, and maintain a record of completed or cancelled pickups.

This is a **complete, runnable Laravel 10 project** (not just the module's app-layer files) — `artisan`, `bootstrap/`, `public/`, HTTP/Console kernels, middleware, and all standard config files are included so it boots on its own. Quick start:

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed --class=PickupStatusSeeder
php artisan serve
```

Then in another terminal:

```bash
php artisan test
```

Note: `vendor/` is not shipped in the zip (dependencies are pulled by `composer install`), so run that first before anything else.

## Features

- **Pickup Scheduling:** Recipients can schedule pickups for their successful matches
- **State Pattern Implementation:** Enforces valid status transitions
- **Time-Slot Conflict Detection:** Prevents double-booking at the same donor address
- **Automatic Expiry:** Unconfirmed pickups expire after 2 hours past scheduled time
- **Donation Release Integration:** Automatically releases donations when pickups are cancelled or expired
- **Role-Based Access Control:** Enforces permissions for donors, recipients, and admins
- **Pickup History:** Users can view their pickup history with filtering and pagination

## Architecture

### State Design Pattern

The module uses the State design pattern to manage pickup status transitions:

- **PickupState** - Interface defining state operations
- **ScheduledState** - Initial state, allows transitions to confirmed, cancelled, or expired_pickup
- **ConfirmedState** - Allows transitions to completed or cancelled
- **CompletedState** - Terminal state, no further transitions
- **CancelledState** - Terminal state, no further transitions
- **ExpiredPickupState** - Terminal state, no further transitions

### Valid Transitions

```
scheduled → confirmed
scheduled → cancelled
scheduled → expired_pickup
confirmed → completed
confirmed → cancelled
completed → (no transitions)
cancelled → (no transitions)
expired_pickup → (no transitions)
```

## Database Schema

### pickup_statuses (Lookup Table)

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT UNSIGNED | Primary key |
| code | VARCHAR(50) | Unique status code |
| name | VARCHAR(100) | Human-readable name |
| description | TEXT | Optional description |
| created_at | TIMESTAMP | Creation timestamp |
| updated_at | TIMESTAMP | Update timestamp |

### pickups

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT UNSIGNED | Primary key |
| match_id | BIGINT UNSIGNED | Foreign key to food_matches |
| pickup_status_id | BIGINT UNSIGNED | Foreign key to pickup_statuses |
| donor_id | BIGINT UNSIGNED | Foreign key to users (donor) |
| recipient_id | BIGINT UNSIGNED | Foreign key to users (recipient) |
| donation_id | BIGINT UNSIGNED | Foreign key to donations |
| pickup_address | VARCHAR(255) | Donor pickup address |
| scheduled_at | DATETIME | Scheduled pickup time (UTC) |
| confirmed_at | DATETIME | When confirmed (UTC) |
| completed_at | DATETIME | When completed (UTC) |
| cancelled_at | DATETIME | When cancelled (UTC) |
| expired_at | DATETIME | When expired (UTC) |
| cancellation_reason | TEXT | Optional cancellation reason |
| donation_release_status | VARCHAR(50) | Donation release status |
| donation_released_at | DATETIME | When donation released (UTC) |
| created_by | BIGINT UNSIGNED | Foreign key to users (creator) |
| created_at | TIMESTAMP | Creation timestamp |
| updated_at | TIMESTAMP | Update timestamp |

## API Endpoints

All endpoints require authentication via Laravel Sanctum.

### POST /api/v1/pickups

Create a scheduled pickup for a successful match.

**Request Body:**
```json
{
  "match_id": 1,
  "scheduled_at": "2026-09-01T10:00:00+08:00"
}
```

**Responses:**
- `201 Created` - Pickup scheduled successfully
- `401 Unauthorized` - Not authenticated
- `403 Forbidden` - Not authorized (not the recipient)
- `409 Conflict` - Unsuccessful match, existing active pickup, or conflicting slot
- `422 Unprocessable Entity` - Validation failure

### PATCH /api/v1/pickups/{pickup}/status

Update pickup status.

**Request Body:**
```json
{
  "status": "confirmed",
  "reason": "Optional cancellation reason"
}
```

**Responses:**
- `200 OK` - Status updated successfully
- `401 Unauthorized` - Not authenticated
- `403 Forbidden` - Not authorized for this transition
- `404 Not Found` - Pickup not found
- `409 Conflict` - Invalid state transition
- `422 Unprocessable Entity` - Validation failure

### GET /api/v1/pickups/history

Get pickup history for the authenticated user.

**Query Parameters:**
- `status` (optional) - Filter by status code
- `date_from` (optional) - Filter by date range start
- `date_to` (optional) - Filter by date range end
- `page` (optional) - Page number (default: 1)
- `per_page` (optional) - Items per page (default: 15, max: 100)

**Responses:**
- `200 OK` - History retrieved successfully
- `401 Unauthorized` - Not authenticated
- `403 Forbidden` - Attempting to view another user's history

### GET /api/v1/pickups/{pickup}

View a specific pickup.

**Responses:**
- `200 OK` - Pickup retrieved successfully
- `401 Unauthorized` - Not authenticated
- `403 Forbidden` - Not authorized (not donor, recipient, or admin)
- `404 Not Found` - Pickup not found

## Role Permissions

### Matched Recipient
- Schedule the pickup
- View the pickup
- View personal pickup history
- Cancel the pickup (when transition permits)

### Matched Donor
- View the pickup
- Confirm the pickup
- Complete the pickup
- Cancel the pickup (when transition permits)
- View personal pickup history

### Admin
- View and update any pickup using valid transitions
- View all pickup history

### Unrelated User
- Receives HTTP 403 without learning sensitive pickup information

### Unauthenticated
- Receives HTTP 401

## Setup Instructions

### 1. Install Dependencies

```bash
composer install
```

### 2. Configure Environment

Copy `.env.example` to `.env` and configure your database:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 3. Generate Application Key

```bash
php artisan key:generate
```

### 4. Run Migrations

```bash
php artisan migrate
```

### 5. Seed Pickup Statuses

```bash
php artisan db:seed --class=PickupStatusSeeder
```

### 6. Configure Scheduler

Add the following to your crontab to run the automatic expiry command every minute:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

Or run manually for testing:

```bash
php artisan pickups:expire
```

### 7. Register Service Provider

The `DonationReleaseGateway` is automatically bound to `FakeDonationReleaseService` in `AppServiceProvider`. To use the actual Module 2 integration, update the binding:

```php
// app/Providers/AppServiceProvider.php
$this->app->bind(DonationReleaseGateway::class, ActualModule2Service::class);
```

## Configuration

Optional configuration in `config/pickup.php`:

```php
return [
    'slot_duration' => env('PICKUP_SLOT_DURATION', 60), // minutes
    'expiry_hours' => env('PICKUP_EXPIRY_HOURS', 2), // hours
];
```

Add to your `.env`:

```env
PICKUP_SLOT_DURATION=60
PICKUP_EXPIRY_HOURS=2
```

## Security Measures

- **Authentication:** All API endpoints require authentication via Laravel Sanctum
- **Server-Side Authorization:** Policies enforce role-based access control
- **IDOR Prevention:** User identity derived from authenticated session, not client-supplied IDs
- **Eloquent ORM:** All database queries use parameterized queries via Eloquent
- **Mass-Assignment Protection:** Models use `$fillable` with explicit field lists
- **Form Request Validation:** All inputs validated before processing
- **Enum Validation:** Status codes validated against allow-list
- **Safe Exception Handling:** Internal exceptions not exposed to API consumers
- **Transactional State Changes:** Status transitions wrapped in database transactions
- **Race Condition Protection:** Time-slot conflicts checked with database locking
- **Rate Limiting:** Apply via Laravel middleware (configurable per route)
- **Escaped Output:** API Resources handle output escaping
- **No Secrets in Code:** All sensitive data in environment variables
- **Minimal Logging:** Personal data minimized in logs
- **Correct HTTP Status Codes:** Proper status codes for all scenarios

## Testing

### Run All Tests

```bash
php artisan test
```

### Run Specific Test Suites

```bash
# Pickup scheduling tests
php artisan test --filter PickupSchedulingTest

# Status transition tests
php artisan test --filter PickupStatusTransitionTest

# Authorization tests
php artisan test --filter PickupAuthorizationTest

# History tests
php artisan test --filter PickupHistoryTest

# Expiry tests
php artisan test --filter PickupExpiryTest

# Donation release integration tests
php artisan test --filter DonationReleaseIntegrationTest
```

## Standalone SQL Script

A standalone MySQL 8 script is available at `database/sql/pickup_module.sql`. This script:

- Creates the `pickup_statuses` and `pickups` tables
- Adds indexes and foreign keys
- Seeds the five required status codes
- Uses transactions for safety
- Includes a development-only section for table reset

To run the script:

```bash
mysql -u username -p database_name < database/sql/pickup_module.sql
```

## Integration Points

### Module 2 (Donation Management)

The module integrates with Module 2 via the `DonationReleaseGateway` interface:

- **Interface:** `App\Services\DonationReleaseGateway`
- **Fake Implementation:** `App\Services\FakeDonationReleaseService`
- **Trigger:** When pickup transitions to `cancelled` or `expired_pickup`
- **Timeout:** 3 seconds
- **Retries:** 1 retry on failure
- **Logging:** Failures logged without exposing sensitive information

To integrate with the actual Module 2:

1. Implement the `DonationReleaseGateway` interface with your Module 2 client
2. Update the binding in `AppServiceProvider`
3. Ensure the implementation handles timeout and retry logic

### Existing Tables

The module references these existing tables:

- **users** - For donor, recipient, and creator references
- **food_matches** - For match information (note: "matches" is a reserved keyword in PHP 8+)
- **donations** - For donation references and status updates

## Assumptions

1. **Time Storage:** All times stored in UTC, converted at presentation boundary
2. **Address Normalization:** Addresses normalized (lowercase, trimmed) for conflict comparison
3. **Pickup Address:** Stored as string; in production, consider dedicated addresses table
4. **Match Table:** Uses `food_matches` table name to avoid reserved keyword issues
5. **Roles:** Simple `is_admin` boolean on users table for demonstration
6. **Authentication:** Uses Laravel Sanctum for API authentication
7. **Module 2 Integration:** Fake implementation provided for demonstration

## File Structure

```
app/
├── Console/
│   ├── Commands/
│   │   └── ExpirePickupsCommand.php
│   └── Kernel.php
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       └── PickupController.php
│   ├── Requests/
│   │   ├── StorePickupRequest.php
│   │   ├── UpdatePickupStatusRequest.php
│   │   └── GetPickupHistoryRequest.php
│   └── Resources/
│       ├── PickupResource.php
│       └── PickupCollection.php
├── Models/
│   ├── Pickup.php
│   ├── PickupStatus.php
│   ├── User.php
│   ├── Donation.php
│   ├── Request.php
│   └── FoodMatch.php
├── Policies/
│   └── PickupPolicy.php
├── Providers/
│   └── AppServiceProvider.php
└── Services/
    ├── States/
    │   ├── PickupState.php
    │   ├── ScheduledState.php
    │   ├── ConfirmedState.php
    │   ├── CompletedState.php
    │   ├── CancelledState.php
    │   ├── ExpiredPickupState.php
    │   └── PickupStateFactory.php
    ├── PickupService.php
    ├── DonationReleaseGateway.php
    └── FakeDonationReleaseService.php

config/
└── pickup.php

database/
├── migrations/
│   ├── 2024_01_01_000000_create_users_table.php
│   ├── 2024_01_01_000001_create_donations_table.php
│   ├── 2024_01_01_000002_create_requests_table.php
│   ├── 2024_01_01_000003_create_food_matches_table.php
│   ├── 2024_01_01_000004_create_pickup_statuses_table.php
│   └── 2024_01_01_000005_create_pickups_table.php
├── seeders/
│   └── PickupStatusSeeder.php
├── factories/
│   ├── UserFactory.php
│   ├── DonationFactory.php
│   ├── FoodMatchFactory.php
│   └── PickupFactory.php
└── sql/
    └── pickup_module.sql

routes/
└── api.php

tests/
└── Feature/
    ├── PickupSchedulingTest.php
    ├── PickupStatusTransitionTest.php
    ├── PickupAuthorizationTest.php
    ├── PickupHistoryTest.php
    ├── PickupExpiryTest.php
    └── DonationReleaseIntegrationTest.php
```

## API Usage Examples

### Schedule a Pickup

```bash
curl -X POST http://localhost:8000/api/v1/pickups \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "match_id": 1,
    "scheduled_at": "2026-09-01T10:00:00+08:00"
  }'
```

### Confirm a Pickup

```bash
curl -X PATCH http://localhost:8000/api/v1/pickups/1/status \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "confirmed"
  }'
```

### Complete a Pickup

```bash
curl -X PATCH http://localhost:8000/api/v1/pickups/1/status \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "completed"
  }'
```

### Cancel a Pickup

```bash
curl -X PATCH http://localhost:8000/api/v1/pickups/1/status \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "cancelled",
    "reason": "Emergency came up"
  }'
```

### Get Pickup History

```bash
curl -X GET "http://localhost:8000/api/v1/pickups/history?status=completed&page=1&per_page=10" \
  -H "Authorization: Bearer {token}"
```

## Troubleshooting

### Pickup Not Expiring

- Verify the scheduler is running: Check crontab configuration
- Manually test: `php artisan pickups:expire`
- Check logs: `storage/logs/laravel.log`

### Time-Slot Conflicts

- Verify address normalization is working
- Check the `PICKUP_SLOT_DURATION` configuration
- Review the conflict detection logic in `PickupService::checkTimeSlotConflict`

### Donation Release Failing

- Check the `DonationReleaseGateway` binding in `AppServiceProvider`
- Review logs for integration failures
- Verify the fake implementation is working for demonstration

## License

This is an educational reference implementation for classroom demonstration purposes.
