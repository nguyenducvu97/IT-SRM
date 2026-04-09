<?php
echo "<h2>KIỂM TRA TOÀN BỘ SCENARIOS THÔNG BÁO</h2>";

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/lib/ServiceRequestNotificationHelper.php';

$db = getDatabaseConnection();
$notificationHelper = new ServiceRequestNotificationHelper();

echo "<h3>1. USER NOTIFICATIONS - Khi yêu cầu thay đổi trạng thái</h3>";

// Test Case 1: Open -> In Progress (Staff nhận yêu cầu)
echo "<h4>1.1 Open -> In Progress (Staff nhận yêu cầu)</h4>";
echo "<p><strong>Logic:</strong> User nhận thông báo khi staff nhận yêu cầu</p>";

// Tìm một request open
$stmt = $db->prepare("SELECT id, title, user_id FROM service_requests WHERE status = 'open' AND (assigned_to IS NULL OR assigned_to = 0) LIMIT 1");
$stmt->execute();
$openRequest = $stmt->fetch(PDO::FETCH_ASSOC);

if ($openRequest) {
    echo "<p><strong>Test Request:</strong> #{$openRequest['id']} - {$openRequest['title']}</p>";
    
    // Simulate staff nhận yêu cầu
    $staffName = 'John Smith';
    $result1 = $notificationHelper->notifyUserRequestInProgress(
        $openRequest['id'], 
        $openRequest['user_id'], 
        $staffName
    );
    
    echo "<p><strong>notifyUserRequestInProgress():</strong> " . ($result1 ? "✅ SUCCESS" : "❌ FAILED") . "</p>";
    
    if ($result1) {
        // Check notification was created
        $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? AND title LIKE '%đang được xử lý%' ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$openRequest['user_id']]);
        $notif = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($notif) {
            echo "<p><strong>Notification Created:</strong> ✅ {$notif['title']} - {$notif['message']}</p>";
        } else {
            echo "<p><strong>Notification Created:</strong> ❌ NOT FOUND</p>";
        }
    }
} else {
    echo "<p><strong>Test Request:</strong> ❌ No open requests found</p>";
}

// Test Case 2: Any -> Pending Approval
echo "<h4>1.2 Any -> Pending Approval (Admin phê duyệt yêu cầu hỗ trợ)</h4>";
echo "<p><strong>Logic:</strong> User nhận thông báo khi admin phê duyệt yêu cầu hỗ trợ</p>";

// Tìm một request có support request
$stmt = $db->prepare("SELECT sr.id, sr.title, sr.user_id, sr.status FROM service_requests sr 
                     INNER JOIN support_requests sup ON sup.service_request_id = sr.id 
                     WHERE sup.decision = 'approved' LIMIT 1");
$stmt->execute();
$approvedRequest = $stmt->fetch(PDO::FETCH_ASSOC);

if ($approvedRequest) {
    echo "<p><strong>Test Request:</strong> #{$approvedRequest['id']} - {$approvedRequest['title']}</p>";
    
    $result2 = $notificationHelper->notifyUserRequestPendingApproval(
        $approvedRequest['id'], 
        $approvedRequest['user_id']
    );
    
    echo "<p><strong>notifyUserRequestPendingApproval():</strong> " . ($result2 ? "✅ SUCCESS" : "❌ FAILED") . "</p>";
    
    if ($result2) {
        $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? AND title LIKE '%chờ phê duyệt%' ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$approvedRequest['user_id']]);
        $notif = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($notif) {
            echo "<p><strong>Notification Created:</strong> ✅ {$notif['title']} - {$notif['message']}</p>";
        } else {
            echo "<p><strong>Notification Created:</strong> ❌ NOT FOUND</p>";
        }
    }
} else {
    echo "<p><strong>Test Request:</strong> ❌ No approved support requests found</p>";
}

// Test Case 3: Any -> Resolved
echo "<h4>1.3 Any -> Resolved (Staff hoàn thành yêu cầu)</h4>";
echo "<p><strong>Logic:</strong> User nhận thông báo khi staff hoàn thành yêu cầu</p>";

