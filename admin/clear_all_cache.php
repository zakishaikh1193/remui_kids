<?php
// Ultimate cache clearing script
header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0, private');
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('ETag: "' . md5(uniqid()) . '"');

echo "<!DOCTYPE html>";
echo "<html><head>";
echo "<meta http-equiv='Cache-Control' content='no-cache, no-store, must-revalidate'>";
echo "<meta http-equiv='Pragma' content='no-cache'>";
echo "<meta http-equiv='Expires' content='0'>";
echo "<title>Clear All Cache</title>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }";
echo ".container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }";
echo ".success { color: #28a745; font-weight: bold; }";
echo ".warning { color: #ffc107; font-weight: bold; }";
echo ".error { color: #dc3545; font-weight: bold; }";
echo ".btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 5px; }";
echo ".btn:hover { background: #0056b3; }";
echo "</style>";
echo "</head><body>";

echo "<div class='container'>";
echo "<h1>🧹 Ultimate Cache Clear</h1>";

// Clear PHP opcache if available
if (function_exists('opcache_reset')) {
    if (opcache_reset()) {
        echo "<p class='success'>✅ PHP OpCache cleared successfully</p>";
    } else {
        echo "<p class='warning'>⚠️ PHP OpCache reset failed</p>";
    }
} else {
    echo "<p class='warning'>⚠️ PHP OpCache not available</p>";
}

// Clear any session data
if (session_status() === PHP_SESSION_ACTIVE) {
    session_destroy();
    echo "<p class='success'>✅ Session data cleared</p>";
} else {
    echo "<p class='warning'>⚠️ No active session to clear</p>";
}

echo "<h2>Cache Status</h2>";
echo "<p><strong>Current Time:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<p><strong>Server:</strong> " . $_SERVER['SERVER_SOFTWARE'] . "</p>";
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";

echo "<h2>Next Steps</h2>";
echo "<ol>";
echo "<li><strong>Close this tab</strong></li>";
echo "<li><strong>Open a new tab</strong></li>";
echo "<li><strong>Go to the upload users page</strong></li>";
echo "<li><strong>If still having issues, try the manual methods below</strong></li>";
echo "</ol>";

echo "<h2>Manual Cache Clear Methods</h2>";

echo "<h3>Method 1: Browser Cache Clear</h3>";
echo "<p>Press <kbd>Ctrl+Shift+Delete</kbd> (Windows) or <kbd>Cmd+Shift+Delete</kbd> (Mac)</p>";
echo "<ul>";
echo "<li>Select 'All time' for time range</li>";
echo "<li>Check 'Cached images and files'</li>";
echo "<li>Click 'Clear data'</li>";
echo "</ul>";

echo "<h3>Method 2: Hard Refresh</h3>";
echo "<ul>";
echo "<li>Windows: <kbd>Ctrl+F5</kbd> or <kbd>Ctrl+Shift+R</kbd></li>";
echo "<li>Mac: <kbd>Cmd+Shift+R</kbd></li>";
echo "<li>Or right-click refresh button and select 'Empty Cache and Hard Reload'</li>";
echo "</ul>";

echo "<h3>Method 3: Developer Tools</h3>";
echo "<ol>";
echo "<li>Open Developer Tools (F12)</li>";
echo "<li>Right-click the refresh button</li>";
echo "<li>Select 'Empty Cache and Hard Reload'</li>";
echo "</ol>";

echo "<h3>Method 4: Incognito/Private Mode</h3>";
echo "<p>Try opening the page in incognito/private mode to bypass all caches</p>";

echo "<div style='margin-top: 30px;'>";
echo "<a href='upload_users.php' class='btn'>Go to Upload Users Page</a>";
echo "<button onclick='location.reload(true)' class='btn'>Force Reload This Page</button>";
echo "<button onclick='window.close()' class='btn'>Close This Tab</button>";
echo "</div>";

echo "</div>";

echo "<script>";
echo "// Clear any client-side caches";
echo "if ('caches' in window) {";
echo "    caches.keys().then(function(names) {";
echo "        names.forEach(function(name) {";
echo "            caches.delete(name);";
echo "        });";
echo "        console.log('Service Worker caches cleared');";
echo "    });";
echo "}";
echo "</script>";

echo "</body></html>";
?>
