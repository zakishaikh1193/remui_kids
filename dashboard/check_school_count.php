<?php
/**
 * School Count Checker
 * Run this script to check school counts in your database
 */

require_once('../../../config.php');

global $DB;

echo "<h2>School Count Analysis</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
    .metric { background-color: #e7f3ff; }
    .school { background-color: #f0fff0; }
</style>";

try {
    // 1. Basic count of all course categories
    $total_categories = $DB->count_records('course_categories', ['visible' => 1]);
    echo "<h3>1. Basic Statistics</h3>";
    echo "<table>";
    echo "<tr><th>Metric</th><th>Count</th></tr>";
    echo "<tr><td>Total Visible Categories</td><td><strong>$total_categories</strong></td></tr>";
    
    // 2. Improved school count
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
    echo "<tr class='metric'><td>Meaningful Schools (Top Level)</td><td><strong>$meaningful_schools</strong></td></tr>";
    
    // 3. Total courses
    $total_courses = $DB->count_records('course', ['visible' => 1], 'id > 1');
    echo "<tr><td>Total Courses (excluding site course)</td><td><strong>$total_courses</strong></td></tr>";
    echo "</table>";
    
    // 4. Detailed view of school categories
    echo "<h3>2. School Categories Details</h3>";
    $school_categories = $DB->get_records_sql(
        "SELECT 
            id,
            name,
            description,
            parent,
            CASE 
                WHEN parent = 0 THEN 'Top Level'
                ELSE 'Sub Category'
            END as category_type
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
        echo "<table>";
        echo "<tr><th>ID</th><th>School Name</th><th>Description</th><th>Type</th></tr>";
        foreach ($school_categories as $school) {
            echo "<tr class='school'>";
            echo "<td>{$school->id}</td>";
            echo "<td><strong>{$school->name}</strong></td>";
            echo "<td>" . ($school->description ?: 'No description') . "</td>";
            echo "<td>{$school->category_type}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p><em>No school categories found. All categories might be system categories.</em></p>";
    }
    
    // 5. Courses per school
    echo "<h3>3. Courses per School</h3>";
    $courses_per_school = $DB->get_records_sql(
        "SELECT 
            cc.id,
            cc.name as school_name,
            COUNT(c.id) as total_courses
         FROM {course_categories} cc
         LEFT JOIN {course} c ON cc.id = c.category AND c.visible = 1 AND c.id > 1
         WHERE cc.visible = 1 
         AND cc.id > 1 
         AND cc.parent = 0
         AND (cc.name NOT LIKE '%Miscellaneous%' 
              AND cc.name NOT LIKE '%Default%' 
              AND cc.name NOT LIKE '%System%'
              AND cc.name NOT LIKE '%General%')
         GROUP BY cc.id, cc.name
         ORDER BY total_courses DESC",
        []
    );
    
    if (!empty($courses_per_school)) {
        echo "<table>";
        echo "<tr><th>School Name</th><th>Total Courses</th></tr>";
        foreach ($courses_per_school as $school) {
            echo "<tr>";
            echo "<td>{$school->school_name}</td>";
            echo "<td><strong>{$school->total_courses}</strong></td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 6. All categories (for debugging)
    echo "<h3>4. All Categories (for debugging)</h3>";
    $all_categories = $DB->get_records('course_categories', ['visible' => 1], 'parent, name', 'id,name,parent');
    
    echo "<table>";
    echo "<tr><th>ID</th><th>Name</th><th>Parent</th><th>Type</th></tr>";
    foreach ($all_categories as $cat) {
        $type = $cat->parent == 0 ? 'Top Level' : 'Sub Category';
        $class = $cat->parent == 0 ? 'school' : '';
        echo "<tr class='$class'>";
        echo "<td>{$cat->id}</td>";
        echo "<td>{$cat->name}</td>";
        echo "<td>{$cat->parent}</td>";
        echo "<td>$type</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<hr>";
    echo "<p><strong>Summary:</strong> Your dashboard shows <strong>$meaningful_schools</strong> schools.</p>";
    echo "<p><em>This count excludes system categories and only includes top-level categories that represent actual schools/organizations.</em></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>

