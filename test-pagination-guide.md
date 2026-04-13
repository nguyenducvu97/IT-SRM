# Pagination Testing Guide - 9 Yêu Câu/Trang

## 1. Quick Test
Visit: `test-all-filters-pagination.php`
- Test all filters automatically
- Check if each filter returns max 9 requests
- Verify pagination data

## 2. Manual Testing Steps

### A. Clear Browser Cache
- Press **Ctrl+F5** to force reload
- Check JavaScript version: **v=20260413-4**

### B. Open Browser Console
- Press **F12** to open Developer Tools
- Go to **Console** tab
- Look for debug messages starting with `=== PAGINATION DEBUG ===`

### C. Test Each Filter

#### 1. Status Filters
- **M** (Open)
- **ang xl** (In Progress) 
- **a giai** (Resolved)
- **u choi** (Rejected)
- **ong** (Closed)

#### 2. Priority Filters
- **Cao** (High)
- **Trung binh** (Medium)
- **Thap** (Low)

#### 3. Category Filters
- Test different categories

#### 4. Search Function
- Type any search term
- Wait 500ms for auto-search
- Check pagination

#### 5. No Filter
- Clear all filters
- Should show 9 requests per page

### D. Expected Console Output
```
=== PAGINATION DEBUG ===
Page: 1
Search: ""
Status: "in_progress"
Priority: ""
Category: ""
Requests count: 9
Pagination data: {page: 1, limit: 9, total: 33, total_pages: 4}
```

### E. Expected UI Behavior
- **Max 9 requests** per page
- **Previous/Next buttons** work correctly
- **Page numbers** show correct pages
- **Different pages** show different requests

## 3. Verification Checklist

### API Response
- [ ] All API calls include `limit=9`
- [ ] Response includes pagination data
- [ ] Total pages calculated correctly

### JavaScript
- [ ] Console shows debug info
- [ ] Requests count <= 9
- [ ] Pagination buttons work

### UI
- [ ] 9 requests displayed max
- [ ] Navigation works
- [ ] Filters work with pagination

## 4. Troubleshooting

### If Still Shows All Requests
1. Check server logs for pagination debug
2. Verify API URL includes `limit=9`
3. Check console for correct request count

### If Navigation Not Working
1. Check `displayPagination()` function
2. Verify page detection logic
3. Check onclick handlers

### If Search Not Working
1. Check search API endpoint
2. Verify search parameters
3. Check search API pagination

## 5. Files Modified
- `assets/js/app.js` - Enhanced pagination logic
- `api/service_requests.php` - Fixed limit logic
- `api/search_requests.php` - Added limit parameter
- `index.html` - Updated version

## 6. Expected Results
- **9 requests per page** for ALL filters
- **Consistent pagination** across all features
- **Proper navigation** between pages
- **Search with pagination** working
