<?php
// Test CSV upload functionality
require_once('../../../config.php');

echo "<h1>CSV Upload Test</h1>";

// Test 1: Check PHP upload settings
echo "<h2>PHP Upload Settings</h2>";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "<br>";
echo "post_max_size: " . ini_get('post_max_size') . "<br>";
echo "max_file_uploads: " . ini_get('max_file_uploads') . "<br>";
echo "file_uploads: " . (ini_get('file_uploads') ? 'Enabled' : 'Disabled') . "<br>";
echo "upload_tmp_dir: " . (ini_get('upload_tmp_dir') ?: 'Default') . "<br>";

// Test 2: Check if we can create a test CSV
echo "<h2>Test CSV Creation</h2>";
$test_csv = "username,email,firstname,lastname,password\n";
$test_csv .= "test.user,test@example.com,Test,User,password123\n";

$test_file = tempnam(sys_get_temp_dir(), 'test_csv_');
file_put_contents($test_file, $test_csv);

echo "Test CSV created: " . $test_file . "<br>";
echo "File size: " . filesize($test_file) . " bytes<br>";
echo "File readable: " . (is_readable($test_file) ? 'Yes' : 'No') . "<br>";

// Test 3: Test CSV parsing
echo "<h2>CSV Parsing Test</h2>";
$handle = fopen($test_file, 'r');
if ($handle) {
    $headers = fgetcsv($handle);
    echo "Headers: " . print_r($headers, true) . "<br>";
    
    $row = fgetcsv($handle);
    echo "First row: " . print_r($row, true) . "<br>";
    
    fclose($handle);
} else {
    echo "Could not open test file<br>";
}

// Test 4: Test form data simulation
echo "<h2>Form Data Test</h2>";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_file'])) {
    echo "File uploaded successfully<br>";
    echo "File info: " . print_r($_FILES['test_file'], true) . "<br>";
} else {
    echo "<form method='post' enctype='multipart/form-data'>";
    echo "<input type='file' name='test_file' accept='.csv'>";
    echo "<input type='submit' value='Test Upload'>";
    echo "</form>";
}

// Cleanup
if (file_exists($test_file)) {
    unlink($test_file);
}

echo "<h2>Test Complete</h2>";
?>
