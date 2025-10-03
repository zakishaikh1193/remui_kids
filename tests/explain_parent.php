<?php
require_once('../../../config.php');

global $DB;

echo "<h2>Understanding parent = 0 in Course Categories</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .top-level { background: #e7f3ff; padding: 10px; margin: 5px 0; border-left: 4px solid #0066cc; }
    .sub-level { background: #f0fff0; padding: 10px; margin: 5px 0 5px 20px; border-left: 4px solid #28a745; }
    .count { font-size: 18px; font-weight: bold; color: #0066cc; }
</style>";

try {
    // Get all categories with their parent information
    $categories = $DB->get_records('course_categories', ['visible' => 1], 'parent, name', 'id,name,parent,description');
    
    echo "<h3>Category Structure in Your Database:</h3>";
    
    $top_level_count = 0;
    $sub_level_count = 0;
    
    foreach ($categories as $cat) {
        if ($cat->parent == 0) {
            $top_level_count++;
            echo "<div class='top-level'>";
            echo "<strong>🏫 TOP LEVEL (parent = 0):</strong> {$cat->name} (ID: {$cat->id})";
            if ($cat->description) {
                echo "<br><em>{$cat->description}</em>";
            }
            echo "</div>";
        } else {
            $sub_level_count++;
            echo "<div class='sub-level'>";
            echo "<strong>📁 SUB CATEGORY (parent = {$cat->parent}):</strong> {$cat->name} (ID: {$cat->id})";
            if ($cat->description) {
                echo "<br><em>{$cat->description}</em>";
            }
            echo "</div>";
        }
    }
    
    echo "<hr>";
    echo "<h3>Summary:</h3>";
    echo "<div class='count'>Top Level Categories (parent = 0): $top_level_count</div>";
    echo "<div class='count'>Sub Categories (parent > 0): $sub_level_count</div>";
    
    echo "<h3>Why We Count Only parent = 0:</h3>";
    echo "<ul>";
    echo "<li><strong>Top-level categories (parent = 0)</strong> = Actual schools/organizations</li>";
    echo "<li><strong>Sub-categories (parent > 0)</strong> = Organizational folders within schools</li>";
    echo "<li><strong>We want to count schools, not folders!</strong></li>";
    echo "</ul>";
    
    // Show the filtered count (what your dashboard shows)
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
    
    echo "<div class='count' style='background: #fff3cd; padding: 15px; border-radius: 5px;'>";
    echo "🎯 Your Dashboard Shows: <strong>$meaningful_schools</strong> schools";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>