// Tìm một request bất kỳ
$stmt = $db->prepare("SELECT id, title, user_id FROM service_requests LIMIT 1");
$stmt->execute();
$anyRequest = $stmt->fetch(PDO::FETCH_ASSOC);

if ($anyRequest) {
    echo "<p><strong>Test Request:</strong> #{$anyRequest['id']} - {$anyRequest['title']}</p>";
    
    $result3 = $notificationHelper->notifyUserRequestResolved(
        $anyRequest['id'], 
        $anyRequest['user_id']
    );
    
    echo "<p><strong>notifyUserRequestResolved():</strong> " . ($result3 ? "✅ SUCCESS" : "❌ FAILED") . "</p>";
    
    if ($result3) {
        $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? AND title LIKE '%hoàn thành%' ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$anyRequest['user_id']]);
        $notif = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($notif) {
            echo "<p><strong>Notification Created:</strong> ✅ {$notif['title']} - {$notif['message']}</p>";
        } else {
            echo "<p><strong>Notification Created:</strong> ❌ NOT FOUND</p>";
        }
    }
} else {
    echo "<p><strong>Test Request:</strong> ❌ No requests found</p>";
}

// Test Case 4: Any -> Rejected
echo "<h4>1.4 Any -> Rejected (Admin từ chối yêu cầu)</h4>";
echo "<p><strong>Logic:</strong> User nhận thông báo khi admin từ chối yêu cầu</p>";

if ($anyRequest) {
    $result4 = $notificationHelper->notifyUserRequestRejected(
        $anyRequest['id'], 
        $anyRequest['user_id'],
        "Yêu cầu không phù hợp chính sách"
    );
    
    echo "<p><strong>notifyUserRequestRejected():</strong> " . ($result4 ? "✅ SUCCESS" : "❌ FAILED") . "</p>";
    
    if ($result4) {
        $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? AND title LIKE '%từ chối%' ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$anyRequest['user_id']]);
        $notif = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($notif) {
            echo "<p><strong>Notification Created:</strong> ✅ {$notif['title']} - {$notif['message']}</p>";
        } else {
            echo "<p><strong>Notification Created:</strong> ❌ NOT FOUND</p>";
        }
    }
}

echo "<h3>2. STAFF NOTIFICATIONS - Khi user tạo yêu cầu, đánh giá, admin quyết định</h3>";

// Test Case 1: User tạo yêu cầu mới
echo "<h4>2.1 User tạo yêu cầu mới</h4>";
echo "<p><strong>Logic:</strong> Staff nhận thông báo khi user tạo yêu cầu mới</p>";

if ($anyRequest) {
    $result5 = $notificationHelper->notifyStaffNewRequest(
        $anyRequest['id'], 
        $anyRequest['title']
    );
    
    echo "<p><strong>notifyStaffNewRequest():</strong> " . ($result5 ? "✅ SUCCESS" : "❌ FAILED") . "</p>";
    
    if ($result5) {
        $stmt = $db->prepare("SELECT * FROM notifications WHERE title LIKE '%yêu cầu mới%' ORDER BY created_at DESC LIMIT 3");
        $stmt->execute();
        $notifs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($notifs) > 0) {
            echo "<p><strong>Notifications Created:</strong> ✅ " . count($notifs) . " notifications</p>";
            foreach ($notifs as $notif) {
                echo "<p>- User {$notif['user_id']}: {$notif['title']}</p>";
            }
        } else {
            echo "<p><strong>Notifications Created:</strong> ❌ NOT FOUND</p>";
        }
    }
}

// Test Case 2: User đánh giá yêu cầu
echo "<h4>2.2 User đánh giá yêu cầu</h4>";
echo "<p><strong>Logic:</strong> Staff nhận thông báo khi user đánh giá yêu cầu</p>";

