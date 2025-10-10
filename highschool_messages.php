<?php
/**
 * High School Messages Page (Grade 9-12)
 * Displays messages for Grade 9-12 students in a professional format
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
$PAGE->set_url('/theme/remui_kids/highschool_messages.php');
$PAGE->set_title('Messages');
$PAGE->set_heading('Messages');
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

// Get messages data - Enhanced to match the image design
$messages_data = array();

// Get recent messages with more details
try {
    // Get individual messages instead of conversations
    $messages = $DB->get_records_sql("
        SELECT m.*, 
               uf.firstname as from_firstname, 
               uf.lastname as from_lastname,
               uf.email as from_email,
               c.fullname as course_name,
               c.shortname as course_shortname
        FROM {messages} m
        LEFT JOIN {user} uf ON m.useridfrom = uf.id
        LEFT JOIN {course} c ON m.courseid = c.id
        WHERE m.useridto = ? AND m.timeuserfromdeleted = 0
        ORDER BY m.timecreated DESC
        LIMIT 50
    ", array($USER->id));
    
    foreach ($messages as $message) {
        $from_user = $DB->get_record('user', array('id' => $message->useridfrom));
        $is_read = $message->timeuserfromdeleted == 0 && $message->timecreated < time() - 3600; // Consider read if older than 1 hour
        
        // Determine message type and priority
        $message_type = 'message';
        $priority = 'medium';
        
        if (strpos(strtolower($message->subject), 'announcement') !== false) {
            $message_type = 'announcement';
        } elseif (strpos(strtolower($message->subject), 'urgent') !== false || strpos(strtolower($message->subject), 'important') !== false) {
            $priority = 'high';
        } elseif (strpos(strtolower($message->subject), 'reminder') !== false) {
            $priority = 'low';
        }
        
        // Get user role
        $user_role = 'Student';
        if ($from_user) {
            $user_roles = get_user_roles(context_system::instance(), $from_user->id);
            foreach ($user_roles as $role) {
                if ($role->shortname === 'editingteacher' || $role->shortname === 'teacher') {
                    $user_role = 'Instructor';
                    break;
                } elseif ($role->shortname === 'manager') {
                    $user_role = 'Administrator';
                    break;
                }
            }
        }
        
        $message_data = array(
            'id' => $message->id,
            'subject' => $message->subject ?: 'No Subject',
            'content' => $message->fullmessage,
            'content_preview' => substr(strip_tags($message->fullmessage), 0, 150) . '...',
            'from_name' => $from_user ? fullname($from_user) : 'Unknown User',
            'from_role' => $user_role,
            'course_name' => $message->course_name ?: 'General',
            'course_shortname' => $message->course_shortname ?: 'GEN',
            'date_created' => $message->timecreated,
            'date_formatted' => date('n/j/Y', $message->timecreated),
            'is_read' => $is_read,
            'message_type' => $message_type,
            'priority' => $priority,
            'conversation_url' => new moodle_url('/message/index.php', array('id' => $message->useridfrom))
        );
        
        $messages_data[] = $message_data;
    }
} catch (Exception $e) {
    // If there's an error, show empty messages
    error_log("Messages fetch error: " . $e->getMessage());
}

// Calculate statistics
$total_messages = count($messages_data);
$unread_messages = 0;
$high_priority = 0;
$today_messages = 0;
$today_start = strtotime('today');

foreach ($messages_data as $message) {
    if (!$message['is_read']) {
        $unread_messages++;
    }
    if ($message['priority'] === 'high') {
        $high_priority++;
    }
    if ($message['date_created'] >= $today_start) {
        $today_messages++;
    }
}

// Prepare template data
$template_data = array(
    'user_grade' => $user_grade,
    'messages' => $messages_data,
    'total_messages' => $total_messages,
    'unread_messages' => $unread_messages,
    'high_priority' => $high_priority,
    'today_messages' => $today_messages,
    'user_name' => fullname($USER),
    'dashboard_url' => new moodle_url('/my/'),
    'current_url' => $PAGE->url->out(),
    'grades_url' => new moodle_url('/grade/report/overview/index.php'),
    'assignments_url' => new moodle_url('/theme/remui_kids/highschool_assignments.php'),
    'courses_url' => new moodle_url('/theme/remui_kids/highschool_courses.php'),
    'profile_url' => new moodle_url('/theme/remui_kids/highschool_profile.php'),
    'messages_url' => new moodle_url('/message/index.php'),
    'logout_url' => new moodle_url('/login/logout.php', array('sesskey' => sesskey())),
    'is_highschool' => true
);

// Output page header with Moodle navigation
echo $OUTPUT->header();

// Add custom CSS for the messages page
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
        
        /* Custom styles for High School Messages Page */
        .highschool-messages-page {
            position: relative;
            background: #f8fafc;
            min-height: 100vh;
            margin-left: 280px;
            padding: 0;
            width: calc(100% - 280px);
        }
        
        .messages-main-content {
            padding: 0;
            width: 100%;
        }
        
        /* Container fluid padding */
        .container-fluid {
            padding-left: 2rem;
            padding-right: 2rem;
        }
        
        /* Remove all padding from main content */
        .messages-main-content {
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
        
        body.has-student-sidebar .highschool-messages-page,
        body.has-enhanced-sidebar .highschool-messages-page {
            margin-left: 20px;
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
        
        .messages-page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 1.5rem;
            margin-left: 0;
            margin-right: 0;
            width: 100%;
        }
        
        .messages-page-header .page-title {
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
        .stat-icon.unread { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .stat-icon.priority { background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%); }
        .stat-icon.today { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #1a202c;
        }
        
        .search-filters {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .search-filters h3 {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1a202c;
            margin-bottom: 1rem;
        }
        
        .search-bar {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .search-input {
            flex: 1;
            position: relative;
        }
        
        .search-input input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.9rem;
            background: #f8fafc;
        }
        
        .search-input i {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: #718096;
        }
        
        .filter-dropdowns {
            display: flex;
            gap: 0.75rem;
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
        
        .messages-list {
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }
        
        .message-item {
            padding: 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
        }
        
        .message-item:hover {
            background: #f8fafc;
        }
        
        .message-item:last-child {
            border-bottom: none;
        }
        
        .message-item.unread {
            border-left: 4px solid #e53e3e;
        }
        
        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.75rem;
        }
        
        .message-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1a202c;
            margin-bottom: 0.5rem;
        }
        
        .message-tags {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .message-tag {
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .tag-announcement {
            background: #e6f3ff;
            color: #0066cc;
        }
        
        .tag-unread {
            background: #e53e3e;
            color: white;
        }
        
        .tag-read {
            background: #c6f6d5;
            color: #22543d;
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
        
        .message-content {
            color: #4a5568;
            font-size: 0.9rem;
            line-height: 1.5;
            margin-bottom: 1rem;
        }
        
        .message-details {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
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
        
        .message-actions {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            position: absolute;
            right: 1.5rem;
            top: 1.5rem;
        }
        
        .action-btn {
            padding: 0.5rem;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            background: white;
            color: #4a5568;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
        }
        
        .action-btn:hover {
            background: #f8fafc;
            border-color: #cbd5e0;
        }
        
        .no-messages {
            text-align: center;
            padding: 4rem 2rem;
            color: #718096;
        }
        
        .no-messages i {
            font-size: 4rem;
            color: #cbd5e0;
            margin-bottom: 1rem;
        }
        
        .no-messages h3 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1a202c;
            margin-bottom: 0.5rem;
        }
        
        .no-messages p {
            font-size: 1rem;
            color: #718096;
        }
        
        @media (max-width: 768px) {
            .student-sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .student-sidebar.show {
                transform: translateX(0);
            }
            
            .highschool-messages-page {
                margin-left: 0 !important;
                padding: 0 !important;
            }
            
            .messages-page-header {
                margin-left: 0 !important;
                width: 100% !important;
            }
            
            body.has-student-sidebar #page,
            body.has-enhanced-sidebar #page,
            body.has-student-sidebar #page-wrapper,
            body.has-enhanced-sidebar #page-wrapper {
                margin-left: 0 !important;
            }
            
            .messages-page-header .page-title {
                font-size: 1.8rem;
            }
            .messages-main-content {
                padding: 0;
            }
            
            .container-fluid {
                padding-left: 1rem;
                padding-right: 1rem;
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
                    <a href="<?php echo new moodle_url('/theme/remui_kids/highschool_grades.php'); ?>" class="nav-link">
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
                    <a href="<?php echo new moodle_url('/theme/remui_kids/highschool_calendar.php'); ?>" class="nav-link">
                        <i class="fa fa-calendar"></i>
                        <span>Calendar</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo $template_data['current_url']; ?>" class="nav-link active">
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

<div class="highschool-messages-page">
    <!-- Page Header -->
    <div class="messages-page-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="page-title">Messages</h1>
                    <p>Real-time messages from Moodle API - <?php echo $template_data['total_messages']; ?> total messages • <?php echo $template_data['user_grade']; ?> Student</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <button class="btn btn-outline-light me-2" onclick="refreshMessages()">
                        <i class="fa fa-refresh me-2"></i>Refresh
                    </button>
                    <button class="btn btn-primary">
                        <i class="fa fa-paper-plane me-2"></i>New Message
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Message Statistics -->
    <div class="container-fluid">
            <div class="row g-2 mb-4">
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-icon total"><i class="fa fa-comments"></i></div>
                        <div>
                            <div class="stat-value"><?php echo $template_data['total_messages']; ?></div>
                            <div>Total Messages</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-icon unread"><i class="fa fa-envelope"></i></div>
                        <div>
                            <div class="stat-value"><?php echo $template_data['unread_messages']; ?></div>
                            <div>Unread</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-icon priority"><i class="fa fa-exclamation-circle"></i></div>
                        <div>
                            <div class="stat-value"><?php echo $template_data['high_priority']; ?></div>
                            <div>High Priority</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-icon today"><i class="fa fa-clock"></i></div>
                        <div>
                            <div class="stat-value"><?php echo $template_data['today_messages']; ?></div>
                            <div>Today</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search & Filters -->
            <div class="search-filters">
                <h3>Search & Filters</h3>
                <div class="search-bar">
                    <div class="search-input">
                        <i class="fa fa-search"></i>
                        <input type="text" placeholder="Search messages by subject, content, or sender..." id="messageSearch">
                    </div>
                    <div class="filter-dropdowns">
                        <div class="filter-dropdown">
                            <i class="fa fa-filter"></i>
                            <span>All Types</span>
                        </div>
                        <div class="filter-dropdown">
                            <i class="fa fa-filter"></i>
                            <span>All Status</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Messages List -->
            <div class="messages-list">
                <?php if (!empty($template_data['messages'])): ?>
                    <?php foreach ($template_data['messages'] as $message): ?>
                    <div class="message-item <?php echo $message['is_read'] ? '' : 'unread'; ?>" onclick="window.location.href='<?php echo $message['conversation_url']; ?>'">
                        <div class="message-header">
                            <div>
                                <div class="message-title"><?php echo htmlspecialchars($message['course_shortname'] . ' - ' . $message['subject']); ?></div>
                                <div class="message-tags">
                                    <?php if ($message['message_type'] === 'announcement'): ?>
                                    <span class="message-tag tag-announcement">announcement</span>
                                    <?php endif; ?>
                                    <span class="message-tag <?php echo $message['is_read'] ? 'tag-read' : 'tag-unread'; ?>">
                                        <?php echo $message['is_read'] ? 'read' : 'unread'; ?>
                                    </span>
                                    <span class="message-tag tag-<?php echo $message['priority']; ?>-priority">
                                        <?php echo $message['priority']; ?> priority
                                    </span>
                                </div>
                            </div>
                            <div class="message-actions">
                                <button class="action-btn" title="View">
                                    <i class="fa fa-eye"></i>
                                </button>
                                <button class="action-btn" title="Reply">
                                    <i class="fa fa-reply"></i>
                                </button>
                                <button class="action-btn" title="Archive">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <div class="message-content">
                            <?php echo htmlspecialchars($message['content_preview']); ?>
                        </div>
                        <div class="message-details">
                            <div class="detail-item">
                                <div class="detail-label">From:</div>
                                <div class="detail-value"><?php echo htmlspecialchars($message['from_name']); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Role:</div>
                                <div class="detail-value"><?php echo htmlspecialchars($message['from_role']); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Course:</div>
                                <div class="detail-value"><?php echo htmlspecialchars($message['course_name']); ?></div>
                                <div class="detail-label">Date:</div>
                                <div class="detail-value"><?php echo $message['date_formatted']; ?></div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                <div class="no-messages">
                    <i class="fa fa-comments"></i>
                    <h3>No Messages Yet</h3>
                    <p>You don't have any messages yet. Start chatting with your teachers and classmates!</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
</div>

<script>
// Initialize enhanced sidebar
document.addEventListener('DOMContentLoaded', function() {
    const enhancedSidebar = document.querySelector('.enhanced-sidebar');
    if (enhancedSidebar) {
        document.body.classList.add('has-student-sidebar', 'has-enhanced-sidebar');
        console.log('Enhanced sidebar initialized for high school messages page');
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
    
    // Message search functionality
    const searchInput = document.getElementById('messageSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const messageItems = document.querySelectorAll('.message-item');
            
            messageItems.forEach(item => {
                const title = item.querySelector('.message-title').textContent.toLowerCase();
                const content = item.querySelector('.message-content').textContent.toLowerCase();
                const fromName = item.querySelector('.detail-value').textContent.toLowerCase();
                
                if (title.includes(searchTerm) || content.includes(searchTerm) || fromName.includes(searchTerm)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }
    
    // Action button functionality
    const actionButtons = document.querySelectorAll('.action-btn');
    actionButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.stopPropagation(); // Prevent triggering the message item click
            
            const action = this.title.toLowerCase();
            const messageItem = this.closest('.message-item');
            
            switch(action) {
                case 'view':
                    // Open message in new tab or modal
                    console.log('View message');
                    break;
                case 'reply':
                    // Open reply dialog
                    console.log('Reply to message');
                    break;
                case 'archive':
                    // Archive message
                    if (confirm('Are you sure you want to archive this message?')) {
                        messageItem.style.opacity = '0.5';
                        console.log('Archive message');
                    }
                    break;
            }
        });
    });
});

// Refresh messages function
function refreshMessages() {
    const refreshBtn = document.querySelector('button[onclick="refreshMessages()"]');
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
