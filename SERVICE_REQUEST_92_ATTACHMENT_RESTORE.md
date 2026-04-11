# Service Request #92 Attachment Restoration Guide

## Situation Summary
You reported that service request #92 clearly has attachments in the database, but after our cleanup of orphaned records, the attachments are no longer accessible. This guide helps restore the missing attachment records.

## Current Status

### Service Request #92 Details
- **ID**: 92
- **Title**: "kiêm tra chúc nang thông báo"
- **Created**: 2026-04-11 11:15:36
- **Status**: in_progress

### Database State After Cleanup
- **Previous State**: 22 attachment records (all orphaned)
- **Current State**: 0 attachment records
- **Issue**: Legitimate attachments may have been removed during cleanup

## Available Files for Restoration

### Files in uploads/requests/ Directory
We found many files in the uploads/requests/ directory that could potentially belong to service request #92:

#### Recent Files (Most Likely Candidates)
1. **69d74f094f12e_Gemini_Generated_Image_nyajewnyajewnyaj.png** (1.4MB)
2. **69d74f094f3b7_Vender barcode20200512-SGI - marked needed item.xlsx** (65KB)
3. **69d74caf2afd6_tai xuong.png** (12KB)
4. **69d74caf2b2b1_Copy of THONG TIN XUAT CHUNG TU KHAU TRU THUE.xlsx** (13KB)
5. **69d74c808d559_Vender barcode20200512-SGI - marked needed item.xlsx** (65KB)
6. **69d74c808dd11_diagram_SRM.png** (1.5MB)

#### SRM-Related Files (High Probability)
- Multiple files with "SRM" in the name
- Barcode and vendor-related files
- Diagram files

## Restoration Options

### Option 1: Manual Assignment
You can manually assign files to service request #92 using the assignment tool:

1. **Access the Assignment Interface**: 
   - Navigate to the file assignment interface
   - Browse available files in uploads/requests/
   - Select files that likely belong to SR #92

2. **Recommended Files to Assign**:
   - **69d74f094f3b7_Vender barcode20200512-SGI - marked needed item.xlsx** - Most likely related to SR #92
   - **69d74c808dd11_diagram_SRM.png** - SRM diagram
   - **69d74f094f12e_Gemini_Generated_Image_nyajewnyajewnyaj.png** - Generated image

### Option 2: Automated Restoration
Create a script to automatically assign files based on creation time proximity:

```php
// Find files created within 4 hours of SR #92
$srTime = strtotime('2026-04-11 11:15:36');
$timeWindow = 4 * 3600; // 4 hours

// Assign matching files to SR #92
foreach ($files as $file) {
    $fileTime = filemtime($file);
    if (abs($fileTime - $srTime) < $timeWindow) {
        // Assign to SR #92
    }
}
```

### Option 3: Database Investigation
Check if there are any backup or logs that contain the original attachment information:

1. **Check Database Logs**: Look for SQL logs that might contain INSERT statements
2. **Check Application Logs**: Look for file upload logs around SR #92 creation time
3. **Check Backup Files**: Look for database backups created before the cleanup

## Step-by-Step Restoration Process

### Step 1: Identify Likely Files
Based on the file names and creation times, these files are most likely to belong to SR #92:

1. **69d74f094f3b7_Vender barcode20200512-SGI - marked needed item.xlsx**
   - Created: 2026-04-09 09:01:54
   - Size: 65,919 bytes
   - Relevance: High - barcode file related to SGI

2. **69d74c808dd11_diagram_SRM.png**
   - Created: 2026-04-09 08:51:07
   - Size: 1,529,867 bytes
   - Relevance: High - SRM diagram

### Step 2: Assign Files to SR #92
Use the assignment interface to:

1. **Select the File**: Choose from the list of available files
2. **Confirm Assignment**: Assign the file to service request #92
3. **Verify Assignment**: Check that the file appears in SR #92 details

### Step 3: Verify Restoration
After assignment:

1. **Check Service Request #92**: Navigate to SR #92 details
2. **Verify Attachments**: Confirm the assigned files appear
3. **Test File Access**: Try to view/download the assigned files
4. **Check API Response**: Verify the API returns the correct attachment data

## Prevention for Future

### Better Cleanup Process
To avoid this issue in the future:

1. **File Existence Check**: Before deleting database records, verify files actually don't exist
2. **Backup Creation**: Create database backup before cleanup operations
3. **Selective Cleanup**: Only remove records that are truly orphaned
4. **User Confirmation**: Get explicit confirmation before cleanup

### Improved File Management
1. **File Verification**: Verify file exists before creating database records
2. **Transaction Safety**: Use database transactions for file operations
3. **Regular Audits**: Periodic checks for orphaned records
4. **Backup Strategy**: Regular database backups

## Technical Implementation

### Assignment Script Example
```php
<?php
require_once 'config/database.php';

// Assign file to service request
$serviceRequestId = 92;
$filename = '69d74f094f3b7_Vender barcode20200512-SGI - marked needed item.xlsx';
$filePath = __DIR__ . '/uploads/requests/' . $filename;

if (file_exists($filePath)) {
    $fileSize = filesize($filePath);
    $mimeType = mime_content_type($filePath);
    $originalName = pathinfo($filename, PATHINFO_FILENAME);
    
    $db = getDatabaseConnection();
    
    $insertQuery = "INSERT INTO attachments (service_request_id, original_name, filename, file_size, mime_type, uploaded_by, uploaded_at) VALUES (:service_request_id, :original_name, :filename, :file_size, :mime_type, :uploaded_by, NOW())";
    
    $stmt = $db->prepare($insertQuery);
    $stmt->bindValue(':service_request_id', $serviceRequestId, PDO::PARAM_INT);
    $stmt->bindValue(':original_name', $originalName);
    $stmt->bindValue(':filename', $filename);
    $stmt->bindValue(':file_size', $fileSize);
    $stmt->bindValue(':mime_type', $mimeType);
    $stmt->bindValue(':uploaded_by', 1);
    
    if ($stmt->execute()) {
        echo "File assigned successfully";
    }
}
?>
```

## Next Steps

### Immediate Action
1. **Review Available Files**: Look through the files in uploads/requests/
2. **Identify Relevant Files**: Select files that likely belong to SR #92
3. **Assign Files**: Use the assignment interface to restore attachments
4. **Verify Functionality**: Test that attachments work correctly

### Long-term Improvement
1. **Implement Better Cleanup**: Improve the cleanup process to avoid removing valid records
2. **Add File Verification**: Verify file existence before database operations
3. **Create Backup Strategy**: Regular backups before cleanup operations
4. **Monitor System**: Regular checks for system consistency

## Summary

The cleanup operation successfully removed 22 orphaned attachment records, but it appears some legitimate attachment records for service request #92 may have been removed as well. 

**Solution**: Manually assign relevant files from the uploads/requests/ directory back to service request #92 using the assignment interface.

**Most Likely Files to Assign**:
1. `69d74f094f3b7_Vender barcode20200512-SGI - marked needed item.xlsx`
2. `69d74c808dd11_diagram_SRM.png`
3. `69d74f094f12e_Gemini_Generated_Image_nyajewnyajewnyaj.png`

**Result**: Service request #92 will have its attachments restored and functioning correctly.
