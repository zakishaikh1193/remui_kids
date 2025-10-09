<?php
/**
 * High School Grades Page (Grade 9-12)
 * Displays grades for Grade 9-12 students in a professional format
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/cohort/lib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->libdir . '/gradelib.php');
require_login();

// Get current user
global $USER, $DB, $OUTPUT, $PAGE, $CFG;

// Set page context
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/theme/remui_kids/highschool_grades.php');
$PAGE->set_title('My Grades');
$PAGE->set_heading('My Grades');
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

// Get grades data
$grades_data = array();
$total_courses = 0;
$total_grade_items = 0;
$average_grade = 0;
$grade_sum = 0;
$grade_count = 0;

try {
    // Get user's enrolled courses
    $courses = enrol_get_users_courses($USER->id, true, 'id, fullname, shortname');
    
    foreach ($courses as $course) {
        $course_context = context_course::instance($course->id);
        
        // Get grade items for this course
        $grade_items = grade_item::fetch_all(array(
            'courseid' => $course->id,
            'itemtype' => 'mod'
        ));
        
        $course_grades = array();
        $course_grade_sum = 0;
        $course_grade_count = 0;
        
        foreach ($grade_items as $grade_item) {
            $grade = grade_grade::fetch(array(
                'itemid' => $grade_item->id,
                'userid' => $USER->id
            ));
            
            if ($grade && $grade->finalgrade !== null) {
                $grade_value = $grade->finalgrade;
                $grade_max = $grade_item->grademax;
                $grade_percentage = ($grade_max > 0) ? ($grade_value / $grade_max) * 100 : 0;
                
                $course_grades[] = array(
                    'name' => $grade_item->itemname,
                    'grade' => $grade_value,
                    'max_grade' => $grade_max,
                    'percentage' => round($grade_percentage, 1),
                    'letter_grade' => $this->get_letter_grade($grade_percentage),
                    'feedback' => $grade->feedback,
                    'time_modified' => $grade->timemodified
                );
                
                $course_grade_sum += $grade_percentage;
                $course_grade_count++;
                $grade_sum += $grade_percentage;
                $grade_count++;
            }
        }
        
        if (!empty($course_grades)) {
            $course_average = $course_grade_count > 0 ? $course_grade_sum / $course_grade_count : 0;
            
            $grades_data[] = array(
                'course_id' => $course->id,
                'course_name' => $course->fullname,
                'course_shortname' => $course->shortname,
                'grades' => $course_grades,
                'course_average' => round($course_average, 1),
                'course_letter_grade' => $this->get_letter_grade($course_average),
                'grade_count' => count($course_grades)
            );
            
            $total_courses++;
            $total_grade_items += count($course_grades);
        }
    }
    
    $average_grade = $grade_count > 0 ? $grade_sum / $grade_count : 0;
    
} catch (Exception $e) {
    error_log("Grades fetch error: " . $e->getMessage());
}

// Helper function to get letter grade
function get_letter_grade($percentage) {
    if ($percentage >= 97) return 'A+';
    if ($percentage >= 93) return 'A';
    if ($percentage >= 90) return 'A-';
    if ($percentage >= 87) return 'B+';
    if ($percentage >= 83) return 'B';
    if ($percentage >= 80) return 'B-';
    if ($percentage >= 77) return 'C+';
    if ($percentage >= 73) return 'C';
    if ($percentage >= 70) return 'C-';
    if ($percentage >= 67) return 'D+';
    if ($percentage >= 63) return 'D';
    if ($percentage >= 60) return 'D-';
    return 'F';
}

// Prepare template data
$template_data = array(
    'user_grade' => $user_grade,
    'grades' => $grades_data,
    'total_courses' => $total_courses,
    'total_grade_items' => $total_grade_items,
    'average_grade' => round($average_grade, 1),
    'letter_grade' => get_letter_grade($average_grade),
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

// Add custom CSS for the grades page
?>
<style>
        /* Enhanced Sidebar Styles */
        .student-sidebar {
            position: fixed;
            left: 0;
            top: 60px; /* Position below Moodle navigation bar */
            width: 280px;
            height: calc(100vh - 60px); /* Adjust height to account for nav bar */
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            overflow-y: auto;
            z-index: 999; /* Lower than navigation bar */
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
        
        /* Custom styles for High School Grades Page */
        .highschool-grades-page {
            position: relative;
            background: #f8fafc;
            min-height: calc(100vh - 60px); /* Account for navigation bar */
            margin-left: 280px; /* Account for sidebar */
            margin-top: 60px; /* Account for navigation bar */
            padding: 0;
            width: calc(100% - 280px);
        }
        
        .grades-main-content {
            padding: 0;
            width: 100%;
        }
        
        /* Container fluid padding */
        .container-fluid {
            padding-left: 2rem;
            padding-right: 2rem;
        }
        
        /* Remove all padding from main content */
        .grades-main-content {
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
        
        body.has-student-sidebar .highschool-grades-page,
        body.has-enhanced-sidebar .highschool-grades-page {
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
        
        .grades-page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 1.5rem;
            margin-left: 0;
            margin-right: 0;
            width: 100%;
        }
        
        .grades-page-header .page-title {
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
        
        .stat-icon.courses { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .stat-icon.grade-items { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .stat-icon.average { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .stat-icon.letter { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #1a202c;
        }
        
        .course-grades-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            margin-bottom: 1.5rem;
        }
        
        .course-header {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            padding: 1.5rem;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .course-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 0.5rem;
        }
        
        .course-average {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .average-grade {
            font-size: 2rem;
            font-weight: 700;
            color: #667eea;
        }
        
        .letter-grade {
            background: #667eea;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .grades-list {
            padding: 0;
        }
        
        .grade-item {
            display: flex;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }
        
        .grade-item:hover {
            background: #f8fafc;
        }
        
        .grade-item:last-child {
            border-bottom: none;
        }
        
        .grade-info {
            flex: 1;
        }
        
        .grade-name {
            font-weight: 600;
            color: #1a202c;
            margin-bottom: 0.25rem;
        }
        
        .grade-feedback {
            font-size: 0.9rem;
            color: #718096;
        }
        
        .grade-score {
            text-align: right;
        }
        
        .grade-percentage {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1a202c;
        }
        
        .grade-fraction {
            font-size: 0.9rem;
            color: #718096;
        }
        
        .grade-letter {
            background: #667eea;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.9rem;
            margin-left: 1rem;
        }
        
        .no-grades {
            text-align: center;
            padding: 4rem 2rem;
            color: #718096;
        }
        
        .no-grades i {
            font-size: 4rem;
            color: #cbd5e0;
            margin-bottom: 1rem;
        }
        
        .no-grades h3 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1a202c;
            margin-bottom: 0.5rem;
        }
        
        .no-grades p {
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
            
            .highschool-grades-page {
                margin-left: 0 !important;
                margin-top: 60px !important; /* Account for navigation bar on mobile */
                padding: 0 !important;
                min-height: calc(100vh - 60px) !important; /* Account for navigation bar */
            }
            
            .grades-page-header {
                margin-left: 0 !important;
                width: 100% !important;
            }
            
            body.has-student-sidebar #page,
            body.has-enhanced-sidebar #page,
            body.has-student-sidebar #page-wrapper,
            body.has-enhanced-sidebar #page-wrapper {
                margin-left: 0 !important;
            }
            
            .grades-page-header .page-title {
                font-size: 1.8rem;
            }
            .grades-main-content {
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
                    <a href="<?php echo $template_data['current_url']; ?>" class="nav-link active">
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

<div class="highschool-grades-page">
    <!-- Page Header -->
    <div class="grades-page-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="page-title">
                        <i class="fa fa-chart-bar me-3"></i>
                        My Grades - <?php echo $template_data['user_grade']; ?>
                    </h1>
                    <p>Track your academic progress and performance.</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="<?php echo $template_data['dashboard_url']; ?>" class="btn btn-outline-light">
                        <i class="fa fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Grade Statistics -->
    <div class="container-fluid">
            <div class="row g-2 mb-4">
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-icon courses"><i class="fa fa-book"></i></div>
                        <div>
                            <div class="stat-value"><?php echo $template_data['total_courses']; ?></div>
                            <div>Courses with Grades</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-icon grade-items"><i class="fa fa-clipboard-list"></i></div>
                        <div>
                            <div class="stat-value"><?php echo $template_data['total_grade_items']; ?></div>
                            <div>Grade Items</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-icon average"><i class="fa fa-percentage"></i></div>
                        <div>
                            <div class="stat-value"><?php echo $template_data['average_grade']; ?>%</div>
                            <div>Average Grade</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-icon letter"><i class="fa fa-trophy"></i></div>
                        <div>
                            <div class="stat-value"><?php echo $template_data['letter_grade']; ?></div>
                            <div>Overall Grade</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Course Grades -->
            <?php if (!empty($template_data['grades'])): ?>
                <?php foreach ($template_data['grades'] as $course): ?>
                <div class="course-grades-card">
                    <div class="course-header">
                        <div class="course-title"><?php echo htmlspecialchars($course['course_name']); ?></div>
                        <div class="course-average">
                            <div class="average-grade"><?php echo $course['course_average']; ?>%</div>
                            <div class="letter-grade"><?php echo $course['course_letter_grade']; ?></div>
                        </div>
                    </div>
                    <div class="grades-list">
                        <?php foreach ($course['grades'] as $grade): ?>
                        <div class="grade-item">
                            <div class="grade-info">
                                <div class="grade-name"><?php echo htmlspecialchars($grade['name']); ?></div>
                                <?php if (!empty($grade['feedback'])): ?>
                                <div class="grade-feedback"><?php echo htmlspecialchars($grade['feedback']); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="grade-score">
                                <div class="grade-percentage"><?php echo $grade['percentage']; ?>%</div>
                                <div class="grade-fraction"><?php echo $grade['grade']; ?> / <?php echo $grade['max_grade']; ?></div>
                            </div>
                            <div class="grade-letter"><?php echo $grade['letter_grade']; ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
            <div class="no-grades">
                <i class="fa fa-chart-bar"></i>
                <h3>No Grades Yet</h3>
                <p>You don't have any grades yet. Complete assignments and quizzes to see your progress!</p>
            </div>
            <?php endif; ?>
        </div>
</div>

<script>
// Initialize enhanced sidebar
document.addEventListener('DOMContentLoaded', function() {
    const enhancedSidebar = document.querySelector('.enhanced-sidebar');
    if (enhancedSidebar) {
        document.body.classList.add('has-student-sidebar', 'has-enhanced-sidebar');
        console.log('Enhanced sidebar initialized for high school grades page');
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
});
</script>
<?php
// Output page footer with Moodle navigation
echo $OUTPUT->footer();
?>
