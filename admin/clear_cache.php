<?php
// Clear cache and test upload functionality
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

echo "<h1>Cache Clear Test</h1>";
echo "<p>Current time: " . date('Y-m-d H:i:s') . "</p>";
echo "<p>Cache headers set successfully</p>";

// Test if we can access the upload script
echo "<h2>Testing Upload Script Access</h2>";
$upload_url = 'upload_users.php?action=get_companies&t=' . time();
echo "<p>Test URL: <a href='$upload_url' target='_blank'>$upload_url</a></p>";

// Test file upload simulation
echo "<h2>File Upload Test</h2>";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_file'])) {
    echo "<p>File uploaded successfully:</p>";
    echo "<pre>" . print_r($_FILES['test_file'], true) . "</pre>";
} else {
    echo "<form method='post' enctype='multipart/form-data'>";
    echo "<input type='file' name='test_file' accept='.csv'>";
    echo "<input type='submit' value='Test File Upload'>";
    echo "</form>";
}

echo "<h2>PHP Settings</h2>";
echo "<p>upload_max_filesize: " . ini_get('upload_max_filesize') . "</p>";
echo "<p>post_max_size: " . ini_get('post_max_size') . "</p>";
echo "<p>max_execution_time: " . ini_get('max_execution_time') . "</p>";
echo "<p>memory_limit: " . ini_get('memory_limit') . "</p>";
?>
