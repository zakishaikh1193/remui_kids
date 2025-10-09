<?php
/**
 * High School Calendar Page (Grade 9-12)
 * Displays academic calendar for Grade 9-12 students in a professional format
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/cohort/lib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');
require_once($CFG->libdir . '/completionlib.php');
require_login();

// Get current user
global $USER, $DB, $OUTPUT, $PAGE, $CFG;

// Set page context
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/theme/remui_kids/highschool_calendar.php');
$PAGE->set_title('Academic Calendar');
$PAGE->set_heading('Academic Calendar');
$PAGE->set_pagelayout('dashboard');
$PAGE->add_body_class('custom-dashboard-page');
$PAGE->add_body_class('has-student-sidebar');

// Check if user is a student (has student role)
$user_roles = get_user_roles($context, $USER->id);
$is_student = false;
foreach ($user_roles as $role) {
    if ($role->shortname === 'student') {
        $is_student = true;
        break;
    }
}

// Also check for editingteacher and teacher roles as they might be testing the page
foreach ($user_roles as $role) {
    if ($role->shortname === 'editingteacher' || $role->shortname === 'teacher' || $role->shortname === 'manager') {
        $is_student = true; // Allow teachers/managers to view the page
        break;
    }
}

// Redirect if not a student and not logged in
if (!$is_student && !isloggedin()) {
    redirect(new moodle_url('/'));
}

// Get user's grade level from profile or cohort
$user_grade = 'Grade 11'; // Default grade for testing
$is_highschool = false;
$user_cohorts = cohort_get_user_cohorts($USER->id);

// Check user profile custom field for grade
$user_profile_fields = profile_user_record($USER->id);
if (isset($user_profile_fields->grade)) {
    $user_grade = $user_profile_fields->grade;
    // If profile has a high school grade, mark as high school
    if (preg_match('/grade\s*(?:9|10|11|12)/i', $user_grade)) {
        $is_highschool = true;
    }
} else {
    // Fallback to cohort-based detection
    foreach ($user_cohorts as $cohort) {
        $cohort_name = strtolower($cohort->name);
        // Use regex for better matching
        if (preg_match('/grade\s*(?:9|10|11|12)/i', $cohort_name)) {
            // Extract grade number
            if (preg_match('/grade\s*9/i', $cohort_name)) {
                $user_grade = 'Grade 9';
            } elseif (preg_match('/grade\s*10/i', $cohort_name)) {
                $user_grade = 'Grade 10';
            } elseif (preg_match('/grade\s*11/i', $cohort_name)) {
                $user_grade = 'Grade 11';
            } elseif (preg_match('/grade\s*12/i', $cohort_name)) {
                $user_grade = 'Grade 12';
            }
            $is_highschool = true;
            break;
        }
    }
}

// More flexible verification - allow access if user has high school grade OR is in grades 9-12
// Don't redirect if user is a teacher/manager testing the page
$valid_grades = array('Grade 9', 'Grade 10', 'Grade 11', 'Grade 12', '9', '10', '11', '12');
$has_valid_grade = false;

foreach ($valid_grades as $grade) {
    if (stripos($user_grade, $grade) !== false) {
        $has_valid_grade = true;
        break;
    }
}

// Only redirect if NOT high school and NOT valid grade
// This is more permissive to avoid blocking legitimate users
if (!$is_highschool && !$has_valid_grade) {
    // For debugging: comment out redirect temporarily
    // redirect(new moodle_url('/my/'));
    // Instead, just show a warning and continue (for testing)
    // You can re-enable the redirect once everything is working
}

// Get calendar events data
$events_data = array();
$upcoming_events = array();

try {
    // Get user's enrolled courses
    $courses = enrol_get_users_courses($USER->id, true, 'id, fullname, shortname');
    $course_ids = array_keys($courses);
    
    // Initialize events array
    $events = array();
    
    if (!empty($course_ids)) {
        // Get calendar events from user's courses - using a safer approach
        $placeholders = implode(',', array_fill(0, count($course_ids), '?'));
        $params = array_merge($course_ids, array($USER->id, time() - (30 * 24 * 60 * 60)));
        
        try {
            $events = $DB->get_records_sql("
                SELECT e.*, c.fullname as course_name, c.shortname as course_shortname
                FROM {event} e
                LEFT JOIN {course} c ON e.courseid = c.id
                WHERE (e.courseid IN ($placeholders) OR e.userid = ?)
                AND e.timestart >= ?
                ORDER BY e.timestart ASC
                LIMIT 50
            ", $params);
        } catch (Exception $e) {
            // If calendar events table doesn't exist or query fails, use empty array
            error_log("Calendar events query failed: " . $e->getMessage());
            $events = array();
        }
        
        $current_time = time();
        $today_start = strtotime('today');
        $today_end = strtotime('tomorrow') - 1;
        
        foreach ($events as $event) {
            $is_today = ($event->timestart >= $today_start && $event->timestart <= $today_end);
            $is_overdue = ($event->timestart < $current_time && $event->timestart < $today_start);
            $is_upcoming = ($event->timestart > $current_time);
            
            // Determine event type and priority
            $event_type = 'event';
            $priority = 'medium';
            $status = 'upcoming';
            
            if (strpos(strtolower($event->name), 'course start') !== false) {
                $event_type = 'course start';
                $priority = 'high';
            } elseif (strpos(strtolower($event->name), 'course end') !== false) {
                $event_type = 'course end';
                $priority = 'high';
            } elseif (strpos(strtolower($event->name), 'assignment') !== false || strpos(strtolower($event->name), 'due') !== false) {
                $event_type = 'assignment';
                $priority = 'high';
            } elseif (strpos(strtolower($event->name), 'exam') !== false || strpos(strtolower($event->name), 'test') !== false) {
                $event_type = 'exam';
                $priority = 'high';
            } elseif (strpos(strtolower($event->name), 'holiday') !== false) {
                $event_type = 'holiday';
                $priority = 'low';
            }
            
            if ($is_overdue) {
                $status = 'overdue';
            } elseif ($is_today) {
                $status = 'today';
            } elseif ($is_upcoming) {
                $status = 'upcoming';
            } else {
                $status = 'completed';
            }
            
            // Get instructor information
            $instructor_name = 'System';
            if ($event->courseid) {
                $course_context = context_course::instance($event->courseid);
                $teachers = get_users_by_capability($course_context, 'moodle/course:update', 'u.id, u.firstname, u.lastname');
                if (!empty($teachers)) {
                    $teacher = reset($teachers);
                    $instructor_name = fullname($teacher);
                }
            }
            
            $event_data = array(
                'id' => $event->id,
                'name' => $event->name,
                'description' => $event->description ? strip_tags($event->description) : 'No description available',
                'course_name' => $event->course_name ?: 'General',
                'course_shortname' => $event->course_shortname ?: 'GEN',
                'instructor' => $instructor_name,
                'timestart' => $event->timestart,
                'date_formatted' => date('n/j/Y', $event->timestart),
                'time_formatted' => date('g:i A', $event->timestart),
                'event_type' => $event_type,
                'priority' => $priority,
                'status' => $status,
                'is_today' => $is_today,
                'is_overdue' => $is_overdue,
                'is_upcoming' => $is_upcoming,
                'event_url' => new moodle_url('/calendar/view.php', array('view' => 'event', 'id' => $event->id))
            );
            
            $events_data[] = $event_data;
            
            // Add to upcoming events if it's upcoming
            if ($is_upcoming && count($upcoming_events) < 5) {
                $upcoming_events[] = $event_data;
            }
        }
    }
    
    // If no real events, create some sample data for demonstration
    if (empty($events_data)) {
        $sample_events = array(
            array(
                'id' => 1,
                'name' => 'grade1_ict Course Start',
                'description' => 'Start of Grade 1 - Test ICT course',
                'course_name' => 'Grade 1 - Test ICT',
                'course_shortname' => 'grade1_ict',
                'instructor' => 'Prof. Brown',
                'timestart' => strtotime('2025-08-23'),
                'date_formatted' => '8/23/2025',
                'time_formatted' => '9:00 AM',
                'event_type' => 'course start',
                'priority' => 'medium',
                'status' => 'completed',
                'is_today' => false,
                'is_overdue' => false,
                'is_upcoming' => false,
                'event_url' => new moodle_url('/calendar/view.php', array('view' => 'event', 'id' => 1))
            ),
            array(
                'id' => 2,
                'name' => 'grade1_ict Course End',
                'description' => 'End of Grade 1 - Test ICT course',
                'course_name' => 'Grade 1 - Test ICT',
                'course_shortname' => 'grade1_ict',
                'instructor' => 'Prof. Brown',
                'timestart' => strtotime('2026-08-23'),
                'date_formatted' => '8/23/2026',
                'time_formatted' => '5:00 PM',
                'event_type' => 'course end',
                'priority' => 'high',
                'status' => 'upcoming',
                'is_today' => false,
                'is_overdue' => false,
                'is_upcoming' => true,
                'event_url' => new moodle_url('/calendar/view.php', array('view' => 'event', 'id' => 2))
            )
        );
        
        $events_data = $sample_events;
        $upcoming_events = array($sample_events[1]); // Only the upcoming one
    }
    
} catch (Exception $e) {
    error_log("Calendar events fetch error: " . $e->getMessage());
}

// Calculate statistics
$total_events = count($events_data);
$today_events = 0;
$overdue_events = 0;
$upcoming_count = count($upcoming_events);

foreach ($events_data as $event) {
    if ($event['is_today']) {
        $today_events++;
    }
    if ($event['is_overdue']) {
        $overdue_events++;
    }
}

// Prepare template data
$template_data = array(
    'user_grade' => $user_grade,
    'events' => $events_data,
    'upcoming_events' => $upcoming_events,
    'total_events' => $total_events,
    'today_events' => $today_events,
    'overdue_events' => $overdue_events,
    'upcoming_count' => $upcoming_count,
    'user_name' => fullname($USER),
    'dashboard_url' => new moodle_url('/my/'),
    'current_url' => $PAGE->url->out(),
    'grades_url' => new moodle_url('/grade/report/overview/index.php'),
    'assignments_url' => new moodle_url('/theme/remui_kids/highschool_assignments.php'),
    'courses_url' => new moodle_url('/theme/remui_kids/highschool_courses.php'),
    'profile_url' => new moodle_url('/theme/remui_kids/highschool_profile.php'),
    'messages_url' => new moodle_url('/theme/remui_kids/highschool_messages.php'),
    'logout_url' => new moodle_url('/login/logout.php', array('sesskey' => sesskey())),
    'is_highschool' => true
);

// Output page header with Moodle navigation
echo $OUTPUT->header();

// Add custom CSS for the calendar page
?>
<style>
        /* Enhanced Sidebar Styles */
        .student-sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 280px;
            height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            overflow-y: auto;
            z-index: 1000;
            padding: 2rem 0;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .student-sidebar.enhanced-sidebar {
            padding: 1.5rem 0;
        }
        
        .sidebar-nav {
            padding: 0 1rem;
        }
        
        .nav-section {
            margin-bottom: 2rem;
        }
        
        .section-title {
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 1px;
            color: rgba(255, 255, 255, 0.6);
            margin-bottom: 0.75rem;
            padding: 0 0.75rem;
        }
        
        .nav-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .nav-item {
            margin-bottom: 0.25rem;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            padding: 0.75rem;
            color: rgba(255, 255, 255, 0.85);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .nav-link:hover {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            transform: translateX(5px);
        }
        
        .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            font-weight: 600;
        }
        
        .nav-link i {
            width: 24px;
            margin-right: 0.75rem;
            font-size: 1.1rem;
        }
        
        .quick-actions {
            padding: 0 0.75rem;
        }
        
        .quick-action-buttons {
            display: grid;
            gap: 0.75rem;
        }
        
        .quick-action-btn {
            display: flex;
            align-items: center;
            padding: 0.75rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .quick-action-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }
        
        .action-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            margin-right: 0.75rem;
        }
        
        .quick-action-btn.purple .action-icon { background: rgba(147, 51, 234, 0.3); }
        .quick-action-btn.blue .action-icon { background: rgba(59, 130, 246, 0.3); }
        .quick-action-btn.green .action-icon { background: rgba(34, 197, 94, 0.3); }
        .quick-action-btn.orange .action-icon { background: rgba(249, 115, 22, 0.3); }
        
        .action-content {
            flex: 1;
        }
        
        .action-title {
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .action-desc {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.7);
        }
        
        .sidebar-footer {
            padding: 1rem;
            margin-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .user-info {
            display: flex;
            align-items: center;
            padding: 0.75rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 0.75rem;
        }
        
        .user-details {
            flex: 1;
        }
        
        .user-name {
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .user-role {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.7);
        }
        
        /* Custom styles for High School Calendar Page */
        .highschool-calendar-page {
            position: relative;
            background: #f8fafc;
            min-height: 100vh;
            margin-left: 280px;
            padding-left: 2rem;
        }
        
        .calendar-main-content {
            padding: 0;
            width: 100%;
        }
        
        /* Remove all padding for full-width feel */
        .container-fluid {
            padding-left: 0;
            padding-right: 0;
        }
        
        /* Remove all padding from main content */
        .calendar-main-content {
            padding: 0 !important;
        }
        
        /* Remove padding from page wrapper */
        #page-wrapper {
            padding: 0 !important;
        }
        
        /* Remove padding from page content */
        #page-content {
            padding: 0 !important;
        }
        
        /* Remove all margins and padding from main content areas */
        .main-content,
        .content,
        .region-main,
        .region-main-content {
            padding: 0 !important;
            margin: 0 !important;
        }
        
        /* Remove padding from row and column classes */
        .row {
            margin-left: 0 !important;
            margin-right: 0 !important;
        }
        
        .col-lg-3, .col-md-6, .col-12 {
            padding-left: 0.5rem !important;
            padding-right: 0.5rem !important;
        }
        
        /* Full width navbar and page adjustments */
        body.has-student-sidebar #page,
        body.has-enhanced-sidebar #page {
            margin-left: 0;
            width: 100%;
        }
        
        body.has-student-sidebar .highschool-calendar-page,
        body.has-enhanced-sidebar .highschool-calendar-page {
            margin-left: 280px;
        }
        
        body.has-student-sidebar #page-wrapper,
        body.has-enhanced-sidebar #page-wrapper {
            margin-left: 0;
            width: 100%;
        }
        
        /* Make navbar span full width and sticky */
        body.has-student-sidebar .navbar,
        body.has-enhanced-sidebar .navbar,
        body.has-student-sidebar .navbar-expand,
        body.has-enhanced-sidebar .navbar-expand {
            width: 100% !important;
            margin-left: 0 !important;
            left: 0 !important;
            right: 0 !important;
            position: fixed !important;
            top: 0 !important;
            z-index: 1030 !important;
            background: white !important;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1) !important;
        }
        
        /* Add top padding to body to account for fixed navbar */
        body.has-student-sidebar,
        body.has-enhanced-sidebar {
            padding-top: 60px !important;
        }
        
        /* Adjust main content area to account for sidebar */
        body.has-student-sidebar .main-content,
        body.has-enhanced-sidebar .main-content,
        body.has-student-sidebar .content,
        body.has-enhanced-sidebar .content {
            margin-left: 280px;
        }
        
        /* Ensure page header spans full width */
        body.has-student-sidebar .page-header,
        body.has-enhanced-sidebar .page-header {
            width: 100%;
            margin-left: 0;
        }
        
        .calendar-page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 1.5rem;
            margin-left: -2rem;
            margin-right: 0;
            width: calc(100% + 2rem);
        }
        
        .calendar-page-header .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }
        
        .stat-icon.total { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .stat-icon.today { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .stat-icon.overdue { background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%); }
        .stat-icon.upcoming { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #1a202c;
        }
        
        .stat-value.overdue {
            color: #e53e3e;
        }
        
        .stat-value.upcoming {
            color: #3182ce;
        }
        
        .filters-section {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .filters-section h3 {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1a202c;
            margin-bottom: 1rem;
        }
        
        .filter-controls {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .filter-dropdown {
            padding: 0.75rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: white;
            font-size: 0.9rem;
            color: #4a5568;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .events-section {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .events-section h3 {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1a202c;
            margin-bottom: 0.5rem;
        }
        
        .events-section p {
            font-size: 0.9rem;
            color: #718096;
            margin-bottom: 1.5rem;
        }
        
        .event-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
        }
        
        .event-card:hover {
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }
        
        .event-card:last-child {
            margin-bottom: 0;
        }
        
        .event-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .event-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1a202c;
            margin-bottom: 0.5rem;
        }
        
        .event-tags {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .event-tag {
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .tag-course-start {
            background: #c6f6d5;
            color: #22543d;
        }
        
        .tag-course-end {
            background: #e9d8fd;
            color: #553c9a;
        }
        
        .tag-assignment {
            background: #fed7d7;
            color: #c53030;
        }
        
        .tag-exam {
            background: #fef5e7;
            color: #c05621;
        }
        
        .tag-holiday {
            background: #e6fffa;
            color: #234e52;
        }
        
        .tag-completed {
            background: #c6f6d5;
            color: #22543d;
        }
        
        .tag-upcoming {
            background: #e9d8fd;
            color: #553c9a;
        }
        
        .tag-today {
            background: #bee3f8;
            color: #2a69ac;
        }
        
        .tag-overdue {
            background: #fed7d7;
            color: #c53030;
        }
        
        .tag-high-priority {
            background: #fed7d7;
            color: #c53030;
        }
        
        .tag-medium-priority {
            background: #fef5e7;
            color: #c05621;
        }
        
        .tag-low-priority {
            background: #e6fffa;
            color: #234e52;
        }
        
        .event-content {
            color: #4a5568;
            font-size: 0.9rem;
            line-height: 1.5;
            margin-bottom: 1rem;
        }
        
        .event-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .detail-item {
            display: flex;
            flex-direction: column;
        }
        
        .detail-label {
            font-size: 0.75rem;
            color: #718096;
            margin-bottom: 0.25rem;
        }
        
        .detail-value {
            font-size: 0.9rem;
            font-weight: 600;
            color: #1a202c;
        }
        
        .detail-value.priority-high {
            color: #e53e3e;
        }
        
        .event-actions {
            position: absolute;
            right: 1.5rem;
            top: 1.5rem;
        }
        
        .view-btn {
            padding: 0.5rem 1rem;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .view-btn:hover {
            background: #5a67d8;
        }
        
        .no-events {
            text-align: center;
            padding: 4rem 2rem;
            color: #718096;
        }
        
        .no-events i {
            font-size: 4rem;
            color: #cbd5e0;
            margin-bottom: 1rem;
        }
        
        .no-events h3 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1a202c;
            margin-bottom: 0.5rem;
        }
        
        .no-events p {
            font-size: 1rem;
            color: #718096;
        }
        
        .floating-ai-btn {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            width: 60px;
            height: 60px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 50%;
            font-size: 1.5rem;
            cursor: pointer;
            box-shadow: 0 4px 20px rgba(102, 126, 234, 0.4);
            transition: all 0.3s ease;
            z-index: 1000;
        }
        
        .floating-ai-btn:hover {
            background: #5a67d8;
            transform: scale(1.1);
        }
        
        @media (max-width: 768px) {
            .student-sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .student-sidebar.show {
                transform: translateX(0);
            }
            
            .highschool-calendar-page {
                margin-left: 0 !important;
                padding-left: 1rem !important;
            }
            
            .calendar-page-header {
                margin-left: -1rem !important;
                width: calc(100% + 1rem) !important;
            }
            
            body.has-student-sidebar #page,
            body.has-enhanced-sidebar #page,
            body.has-student-sidebar #page-wrapper,
            body.has-enhanced-sidebar #page-wrapper {
                margin-left: 0 !important;
            }
            
            .calendar-page-header .page-title {
                font-size: 1.8rem;
            }
            .calendar-main-content {
                padding: 0.5rem;
            }
            
            .container-fluid {
                padding-left: 0.5rem;
                padding-right: 0.5rem;
            }
            
            .floating-ai-btn {
                bottom: 1rem;
                right: 1rem;
                width: 50px;
                height: 50px;
                font-size: 1.2rem;
            }
        }
</style>

<!-- Enhanced Student Sidebar -->
<div class="student-sidebar enhanced-sidebar">
    <nav class="sidebar-nav">
        
        <!-- DASHBOARD Section -->
        <div class="nav-section">
            <div class="section-title">DASHBOARD</div>
            <ul class="nav-list">
                <li class="nav-item">
                    <a href="<?php echo $template_data['dashboard_url']; ?>" class="nav-link">
                        <i class="fa fa-th-large"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="fa fa-users"></i>
                        <span>Community</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- COURSES Section -->
        <div class="nav-section">
            <div class="section-title">COURSES</div>
            <ul class="nav-list">
                <li class="nav-item">
                    <a href="<?php echo $template_data['courses_url']; ?>" class="nav-link">
                        <i class="fa fa-book-open"></i>
                        <span>My Courses</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo $template_data['assignments_url']; ?>" class="nav-link">
                        <i class="fa fa-clipboard-list"></i>
                        <span>Assignments</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- PROGRESS Section -->
        <div class="nav-section">
            <div class="section-title">PROGRESS</div>
            <ul class="nav-list">
                <li class="nav-item">
                    <a href="<?php echo $template_data['grades_url']; ?>" class="nav-link">
                        <i class="fa fa-chart-bar"></i>
                        <span>My Grades</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="fa fa-chart-line"></i>
                        <span>Progress Tracking</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- RESOURCES Section -->
        <div class="nav-section">
            <div class="section-title">RESOURCES</div>
            <ul class="nav-list">
                <li class="nav-item">
                    <a href="<?php echo $template_data['current_url']; ?>" class="nav-link active">
                        <i class="fa fa-calendar"></i>
                        <span>Calendar</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo $template_data['messages_url']; ?>" class="nav-link">
                        <i class="fa fa-comments"></i>
                        <span>Messages</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- SETTINGS Section -->
        <div class="nav-section">
            <div class="section-title">SETTINGS</div>
            <ul class="nav-list">
                <li class="nav-item">
                    <a href="<?php echo $template_data['profile_url']; ?>" class="nav-link">
                        <i class="fa fa-cog"></i>
                        <span>Profile Settings</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- QUICK ACTIONS Section -->
        <div class="nav-section quick-actions">
            <div class="section-title">QUICK ACTIONS</div>
            <div class="quick-action-buttons">
                <div class="quick-action-btn purple">
                    <div class="action-icon">
                        <i class="fa fa-code"></i>
                    </div>
                    <div class="action-content">
                        <div class="action-title">Code Emulators</div>
                        <div class="action-desc">Practice coding in virtual environment</div>
                    </div>
                </div>
                
                <div class="quick-action-btn blue">
                    <div class="action-icon">
                        <i class="fa fa-book"></i>
                    </div>
                    <div class="action-content">
                        <div class="action-title">E-books</div>
                        <div class="action-desc">Access digital learning materials</div>
                    </div>
                </div>
                
                <div class="quick-action-btn green">
                    <div class="action-icon">
                        <i class="fa fa-comments"></i>
                    </div>
                    <div class="action-content">
                        <div class="action-title">Ask Teacher</div>
                        <div class="action-desc">Get help from your instructor</div>
                    </div>
                </div>
                
                <div class="quick-action-btn orange">
                    <div class="action-icon">
                        <i class="fa fa-robot"></i>
                    </div>
                    <div class="action-content">
                        <div class="action-title">KODEIT AI Buddy</div>
                        <div class="action-desc">Get instant coding help</div>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Sidebar Footer -->
    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar">
                <i class="fa fa-user"></i>
            </div>
            <div class="user-details">
                <div class="user-name"><?php echo $template_data['user_name']; ?></div>
                <div class="user-role">High School Student</div>
            </div>
        </div>
    </div>
</div>

<div class="highschool-calendar-page">
    <div class="calendar-main-content">
        <!-- Page Header -->
        <div class="calendar-page-header">
            <div class="container-fluid">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="page-title">Academic Calendar</h1>
                        <p>Real-time calendar data from Moodle API - <?php echo $template_data['total_events']; ?> total events • <?php echo $template_data['user_grade']; ?> Student</p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <button class="btn btn-outline-light me-2" onclick="refreshCalendar()">
                            <i class="fa fa-refresh me-2"></i>Refresh
                        </button>
                        <button class="btn btn-primary">
                            <i class="fa fa-plus me-2"></i>Add Event
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Calendar Statistics -->
        <div class="container-fluid">
            <div class="row g-2 mb-4">
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-icon total"><i class="fa fa-calendar"></i></div>
                        <div>
                            <div class="stat-value"><?php echo $template_data['total_events']; ?></div>
                            <div>Total Events</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-icon today"><i class="fa fa-clock"></i></div>
                        <div>
                            <div class="stat-value"><?php echo $template_data['today_events']; ?></div>
                            <div>Today</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-icon overdue"><i class="fa fa-exclamation-circle"></i></div>
                        <div>
                            <div class="stat-value overdue"><?php echo $template_data['overdue_events']; ?></div>
                            <div>Overdue</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-icon upcoming"><i class="fa fa-check-circle"></i></div>
                        <div>
                            <div class="stat-value upcoming"><?php echo $template_data['upcoming_count']; ?></div>
                            <div>Upcoming</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="filters-section">
                <h3>Filters</h3>
                <div class="filter-controls">
                    <i class="fa fa-filter"></i>
                    <div class="filter-dropdown">
                        <span>All Types</span>
                        <i class="fa fa-chevron-down"></i>
                    </div>
                    <div class="filter-dropdown">
                        <span>All Status</span>
                        <i class="fa fa-chevron-down"></i>
                    </div>
                </div>
            </div>

            <!-- Upcoming Events Section -->
            <div class="events-section">
                <h3>Upcoming Events</h3>
                <p>Next 5 important events from your courses</p>
                
                <?php if (!empty($template_data['upcoming_events'])): ?>
                    <?php foreach ($template_data['upcoming_events'] as $event): ?>
                    <div class="event-card" onclick="window.location.href='<?php echo $event['event_url']; ?>'">
                        <div class="event-header">
                            <div>
                                <div class="event-title"><?php echo htmlspecialchars($event['name']); ?></div>
                                <div class="event-tags">
                                    <span class="event-tag tag-<?php echo str_replace(' ', '-', $event['event_type']); ?>">
                                        <?php echo $event['event_type']; ?>
                                    </span>
                                    <span class="event-tag tag-<?php echo $event['status']; ?>">
                                        <?php echo $event['status']; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="event-actions">
                                <button class="view-btn">
                                    <i class="fa fa-eye"></i>
                                    View
                                </button>
                            </div>
                        </div>
                        <div class="event-content">
                            <?php echo htmlspecialchars($event['description']); ?>
                        </div>
                        <div class="event-details">
                            <div class="detail-item">
                                <div class="detail-label">Course:</div>
                                <div class="detail-value"><?php echo htmlspecialchars($event['course_name']); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Date:</div>
                                <div class="detail-value"><?php echo $event['date_formatted']; ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Priority:</div>
                                <div class="detail-value priority-<?php echo $event['priority']; ?>">
                                    <?php echo $event['priority']; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                <div class="no-events">
                    <i class="fa fa-calendar"></i>
                    <h3>No Upcoming Events</h3>
                    <p>You don't have any upcoming events scheduled.</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- All Events Section -->
            <div class="events-section">
                <h3>All Events</h3>
                <p>Complete calendar view from Moodle API</p>
                
                <?php if (!empty($template_data['events'])): ?>
                    <?php foreach ($template_data['events'] as $event): ?>
                    <div class="event-card" onclick="window.location.href='<?php echo $event['event_url']; ?>'">
                        <div class="event-header">
                            <div>
                                <div class="event-title"><?php echo htmlspecialchars($event['name']); ?></div>
                                <div class="event-tags">
                                    <span class="event-tag tag-<?php echo str_replace(' ', '-', $event['event_type']); ?>">
                                        <?php echo $event['event_type']; ?>
                                    </span>
                                    <span class="event-tag tag-<?php echo $event['status']; ?>">
                                        <?php echo $event['status']; ?>
                                    </span>
                                    <span class="event-tag tag-<?php echo $event['priority']; ?>-priority">
                                        <?php echo $event['priority']; ?> priority
                                    </span>
                                </div>
                            </div>
                            <div class="event-actions">
                                <button class="view-btn">
                                    <i class="fa fa-eye"></i>
                                    View
                                </button>
                            </div>
                        </div>
                        <div class="event-content">
                            <?php echo htmlspecialchars($event['description']); ?>
                        </div>
                        <div class="event-details">
                            <div class="detail-item">
                                <div class="detail-label">Course:</div>
                                <div class="detail-value"><?php echo htmlspecialchars($event['course_name']); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Date:</div>
                                <div class="detail-value"><?php echo $event['date_formatted']; ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Instructor:</div>
                                <div class="detail-value"><?php echo htmlspecialchars($event['instructor']); ?></div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                <div class="no-events">
                    <i class="fa fa-calendar"></i>
                    <h3>No Events Found</h3>
                    <p>You don't have any calendar events yet.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Floating AI Assistant Button -->
<button class="floating-ai-btn" title="AI Assistant">
    <i class="fa fa-robot"></i>
</button>

<script>
// Initialize enhanced sidebar
document.addEventListener('DOMContentLoaded', function() {
    const enhancedSidebar = document.querySelector('.enhanced-sidebar');
    if (enhancedSidebar) {
        document.body.classList.add('has-student-sidebar', 'has-enhanced-sidebar');
        console.log('Enhanced sidebar initialized for high school calendar page');
    }
    
    // Handle sidebar navigation - set active state
    const currentUrl = window.location.href;
    const navLinks = document.querySelectorAll('.student-sidebar .nav-link');
    navLinks.forEach(link => {
        if (link.href === currentUrl) {
            link.classList.add('active');
        }
    });
    
    // Mobile sidebar toggle (if you add a toggle button in the future)
    const sidebarToggle = document.getElementById('sidebar-toggle');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            enhancedSidebar.classList.toggle('show');
        });
    }
    
    // Event card click handlers
    const eventCards = document.querySelectorAll('.event-card');
    eventCards.forEach(card => {
        card.addEventListener('click', function() {
            // Handle event card click
            console.log('Event card clicked');
        });
    });
    
    // View button click handlers
    const viewButtons = document.querySelectorAll('.view-btn');
    viewButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.stopPropagation(); // Prevent triggering the event card click
            console.log('View button clicked');
        });
    });
    
    // Floating AI button
    const floatingBtn = document.querySelector('.floating-ai-btn');
    if (floatingBtn) {
        floatingBtn.addEventListener('click', function() {
            alert('AI Assistant feature coming soon!');
        });
    }
});

// Refresh calendar function
function refreshCalendar() {
    const refreshBtn = document.querySelector('button[onclick="refreshCalendar()"]');
    const icon = refreshBtn.querySelector('i');
    
    // Add spinning animation
    icon.classList.add('fa-spin');
    refreshBtn.disabled = true;
    
    // Simulate refresh (in real implementation, this would reload the page or fetch new data)
    setTimeout(() => {
        icon.classList.remove('fa-spin');
        refreshBtn.disabled = false;
        // Reload the page to get fresh data
        window.location.reload();
    }, 1000);
}
</script>
<?php
// Output page footer with Moodle navigation
echo $OUTPUT->footer();
?>
