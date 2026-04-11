<?php
// Debug Staff Accept Notification Issue
// This script helps debug why staff acceptance doesn't send notifications

require_once 'config/database.php';
require_once 'config/session.php';

session_start();

echo "<h1>Debug: Staff Accept Notification Issue</h1>";

echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;'>";
echo "<h2>Problem Identified:</h2>";
echo "<p><strong>Issue:</strong> Staff accepts request but no notifications sent to admin and user</p>";
echo "<p><strong>Root Cause:</strong> There are 2 different accept_request endpoints with different notification logic</p>";
echo "</div>";

// Check both accept_request implementations
echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>";
echo "<h3>Two Accept Request Implementations:</h3>";

echo "<div style='display: grid; grid-template-columns: 1fr 1fr; gap: 20px;'>";

echo "<div>";
echo "<h4>GET accept_request (Line 5303):</h4>";
echo "<p><strong>Method:</strong> GET</p>";
echo "<p><strong>Location:</strong> Line 5303-5684</p>";
echo "<p><strong>Notifications:</strong></p>";
echo "<ul>";
echo "<li style='color: green;'>notifyUserRequestInProgress() - Line 5638</li>";
echo "<li style='color: green;'>notifyAdminStatusChange() - Line 5652</li>";
echo "</ul>";
echo "<p style='color: green;'><strong>Status: WORKING</strong></p>";
echo "</div>";

echo "<div>";
echo "<h4>PUT accept_request (Line 6844):</h4>";
echo "<p><strong>Method:</strong> PUT</p>";
echo "<p><strong>Location:</strong> Line 6844-7087</p>";
echo "<p><strong>Notifications:</strong></p>";
echo "<ul>";
echo "<li style='color: green;'>notifyUserRequestInProgress() - Line 7008</li>";
echo "<li style='color: green;'>notifyAdminStatusChange() - Line 7021</li>";
echo "<li style='color: orange;'>notifyStaffAdminApproved() - Line 7038 (WRONG!)</li>";
echo "</ul>";
echo "<p style='color: orange;'><strong>Status: HAS WRONG NOTIFICATION</strong></p>";
echo "</div>";

echo "</div>";
echo "</div>";

// Explain the issue
echo "<div style='background: #fff3cd; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>";
echo "<h3>The Problem:</h3>";
echo "<p><strong>PUT accept_request (Line 7038):</strong></p>";
echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 4px; font-size: 12px;'>";
echo "// WRONG! This should not be here
\$notificationHelper->notifyStaffAdminApproved(
    \$request_id, 
    \$request_data['title'], 
    \$request_data['assigned_name']
);";
echo "</pre>";
echo "<p><strong>Why it's wrong:</strong></p>";
echo "<ul>";
echo "<li>notifyStaffAdminApproved() is for when ADMIN approves, not when STAFF accepts</li>";
echo "<li>This sends notification to ALL STAFF (including the accepting staff)</li>";
echo "<li>This creates confusion and wrong notifications</li>";
echo "</ul>";
echo "</div>";

// Check which endpoint is being used
echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>";
echo "<h3>Which Endpoint is Used?</h3>";
echo "<p>Check your frontend code to see which method is called:</p>";
echo "<ul>";
echo "<li>If using <strong>GET /api/service_requests.php?action=accept_request</strong> - Notifications work correctly</li>";
echo "<li>If using <strong>PUT /api/service_requests.php?action=accept_request</strong> - Has wrong notifications</li>";
echo "</ul>";
echo "<p><strong>Most likely:</strong> Your frontend is using PUT method, which has the wrong notification logic</p>";
echo "</div>";

// Solution
echo "<div style='background: #f8d7da; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>";
echo "<h3>Solution:</h3>";
echo "<p><strong>Remove the wrong notification from PUT accept_request:</strong></p>";
echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 4px; font-size: 12px;'>";
echo "// REMOVE THIS (Line 7038)
\$notificationHelper->notifyStaffAdminApproved(
    \$request_id, 
    \$request_data['title'], 
    \$request_data['assigned_name']
);

// KEEP ONLY THESE:
// 1. notifyUserRequestInProgress() - Line 7008
// 2. notifyAdminStatusChange() - Line 7021";
echo "</pre>";
echo "</div>";

// Test current notifications
echo "<div style='background: #28a745; color: white; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>";
echo "<h3>Current Status:</h3>";
echo "<p><strong>Expected notifications when staff accepts request:</strong></p>";
echo "<ol>";
echo "<li><strong>User receives:</strong> \"Yêu yêu #X dang duoc xu ly\"</li>";
echo "<li><strong>Admin receives:</strong> \"Nhan vien [Staff Name] da thay doi trang thai yêu yêu #X tu 'open' thanh 'in_progress'\"</li>";
echo "</ol>";
echo "<p><strong>What's happening:</strong></p>";
echo "<ul>";
echo "<li>If using GET method - Both notifications sent correctly</li>";
echo "<li>If using PUT method - User gets notification, Admin gets notification, BUT also all staff get wrong notification</li>";
echo "</ul>";
echo "</div>";

echo "<div style='background: #6f42c1; color: white; padding: 15px; border-radius: 8px;'>";
echo "<h2>Next Steps:</h2>";
echo "<ol>";
echo "<li>Check frontend code to confirm which method is used</li>";
echo "<li>If PUT method is used, remove the wrong notifyStaffAdminApproved() call</li>";
echo "<li>Test staff acceptance again</li>";
echo "<li>Verify only user and admin get notifications</li>";
echo "</ol>";
echo "</div>";

// Auto-refresh
echo "<script>";
echo "setTimeout(() => { location.reload(); }, 20000);";
echo "</script>";

?>