if ($anyRequest) {
    $result6 = $notificationHelper->notifyStaffUserFeedback(
        $anyRequest['id'], 
        $anyRequest['user_id'],
        5, // avg_rating
        "Rất tốt, cảm ơn staff đã hỗ trợ nhanh chóng" // feedback
    );
    
    echo "<p><strong>notifyStaffUserFeedback():</strong> " . ($result6 ? "✅ SUCCESS" : "❌ FAILED") . "</p>";
    
    if ($result6) {
        $stmt = $db->prepare("SELECT * FROM notifications WHERE title LIKE '%phản hồi%' ORDER BY created_at DESC LIMIT 3");
        $stmt->execute();
        $notifs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($notifs) > 0) {
            echo "<p><strong>Notifications Created:</strong> ✅ " . count($notifs) . " notifications</p>";
            foreach ($notifs as $notif) {
                echo "<p>- User {$notif['user_id']}: {$notif['title']}</p>";
            }
        } else {
            echo "<p><strong>Notifications Created:</strong> ❌ NOT FOUND</p>";
        }
    }
}

// Test Case 3: Admin phê duyệt yêu cầu
echo "<h4>2.3 Admin phê duyệt yêu cầu</h4>";
echo "<p><strong>Logic:</strong> Staff nhận thông báo khi admin phê duyệt yêu cầu</p>";

if ($anyRequest) {
    $result7 = $notificationHelper->notifyStaffAdminApproved(
        $anyRequest['id'], 
        $anyRequest['title'],
        'Admin'
    );
    
    echo "<p><strong>notifyStaffAdminApproved():</strong> " . ($result7 ? "✅ SUCCESS" : "❌ FAILED") . "</p>";
    
    if ($result7) {
        $stmt = $db->prepare("SELECT * FROM notifications WHERE title LIKE '%phê duyệt%' ORDER BY created_at DESC LIMIT 3");
        $stmt->execute();
        $notifs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($notifs) > 0) {
            echo "<p><strong>Notifications Created:</strong> ✅ " . count($notifs) . " notifications</p>";
            foreach ($notifs as $notif) {
                echo "<p>- User {$notif['user_id']}: {$notif['title']}</p>";
            }
        } else {
            echo "<p><strong>Notifications Created:</strong> ❌ NOT FOUND</p>";
        }
    }
}

// Test Case 4: Admin từ chối yêu cầu
echo "<h4>2.4 Admin từ chối yêu cầu</h4>";
echo "<p><strong>Logic:</strong> Staff nhận thông báo khi admin từ chối yêu cầu</p>";

if ($anyRequest) {
    $result8 = $notificationHelper->notifyStaffAdminRejected(
        $anyRequest['id'], 
        $anyRequest['title'],
        'Admin',
        'Không phù hợp'
    );
    
    echo "<p><strong>notifyStaffAdminRejected():</strong> " . ($result8 ? "✅ SUCCESS" : "❌ FAILED") . "</p>";
    
    if ($result8) {
        $stmt = $db->prepare("SELECT * FROM notifications WHERE title LIKE '%từ chối%' AND user_id != ? ORDER BY created_at DESC LIMIT 3");
        $stmt->execute([$anyRequest['user_id']]);
        $notifs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($notifs) > 0) {
            echo "<p><strong>Notifications Created:</strong> ✅ " . count($notifs) . " notifications</p>";
            foreach ($notifs as $notif) {
                echo "<p>- User {$notif['user_id']}: {$notif['title']}</p>";
            }
        } else {
            echo "<p><strong>Notifications Created:</strong> ❌ NOT FOUND</p>";
        }
    }
}

echo "<h3>3. ADMIN NOTIFICATIONS - Khi user tạo yêu cầu, staff thay đổi, staff gửi yêu cầu</h3>";

// Test Case 1: User tạo yêu cầu mới
echo "<h4>3.1 User tạo yêu cầu mới</h4>";
echo "<p><strong>Logic:</strong> Admin nhận thông báo khi user tạo yêu cầu mới</p>";

