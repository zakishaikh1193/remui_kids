<?php
/**
 * Simple AJAX Test - Test the AJAX endpoint directly
 */

echo "<h2>Simple AJAX Test</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .result { background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 10px 0; }
    .success { background: #d4edda; padding: 10px; border-radius: 5px; margin: 5px 0; }
    .error { background: #f8d7da; padding: 10px; border-radius: 5px; margin: 5px 0; }
    .json { background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; white-space: pre-wrap; }
</style>";

// Test the AJAX endpoint
$ajax_url = '$CFG->wwwroot/test_ajax.php';

echo "<div class='result'>";
echo "<h3>Testing AJAX Endpoint</h3>";
echo "<p><strong>URL:</strong> $ajax_url</p>";

// Use file_get_contents to test the endpoint
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'timeout' => 10
    ]
]);

$response = @file_get_contents($ajax_url, false, $context);

if ($response !== false) {
    echo "<div class='success'>";
    echo "<h4>✅ AJAX Endpoint Response</h4>";
    echo "<div class='json'>" . htmlspecialchars($response) . "</div>";
    echo "</div>";
    
    // Try to parse JSON
    $data = json_decode($response, true);
    if ($data) {
        echo "<div class='success'>";
        echo "<h4>✅ JSON Parsed Successfully</h4>";
        echo "<p><strong>Status:</strong> " . ($data['status'] ?? 'unknown') . "</p>";
        echo "<p><strong>Total Schools:</strong> " . ($data['total_schools'] ?? 'unknown') . "</p>";
        echo "<p><strong>Total Courses:</strong> " . ($data['total_courses'] ?? 'unknown') . "</p>";
        echo "<p><strong>Total Students:</strong> " . ($data['total_students'] ?? 'unknown') . "</p>";
        echo "<p><strong>Timestamp:</strong> " . ($data['timestamp'] ?? 'unknown') . "</p>";
        echo "</div>";
    } else {
        echo "<div class='error'>";
        echo "<h4>❌ JSON Parse Failed</h4>";
        echo "<p>Response is not valid JSON.</p>";
        echo "</div>";
    }
} else {
    echo "<div class='error'>";
    echo "<h4>❌ AJAX Endpoint Not Accessible</h4>";
    echo "<p>Could not reach the AJAX endpoint. This might be due to:</p>";
    echo "<ul>";
    echo "<li>Web server not running</li>";
    echo "<li>Incorrect URL path</li>";
    echo "<li>File permissions issue</li>";
    echo "<li>PHP errors in the endpoint</li>";
    echo "</ul>";
    echo "</div>";
}

echo "</div>";

// Also test with JavaScript fetch
echo "<div class='result'>";
echo "<h3>JavaScript Fetch Test</h3>";
echo "<button onclick='testAjax()' style='background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>Test AJAX with JavaScript</button>";
echo "<div id='ajax-result' style='margin-top: 10px;'></div>";
echo "</div>";

?>

<script>
function testAjax() {
    const resultDiv = document.getElementById('ajax-result');
    resultDiv.innerHTML = '<p>Testing AJAX...</p>';
    
    fetch('<?php echo $ajax_url; ?>')
        .then(response => {
            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }
            return response.text();
        })
        .then(text => {
            resultDiv.innerHTML = '<div style="background: #d4edda; padding: 10px; border-radius: 5px;"><h4>✅ JavaScript Fetch Success</h4><pre style="background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; white-space: pre-wrap;">' + text + '</pre></div>';
            
            try {
                const data = JSON.parse(text);
                resultDiv.innerHTML += '<div style="background: #d4edda; padding: 10px; border-radius: 5px; margin-top: 10px;"><h4>✅ Parsed Data</h4><p><strong>Students:</strong> ' + (data.total_students || 'unknown') + '</p><p><strong>Status:</strong> ' + (data.status || 'unknown') + '</p></div>';
            } catch (e) {
                resultDiv.innerHTML += '<div style="background: #f8d7da; padding: 10px; border-radius: 5px; margin-top: 10px;"><h4>❌ JSON Parse Error</h4><p>' + e.message + '</p></div>';
            }
        })
        .catch(error => {
            resultDiv.innerHTML = '<div style="background: #f8d7da; padding: 10px; border-radius: 5px;"><h4>❌ JavaScript Fetch Error</h4><p>' + error.message + '</p></div>';
        });
}
</script>



