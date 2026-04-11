# Fix: Duplicate Entry Error in Reject Request Creation

## Problem Identified
When creating a reject request, users received a database error: "SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry '92-2' for key 'unique_reject_per_request'" but the operation still succeeded.

## Root Cause Analysis

### The Issue
1. **Unique Constraint**: Table `reject_requests` has a unique constraint `unique_reject_per_request` on `(service_request_id, rejected_by, status)`
2. **Race Condition**: The duplicate check logic was working, but the unique constraint was triggered before the update logic could execute
3. **Error Handling**: The catch block was showing the database error instead of handling the duplicate gracefully

### Database State
```
Existing Reject Request:
- ID: 82
- Service Request ID: 92
- Rejected By: 2 (staff user)
- Status: pending
- Created: 2026-04-11 13:40:22

When trying to create another reject request for the same combination:
- Unique constraint triggered: '92-2' (service_request_id-rejected_by)
- Error shown: "Database error: SQLSTATE[23000]: Integrity constraint violation..."
- But operation should update existing pending request instead
```

### Technical Details

#### Unique Constraint Definition
```sql
ALTER TABLE reject_requests 
ADD UNIQUE INDEX unique_reject_per_request (service_request_id, rejected_by, status(20));
```

This prevents multiple pending reject requests from the same user for the same service request.

#### Original Logic Flow
1. Check for existing pending reject request
2. If found and pending: UPDATE existing record
3. If not found or not pending: INSERT new record
4. If INSERT fails due to unique constraint: Show error (problem)

#### The Problem
The unique constraint was triggered during INSERT, but the error handling wasn't smart enough to fall back to the UPDATE logic.

## Solution Applied

### Enhanced Error Handling
Modified the catch block in `api/service_requests.php` to specifically handle duplicate key errors:

```php
} catch (Exception $e) {
    // Check if this is a duplicate key error
    if ($e->getCode() === '23000' && strpos($e->getMessage(), 'Duplicate entry') !== false) {
        
        // This means there's already a pending request, try to update it instead
        try {
            // Get the existing pending request
            $existing_query = "SELECT id, status, reject_reason, reject_details FROM reject_requests 
                             WHERE service_request_id = :request_id AND rejected_by = :rejected_by
                             ORDER BY created_at DESC LIMIT 1";
            
            // ... update logic ...
            
        } catch (Exception $update_e) {
            error_log("Failed to handle duplicate key: " . $update_e->getMessage());
        }
    }
    
    // If we get here, it's a genuine error
    serviceJsonResponse(false, "Database error: " . $e->getMessage());
    return;
}
```

### Key Improvements

#### 1. Specific Error Detection
- **Error Code**: Check for `SQLSTATE[23000]`
- **Error Message**: Look for "Duplicate entry" in message
- **Fallback Logic**: Attempt UPDATE when INSERT fails due to duplicate

#### 2. Graceful Fallback
- **Find Existing**: Query for existing pending request
- **Update Instead**: Update existing record instead of creating new
- **File Uploads**: Handle file uploads for updated requests
- **Success Response**: Return success message with update indication

#### 3. Complete Functionality
- **File Upload Support**: New files can be added to updated requests
- **Response Consistency**: Same response format as normal operations
- **Error Logging**: Log errors for debugging but don't show to users

## Results

### Before Fix
- **User Experience**: Saw database error message
- **Operation Status**: Actually succeeded but looked like failure
- **Confusion**: Users thought operation failed when it worked

### After Fix
- **User Experience**: Clean success message
- **Operation Status**: Clear indication of update vs create
- **Consistency**: Proper error handling for all cases

### Response Examples

#### New Reject Request
```json
{
    "success": true,
    "message": "Reject request submitted successfully",
    "reject_id": 83
}
```

#### Updated Reject Request (was duplicate)
```json
{
    "success": true,
    "message": "Reject request updated successfully",
    "reject_id": 82,
    "updated": true
}
```

#### With File Uploads
```json
{
    "success": true,
    "message": "Reject request updated successfully with 2 new file(s) attached",
    "reject_id": 82,
    "updated": true,
    "uploaded_files": [...]
}
```

## Technical Implementation Details

### Error Detection Logic
```php
if ($e->getCode() === '23000' && strpos($e->getMessage(), 'Duplicate entry') !== false)
```

- **SQLSTATE[23000]**: Integrity constraint violation
- **Duplicate entry**: Specific to unique constraint violations
- **Fallback**: Only trigger fallback for these specific errors

### Update Logic
```php
$update_query = "UPDATE reject_requests 
               SET reject_reason = :reject_reason, reject_details = :reject_details, updated_at = NOW()
               WHERE id = :existing_id";
```

- **Same Fields**: Update same fields as original INSERT
- **Timestamp**: Update the `updated_at` timestamp
- **Preserve ID**: Keep the same reject request ID

### File Upload Handling
- **Unique Filenames**: Generate unique filenames for new uploads
- **Database Storage**: Store file information in `reject_request_attachments`
- **File System**: Save files to `/uploads/reject_requests/` directory

## Prevention and Best Practices

### For Future Development
1. **Transaction Safety**: Consider using database transactions for the entire operation
2. **Upsert Logic**: Consider using `INSERT ... ON DUPLICATE KEY UPDATE` syntax
3. **Pre-check Validation**: More robust pre-validation before attempting operations

### For Current System
1. **Error Monitoring**: Monitor for similar constraint violations
2. **User Feedback**: Ensure users understand when requests are updated vs created
3. **Data Integrity**: Maintain data consistency with the unique constraint

## Summary

The duplicate entry error was caused by a race condition between the duplicate check logic and the unique constraint. The fix adds intelligent error handling that:

1. **Detects** duplicate key errors specifically
2. **Falls back** to updating existing pending requests
3. **Handles** file uploads for updated requests
4. **Provides** clear success messages to users
5. **Maintains** data integrity through the unique constraint

**The fix ensures users get a smooth experience without confusing database error messages, while maintaining the business rule of preventing duplicate pending reject requests.**