if ($anyRequest) {
    $result9 = $notificationHelper->notifyAdminNewRequest(
        $anyRequest['id'], 
        $anyRequest['title'],
        'Test User'
    );
    
    echo "<p><strong>notifyAdminNewRequest():</strong> " . ($result9 ? "✅ SUCCESS" : "❌ FAILED") . "</p>";
    
    if ($result9) {
        $stmt = $db->prepare("SELECT * FROM notifications WHERE title LIKE '%yêu cầu mới%' AND user_id IN (SELECT id FROM users WHERE role = 'admin') ORDER BY created_at DESC LIMIT 3");
        $stmt->execute();
        $notifs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($notifs) > 0) {
            echo "<p><strong>Notifications Created:</strong> ✅ " . count($notifs) . " notifications</p>";
            foreach ($notifs as $notif) {
                echo "<p>- User {$notif['user_id']}: {$notif['title']}</p>";
            }
        } else {
            echo "<p><strong>Notifications Created:</strong> ❌ NOT FOUND</p>";
        }
    }
}

// Test Case 2: Staff thay đổi trạng thái yêu cầu
echo "<h4>3.2 Staff thay đổi trạng thái yêu cầu</h4>";
echo "<p><strong>Logic:</strong> Admin nhận thông báo khi staff thay đổi trạng thái yêu cầu</p>";

if ($anyRequest) {
    $result10 = $notificationHelper->notifyAdminStatusChange(
        $anyRequest['id'], 
        'open', 
        'in_progress', 
        'John Smith', 
        $anyRequest['title']
    );
    
    echo "<p><strong>notifyAdminStatusChange():</strong> " . ($result10 ? "✅ SUCCESS" : "❌ FAILED") . "</p>";
    
    if ($result10) {
        $stmt = $db->prepare("SELECT * FROM notifications WHERE title LIKE '%thay đổi trạng thái%' AND user_id IN (SELECT id FROM users WHERE role = 'admin') ORDER BY created_at DESC LIMIT 3");
        $stmt->execute();
        $notifs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($notifs) > 0) {
            echo "<p><strong>Notifications Created:</strong> ✅ " . count($notifs) . " notifications</p>";
            foreach ($notifs as $notif) {
                echo "<p>- User {$notif['user_id']}: {$notif['title']}</p>";
            }
        } else {
            echo "<p><strong>Notifications Created:</strong> ❌ NOT FOUND</p>";
        }
    }
}

// Test Case 3: Staff gửi yêu cầu hỗ trợ
echo "<h4>3.3 Staff gửi yêu cầu hỗ trợ</h4>";
echo "<p><strong>Logic:</strong> Admin nhận thông báo khi staff gửi yêu cầu hỗ trợ</p>";

if ($anyRequest) {
    $result11 = $notificationHelper->notifyAdminSupportRequest(
        $anyRequest['id'], 
        "Cần hỗ trợ kỹ thuật cho yêu cầu này",
        'John Smith'
    );
    
    echo "<p><strong>notifyAdminSupportRequest():</strong> " . ($result11 ? "✅ SUCCESS" : "❌ FAILED") . "</p>";
    
    if ($result11) {
        $stmt = $db->prepare("SELECT * FROM notifications WHERE title LIKE '%yêu cầu hỗ trợ%' AND user_id IN (SELECT id FROM users WHERE role = 'admin') ORDER BY created_at DESC LIMIT 3");
        $stmt->execute();
        $notifs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($notifs) > 0) {
            echo "<p><strong>Notifications Created:</strong> ✅ " . count($notifs) . " notifications</p>";
            foreach ($notifs as $notif) {
                echo "<p>- User {$notif['user_id']}: {$notif['title']}</p>";
            }
        } else {
            echo "<p><strong>Notifications Created:</strong> ❌ NOT FOUND</p>";
        }
    }
}

// Test Case 4: Staff gửi yêu cầu từ chối
echo "<h4>3.4 Staff gửi yêu cầu từ chối</h4>";
echo "<p><strong>Logic:</strong> Admin nhận thông báo khi staff gửi yêu cầu từ chối</p>";

