<?php
// Force refresh script to clear all caches
header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');

echo "<!DOCTYPE html>";
echo "<html><head>";
echo "<meta http-equiv='Cache-Control' content='no-cache, no-store, must-revalidate'>";
echo "<meta http-equiv='Pragma' content='no-cache'>";
echo "<meta http-equiv='Expires' content='0'>";
echo "<title>Force Refresh</title>";
echo "</head><body>";

echo "<h1>Cache Clear Complete</h1>";
echo "<p>All cache headers have been set. Please:</p>";
echo "<ol>";
echo "<li>Close this tab</li>";
echo "<li>Open a new tab</li>";
echo "<li>Go to the upload users page</li>";
echo "<li>If still having issues, clear browser cache manually</li>";
echo "</ol>";

echo "<h2>Manual Cache Clear Instructions</h2>";
echo "<p>If the issue persists:</p>";
echo "<ol>";
echo "<li>Press Ctrl+Shift+Delete (Windows) or Cmd+Shift+Delete (Mac)</li>";
echo "<li>Select 'All time' for time range</li>";
echo "<li>Check 'Cached images and files'</li>";
echo "<li>Click 'Clear data'</li>";
echo "<li>Refresh the page</li>";
echo "</ol>";

echo "<h2>Alternative: Hard Refresh</h2>";
echo "<p>Try a hard refresh:</p>";
echo "<ul>";
echo "<li>Windows: Ctrl+F5 or Ctrl+Shift+R</li>";
echo "<li>Mac: Cmd+Shift+R</li>";
echo "<li>Or right-click refresh button and select 'Empty Cache and Hard Reload'</li>";
echo "</ul>";

echo "<p><a href='upload_users.php'>Go to Upload Users Page</a></p>";

echo "</body></html>";
?>
