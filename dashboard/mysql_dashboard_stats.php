<?php
/**
 * MySQL-compatible dashboard statistics queries
 * These queries use actual MySQL table names instead of Moodle's {table_name} syntax
 */

require_once('../../../config.php');

global $DB, $USER;

// Get current user ID
$userid = $USER->id;

echo "=== MYSQL-COMPATIBLE DASHBOARD STATISTICS ===" . PHP_EOL;
echo "User ID: $userid" . PHP_EOL . PHP_EOL;

// Get the actual table prefix from Moodle config
$prefix = $DB->get_prefix();

echo "=== MYSQL QUERIES (Copy and paste these into phpMyAdmin or MySQL client) ===" . PHP_EOL . PHP_EOL;

// 1. TOTAL COURSES ENROLLED
echo "1. TOTAL COURSES ENROLLED:" . PHP_EOL;
$courses_query = "
SELECT COUNT(DISTINCT c.id) as total_courses
FROM {$prefix}course c 
JOIN {$prefix}enrol e ON c.id = e.courseid 
JOIN {$prefix}user_enrolments ue ON e.id = ue.enrolid 
WHERE ue.userid = $userid AND c.visible = 1 AND c.id > 1;
";
echo $courses_query . PHP_EOL;

// Execute and show result
$totalcourses = $DB->count_records_sql(
    "SELECT COUNT(DISTINCT c.id) 
     FROM {course} c 
     JOIN {enrol} e ON c.id = e.courseid 
     JOIN {user_enrolments} ue ON e.id = ue.enrolid 
     WHERE ue.userid = ? AND c.visible = 1 AND c.id > 1",
    [$userid]
);
echo "Result: $totalcourses courses" . PHP_EOL . PHP_EOL;

// 2. LESSONS COMPLETED
echo "2. LESSONS COMPLETED:" . PHP_EOL;
$lessons_query = "
SELECT COUNT(DISTINCT cmc.coursemoduleid) as lessons_completed
FROM {$prefix}course_modules_completion cmc 
JOIN {$prefix}course_modules cm ON cmc.coursemoduleid = cm.id 
JOIN {$prefix}course c ON cm.course = c.id 
WHERE cmc.userid = $userid AND cmc.completionstate > 0 AND c.visible = 1 AND c.id > 1;
";
echo $lessons_query . PHP_EOL;

$lessonscompleted = $DB->count_records_sql(
    "SELECT COUNT(DISTINCT cmc.coursemoduleid) 
     FROM {course_modules_completion} cmc 
     JOIN {course_modules} cm ON cmc.coursemoduleid = cm.id 
     JOIN {course} c ON cm.course = c.id 
     WHERE cmc.userid = ? AND cmc.completionstate > 0 AND c.visible = 1 AND c.id > 1",
    [$userid]
);
echo "Result: $lessonscompleted lessons completed" . PHP_EOL . PHP_EOL;

// 3. ACTIVITIES COMPLETED
echo "3. ACTIVITIES COMPLETED:" . PHP_EOL;
$activities_query = "
SELECT COUNT(*) as activities_completed
FROM {$prefix}course_modules_completion cmc 
JOIN {$prefix}course_modules cm ON cmc.coursemoduleid = cm.id 
JOIN {$prefix}course c ON cm.course = c.id 
WHERE cmc.userid = $userid AND cmc.completionstate > 0 AND c.visible = 1 AND c.id > 1;
";
echo $activities_query . PHP_EOL;

$activitiescompleted = $DB->count_records_sql(
    "SELECT COUNT(*) 
     FROM {course_modules_completion} cmc 
     JOIN {course_modules} cm ON cmc.coursemoduleid = cm.id 
     JOIN {course} c ON cm.course = c.id 
     WHERE cmc.userid = ? AND cmc.completionstate > 0 AND c.visible = 1 AND c.id > 1",
    [$userid]
);
echo "Result: $activitiescompleted activities completed" . PHP_EOL . PHP_EOL;

// 4. TOTAL ACTIVITIES AVAILABLE
echo "4. TOTAL ACTIVITIES AVAILABLE:" . PHP_EOL;
$total_activities_query = "
SELECT COUNT(*) as total_activities
FROM {$prefix}course_modules cm 
JOIN {$prefix}course c ON cm.course = c.id 
JOIN {$prefix}enrol e ON c.id = e.courseid 
JOIN {$prefix}user_enrolments ue ON e.id = ue.enrolid 
WHERE ue.userid = $userid AND c.visible = 1 AND c.id > 1 AND cm.completion > 0;
";
echo $total_activities_query . PHP_EOL;

