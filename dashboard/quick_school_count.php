<?php
/**
 * Quick School Count Check
 */

require_once('../../../config.php');

global $DB;

echo "<h2>Quick School Count Check</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .result { background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 10px 0; }
    .count { font-size: 24px; font-weight: bold; color: #0066cc; }
</style>";

try {
    // The exact query from your dashboard
    $meaningful_schools = $DB->count_records_sql(
        "SELECT COUNT(*) 
         FROM {course_categories} 
         WHERE visible = 1 
         AND id > 1 
         AND (name NOT LIKE '%Miscellaneous%' 
              AND name NOT LIKE '%Default%' 
              AND name NOT LIKE '%System%'
              AND name NOT LIKE '%General%')
         AND parent = 0",
        []
    );
    
    echo "<div class='result'>";
    echo "<h3>Dashboard School Count</h3>";
    echo "<div class='count'>$meaningful_schools</div>";
    echo "<p>This is the count that appears in your dashboard's 'TOTAL SCHOOLS' card.</p>";
    echo "</div>";
    
    // Also show what categories are being counted
    $school_categories = $DB->get_records_sql(
        "SELECT id, name, description
         FROM {course_categories} 
         WHERE visible = 1 
         AND id > 1 
         AND (name NOT LIKE '%Miscellaneous%' 
              AND name NOT LIKE '%Default%' 
              AND name NOT LIKE '%System%'
              AND name NOT LIKE '%General%')
         AND parent = 0
         ORDER BY name",
        []
    );
    
    if (!empty($school_categories)) {
        echo "<div class='result'>";
        echo "<h3>School Categories Being Counted:</h3>";
        echo "<ul>";
        foreach ($school_categories as $school) {
            echo "<li><strong>{$school->name}</strong> (ID: {$school->id})</li>";
        }
        echo "</ul>";
        echo "</div>";
    } else {
        echo "<div class='result'>";
        echo "<h3>No School Categories Found</h3>";
        echo "<p>This means all your course categories are either:</p>";
        echo "<ul>";
        echo "<li>System categories (Miscellaneous, Default, System, General)</li>";
        echo "<li>Sub-categories (not top-level)</li>";
        echo "<li>Hidden (visible = 0)</li>";
        echo "</ul>";
        echo "</div>";
    }
    
    // Show all categories for debugging
    echo "<div class='result'>";
    echo "<h3>All Categories (for debugging):</h3>";
    $all_categories = $DB->get_records('course_categories', ['visible' => 1], 'parent, name', 'id,name,parent');
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Parent</th><th>Type</th></tr>";
    foreach ($all_categories as $cat) {
        $type = $cat->parent == 0 ? 'Top Level' : 'Sub Category';
        $style = $cat->parent == 0 ? 'background-color: #f0fff0;' : '';
        echo "<tr style='$style'>";
        echo "<td>{$cat->id}</td>";
        echo "<td>{$cat->name}</td>";
        echo "<td>{$cat->parent}</td>";
        echo "<td>$type</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='result' style='background: #ffe7e7;'>";
    echo "<h3>Error</h3>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "</div>";
}
?>

