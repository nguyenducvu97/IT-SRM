# 🚨 HƯỚNG DẪN CLEAR CACHE CHO STAFF

## VẤN ĐỀ:
Yêu cầu #132 được nhận nhưng không gửi thông báo do browser đang load JavaScript cũ.

## GIẢI PHÁP:

### CÁCH 1: TRUY CẬP LINK TỰ ĐỘNG
```
http://localhost/it-service-request/force-cache-clear.html
```

### CÁCH 2: MANUAL CLEAR CACHE
**Chrome/Edge:**
1. Nhấn `Ctrl + Shift + Delete`
2. Chọn "All time"
3. Tích "Cached images and files"
4. Nhấn "Clear data"

**Firefox:**
1. Nhấn `Ctrl + Shift + Delete`
2. Chọn "Everything"
3. Tích "Cache"
4. Nhấn "Clear Now"

### CÁCH 3: HARD REFRESH
- Nhấn `Ctrl + F5` hoặc `Ctrl + Shift + R`
- Hoặc `Shift + F5`

## KIỂM TRA SAU KHI CLEAR CACHE:
1. Login lại vào hệ thống
2. Nhận một yêu cầu mới
3. Kiểm tra có thông báo không

## KẾT QUẢ MONG ĐỢI:
- ✅ Staff nhận yêu cầu
- ✅ User nhận thông báo
- ✅ Admin nhận thông báo
- ✅ Debug logs xuất hiện

## LIÊN HỆ:
Nếu vẫn không hoạt động, liên hệ admin để kiểm tra logs.

---
*Ngày: 13/04/2026*
*Version: 20260413-1*
