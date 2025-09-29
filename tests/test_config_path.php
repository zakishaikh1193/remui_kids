<?php
/**
 * Test script to verify config.php path is correct
 */

echo "<h2>Testing Config Path</h2>";

// Test the path
$config_path = '../../../config.php';
echo "<p>Testing path: $config_path</p>";

if (file_exists($config_path)) {
    echo "<p style='color: green;'>✅ Config file found!</p>";
    echo "<p>Full path: " . realpath($config_path) . "</p>";
    
    // Try to include it
    try {
        require_once($config_path);
        echo "<p style='color: green;'>✅ Config file loaded successfully!</p>";
        
        // Test if Moodle is loaded
        if (isset($CFG)) {
            echo "<p style='color: green;'>✅ Moodle configuration loaded!</p>";
            echo "<p>Site name: " . $CFG->fullname . "</p>";
        } else {
            echo "<p style='color: red;'>❌ Moodle configuration not loaded</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error loading config: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Config file not found at: $config_path</p>";
    echo "<p>Current directory: " . getcwd() . "</p>";
    echo "<p>Files in current directory:</p>";
    echo "<pre>" . print_r(scandir('.'), true) . "</pre>";
}
?>
