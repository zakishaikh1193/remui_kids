<?php
require_once('../../../config.php');

global $DB;

try {
    // Get the exact school count that your dashboard shows
    $school_count = $DB->count_records_sql(
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
    
    echo "TOTAL SCHOOLS: " . $school_count . "\n";
    
    // Also show what's being counted
    $schools = $DB->get_records_sql(
        "SELECT id, name 
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
    
    if (!empty($schools)) {
        echo "\nSchool Categories:\n";
        foreach ($schools as $school) {
            echo "- " . $school->name . " (ID: " . $school->id . ")\n";
        }
    } else {
        echo "\nNo school categories found.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>

