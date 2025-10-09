<?php
require_once('../../config.php');
require_login();

// Only allow site administrators to clear cache
if (!is_siteadmin()) {
    die('Access denied. Only site administrators can clear cache.');
}

// Clear various Moodle caches
purge_all_caches();

// Clear theme cache specifically
if (method_exists($CFG, 'themedir')) {
    $cachedir = $CFG->dataroot . '/cache/';
    if (is_dir($cachedir)) {
        // Clear theme cache files
        $files = glob($cachedir . '*theme*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
}

// Clear JavaScript cache
$jsdir = $CFG->dataroot . '/cache/js/';
if (is_dir($jsdir)) {
    $files = glob($jsdir . '*');
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
}

echo "<h2>Cache Cleared Successfully!</h2>";
echo "<p>All Moodle caches have been cleared, including:</p>";
echo "<ul>";
echo "<li>Theme cache</li>";
echo "<li>JavaScript cache</li>";
echo "<li>General Moodle cache</li>";
echo "</ul>";
echo "<p><a href='school_manager_dashboard.php'>Go to Dashboard</a></p>";
?>

