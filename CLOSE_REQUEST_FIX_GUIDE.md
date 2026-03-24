# Fix Guide: Close Request Functionality

## Problem
Users getting error "Invalid action for PUT method" when trying to close requests.

## Root Cause
The API was missing the `close_request` action handler in the PUT method.

## Solution Implemented

### 1. API Changes
- Added `close_request` action handler to PUT method in `api/service_requests.php`
- Moved feedback storage from `service_requests` table to `request_feedback` table (proper normalization)
- Added proper permission checks and validation

### 2. Database Changes
Created SQL scripts to add missing columns:
- `database/add_feedback_rating_columns.sql` - Adds rating columns to request_feedback table
- `database/add_closed_at_column.sql` - Adds closed_at column to service_requests table

### 3. Files Modified
- `api/service_requests.php` - Added close_request handler in PUT method
- `lib/NotificationHelper.php` - Updated constructor to accept database parameter

## Setup Instructions

### Step 1: Update Database
Run the database setup script:
```bash
php setup_database_feedback.php
```

Or manually execute SQL files:
1. `database/create_request_feedback_table.sql`
2. `database/add_software_feedback.sql`
3. `database/add_feedback_rating_columns.sql`
4. `database/add_closed_at_column.sql`

### Step 2: Test the Fix
Run the test script:
```bash
php test_close_request.php
```

### Step 3: Verify in Application
1. Login as a user
2. Find a resolved request (status = 'resolved')
3. Click "Close Request" button
4. Fill feedback form and submit
5. Should work without errors

## Technical Details

### API Endpoint
- **Method**: PUT
- **URL**: `/api/service_requests.php`
- **Action**: `close_request`
- **Required Fields**: `request_id`
- **Optional Fields**: `rating`, `feedback`, `software_feedback`, `would_recommend`, `ease_of_use`, `speed_stability`, `requirement_meeting`

### Permission Rules
- Users can only close their own requests
- Staff/Admin can close any request
- Only resolved requests can be closed

### Database Schema
**request_feedback table:**
- `service_request_id` - FK to service_requests
- `created_by` - FK to users (who closed the request)
- `rating` - 1-5 rating
- `feedback` - General feedback text
- `software_feedback` - Feedback about IT SRM system
- `would_recommend` - yes/no/maybe
- `ease_of_use` - 1-5 rating
- `speed_stability` - 1-5 rating  
- `requirement_meeting` - 1-5 rating

**service_requests table:**
- `closed_at` - Timestamp when request was closed

## Troubleshooting

### Error: "Invalid action for PUT method"
- Ensure you're using the updated API file
- Check that the request method is PUT (not POST)
- Verify the action parameter is `close_request`

### Error: "Only resolved requests can be closed"
- The request must have status 'resolved' before closing
- Check the request status in the database

### Error: "Access denied"
- Users can only close their own requests
- Staff/Admin can close any request
- Verify user permissions

### Error: "Failed to create feedback record"
- Check if request_feedback table exists
- Verify all required columns are present
- Check database connection

## Testing
Use the provided test script to verify the fix:
```bash
php test_close_request.php
```

The test will:
1. Create a test session
2. Send a PUT request with sample feedback data
3. Display the API response
4. Show any errors that occur

## Files Created/Modified
- ✅ `api/service_requests.php` - Added close_request handler
- ✅ `lib/NotificationHelper.php` - Updated constructor
- ✅ `database/add_feedback_rating_columns.sql` - New
- ✅ `database/add_closed_at_column.sql` - New  
- ✅ `setup_database_feedback.php` - New
- ✅ `test_close_request.php` - New
- ✅ `CLOSE_REQUEST_FIX_GUIDE.md` - New
