# Quy trình Xử lý Yêu cầu Hỗ trợ

## Tổng quan
Hệ thống cho phép staff tạo yêu cầu hỗ trợ khi cần thêm resources, và admin sẽ phê duyệt hoặc từ chối các yêu cầu này.

## Flow của Yêu cầu Hỗ trợ

### 1. Staff tạo Yêu cầu Hỗ trợ
- **Điều kiện**: Staff chỉ có thể tạo yêu cầu hỗ trợ cho các service request đang ở trạng thái `in_progress` và được giao cho họ
- **Loại hỗ trợ**: 
  - `equipment` - Thiết bị
  - `person` - Nhân sự  
  - `department` - Bộ phận khác
- **Trạng thái service request**: Tự động chuyển từ `in_progress` → `request_support`

### 2. Admin xử lý Yêu cầu Hỗ trợ
- **Chỉ Admin** mới có quyền xử lý (phê duyệt/từ chối)
- Admin có thể xem tất cả các yêu cầu hỗ trợ trong dashboard

---

## Khi PHÊ DUYỆT (Approved)

### Quy trình xử lý:
1. **Cập nhật Support Request**:
   - `status`: `pending` → `approved`
   - `admin_reason`: Lý do phê duyệt
   - `processed_by`: ID của admin
   - `processed_at`: Thời gian xử lý

2. **Cập nhật Service Request**:
   - `status`: `request_support` → `in_progress`
   - `assigned_to`: Chuyển cho admin đã phê duyệt
   - Request tiếp tục được xử lý bởi admin

3. **Gửi Notifications**:
   - **Tất cả Staff & Admin**: 
     - Title: "Yêu cầu hỗ trợ #X đã được duyệt"
     - Message: "Yêu cầu hỗ trợ từ [tên staff] đã được duyệt. Lý do: [lý do]"
     - Type: `success`
   - **Staff tạo request**: Cùng thông báo trên

### Kết quả:
- ✅ Yêu cầu được duyệt, Request chuyển sang trạng thái in_progress kèm với thông tin phê duyệt từ admin.
- ✅ Staff được thông báo về việc phê duyệt
- ✅ Request được staff xử lý tiếp.

---

## Khi TỪ CHỐI (Rejected)

### Quy trình xử lý:
1. **Cập nhật Support Request**:
   - `status`: `pending` → `rejected`
   - `admin_reason`: Lý do từ chối
   - `processed_by`: ID của admin
   - `processed_at`: Thời gian xử lý

2. **Cập nhật Service Request**:
   - `status`: `request_support` → `in_progress`
   - Request tiếp tục được xử lý bởi staff kèm với thông tin từ chối từ admin.

3. **Gửi Notifications**:
   - **Tất cả Staff & Admin**:
     - Title: "Yêu cầu hỗ trợ #X đã được từ chối"  
     - Message: "Yêu cầu hỗ trợ từ [tên staff] đã được từ chối. Lý do: [lý do]"
     - Type: `warning`
   - **Staff tạo request**: Cùng thông báo trên

### Kết quả:
- ❌ Yêu cầu bị từ chối
- Service request tiếp tục được xử lý bởi staff kèm với thông tin từ chối từ admin.
- ⚠️ Staff được thông báo về việc từ chối và lý do

---

## Interface cho Admin

### Modal "Xử lý yêu cầu hỗ trợ":
- **Hiển thị chi tiết**: Loại hỗ trợ, chi tiết, lý do từ staff
- **Select quyết định**: 
  - Phê duyệt (approved)
  - Từ chối (rejected)
- **Textarea lý do**: Bắt buộc nhập lý do
- **Buttons**: Xác nhận quyết định / Đóng

### API Endpoint:
```
PUT api/support_requests.php
Body: {
  "id": support_id,
  "decision": "approved" | "rejected", 
  "reason": "Lý do xử lý"
}
```

---

## Quyền hạn

| Role | Create | View | Process | Delete |
|------|--------|------|---------|--------|
| User | ❌ | ❌ | ❌ | ❌ |
| Staff | ✅ | ✅ (của mình) | ❌ | ❌ |
| Admin | ❌ | ✅ (tất cả) | ✅ | ✅ |

---

## Status Flow

```
Staff: in_progress → request_support
                    ↓
Admin: pending → approved → in_progress (tiếp tục cho staff)
       ↓
       rejected → in_progress (tiếp tục cho staff)
```

---

## Error Handling

- **Không tìm thấy**: Support request không tồn tại hoặc đã được xử lý
- **Access denied**: Chỉ admin mới được xử lý
- **Invalid decision**: Chỉ chấp nhận approved/rejected
- **Missing fields**: ID, decision, reason là bắt buộc

---

## Database Changes

### Support Requests Table:
- `status`: pending/approved/rejected
- `admin_reason`: Lý do từ admin
- `processed_by`: ID admin xử lý
- `processed_at`: Thời gian xử lý

### Service Requests Table:
- `status`: Luôn chuyển về `in_progress` sau khi xử lý support request
- `assigned_to`: Giữ nguyên staff hiện tại (không thay đổi)
