# 🧪 Staff Accept Request Functionality Test Summary

## 📋 Overview
This document provides comprehensive testing procedures for the staff accept request functionality in the IT Service Request system.

## 🎯 Functionality Description
Staff members can accept open service requests, which:
- Assigns the request to the staff member
- Changes status from 'open' to 'in_progress'
- Sends notifications to the requester and admins
- Updates timestamps for assignment and acceptance

## 📁 Test Files Created

### 1. `test-staff-accept.php`
**Purpose**: Complete staff acceptance flow with UI
**Features**:
- Shows available requests for acceptance
- Simulates staff login
- Tests actual database updates
- Tests notification system
- Provides detailed debugging information

**Usage**: Visit in browser to test complete flow

### 2. `check-open-requests.php`
**Purpose**: Quick check for available requests
**Features**:
- Lists open, unassigned requests
- Shows request status summary
- Simple table format

**Usage**: Quick reference to see what requests can be accepted

### 3. `test-accept-api.php`
**Purpose**: Direct API testing
**Features**:
- Simulates staff session
- Tests API endpoint directly
- Shows request before/after states
- Tests notification system
- Comprehensive error handling

**Usage**: Test API logic without UI

### 4. `test-complete-accept-flow.php`
**Purpose**: End-to-end testing
**Features**:
- Step-by-step testing process
- Authentication simulation
- Request availability checking
- Database update verification
- Notification testing
- JavaScript flow documentation

**Usage**: Complete functionality verification

### 5. `test-accept-javascript.html`
**Purpose**: Frontend JavaScript testing
**Features**:
- Tests JavaScript function loading
- Simulates button clicks
- Tests API calls from frontend
- Console output for debugging
- Manual test links

**Usage**: Test frontend JavaScript functionality

## 🔍 Test Scenarios

### ✅ Scenario 1: Happy Path
1. Staff logs into system
2. Views open request details
3. Clicks "Nhận yêu cầu" button
4. Request is assigned to staff
5. Status changes to 'in_progress'
6. Notifications are sent
7. Page reloads with updated status

### ❌ Scenario 2: Already Assigned Request
1. Staff tries to accept already assigned request
2. System shows error message
3. Request remains unchanged

### ❌ Scenario 3: Invalid Request ID
1. Staff tries to accept non-existent request
2. System shows "Request not found" error
3. No changes made to database

### ❌ Scenario 4: Unauthorized Access
1. Regular user tries to accept request
2. System shows "Access denied" error
3. No changes made to database

## 🛠️ Technical Implementation

### Backend API (`api/service_requests.php`)
```php
// Endpoint: PUT api/service_requests.php
// Action: accept_request
// Required fields: action, request_id
// Response: {success: true/false, message: "..."}

// Database Update:
UPDATE service_requests 
SET assigned_to = :user_id, 
    status = 'in_progress', 
    assigned_at = NOW(), 
    accepted_at = NOW(), 
    updated_at = NOW() 
WHERE id = :request_id
```

### Frontend JavaScript (`assets/js/request-detail.js`)
```javascript
// Function: acceptRequest(id)
// Location: Line 14835
// Trigger: onclick="requestDetailApp.acceptRequest({request.id})"

// API Call:
PUT api/service_requests.php
{
    "action": "accept_request",
    "request_id": 123
}
```

### Notification System
- **User Notification**: Request is now in progress
- **Admin Notification**: Status change notification
- **Email**: Optional email to requester

## 📊 Expected Results

### Successful Acceptance
- ✅ Request status: 'open' → 'in_progress'
- ✅ Assigned to: Staff member ID
- ✅ Timestamps: assigned_at, accepted_at set
- ✅ Notifications sent to user and admins
- ✅ UI shows updated status
- ✅ Button disappears (request no longer available)

### Error Conditions
- ❌ Clear error messages for invalid requests
- ❌ No database changes on errors
- ❌ Appropriate HTTP status codes
- ❌ Detailed logging for debugging

## 🚀 How to Test

### Quick Test
1. Visit `test-complete-accept-flow.php`
2. Follow step-by-step instructions
3. Verify all components work

### Comprehensive Test
1. Visit `test-staff-accept.php`
2. Test with actual open requests
3. Verify database changes
4. Check notifications

### JavaScript Test
1. Visit `test-accept-javascript.html`
2. Load JavaScript components
3. Run API simulation tests
4. Check console output

### Manual Test
1. Login as staff user
2. Go to `request-detail.html?id=[open_request_id]`
3. Click "Nhận yêu cầu" button
4. Verify success message and page reload

## 🔧 Troubleshooting

### Common Issues
1. **Button not visible**: Check if request is open and unassigned
2. **API returns 401**: Check authentication and session
3. **No notifications**: Check ServiceRequestNotificationHelper
4. **Database errors**: Check table structure and permissions

### Debug Steps
1. Check browser console for JavaScript errors
2. Verify network requests in browser dev tools
3. Check PHP error logs
4. Test with `test-accept-api.php` for isolated testing

## 📝 Test Checklist

- [ ] Staff can see "Nhận yêu cầu" button for open requests
- [ ] Button is hidden for assigned/closed requests
- [ ] Clicking button shows loading state
- [ ] API call succeeds with proper data
- [ ] Database is updated correctly
- [ ] Notifications are sent
- [ ] Page reloads with updated status
- [ ] Error handling works for invalid requests
- [ ] Access control prevents unauthorized users
- [ ] All timestamps are set correctly

## 🎯 Success Criteria

The staff accept request functionality is working correctly when:
1. Staff can successfully accept open, unassigned requests
2. Request status and assignment are updated in database
3. Appropriate notifications are sent
4. UI reflects changes immediately
5. Error conditions are handled gracefully
6. Access control is enforced

---

**Last Updated**: April 22, 2026
**Test Coverage**: Complete end-to-end functionality
**Status**: Ready for testing
