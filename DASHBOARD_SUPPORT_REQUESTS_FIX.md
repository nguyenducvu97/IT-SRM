# Dashboard Support Requests Fix Summary

## Problem
Ở role staff: dashboard: Mục Yêu cầu gần đây không có hiển thị yêu cầu ở trạng thái yêu cầu hỗ trợ (request_support).

## Root Cause Analysis
1. **Database có support requests**: Có 1 yêu cầu hỗ trợ (ID: 27, Title: "Lỗi màn hình đen")
2. **API hoạt động đúng**: API `service_requests.php` trả về đúng dữ liệu cho staff (có thể thấy tất cả requests)
3. **Vấn đề ở logic hiển thị**: Dashboard chỉ lấy 5 requests gần nhất theo created_at, không ưu tiên theo status

### Chi tiết:
- Support request (ID: 27) được tạo ngày 2026-03-07
- Top 5 requests gần nhất đều từ 2026-03-09 trở về sau
- Do đó, support request không xuất hiện trong "Yêu cầu gần đây"

## Solution
Modified `assets/js/app.js` - `loadDashboard()` method:

### Changes Made:
1. **Tăng limit từ 5 lên 10**: Lấy nhiều dữ liệu hơn để có thể ưu tiên
2. **Thêm logic ưu tiên support requests**:
   ```javascript
   // Prioritize support requests in recent requests
   const supportRequests = recentRequests.filter(r => r.status === 'request_support');
   const otherRequests = recentRequests.filter(r => r.status !== 'request_support');
   
   // Put support requests first, then other requests, limit to 5 total
   recentRequests = [...supportRequests, ...otherRequests].slice(0, 5);
   ```

### How It Works Now:
1. Lấy 10 requests gần nhất từ API
2. Phân tách support requests và other requests
3. Đưa support requests lên đầu danh sách
4. Giới hạn lại chỉ hiển thị 5 requests tổng cộng
5. Kết quả: Support requests sẽ luôn xuất hiện đầu tiên nếu có

## Files Modified
- `assets/js/app.js` - Updated `loadDashboard()` method

## Expected Result
✅ Staff dashboard sẽ hiển thị support requests trong mục "Yêu cầu gần đây"
✅ Support requests được ưu tiên hiển thị đầu tiên
✅ Vẫn giữ nguyên giới hạn 5 requests tổng cộng
✅ Các status khác vẫn hiển thị bình thường sau support requests

## Testing
- Database có 1 support request (ID: 27)
- Dashboard sẽ ưu tiên hiển thị request này đầu tiên
- 4 requests còn lại là các requests gần nhất khác
