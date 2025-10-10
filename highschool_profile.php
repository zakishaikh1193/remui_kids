<?php
/**
 * High School Profile Settings Page (Grade 9-12)
 * Displays profile settings for Grade 9-12 students in a professional format
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
$PAGE->set_url('/theme/remui_kids/highschool_profile.php');
$PAGE->set_title('Profile Settings');
$PAGE->set_heading('Profile Settings');
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

// Get user profile information
$user_profile = profile_user_record($USER->id);
$user_picture = $OUTPUT->user_picture($USER, array('size' => 100));

// Get user's enrolled courses count
$enrolled_courses = enrol_get_users_courses($USER->id, true);
$courses_count = count($enrolled_courses) - 1; // Subtract 1 for site course

// Get user's completion statistics
$completed_courses = 0;
$total_progress = 0;
foreach ($enrolled_courses as $course) {
    if ($course->id == 1) continue; // Skip site course
    try {
        $completion = new completion_info($course);
        if ($completion->is_enabled()) {
            $progress = (int) \core_completion\progress::get_course_progress_percentage($course, $USER->id);
            $total_progress += $progress;
            if ($progress >= 100) {
                $completed_courses++;
            }
        }
    } catch (Exception $e) {
        // Skip courses that don't exist
    }
}

$average_progress = $courses_count > 0 ? round($total_progress / $courses_count) : 0;

// Prepare template data
$template_data = array(
    'user_grade' => $user_grade,
    'user_name' => fullname($USER),
    'user_firstname' => $USER->firstname,
    'user_lastname' => $USER->lastname,
    'user_email' => $USER->email,
    'user_phone' => isset($USER->phone1) ? $USER->phone1 : '',
    'user_city' => isset($USER->city) ? $USER->city : '',
    'user_country' => isset($USER->country) ? $USER->country : '',
    'user_description' => isset($USER->description) ? $USER->description : '',
    'user_picture' => $user_picture,
    'courses_count' => $courses_count,
    'completed_courses' => $completed_courses,
    'average_progress' => $average_progress,
    'dashboard_url' => new moodle_url('/my/'),
    'current_url' => $PAGE->url->out(),
    'grades_url' => new moodle_url('/grade/report/overview/index.php'),
    'assignments_url' => new moodle_url('/theme/remui_kids/highschool_assignments.php'),
    'courses_url' => new moodle_url('/theme/remui_kids/highschool_courses.php'),
    'messages_url' => new moodle_url('/message/index.php'),
    'profile_url' => new moodle_url('/user/profile.php', array('id' => $USER->id)),
    'logout_url' => new moodle_url('/login/logout.php', array('sesskey' => sesskey())),
    'is_highschool' => true
);

// Output page header with Moodle navigation
echo $OUTPUT->header();

// Add custom CSS for the profile page
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
        
        /* Custom styles for High School Profile Page */
        .highschool-profile-page {
            position: relative;
            background: #f8fafc;
            min-height: 100vh;
            margin-left: 280px;
            padding-left: 2rem;
        }
        
        .profile-main-content {
            padding: 0;
            width: 100%;
        }
        
        /* Remove all padding for full-width feel */
        .container-fluid {
            padding-left: 0;
            padding-right: 0;
        }
        
        /* Remove all padding from main content */
        .profile-main-content {
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
        
        body.has-student-sidebar .highschool-profile-page,
        body.has-enhanced-sidebar .highschool-profile-page {
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
        
        .profile-page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 1.5rem;
            margin-left: -2rem;
            margin-right: 0;
            width: calc(100% + 2rem);
        }
        
        .profile-page-header .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .profile-stats-section {
            padding: 2rem 0;
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
        .stat-icon.completed { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .stat-icon.progress { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
        .stat-icon.grade { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #1a202c;
        }
        
        .profile-content-section {
            padding: 2rem 0;
        }
        
        .profile-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }
        
        .profile-avatar-section {
            text-align: center;
            padding: 2rem;
            border-bottom: 1px solid #e2e8f0;
            margin-bottom: 2rem;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin: 0 auto 1rem;
            border: 4px solid #667eea;
            overflow: hidden;
        }
        
        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .profile-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 0.5rem;
        }
        
        .profile-grade {
            font-size: 1rem;
            color: #667eea;
            font-weight: 600;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            font-weight: 600;
            color: #1a202c;
            margin-bottom: 0.5rem;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #f7fafc;
            border: 1px solid #e2e8f0;
            color: #4a5568;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-secondary:hover {
            background: #edf2f7;
        }
        
        @media (max-width: 768px) {
            .student-sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .student-sidebar.show {
                transform: translateX(0);
            }
            
            .highschool-profile-page {
                margin-left: 0 !important;
                padding-left: 1rem !important;
            }
            
            .profile-page-header {
                margin-left: -1rem !important;
                width: calc(100% + 1rem) !important;
            }
            
            body.has-student-sidebar #page,
            body.has-enhanced-sidebar #page,
            body.has-student-sidebar #page-wrapper,
            body.has-enhanced-sidebar #page-wrapper {
                margin-left: 0 !important;
            }
            
            .profile-page-header .page-title {
                font-size: 1.8rem;
            }
            .profile-main-content {
                padding: 0.5rem;
            }
            
            .container-fluid {
                padding-left: 0.5rem;
                padding-right: 0.5rem;
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
                    <a href="<?php echo new moodle_url('/theme/remui_kids/highschool_messages.php'); ?>" class="nav-link">
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
                    <a href="<?php echo $template_data['current_url']; ?>" class="nav-link active">
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

<div class="highschool-profile-page">
    <div class="profile-main-content">
        <!-- Page Header -->
        <div class="profile-page-header">
            <div class="container-fluid">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="page-title">
                            <i class="fa fa-user-cog me-3"></i>
                            Profile Settings - <?php echo $template_data['user_grade']; ?>
                        </h1>
                        <p>Manage your profile information and preferences.</p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <a href="<?php echo $template_data['dashboard_url']; ?>" class="btn btn-outline-light">
                            <i class="fa fa-arrow-left me-2"></i>Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Profile Statistics -->
        <div class="container-fluid">
            <div class="profile-stats-section">
                <div class="row g-2 mb-4">
                    <div class="col-lg-3 col-md-6">
                        <div class="stat-card">
                            <div class="stat-icon courses"><i class="fa fa-book"></i></div>
                            <div>
                                <div class="stat-value"><?php echo $template_data['courses_count']; ?></div>
                                <div>Enrolled Courses</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="stat-card">
                            <div class="stat-icon completed"><i class="fa fa-check-circle"></i></div>
                            <div>
                                <div class="stat-value"><?php echo $template_data['completed_courses']; ?></div>
                                <div>Completed</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="stat-card">
                            <div class="stat-icon progress"><i class="fa fa-chart-line"></i></div>
                            <div>
                                <div class="stat-value"><?php echo $template_data['average_progress']; ?>%</div>
                                <div>Average Progress</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="stat-card">
                            <div class="stat-icon grade"><i class="fa fa-graduation-cap"></i></div>
                            <div>
                                <div class="stat-value"><?php echo $template_data['user_grade']; ?></div>
                                <div>Grade Level</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Profile Content -->
            <div class="profile-content-section">
                <div class="row">
                    <div class="col-lg-4">
                        <!-- Profile Avatar Card -->
                        <div class="profile-card">
                            <div class="profile-avatar-section">
                                <div class="profile-avatar">
                                    <?php echo $template_data['user_picture']; ?>
                                </div>
                                <div class="profile-name"><?php echo $template_data['user_name']; ?></div>
                                <div class="profile-grade"><?php echo $template_data['user_grade']; ?> Student</div>
                            </div>
                            <div class="text-center">
                                <button class="btn btn-secondary">
                                    <i class="fa fa-camera me-2"></i>Change Photo
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-8">
                        <!-- Profile Information Card -->
                        <div class="profile-card">
                            <h3 class="mb-4">
                                <i class="fa fa-user me-2"></i>Personal Information
                            </h3>
                            
                            <form>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">First Name</label>
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($template_data['user_firstname']); ?>" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">Last Name</label>
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($template_data['user_lastname']); ?>" readonly>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" class="form-control" value="<?php echo htmlspecialchars($template_data['user_email']); ?>" readonly>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">Phone Number</label>
                                            <input type="tel" class="form-control" value="<?php echo htmlspecialchars($template_data['user_phone']); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">City</label>
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($template_data['user_city']); ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Country</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($template_data['user_country']); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Bio / Description</label>
                                    <textarea class="form-control" rows="4" placeholder="Tell us about yourself..."><?php echo htmlspecialchars($template_data['user_description']); ?></textarea>
                                </div>
                                
                                <div class="text-end">
                                    <button type="button" class="btn btn-secondary me-2">
                                        <i class="fa fa-times me-2"></i>Cancel
                                    </button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa fa-save me-2"></i>Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Account Settings Card -->
                        <div class="profile-card">
                            <h3 class="mb-4">
                                <i class="fa fa-cog me-2"></i>Account Settings
                            </h3>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Change Password</label>
                                        <button type="button" class="btn btn-secondary">
                                            <i class="fa fa-key me-2"></i>Update Password
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Notification Preferences</label>
                                        <button type="button" class="btn btn-secondary">
                                            <i class="fa fa-bell me-2"></i>Manage Notifications
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize enhanced sidebar
document.addEventListener('DOMContentLoaded', function() {
    const enhancedSidebar = document.querySelector('.enhanced-sidebar');
    if (enhancedSidebar) {
        document.body.classList.add('has-student-sidebar', 'has-enhanced-sidebar');
        console.log('Enhanced sidebar initialized for high school profile page');
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
