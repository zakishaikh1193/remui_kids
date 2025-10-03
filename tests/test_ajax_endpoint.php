<?php
/**
 * Test script to verify AJAX endpoint is working
 */

require_once('../../../config.php');

echo "<h2>Testing AJAX Endpoint</h2>";
echo "<p>Testing: /theme/remui_kids/tests/test_ajax.php</p>";

// Test the AJAX endpoint
$url = $CFG->wwwroot . '/theme/remui_kids/tests/test_ajax.php';

echo "<h3>Testing via cURL:</h3>";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p>HTTP Code: " . $httpCode . "</p>";
echo "<p>Response:</p>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

echo "<h3>Testing via file_get_contents:</h3>";
try {
    $content = file_get_contents($url);
    echo "<p>Response:</p>";
    echo "<pre>" . htmlspecialchars($content) . "</pre>";
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}

echo "<h3>Direct file test:</h3>";
echo "<p>Testing direct file access...</p>";
echo "<p>File exists: " . (file_exists('tests/test_ajax.php') ? 'Yes' : 'No') . "</p>";
echo "<p>File path: " . realpath('tests/test_ajax.php') . "</p>";
?>
