<?php
// Test KPI Formulas
echo "<h2>📊 Test Công thức KPI</h2>";

// Test K1 Formula
echo "<h3>📈 K1 - Tốc độ phản hồi</h3>";
echo "<p>Công thức: =MAX(1; MIN(5; 5 - (L2/30)))</p>";
echo "<table class='table table-bordered'>";
echo "<tr><th>L2 (phút)</th><th>5 - (L2/30)</th><th>MIN(5; result)</th><th>MAX(1; result)</th><th>Điểm K1</th></tr>";

$test_times = [5, 15, 30, 60, 90, 120];
foreach ($test_times as $l2) {
    $formula_result = 5 - ($l2 / 30);
    $min_result = min(5, $formula_result);
    $k1_score = max(1, $min_result);
    echo "<tr><td>$l2</td><td>" . number_format($formula_result, 2) . "</td><td>" . number_format($min_result, 2) . "</td><td>" . number_format(max(1, $min_result), 2) . "</td><td><strong>$k1_score</strong></td></tr>";
}
echo "</table>";

// Test K2 Formula  
echo "<h3>⏱️ K2 - Tiến độ hoàn thành</h3>";
echo "<p>Công thức: =MAX(1; MIN(5; 5 - (R2/1.2)))</p>";
echo "<table class='table table-bordered'>";
echo "<tr><th>R2 (tỷ lệ)</th><th>5 - (R2/1.2)</th><th>MIN(5; result)</th><th>MAX(1; result)</th><th>Điểm K2</th></tr>";

$test_ratios = [0.5, 0.8, 0.9, 1.0, 1.1, 1.2, 1.5];
foreach ($test_ratios as $r2) {
    $formula_result = 5 - ($r2 / 1.2);
    $min_result = min(5, $formula_result);
    $k2_score = max(1, $min_result);
    echo "<tr><td>" . number_format($r2, 2) . "</td><td>" . number_format($formula_result, 2) . "</td><td>" . number_format($min_result, 2) . "</td><td>" . number_format(max(1, $min_result), 2) . "</td><td><strong>$k2_score</strong></td></tr>";
}
echo "</table>";

// Test K3 Formula
echo "<h3>⭐ K3 - Đánh giá chung</h3>";
echo "<p>Công thức: =MAX(1; MIN(5; N2))</p>";
echo "<table class='table table-bordered'>";
echo "<tr><th>N2 (đánh giá)</th><th>MIN(5; N2)</th><th>MAX(1; result)</th><th>Điểm K3</th></tr>";

$test_ratings = [1, 2, 3, 4, 5];
foreach ($test_ratings as $n2) {
    $min_result = min(5, $n2);
    $k3_score = max(1, $min_result);
    echo "<tr><td>$n2</td><td>$min_result</td><td>" . max(1, $min_result) . "</td><td><strong>$k3_score</strong></td></tr>";
}
echo "</table>";

// Test K4 Formula
echo "<h3>👍 K4 - Chất lượng xử lý</h3>";
echo "<p>Công thức: =MAX(1; MIN(5; O2/20))</p>";
echo "<table class='table table-bordered'>";
echo "<tr><th>O2 (%)</th><th>O2/20</th><th>MIN(5; result)</th><th>MAX(1; result)</th><th>Điểm K4</th></tr>";

$test_percentages = [0, 20, 40, 60, 80, 100];
foreach ($test_percentages as $o2) {
    $formula_result = $o2 / 20;
    $min_result = min(5, $formula_result);
    $k4_score = max(1, $min_result);
    echo "<tr><td>$o2%</td><td>" . number_format($formula_result, 2) . "</td><td>" . number_format($min_result, 2) . "</td><td>" . number_format(max(1, $min_result), 2) . "</td><td><strong>$k4_score</strong></td></tr>";
}
echo "</table>";

// Test Total KPI Formula
echo "<h3>🎯 Công thức KPI tổng hợp</h3>";
echo "<p>Công thức: =(P2*0.15)+(Q2*0.35)+(R2*0.40)+(S2*0.10)</p>";
echo "<table class='table table-bordered'>";
echo "<tr><th>K1</th><th>K2</th><th>K3</th><th>K4</th><th>KPI Tổng</th></tr>";

$test_kpi_combinations = [
    [1, 1, 1, 1],
    [3, 3, 3, 3], 
    [5, 5, 5, 5],
    [5, 4, 5, 5],
    [3, 5, 4, 3]
];

foreach ($test_kpi_combinations as $combo) {
    list($p2, $q2, $r2, $s2) = $combo;
    $total_kpi = ($p2 * 0.15) + ($q2 * 0.35) + ($r2 * 0.40) + ($s2 * 0.10);
    echo "<tr><td>$p2</td><td>$q2</td><td>$r2</td><td>$s2</td><td><strong>" . number_format($total_kpi, 2) . "</strong></td></tr>";
}
echo "</table>";

echo "<style>
table { border-collapse: collapse; width: 100%; margin: 10px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }
th { background-color: #f2f2f2; }
</style>";
?>
