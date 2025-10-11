<?php
/**
 * School Manager Dashboard
 * Main dashboard for school/department managers
 * 
 * @package   theme_remui_kids
 * @copyright 2024
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

// Require login
require_login();
$context = context_system::instance();

// Check if user is a department manager (school manager)
global $USER, $DB;

// Get user's company assignment
$company_user = $DB->get_record('company_users', ['userid' => $USER->id]);

if (!$company_user || $company_user->managertype != 2) {
    throw new moodle_exception('nopermissions', 'error', '', 'access school manager dashboard - you must be a department/school manager');
}

// Get department/school information
$department = $DB->get_record('department', ['id' => $company_user->departmentid]);
$company = $DB->get_record('company', ['id' => $company_user->companyid]);

// Set up the page
$PAGE->set_context($context);
$PAGE->set_url('/theme/remui_kids/school_manager/dashboard.php');
$PAGE->set_pagelayout('admin');
$PAGE->set_title('School Manager Dashboard');
$PAGE->set_heading('School Manager Dashboard');

// Handle AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['action']) {
        case 'get_dashboard_stats':
            try {
                // Get students in this department
                $students_count = $DB->count_records_sql(
                    "SELECT COUNT(DISTINCT cu.userid)
                     FROM {company_users} cu
                     JOIN {user} u ON cu.userid = u.id
                     WHERE cu.departmentid = ? AND cu.managertype = 0 AND u.deleted = 0 AND cu.suspended = 0",
                    [$company_user->departmentid]
                );
                
                // Get teachers in this department
                $teachers_count = $DB->count_records_sql(
                    "SELECT COUNT(DISTINCT cu.userid)
                     FROM {company_users} cu
                     JOIN {user} u ON cu.userid = u.id
                     JOIN {role_assignments} ra ON u.id = ra.userid
                     JOIN {role} r ON ra.roleid = r.id
                     WHERE cu.departmentid = ? AND r.shortname IN ('editingteacher', 'teacher') 
                     AND u.deleted = 0 AND cu.suspended = 0",
                    [$company_user->departmentid]
                );
                
                // Get courses in this department
                $courses_count = $DB->count_records_sql(
                    "SELECT COUNT(DISTINCT cc.courseid)
                     FROM {company_course} cc
                     WHERE cc.departmentid = ?",
                    [$company_user->departmentid]
                );
                
                // Get active students (logged in within last 30 days)
                $active_students = $DB->count_records_sql(
                    "SELECT COUNT(DISTINCT cu.userid)
                     FROM {company_users} cu
                     JOIN {user} u ON cu.userid = u.id
                     LEFT JOIN {user_lastaccess} ul ON u.id = ul.userid
                     WHERE cu.departmentid = ? AND cu.managertype = 0 
                     AND u.deleted = 0 AND cu.suspended = 0
                     AND (ul.timeaccess > ? OR u.lastaccess > ?)",
                    [$company_user->departmentid, time() - (30 * 24 * 60 * 60), time() - (30 * 24 * 60 * 60)]
                );
                
                // Get total enrollments
                $total_enrollments = $DB->count_records_sql(
                    "SELECT COUNT(DISTINCT ue.id)
                     FROM {user_enrolments} ue
                     JOIN {enrol} e ON ue.enrolid = e.id
                     JOIN {company_course} cc ON e.courseid = cc.courseid
                     JOIN {company_users} cu ON ue.userid = cu.userid
                     WHERE cc.departmentid = ? AND cu.departmentid = ?",
                    [$company_user->departmentid, $company_user->departmentid]
                );
                
                // Get completion rate
                $completed_courses = $DB->count_records_sql(
                    "SELECT COUNT(DISTINCT cc.id)
                     FROM {course_completions} cc
                     JOIN {company_users} cu ON cc.userid = cu.userid
                     WHERE cu.departmentid = ? AND cc.timecompleted IS NOT NULL",
                    [$company_user->departmentid]
                );
                
                $completion_rate = $total_enrollments > 0 
                    ? round(($completed_courses / $total_enrollments) * 100, 1) 
                    : 0;
                
                echo json_encode([
                    'status' => 'success',
                    'stats' => [
                        'students_count' => $students_count,
                        'teachers_count' => $teachers_count,
                        'courses_count' => $courses_count,
                        'active_students' => $active_students,
                        'total_enrollments' => $total_enrollments,
                        'completion_rate' => $completion_rate,
                        'department_name' => $department->name ?? 'Unknown',
                        'company_name' => $company->name ?? 'Unknown'
                    ]
                ]);
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
            exit;
            
        case 'get_recent_activity':
            try {
                // Get recent student enrollments
                $recent_enrollments = $DB->get_records_sql(
                    "SELECT u.id, u.firstname, u.lastname, c.fullname as course_name, ue.timecreated
                     FROM {user_enrolments} ue
                     JOIN {enrol} e ON ue.enrolid = e.id
                     JOIN {course} c ON e.courseid = c.id
                     JOIN {user} u ON ue.userid = u.id
                     JOIN {company_users} cu ON u.id = cu.userid
                     WHERE cu.departmentid = ?
                     ORDER BY ue.timecreated DESC
                     LIMIT 10",
                    [$company_user->departmentid]
                );
                
                echo json_encode([
                    'status' => 'success',
                    'recent_enrollments' => array_values($recent_enrollments)
                ]);
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
            exit;
    }
}

// Prepare initial template data
$template_data = [
    'department_name' => $department->name ?? 'Unknown Department',
    'company_name' => $company->name ?? 'Unknown Company',
    'manager_name' => fullname($USER),
    'config' => [
        'wwwroot' => $CFG->wwwroot
    ]
];

echo $OUTPUT->header();

// Include sidebar and main content
include('includes/sidebar.php');

echo '<div class="school-manager-main-content">';
echo $OUTPUT->render_from_template('theme_remui_kids/school_manager_dashboard', $template_data);
echo '</div>';

echo $OUTPUT->footer();
?>