if ($anyRequest) {
    $result12 = $notificationHelper->notifyAdminRejectionRequest(
        $anyRequest['id'], 
        "Yêu cầu vi phạm chính sách",
        'John Smith'
    );
    
    echo "<p><strong>notifyAdminRejectionRequest():</strong> " . ($result12 ? "✅ SUCCESS" : "❌ FAILED") . "</p>";
    
    if ($result12) {
        $stmt = $db->prepare("SELECT * FROM notifications WHERE title LIKE '%yêu cầu từ chối%' AND user_id IN (SELECT id FROM users WHERE role = 'admin') ORDER BY created_at DESC LIMIT 3");
        $stmt->execute();
        $notifs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($notifs) > 0) {
            echo "<p><strong>Notifications Created:</strong> ✅ " . count($notifs) . " notifications</p>";
            foreach ($notifs as $notif) {
                echo "<p>- User {$notif['user_id']}: {$notif['title']}</p>";
            }
        } else {
            echo "<p><strong>Notifications Created:</strong> ❌ NOT FOUND</p>";
        }
    }
}

echo "<h3>4. TỔNG KẾT KẾT QUẢ</h3>";

$totalTests = 12;
$successCount = 0;

$results = [$result1, $result2, $result3, $result4, $result5, $result6, $result7, $result8, $result9, $result10, $result11, $result12];

foreach ($results as $result) {
    if ($result) $successCount++;
}

$successRate = round(($successCount / $totalTests) * 100, 1);

echo "<div style='background-color: #e8f5e8; padding: 15px; border-radius: 5px;'>";
echo "<h4>&#128204; KẾT QUẢ TEST:</h4>";
echo "<ul>";
echo "<li><strong>Tổng số test:</strong> {$totalTests}</li>";
echo "<li><strong>Thành công:</strong> {$successCount}</li>";
echo "<li><strong>Thất bại:</strong> " . ($totalTests - $successCount) . "</li>";
echo "<li><strong>Tỷ lệ thành công:</strong> {$successRate}%</li>";
echo "</ul>";

if ($successRate == 100) {
    echo "<p style='color: green; font-size: 18px;'><strong>&#10004; TẤT CẢ CÁC FUNCTION THÔNG BÁO ĐỀU HOẠT ĐỘNG HOÀN HẢO!</strong></p>";
    echo "<p><strong>Vấn đề có thể là:</strong></p>";
    echo "<ul>";
    echo "<li>API không gọi function đúng lúc</li>";
    echo "<li>Browser session issue</li>";
    echo "<li>Frontend không hiển thị notifications</li>";
    echo "</ul>";
} else {
    echo "<p style='color: red; font-size: 18px;'><strong>&#10027; CÓ " . ($totalTests - $successCount) . " FUNCTION THÔNG BÁO KHÔNG HOẠT ĐỘNG!</strong></p>";
}
echo "</div>";

echo "<h3>5. KIỂM TRA GIAO DIỆN HIỂN THỊ THÔNG BÁO</h3>";

// Kiểm tra xem frontend có hiển thị notifications không
echo "<p><strong>Check recent notifications in database:</strong></p>";

$stmt = $db->prepare("SELECT COUNT(*) as total FROM notifications");
$stmt->execute();
$count = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<p><strong>Total notifications in database:</strong> {$count['total']}</p>";

if ($count['total'] > 0) {
    $stmt = $db->prepare("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 10");
    $stmt->execute();
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #f0f0f0;'>";
    echo "<th>ID</th><th>User ID</th><th>Title</th><th>Message</th><th>Type</th><th>Created</th>";
    echo "</tr>";
    
    foreach ($notifications as $notif) {
        echo "<tr>";
        echo "<td>{$notif['id']}</td>";
        echo "<td>{$notif['user_id']}</td>";
        echo "<td>" . htmlspecialchars($notif['title']) . "</td>";
        echo "<td>" . htmlspecialchars(substr($notif['message'], 0, 50)) . "...</td>";
        echo "<td>{$notif['type']}</td>";
        echo "<td>{$notif['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p><strong>Nếu database có notifications nhưng frontend không hiển thị:</strong></p>";
    echo "<ul>";
    echo "<li>Kiểm tra JavaScript fetch notifications API</li>";
    echo "<li>Kiểm tra HTML hiển thị notifications</li>";
    echo "<li>Kiểm tra CSS style cho notifications</li>";
    echo "</ul>";
} else {
    echo "<p style='color: red;'><strong>Không có notifications trong database!</strong></p>";
    echo "<p>Đây là vấn đề - các function notifications không được gọi hoặc không hoạt động.</p>";
}
?>
