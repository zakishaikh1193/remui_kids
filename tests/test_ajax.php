<?php
/**
 * Simple test endpoint in root directory
 */

header('Content-Type: application/json');

try {
    require_once('../../../config.php');
    
    global $DB;
    
    // Simple school count
    $schools = $DB->count_records_sql(
        "SELECT COUNT(*) FROM {company}",
        []
    );

     
    
    $courses = $DB->count_records_sql(
        "SELECT COUNT(*) FROM {course} WHERE visible = 1 AND id > 1",
        []
    );
    
    // Get total students - using trainee role count (any context)
    $traineerole = $DB->get_record('role', ['shortname' => 'student']);
    $students = 0;
    if ($traineerole) {
        $students = $DB->count_records_sql(
            "SELECT COUNT(DISTINCT u.id)
             FROM {user} u
             JOIN {role_assignments} ra ON u.id = ra.userid
             JOIN {role} r ON ra.roleid = r.id
             WHERE r.shortname = 'student' AND u.deleted = 0 AND u.suspended = 0"
        );
    }
    
    // Get additional counts for comprehensive dashboard
    $total_users = $DB->count_records('user', ['deleted' => 0]);
    
    // Get teacher count (users with teachers role)
    //  $teacherroles = $DB->count_records_sql(
    //     "SELECT * FROM {role} WHERE shortname IN ('editingteacher', 'editingteacher')"
    // );
    // $teachers = 0;
    // if ($teacherroles) {
    //     $teachers = $DB->count_records_sql(
    //         "SELECT COUNT(DISTINCT u.id)
    //          FROM {user} u
    //          JOIN {role_assignments} ra ON u.id = ra.userid
    //          JOIN {context} ctx ON ra.contextid = ctx.id
    //          WHERE ctx.contextlevel = ? AND ra.roleid = ? AND u.deleted = 0 ",
    //         [CONTEXT_SYSTEM, $teacherroles->id]
    //     );
    // }

     // Get teachers count
        $teacherrole = $DB->get_record('role', ['shortname' => 'editingteacher']);
        $teachers = 0;
        if ($teacherrole) {
            $teachers = $DB->count_records_sql(
                "SELECT COUNT(DISTINCT u.id) 
                 FROM {user} u 
                 JOIN {role_assignments} ra ON u.id = ra.userid 
                 JOIN {context} ctx ON ra.contextid = ctx.id 
                 WHERE ctx.contextlevel = ? AND ra.roleid = ? AND u.deleted = 0",
                [CONTEXT_SYSTEM, $teacherrole->id]
            );
        }
    
    // Get admin count (users with manager role)
    $managerrole = $DB->get_record('role', ['shortname' => 'manager']);
    $admins = 0;
    if ($managerrole) {
        $admins = $DB->count_records_sql(
            "SELECT COUNT(DISTINCT u.id)
             FROM {user} u
             JOIN {role_assignments} ra ON u.id = ra.userid
             JOIN {context} ctx ON ra.contextid = ctx.id
             WHERE ctx.contextlevel = ? AND ra.roleid = ? AND u.deleted = 0 AND u.suspended = 0",
            [CONTEXT_SYSTEM, $managerrole->id]
        );
    }
    
    // Get new users this month
    $new_this_month = $DB->count_records_sql(
        "SELECT COUNT(*) FROM {user} WHERE timecreated > ? AND deleted = 0",
        [strtotime('first day of this month')]
    );
    
    // Get active users (logged in within last 30 days)
    $active_users = $DB->count_records_sql(
        "SELECT COUNT(DISTINCT u.id) FROM {user} u 
         JOIN {user_lastaccess} ul ON u.id = ul.userid 
         WHERE u.deleted = 0 AND ul.timeaccess > ?",
        [time() - (30 * 24 * 60 * 60)] // Last 30 days
    );
    
    // Get completion rate (mock data for now)
    $completion_rate = 0; // Will be implemented when completion tracking is available
    
    // Get average rating (mock data for now)
    $avg_rating = 0; // Will be implemented when rating system is available
    
    // Get categories count
    $categories = $DB->count_records('course_categories', ['visible' => 1 , 'parent' => 0]);

    echo json_encode([
        'status' => 'success',
        'total_schools' => $schools,
        'total_courses' => $courses,
        'total_students' => $students,
        'total_users' => $total_users,
        'teachers' => $teachers,
        'admins' => $admins,
        'active_users' => $active_users,
        'new_this_month' => $new_this_month,
        'completion_rate' => $completion_rate,
        'avg_rating' => $avg_rating,
        'categories' => $categories,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
