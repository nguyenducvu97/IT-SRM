<?php
// Force session reset
session_start();
session_destroy();
session_regenerate_id(true);

// Start fresh session
session_start();

echo "<h2>Session Reset Complete</h2>";
echo "<p>Old session destroyed, new session created</p>";
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p><a href='index.html'>Go to main app</a></p>";
?>
