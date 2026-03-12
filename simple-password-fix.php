<?php
// Simple password fix
echo "Creating new admin password hash...<br>";

$new_hash = password_hash('admin', PASSWORD_DEFAULT);
echo "New hash: $new_hash<br>";

// Save to file for manual copy
file_put_contents('admin_hash.txt', $new_hash);
echo "Hash saved to admin_hash.txt<br>";

echo "<br>Manual SQL command:<br>";
echo "UPDATE users SET password_hash = '$new_hash' WHERE username = 'admin';<br>";

// Test verification
echo "<br>Testing verification:<br>";
echo "password_verify('admin', '$new_hash') = " . (password_verify('admin', $new_hash) ? 'TRUE' : 'FALSE');
?>