$totalactivities = $DB->count_records_sql(
    "SELECT COUNT(*) 
     FROM {course_modules} cm 
     JOIN {course} c ON cm.course = c.id 
     JOIN {enrol} e ON c.id = e.courseid 
     JOIN {user_enrolments} ue ON e.id = ue.enrolid 
     WHERE ue.userid = ? AND c.visible = 1 AND c.id > 1 AND cm.completion > 0",
    [$userid]
);
echo "Result: $totalactivities total activities" . PHP_EOL . PHP_EOL;

// 5. OVERALL PROGRESS CALCULATION
echo "5. OVERALL PROGRESS CALCULATION:" . PHP_EOL;
$progress_query = "
SELECT 
    COUNT(*) as total_activities,
    (SELECT COUNT(*) 
     FROM {$prefix}course_modules_completion cmc2 
     JOIN {$prefix}course_modules cm2 ON cmc2.coursemoduleid = cm2.id 
     JOIN {$prefix}course c2 ON cm2.course = c2.id 
     WHERE cmc2.userid = $userid AND cmc2.completionstate > 0 AND c2.visible = 1 AND c2.id > 1) as completed_activities,
    ROUND(
        (SELECT COUNT(*) 
         FROM {$prefix}course_modules_completion cmc3 
         JOIN {$prefix}course_modules cm3 ON cmc3.coursemoduleid = cm3.id 
         JOIN {$prefix}course c3 ON cm3.course = c3.id 
         WHERE cmc3.userid = $userid AND cmc3.completionstate > 0 AND c3.visible = 1 AND c3.id > 1) * 100.0 / 
        COUNT(*), 2
    ) as progress_percentage
FROM {$prefix}course_modules cm 
JOIN {$prefix}course c ON cm.course = c.id 
JOIN {$prefix}enrol e ON c.id = e.courseid 
JOIN {$prefix}user_enrolments ue ON e.id = ue.enrolid 
WHERE ue.userid = $userid AND c.visible = 1 AND c.id > 1 AND cm.completion > 0;
";
echo $progress_query . PHP_EOL;

$overallprogress = 0;
if ($totalactivities > 0) {
    $overallprogress = round(($activitiescompleted / $totalactivities) * 100);
}
echo "Result: $overallprogress% overall progress" . PHP_EOL . PHP_EOL;

// 6. DETAILED COURSE BREAKDOWN
echo "6. DETAILED COURSE BREAKDOWN:" . PHP_EOL;
$detailed_query = "
SELECT 
    c.id,
    c.fullname,
    c.shortname,
    COUNT(DISTINCT cm.id) as total_activities,
    COUNT(DISTINCT CASE WHEN cmc.completionstate > 0 THEN cmc.coursemoduleid END) as completed_activities,
    ROUND(COUNT(DISTINCT CASE WHEN cmc.completionstate > 0 THEN cmc.coursemoduleid END) * 100.0 / COUNT(DISTINCT cm.id), 2) as progress_percentage
FROM {$prefix}course c 
JOIN {$prefix}enrol e ON c.id = e.courseid 
JOIN {$prefix}user_enrolments ue ON e.id = ue.enrolid 
LEFT JOIN {$prefix}course_modules cm ON c.id = cm.course AND cm.completion > 0
LEFT JOIN {$prefix}course_modules_completion cmc ON cm.id = cmc.coursemoduleid AND cmc.userid = $userid
WHERE ue.userid = $userid AND c.visible = 1 AND c.id > 1
GROUP BY c.id, c.fullname, c.shortname
ORDER BY c.fullname;
";
echo $detailed_query . PHP_EOL . PHP_EOL;

echo "=== SUMMARY FOR DASHBOARD CARDS ===" . PHP_EOL;
echo "Courses Card: $totalcourses" . PHP_EOL;
echo "Lessons Done Card: $lessonscompleted" . PHP_EOL;
echo "Activities Done Card: $activitiescompleted" . PHP_EOL;
echo "Overall Progress Card: $overallprogress%" . PHP_EOL . PHP_EOL;

echo "=== TABLE PREFIX USED: $prefix ===" . PHP_EOL;
echo "If your table prefix is different, replace '$prefix' in the queries above with your actual prefix." . PHP_EOL;
?>
