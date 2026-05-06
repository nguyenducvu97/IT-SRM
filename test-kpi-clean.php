<?php
// Include required files
require_once 'config/session.php';
require_once 'config/database.php';

// Start session
startSession();

// Simulate admin login
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'admin';
$_SESSION['full_name'] = 'Administrator';

// Simulate POST data
$_POST['start_date'] = '2026-04-01';
$_POST['end_date'] = '2026-05-30';
$_SERVER['REQUEST_METHOD'] = 'POST';

// Include and execute KPI Export API
include 'api/kpi_export.php';
?>
