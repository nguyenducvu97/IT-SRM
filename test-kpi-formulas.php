<?php
// Test file to add KPI formulas to exportStaffDetails function

echo "Công thức tính KPI cần thêm vào exportStaffDetails:\n";
echo "1. K1 - Tốc độ phản hồi (1-5): =MAX(1; MIN(5; 5 - (L2/30)))\n";
echo "   L2 = Thời gian phản hồi (phút)\n";
echo "2. K2 - Tiến độ hoàn thành (1-5): =MAX(1; MIN(5; 5 - (M2/24)))\n";
echo "   M2 = Thời gian hoàn thành (giờ)\n";
echo "3. K3 - Đánh giá chung (1-5): =MAX(1; MIN(5; N2))\n";
echo "   N2 = Đánh giá chung (1-5)\n";
echo "4. K4 - Chất lượng xử lý (1-5): =MAX(1; MIN(5; O2/20))\n";
echo "   O2 = Đánh giá staff xử lý yêu cầu\n";
echo "5. KPI yêu cầu (1-5): =(P2*0.15)+(Q2*0.35)+(R2*0.40)+(S2*0.10)\n";
echo "   P2=K1(15%), Q2=K2(35%), R2=K3(40%), S2=K4(10%)\n";

echo "\nVị trí cần thêm trong file kpi_export.php:\n";
echo "- Trước dòng 845 (fclose)\n";
echo "- Sau fputcsv các dòng công thức\n";
echo "- Trước fclose và exit\n";

?>
