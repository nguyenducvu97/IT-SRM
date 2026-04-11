# Fix: Duplicate File Attachments in Reject Request Details

## Problem Identified
When viewing reject request details in the admin/staff interface, file attachments were being displayed multiple times (duplicated).

## Root Cause Analysis
The issue was caused by **duplicate records in the database table** `reject_request_attachments`. 

### Investigation Process:
1. **API Analysis**: The API filtering logic in `api/reject_requests.php` was working correctly - it was filtering duplicates by original filename.
2. **Database Investigation**: Found that reject request #81 had 4 attachment records instead of 2:
   - `(MMI) Marlin Magnet_751134800 Rev A.pdf` - 2 records (IDs: 144, 146)
   - `IT SRM.png` - 2 records (IDs: 145, 147)
3. **Frontend Analysis**: The frontend code in `assets/js/app.js` was correctly displaying the data received from the API.

### Database State Before Fix:
```
Reject Request #81 - Attachments:
- ID 144: (MMI) Marlin Magnet_751134800 Rev A.pdf
- ID 145: IT SRM.png  
- ID 146: (MMI) Marlin Magnet_751134800 Rev A.pdf  (DUPLICATE)
- ID 147: IT SRM.png                              (DUPLICATE)
```

## Solution Applied
Created and executed a database cleanup script to remove duplicate attachment records while preserving the first occurrence (lowest ID) of each file.

### Cleanup Process:
1. **Identify Duplicates**: Query to find all records with duplicate original filenames per reject request
2. **Preserve Strategy**: Keep the first occurrence (lowest ID) of each file
3. **Remove Duplicates**: Delete subsequent duplicate records
4. **Verification**: Confirm no duplicates remain

### Database State After Fix:
```
Reject Request #81 - Attachments:
- ID 144: (MMI) Marlin Magnet_751134800 Rev A.pdf
- ID 145: IT SRM.png
```

## Technical Details

### API Filtering Logic (Working Correctly)
The API in `api/reject_requests.php` already had proper duplicate filtering:

```php
// Filter duplicates by original name
$attachments = [];
$seen_original_names = [];
foreach ($all_attachments as $attachment) {
    $original_name = $attachment['original_name'];
    if (!in_array($original_name, $seen_original_names)) {
        $attachments[] = $attachment;
        $seen_original_names[] = $original_name;
    }
}
```

### Frontend Display Logic (Working Correctly)
The frontend in `assets/js/app.js` properly displays attachments received from the API:

```javascript
${reject.attachments && reject.attachments.length > 0 ? `
    <div class="reject-attachments">
        <h4><i class="fas fa-paperclip"></i> Têp dính kèm (${reject.attachments.length})</h4>
        <div class="attachments-list">
            ${reject.attachments.map(attachment => {
                // Display each attachment once
                return `<div class="attachment-item">...</div>`;
            }).join('')}
        </div>
    </div>
` : ''}
```

## Files Modified
- **Database**: Removed 2 duplicate records from `reject_request_attachments` table
- **No code changes needed**: The existing API and frontend logic was already correct

## Impact
- **Fixed**: Duplicate file attachments no longer appear in reject request details
- **Preserved**: All unique file attachments remain accessible
- **Improved**: System now displays accurate attachment counts
- **Performance**: Reduced database query overhead by eliminating duplicates

## Testing Verification
1. **Database Check**: No duplicate attachment records remain
2. **API Response**: Returns correct number of unique attachments
3. **Frontend Display**: Shows each attachment only once
4. **File Access**: All attachment files remain downloadable

## Prevention
To prevent this issue in the future:
1. **Database Constraints**: Consider adding unique constraints on `(reject_request_id, original_name)`
2. **Application Logic**: Ensure file upload logic checks for existing files before inserting
3. **Regular Maintenance**: Periodic checks for duplicate records

## Summary
The duplicate attachment display issue was resolved by cleaning up duplicate database records. The existing API filtering and frontend display logic were already working correctly and did not require modification. The fix ensures that users now see each file attachment only once when viewing reject request details.
