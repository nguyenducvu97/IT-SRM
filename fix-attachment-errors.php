<?php
// Fix incomplete error messages in JavaScript files

// Fix app.js
$app_js_content = file_get_contents('assets/js/app.js');
$app_js_content = str_replace(
    '<i class="fas fa-exclamation-triangle"></i> Không hi',
    '<i class="fas fa-exclamation-triangle"></i> Không hi',
    $app_js_content
);
file_put_contents('assets/js/app.js', $app_js_content);

// Fix request-detail.js
$request_detail_js_content = file_get_contents('assets/js/request-detail.js');
$request_detail_js_content = str_replace(
    '<i class="fas fa-exclamation-triangle"></i> Không hi',
    '<i class="fas fa-exclamation-triangle"></i> Không hi',
    $request_detail_js_content
);
file_put_contents('assets/js/request-detail.js', $request_detail_js_content);

echo "Fixed error messages in JavaScript files";
?>
