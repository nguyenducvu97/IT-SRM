# SECURITY BREACH INVESTIGATION REPORT

## Issue Summary
**User Nguyễn văn Tín accessed Request ID 27 belonging to Vu nguyen duc**

## Root Cause Analysis
Found multiple access control vulnerabilities in the API endpoints:

### 1. **COMMENTS API VULNERABILITY** ✅ FIXED
**File:** `api/comments.php` (Line 55-58)
**Issue:** Users could access requests assigned to them, not just their own
```php
// BEFORE (VULNERABLE)
if ($user_role != 'admin' && $user_role != 'staff' && 
    $request['user_id'] != $user_id && $request['assigned_to'] != $user_id) {
    jsonResponse(false, "Access denied");
}

// AFTER (FIXED)
if ($user_role != 'admin' && $user_role != 'staff' && 
    $request['user_id'] != $user_id) {
    jsonResponse(false, "Access denied");
}
```

### 2. **SERVICE REQUESTS API** ✅ ALREADY FIXED
**File:** `api/service_requests.php`
**Status:** Previously fixed in earlier implementation

## Security Gaps Identified

### Vulnerability Vector 1: Comments API
- Regular users could post/view comments on requests assigned to them
- This allowed indirect access to other users' request information

### Vulnerability Vector 2: Potential Assignment Exploitation
- If a regular user was somehow assigned to a request, they could access it
- This should not be possible as only staff/admin can assign requests

## Investigation Required

### Database Checks Needed:
1. **Request ID 27 Ownership:**
   ```sql
   SELECT sr.*, u.full_name, u.username 
   FROM service_requests sr 
   LEFT JOIN users u ON sr.user_id = u.id 
   WHERE sr.id = 27;
   ```

2. **User Nguyễn văn Tín Details:**
   ```sql
   SELECT id, username, full_name, email, role 
   FROM users 
   WHERE full_name LIKE '%Nguyễn văn Tín%';
   ```

3. **Assignment Check:**
   ```sql
   SELECT assigned_to FROM service_requests WHERE id = 27;
   ```

### Potential Scenarios:
1. **Accidental Assignment:** User was mistakenly assigned to the request
2. **Role Misconfiguration:** User has staff/admin role instead of 'user'
3. **Session Hijacking:** User session compromised
4. **API Bypass:** User found alternative access method (now fixed)

## Files Modified
- `api/comments.php` - Fixed access control logic

## Security Verification Steps
1. Verify request ownership in database
2. Check user roles and assignments
3. Review application logs for access patterns
4. Test all API endpoints with regular user accounts

## Immediate Actions Required
1. **Database Investigation:** Run the security-investigation.php script
2. **User Role Verification:** Ensure Nguyễn văn Tín has correct 'user' role
3. **Assignment Audit:** Check if user was incorrectly assigned to request 27
4. **Log Review:** Check access logs for unusual activity

## Prevention Measures
1. **Role-Based Access Control:** Strict enforcement of user roles
2. **Assignment Restrictions:** Only staff/admin can assign requests
3. **Audit Logging:** Log all access attempts to sensitive data
4. **Regular Security Audits:** Periodic checks of access control logic

## Risk Assessment
**Severity:** HIGH
- Unauthorized access to user data
- Potential data breach
- Privacy violation

**Impact:** MEDIUM - Limited to single request access
**Likelihood:** LOW - Requires specific conditions or misconfiguration

## Status
✅ **Vulnerability Fixed**
⏳ **Database Investigation Required**
⏳ **User Access Audit Needed**
