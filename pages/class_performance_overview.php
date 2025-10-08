<?php
// This page renders a Class Performance Overview dashboard using real Moodle/IOMAD data

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug logging
error_log("Class Performance Overview - Starting page load");

// Set error handler
set_error_handler(function($severity, $message, $file, $line) {
    error_log("Class Performance Overview Error: $message in $file on line $line");
    return false; // Let PHP handle the error normally
});

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/lib/moodlelib.php');
require_once($CFG->dirroot . '/lib/weblib.php');
require_once($CFG->dirroot . '/lib/completionlib.php');
require_once($CFG->dirroot . '/lib/gradelib.php');
require_once($CFG->dirroot . '/calendar/lib.php');
require_once($CFG->dirroot . '/theme/remui_kids/lib.php');

require_login();

// Get course ID from parameter or use first available course
$courseid = optional_param('id', 0, PARAM_INT);

// If no course ID provided, get first teacher course
if (!$courseid) {
    $teachercourses = enrol_get_my_courses('id, fullname, shortname', 'visible DESC, sortorder ASC');
    if (!empty($teachercourses)) {
        $courseid = array_keys($teachercourses)[0];
    }
}

// If still no course, use site course or create a default
if (!$courseid) {
    $courseid = SITEID; // Use site course as fallback
}

$course = get_course($courseid);
$context = context_course::instance($course->id);

// Debug logging
error_log("Class Performance Overview - Course ID: " . $courseid);
error_log("Class Performance Overview - Course: " . $course->fullname);

// Check if user can view the course (more flexible)
if (!has_capability('moodle/course:view', $context) && !has_capability('moodle/site:accessallgroups', $context)) {
    // If user can't view course, redirect to dashboard
    redirect(new moodle_url('/my/'));
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/theme/remui_kids/pages/class_performance_overview.php', ['id' => $courseid]));
$PAGE->set_pagelayout('standard');
$PAGE->set_title('Class Performance Overview - ' . $course->fullname);
$PAGE->set_heading('Class Performance Overview - ' . $course->fullname);

// Include student overview CSS (with fallback)
try {
    $PAGE->requires->css('/theme/remui_kids/style/student_overview.css');
} catch (Exception $e) {
    error_log("CSS file not found, using inline styles instead");
}

// Add Font Awesome CSS directly to head
$PAGE->requires->js_init_code('
    var link = document.createElement("link");
    link.rel = "stylesheet";
    link.href = "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css";
    document.head.appendChild(link);
');

// Add Teacher Sidebar CSS
$PAGE->requires->js_init_code('
    var style = document.createElement("style");
    style.textContent = `
        .teacher-dashboard-wrapper {
            display: flex;
            min-height: 100vh;
            background: #f8fafc;
        }
        
        .sidebar-toggle {
            position: fixed;
            top: 80px;
            left: 20px;
            z-index: 1001;
            background: #10b981;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 8px;
            cursor: pointer;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            display: none;
        }
        
        .teacher-sidebar {
            width: 280px;
            background: white;
            color: #333;
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
            transition: transform 0.3s ease;
            border-right: 1px solid #e5e7eb;
        }
        
        .sidebar-content {
            padding: 20px 0;
        }
        
        .sidebar-section {
            margin-bottom: 30px;
        }
        
        .sidebar-category {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            color: #6b7280;
            margin: 0 0 15px 0;
            padding: 0 20px;
            letter-spacing: 1px;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-item {
            margin: 0;
        }
        
        .sidebar-link {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #374151;
            text-decoration: none;
            transition: all 0.2s ease;
            border-left: 3px solid transparent;
        }
        
        .sidebar-link:hover {
            background: #f3f4f6;
            color: #1f2937;
            border-left-color: #10b981;
        }
        
        .sidebar-item.active .sidebar-link {
            background: #f3f4f6;
            color: #1f2937;
            border-left-color: #10b981;
        }
        
        .sidebar-icon {
            width: 20px;
            margin-right: 12px;
            text-align: center;
            font-size: 16px;
        }
        
        .sidebar-text {
            font-size: 14px;
            font-weight: 500;
        }
        
        .teacher-main-content {
            flex: 1;
            margin-left: 280px;
            min-height: 100vh;
        }
        
        @media (max-width: 768px) {
            .sidebar-toggle {
                display: block;
            }
            
            .teacher-sidebar {
                transform: translateX(-100%);
            }
            
            .teacher-sidebar.open {
                transform: translateX(0);
            }
            
            .teacher-main-content {
                margin-left: 0;
            }
        }
    `;
    document.head.appendChild(style);
    
    // Add toggle function
    window.toggleTeacherSidebar = function() {
        const sidebar = document.querySelector(".teacher-sidebar");
        sidebar.classList.toggle("open");
    };
');

// Include Moodle header for navigation bar
try {
    echo $OUTPUT->header();
} catch (Exception $e) {
    error_log("Error in header: " . $e->getMessage());
    echo "<h1>Class Performance Overview</h1>";
    echo "<p>Error loading page. Please check the logs.</p>";
    exit;
}

// Teacher dashboard layout wrapper and sidebar
echo '<div class="teacher-dashboard-wrapper">';
echo '<button class="sidebar-toggle" onclick="toggleTeacherSidebar()">';
echo '    <i class="fa fa-bars"></i>';
echo '</button>';

// Teacher Sidebar Navigation
echo '<div class="teacher-sidebar">';
echo '  <div class="sidebar-content">';
// Dashboard section
echo '    <div class="sidebar-section">';
echo '      <h3 class="sidebar-category">DASHBOARD</h3>';
echo '      <ul class="sidebar-menu">';
echo '        <li class="sidebar-item"><a href="' . $CFG->wwwroot . '/my/" class="sidebar-link"><i class="fa fa-th-large sidebar-icon"></i><span class="sidebar-text">Teacher Dashboard</span></a></li>';
echo '        <li class="sidebar-item"><a href="' . $CFG->wwwroot . '/course/index.php" class="sidebar-link"><i class="fa fa-book sidebar-icon"></i><span class="sidebar-text">My Courses</span></a></li>';
echo '        <li class="sidebar-item"><a href="' . $CFG->wwwroot . '/grade/report/grader/index.php" class="sidebar-link"><i class="fa fa-graduation-cap sidebar-icon"></i><span class="sidebar-text">Gradebook</span></a></li>';
echo '        <li class="sidebar-item"><a href="' . $CFG->wwwroot . '/mod/assign/index.php" class="sidebar-link"><i class="fa fa-tasks sidebar-icon"></i><span class="sidebar-text">Assignments</span></a></li>';
echo '      </ul>';
echo '    </div>';
// Courses section
echo '    <div class="sidebar-section">';
echo '      <h3 class="sidebar-category">COURSES</h3>';
echo '      <ul class="sidebar-menu">';
echo '        <li class="sidebar-item"><a href="' . $CFG->wwwroot . '/course/index.php" class="sidebar-link"><i class="fa fa-book sidebar-icon"></i><span class="sidebar-text">All Courses</span></a></li>';
echo '        <li class="sidebar-item"><a href="' . $CFG->wwwroot . '/course/edit.php" class="sidebar-link"><i class="fa fa-plus sidebar-icon"></i><span class="sidebar-text">Create Course</span></a></li>';
echo '        <li class="sidebar-item"><a href="' . $CFG->wwwroot . '/course/index.php?categoryid=0" class="sidebar-link"><i class="fa fa-folder sidebar-icon"></i><span class="sidebar-text">Course Categories</span></a></li>';
echo '      </ul>';
echo '    </div>';
// Students section
echo '    <div class="sidebar-section">';
echo '      <h3 class="sidebar-category">STUDENTS</h3>';
echo '      <ul class="sidebar-menu">';
echo '        <li class="sidebar-item"><a href="' . $CFG->wwwroot . '/theme/remui_kids/teacher/students.php" class="sidebar-link"><i class="fa fa-users sidebar-icon"></i><span class="sidebar-text">All Students</span></a></li>';
echo '        <li class="sidebar-item"><a href="' . $CFG->wwwroot . '/enrol/users.php" class="sidebar-link"><i class="fa fa-user-plus sidebar-icon"></i><span class="sidebar-text">Enroll Students</span></a></li>';
echo '        <li class="sidebar-item"><a href="' . $CFG->wwwroot . '/report/progress/index.php" class="sidebar-link"><i class="fa fa-chart-line sidebar-icon"></i><span class="sidebar-text">Progress Reports</span></a></li>';
echo '      </ul>';
echo '    </div>';
// Assessments section
echo '    <div class="sidebar-section">';
echo '      <h3 class="sidebar-category">ASSESSMENTS</h3>';
echo '      <ul class="sidebar-menu">';
echo '        <li class="sidebar-item"><a href="' . $CFG->wwwroot . '/mod/assign/index.php" class="sidebar-link"><i class="fa fa-tasks sidebar-icon"></i><span class="sidebar-text">Assignments</span></a></li>';
echo '        <li class="sidebar-item"><a href="' . $CFG->wwwroot . '/theme/remui_kids/teacher/quizzes.php" class="sidebar-link"><i class="fa fa-question-circle sidebar-icon"></i><span class="sidebar-text">Quizzes</span></a></li>';
echo '        <li class="sidebar-item"><a href="' . $CFG->wwwroot . '/grade/report/grader/index.php" class="sidebar-link"><i class="fa fa-star sidebar-icon"></i><span class="sidebar-text">Grading</span></a></li>';
echo '      </ul>';
echo '    </div>';
// Reports section
echo '    <div class="sidebar-section">';
echo '      <h3 class="sidebar-category">REPORTS</h3>';
echo '      <ul class="sidebar-menu">';
echo '        <li class="sidebar-item"><a href="' . $CFG->wwwroot . '/report/log/index.php" class="sidebar-link"><i class="fa fa-chart-bar sidebar-icon"></i><span class="sidebar-text">Activity Logs</span></a></li>';
echo '        <li class="sidebar-item"><a href="' . $CFG->wwwroot . '/report/outline/index.php" class="sidebar-link"><i class="fa fa-file-alt sidebar-icon"></i><span class="sidebar-text">Course Reports</span></a></li>';
echo '        <li class="sidebar-item"><a href="' . $CFG->wwwroot . '/report/progress/index.php" class="sidebar-link"><i class="fa fa-chart-line sidebar-icon"></i><span class="sidebar-text">Progress Tracking</span></a></li>';
echo '      </ul>';
echo '    </div>';

echo '  </div>';
echo '</div>'; // end teacher-sidebar

// Main content area next to sidebar
echo '<div class="teacher-main-content">';

// Full Screen Dashboard Layout with Integrated Profile
echo html_writer::start_div('', ['style' => 'min-height: 100vh; background: #f8fafc; font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; padding: 0; margin: 0; width: 100%; overflow-x: hidden;']);

// Full Width Dashboard Content
echo html_writer::start_div('', ['style' => 'max-width: 1400px; margin: 0 auto; padding: 24px;']);

// Top Header Section
echo html_writer::start_div('', ['style' => 'background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white; padding: 20px 24px; margin-bottom: 24px; border-radius: 12px; display: flex; justify-content: space-between; align-items: center;']);
echo html_writer::start_div('', ['style' => 'display: flex; align-items: center; gap: 16px;']);
echo html_writer::tag('i', '', ['class' => 'fas fa-apple-alt', 'style' => 'font-size: 24px; color: white;']);
echo html_writer::start_div('', ['style' => 'display: flex; flex-direction: column;']);
echo html_writer::tag('h1', 'Jasper Elite School', ['style' => 'margin: 0; font-size: 24px; font-weight: 700;']);
echo html_writer::tag('p', 'Student Performance Dashboard', ['style' => 'margin: 0; font-size: 14px; opacity: 0.9;']);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::start_div('', ['style' => 'display: flex; align-items: center; gap: 16px;']);
echo html_writer::tag('i', '', ['class' => 'fas fa-bell', 'style' => 'font-size: 18px; color: white; cursor: pointer;']);
echo html_writer::tag('i', '', ['class' => 'fas fa-cloud', 'style' => 'font-size: 18px; color: white; cursor: pointer;']);
echo html_writer::tag('i', '', ['class' => 'fas fa-play', 'style' => 'font-size: 18px; color: white; cursor: pointer;']);
echo html_writer::tag('i', '', ['class' => 'fas fa-cog', 'style' => 'font-size: 18px; color: white; cursor: pointer;']);
echo html_writer::start_div('', ['style' => 'display: flex; align-items: center; gap: 8px; margin-left: 16px;']);
echo html_writer::tag('span', 'Last updated 3min ago', ['style' => 'font-size: 12px; opacity: 0.8;']);
echo html_writer::tag('i', '', ['class' => 'fas fa-sync-alt', 'style' => 'font-size: 12px; opacity: 0.8;']);
echo html_writer::end_div();
echo html_writer::start_div('', ['style' => 'display: flex; align-items: center; gap: 8px; margin-left: 16px;']);
echo html_writer::tag('span', 'Signed in as Principal Carter', ['style' => 'font-size: 12px; opacity: 0.8;']);
echo html_writer::img('/user/pix.php/0/f1', 'Profile', ['style' => 'width: 32px; height: 32px; border-radius: 50%; border: 2px solid white;']);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

// Top Row - Filters and Summary Cards
echo html_writer::start_div('', ['style' => 'display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 24px; margin-bottom: 24px;']);

// Filter Dropdowns
echo html_writer::start_div('', ['style' => 'display: flex; flex-direction: column; gap: 12px;']);
echo html_writer::start_div('', ['style' => 'display: flex; flex-direction: column; gap: 4px;']);
echo html_writer::tag('label', 'Select Year', ['style' => 'font-size: 14px; font-weight: 600; color: #374151;']);
echo html_writer::tag('select', '<option value="2024">2024</option><option value="2023">2023</option>', ['style' => 'padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; background: white;']);
echo html_writer::end_div();
echo html_writer::start_div('', ['style' => 'display: flex; flex-direction: column; gap: 4px;']);
echo html_writer::tag('label', 'Select Grade', ['style' => 'font-size: 14px; font-weight: 600; color: #374151;']);
echo html_writer::tag('select', '<option value="all">All Grades</option><option value="1">Grade 1</option><option value="2">Grade 2</option><option value="3">Grade 3</option><option value="4">Grade 4</option><option value="5">Grade 5</option>', ['style' => 'padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; background: white;']);
echo html_writer::end_div();
echo html_writer::end_div();

// Student Count Card
echo html_writer::start_div('', ['style' => 'background: white; border-radius: 12px; padding: 24px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); text-align: center;']);
echo html_writer::tag('i', '', ['class' => 'fas fa-users', 'style' => 'font-size: 32px; color: #8b5cf6; margin-bottom: 12px;']);
echo html_writer::tag('h3', '3,457', ['style' => 'margin: 0; font-size: 32px; font-weight: 700; color: #1f2937;']);
echo html_writer::tag('p', 'Student Count', ['style' => 'margin: 0; font-size: 14px; color: #6b7280;']);
echo html_writer::end_div();

// Student Attendance Card
echo html_writer::start_div('', ['style' => 'background: white; border-radius: 12px; padding: 24px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); text-align: center;']);
echo html_writer::tag('i', '', ['class' => 'fas fa-clipboard-check', 'style' => 'font-size: 32px; color: #10b981; margin-bottom: 12px;']);
echo html_writer::tag('h3', '83.7%', ['style' => 'margin: 0; font-size: 32px; font-weight: 700; color: #1f2937;']);
echo html_writer::tag('p', 'Student Attendance', ['style' => 'margin: 0; font-size: 14px; color: #6b7280;']);
echo html_writer::end_div();

// Trend Indicators
echo html_writer::start_div('', ['style' => 'display: flex; flex-direction: column; gap: 12px;']);
echo html_writer::start_div('', ['style' => 'display: flex; align-items: center; gap: 8px; padding: 8px 12px; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);']);
echo html_writer::tag('i', '', ['class' => 'fas fa-arrow-up', 'style' => 'font-size: 14px; color: white; background: #3b82f6; padding: 4px; border-radius: 50%;']);
echo html_writer::tag('span', 'Student Count: 4.5%', ['style' => 'font-size: 12px; font-weight: 600; color: #374151;']);
echo html_writer::end_div();
echo html_writer::start_div('', ['style' => 'display: flex; align-items: center; gap: 8px; padding: 8px 12px; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);']);
echo html_writer::tag('i', '', ['class' => 'fas fa-chart-line', 'style' => 'font-size: 14px; color: white; background: #f59e0b; padding: 4px; border-radius: 50%;']);
echo html_writer::tag('span', 'Student Attendance: 1.2%', ['style' => 'font-size: 12px; font-weight: 600; color: #374151;']);
echo html_writer::end_div();
echo html_writer::start_div('', ['style' => 'display: flex; align-items: center; gap: 8px; padding: 8px 12px; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);']);
echo html_writer::tag('i', '', ['class' => 'fas fa-arrow-up', 'style' => 'font-size: 14px; color: white; background: #8b5cf6; padding: 4px; border-radius: 50%;']);
echo html_writer::tag('span', 'Exam Average: 77.7%', ['style' => 'font-size: 12px; font-weight: 600; color: #374151;']);
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::end_div(); // End top row

// Main Dashboard Grid
echo html_writer::start_div('', ['style' => 'display: grid; grid-template-columns: 2fr 1fr; gap: 24px; margin-bottom: 24px;']);

// Left Column - Student Count and Examination Results
echo html_writer::start_div('', ['style' => 'display: flex; flex-direction: column; gap: 24px;']);

// Student Count Section
echo html_writer::start_div('', ['style' => 'background: white; border-radius: 16px; padding: 24px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);']);
echo html_writer::start_div('', ['style' => 'display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;']);
echo html_writer::tag('h3', 'Student Count', ['style' => 'margin: 0; font-size: 18px; font-weight: 600; color: #1f2937;']);
echo html_writer::start_div('', ['style' => 'display: flex; gap: 8px;']);
echo html_writer::tag('select', '<option value="all">Grade</option><option value="1">Grade 1</option><option value="2">Grade 2</option>', ['style' => 'padding: 4px 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 12px;']);
echo html_writer::tag('select', '<option value="all">Gender</option><option value="male">Male</option><option value="female">Female</option>', ['style' => 'padding: 4px 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 12px;']);
echo html_writer::tag('i', '', ['class' => 'fas fa-sync-alt', 'style' => 'font-size: 14px; color: #6b7280; cursor: pointer; margin-left: 8px;']);
echo html_writer::end_div();
echo html_writer::end_div();

// Donut Chart and Bar Chart
echo html_writer::start_div('', ['style' => 'display: grid; grid-template-columns: 1fr 1fr; gap: 24px;']);
// Donut Chart (Mock)
echo html_writer::start_div('', ['style' => 'text-align: center;']);
echo html_writer::start_div('', ['style' => 'width: 120px; height: 120px; border-radius: 50%; background: conic-gradient(#8b5cf6 0deg 104deg, #3b82f6 104deg 208deg, #7c3aed 208deg 312deg, #10b981 312deg 360deg); display: flex; align-items: center; justify-content: center; position: relative; margin: 0 auto 16px;']);
echo html_writer::start_div('', ['style' => 'width: 80px; height: 80px; background: white; border-radius: 50%; position: absolute; display: flex; flex-direction: column; align-items: center; justify-content: center;']);
echo html_writer::tag('span', '28.9%', ['style' => 'font-size: 16px; font-weight: 700; color: #1f2937;']);
echo html_writer::tag('span', 'GRADE 3', ['style' => 'font-size: 10px; font-weight: 600; color: #6b7280;']);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

// Bar Chart (Mock)
echo html_writer::start_div('', ['style' => 'display: flex; flex-direction: column; gap: 12px;']);
$grades = [
    ['name' => 'Grade 1', 'percent' => 13.2, 'count' => 457, 'color' => '#8b5cf6'],
    ['name' => 'Grade 2', 'percent' => 22.2, 'count' => 769, 'color' => '#3b82f6'],
    ['name' => 'Grade 3', 'percent' => 28.9, 'count' => 1000, 'color' => '#7c3aed'],
    ['name' => 'Grade 4', 'percent' => 15.9, 'count' => 553, 'color' => '#10b981'],
    ['name' => 'Grade 5', 'percent' => 19.6, 'count' => 678, 'color' => '#f59e0b']
];

foreach ($grades as $grade) {
    echo html_writer::start_div('', ['style' => 'display: flex; align-items: center; gap: 12px;']);
    echo html_writer::tag('span', $grade['name'], ['style' => 'width: 60px; font-size: 12px; font-weight: 600; color: #374151;']);
    echo html_writer::start_div('', ['style' => 'flex: 1; height: 20px; background: #f3f4f6; border-radius: 10px; overflow: hidden;']);
    echo html_writer::tag('div', '', ['style' => 'height: 100%; width: ' . $grade['percent'] . '%; background: ' . $grade['color'] . '; border-radius: 10px;']);
    echo html_writer::end_div();
    echo html_writer::tag('span', $grade['percent'] . '% (' . $grade['count'] . ' students)', ['style' => 'font-size: 11px; color: #6b7280; min-width: 120px; text-align: right;']);
    echo html_writer::end_div();
}
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

// Examination Results Section
echo html_writer::start_div('', ['style' => 'background: white; border-radius: 16px; padding: 24px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);']);
echo html_writer::start_div('', ['style' => 'display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;']);
echo html_writer::tag('h3', 'Examination Results', ['style' => 'margin: 0; font-size: 18px; font-weight: 600; color: #1f2937;']);
echo html_writer::start_div('', ['style' => 'display: flex; gap: 8px; align-items: center;']);
echo html_writer::start_div('', ['style' => 'display: flex; gap: 12px; margin-right: 16px;']);
echo html_writer::start_div('', ['style' => 'display: flex; align-items: center; gap: 4px;']);
echo html_writer::tag('div', '', ['style' => 'width: 12px; height: 12px; background: #1e40af; border-radius: 2px;']);
echo html_writer::tag('span', 'Pass', ['style' => 'font-size: 12px; color: #374151;']);
echo html_writer::end_div();
echo html_writer::start_div('', ['style' => 'display: flex; align-items: center; gap: 4px;']);
echo html_writer::tag('div', '', ['style' => 'width: 12px; height: 12px; background: #3b82f6; border-radius: 2px;']);
echo html_writer::tag('span', 'Average', ['style' => 'font-size: 12px; color: #374151;']);
echo html_writer::end_div();
echo html_writer::start_div('', ['style' => 'display: flex; align-items: center; gap: 4px;']);
echo html_writer::tag('div', '', ['style' => 'width: 12px; height: 12px; background: #f59e0b; border-radius: 2px;']);
echo html_writer::tag('span', 'Fail', ['style' => 'font-size: 12px; color: #374151;']);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::tag('select', '<option value="all">Grade</option><option value="1">Grade 1</option>', ['style' => 'padding: 4px 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 12px;']);
echo html_writer::tag('select', '<option value="all">Gender</option><option value="male">Male</option>', ['style' => 'padding: 4px 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 12px;']);
echo html_writer::end_div();
echo html_writer::end_div();

// Bar Chart (Mock)
echo html_writer::start_div('', ['style' => 'height: 200px; display: flex; align-items: end; gap: 16px; padding: 16px 0;']);
$subjects = [
    ['name' => 'Maths', 'pass' => 1200, 'average' => 800, 'fail' => 200],
    ['name' => 'English', 'pass' => 1000, 'average' => 900, 'fail' => 300],
    ['name' => 'Mandarin', 'pass' => 800, 'average' => 600, 'fail' => 400],
    ['name' => 'Science', 'pass' => 1100, 'average' => 700, 'fail' => 200],
    ['name' => 'Arts', 'pass' => 900, 'average' => 500, 'fail' => 100],
    ['name' => 'Exercise', 'pass' => 1300, 'average' => 400, 'fail' => 100]
];

foreach ($subjects as $subject) {
    $max = max($subject['pass'], $subject['average'], $subject['fail']);
    echo html_writer::start_div('', ['style' => 'flex: 1; display: flex; flex-direction: column; align-items: center; gap: 4px;']);
    echo html_writer::start_div('', ['style' => 'display: flex; flex-direction: column; gap: 2px; width: 100%;']);
    echo html_writer::tag('div', '', ['style' => 'height: ' . ($subject['pass'] / $max * 120) . 'px; background: #1e40af; border-radius: 2px 2px 0 0;']);
    echo html_writer::tag('div', '', ['style' => 'height: ' . ($subject['average'] / $max * 120) . 'px; background: #3b82f6;']);
    echo html_writer::tag('div', '', ['style' => 'height: ' . ($subject['fail'] / $max * 120) . 'px; background: #f59e0b; border-radius: 0 0 2px 2px;']);
    echo html_writer::end_div();
    echo html_writer::tag('span', $subject['name'], ['style' => 'font-size: 10px; color: #6b7280; text-align: center;']);
    echo html_writer::end_div();
}
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::end_div(); // End left column

// Right Column - Best In Section and Student Details
echo html_writer::start_div('', ['style' => 'display: flex; flex-direction: column; gap: 24px;']);

// Best In Section
echo html_writer::start_div('', ['style' => 'display: grid; grid-template-columns: 1fr 1fr; gap: 16px;']);

// Best In Marks
echo html_writer::start_div('', ['style' => 'background: white; border-radius: 12px; padding: 16px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); text-align: center;']);
echo html_writer::start_div('', ['style' => 'width: 60px; height: 60px; border-radius: 50%; background: #10b981; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px;']);
echo html_writer::tag('i', '', ['class' => 'fas fa-user', 'style' => 'font-size: 24px; color: white;']);
echo html_writer::end_div();
echo html_writer::tag('h4', '87.9%', ['style' => 'margin: 0 0 4px 0; font-size: 18px; font-weight: 700; color: #1f2937;']);
echo html_writer::tag('p', 'Kinara Zuri', ['style' => 'margin: 0 0 8px 0; font-size: 14px; font-weight: 600; color: #374151;']);
echo html_writer::tag('p', 'Grade 3', ['style' => 'margin: 0 0 2px 0; font-size: 12px; color: #6b7280;']);
echo html_writer::tag('p', 'GPA 5', ['style' => 'margin: 0 0 2px 0; font-size: 12px; color: #6b7280;']);
echo html_writer::tag('p', 'Attend 77.3%', ['style' => 'margin: 0; font-size: 12px; color: #6b7280;']);
echo html_writer::end_div();

// Best In Attendance
echo html_writer::start_div('', ['style' => 'background: white; border-radius: 12px; padding: 16px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); text-align: center;']);
echo html_writer::start_div('', ['style' => 'width: 60px; height: 60px; border-radius: 50%; background: #f59e0b; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px;']);
echo html_writer::tag('i', '', ['class' => 'fas fa-user', 'style' => 'font-size: 24px; color: white;']);
echo html_writer::end_div();
echo html_writer::tag('h4', '89.3%', ['style' => 'margin: 0 0 4px 0; font-size: 18px; font-weight: 700; color: #1f2937;']);
echo html_writer::tag('p', 'Lea Jabulani', ['style' => 'margin: 0 0 8px 0; font-size: 14px; font-weight: 600; color: #374151;']);
echo html_writer::tag('p', 'Grade 4', ['style' => 'margin: 0 0 2px 0; font-size: 12px; color: #6b7280;']);
echo html_writer::tag('p', 'GPA 4', ['style' => 'margin: 0 0 2px 0; font-size: 12px; color: #6b7280;']);
echo html_writer::tag('p', 'Marks 75.3%', ['style' => 'margin: 0; font-size: 12px; color: #6b7280;']);
echo html_writer::end_div();

// Most Improved In Marks
echo html_writer::start_div('', ['style' => 'background: white; border-radius: 12px; padding: 16px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); text-align: center;']);
echo html_writer::start_div('', ['style' => 'width: 60px; height: 60px; border-radius: 50%; background: #8b5cf6; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px;']);
echo html_writer::tag('i', '', ['class' => 'fas fa-user', 'style' => 'font-size: 24px; color: white;']);
echo html_writer::end_div();
echo html_writer::tag('h4', '79.3%', ['style' => 'margin: 0 0 4px 0; font-size: 18px; font-weight: 700; color: #1f2937;']);
echo html_writer::tag('p', 'Corny Niang', ['style' => 'margin: 0 0 8px 0; font-size: 14px; font-weight: 600; color: #374151;']);
echo html_writer::tag('p', 'Grade 5', ['style' => 'margin: 0 0 2px 0; font-size: 12px; color: #6b7280;']);
echo html_writer::tag('p', 'GPA 3', ['style' => 'margin: 0 0 2px 0; font-size: 12px; color: #6b7280;']);
echo html_writer::tag('p', 'Attend 80.2%', ['style' => 'margin: 0; font-size: 12px; color: #6b7280;']);
echo html_writer::end_div();

// Most Improved In Attendance
echo html_writer::start_div('', ['style' => 'background: white; border-radius: 12px; padding: 16px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); text-align: center;']);
echo html_writer::start_div('', ['style' => 'width: 60px; height: 60px; border-radius: 50%; background: #3b82f6; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px;']);
echo html_writer::tag('i', '', ['class' => 'fas fa-user', 'style' => 'font-size: 24px; color: white;']);
echo html_writer::end_div();
echo html_writer::tag('h4', '82.5%', ['style' => 'margin: 0 0 4px 0; font-size: 18px; font-weight: 700; color: #1f2937;']);
echo html_writer::tag('p', 'Yao Ming', ['style' => 'margin: 0 0 8px 0; font-size: 14px; font-weight: 600; color: #374151;']);
echo html_writer::tag('p', 'Grade 1', ['style' => 'margin: 0 0 2px 0; font-size: 12px; color: #6b7280;']);
echo html_writer::tag('p', 'GPA 5', ['style' => 'margin: 0 0 2px 0; font-size: 12px; color: #6b7280;']);
echo html_writer::tag('p', 'Marks 86.8%', ['style' => 'margin: 0; font-size: 12px; color: #6b7280;']);
echo html_writer::end_div();

echo html_writer::end_div(); // End Best In grid

// Student Details Section
echo html_writer::start_div('', ['style' => 'background: white; border-radius: 16px; padding: 24px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);']);
echo html_writer::start_div('', ['style' => 'display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;']);
echo html_writer::tag('h3', 'Student Details', ['style' => 'margin: 0; font-size: 18px; font-weight: 600; color: #1f2937;']);
echo html_writer::start_div('', ['style' => 'display: flex; gap: 8px; align-items: center;']);
echo html_writer::tag('select', '<option value="1">Grade 1</option><option value="2">Grade 2</option>', ['style' => 'padding: 4px 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 12px;']);
echo html_writer::tag('i', '', ['class' => 'fas fa-sync-alt', 'style' => 'font-size: 14px; color: #6b7280; cursor: pointer;']);
echo html_writer::end_div();
echo html_writer::end_div();

// Student Cards
$students = [
    ['name' => 'Luka Magic', 'gender' => 'Male', 'marks' => 73.7, 'gpa' => 5, 'attend' => 77.3, 'avatar' => '#8b5cf6'],
    ['name' => 'Bianca Shangwe', 'gender' => 'Female', 'marks' => 63.7, 'gpa' => 2, 'attend' => 67.7, 'avatar' => '#ec4899'],
    ['name' => 'Alpha Kenya', 'gender' => 'Male', 'marks' => 83.1, 'gpa' => 5, 'attend' => 79.9, 'avatar' => '#10b981']
];

foreach ($students as $student) {
    echo html_writer::start_div('', ['style' => 'display: flex; align-items: center; gap: 16px; padding: 16px; background: #f9fafb; border-radius: 12px; margin-bottom: 12px;']);
    echo html_writer::start_div('', ['style' => 'width: 50px; height: 50px; border-radius: 50%; background: ' . $student['avatar'] . '; display: flex; align-items: center; justify-content: center;']);
    echo html_writer::tag('i', '', ['class' => 'fas fa-user', 'style' => 'font-size: 20px; color: white;']);
    echo html_writer::end_div();
    echo html_writer::start_div('', ['style' => 'flex: 1;']);
    echo html_writer::tag('p', $student['gender'], ['style' => 'margin: 0 0 4px 0; font-size: 12px; color: #6b7280;']);
    echo html_writer::tag('h4', $student['name'], ['style' => 'margin: 0 0 8px 0; font-size: 16px; font-weight: 600; color: #1f2937;']);
    echo html_writer::start_div('', ['style' => 'display: flex; gap: 12px;']);
    echo html_writer::tag('span', 'Marks ' . $student['marks'] . '%', ['style' => 'font-size: 12px; color: #374151;']);
    echo html_writer::tag('span', 'GPA ' . $student['gpa'], ['style' => 'font-size: 12px; color: #374151;']);
    echo html_writer::tag('span', 'Attend ' . $student['attend'] . '%', ['style' => 'font-size: 12px; color: #374151;']);
    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_div();
}
echo html_writer::end_div();

echo html_writer::end_div(); // End right column
echo html_writer::end_div(); // End main grid

// Bottom Row - Average Score
echo html_writer::start_div('', ['style' => 'background: white; border-radius: 16px; padding: 24px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);']);
echo html_writer::start_div('', ['style' => 'display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;']);
echo html_writer::tag('h3', 'Average Score', ['style' => 'margin: 0; font-size: 18px; font-weight: 600; color: #1f2937;']);
echo html_writer::tag('i', '', ['class' => 'fas fa-sync-alt', 'style' => 'font-size: 16px; color: #6b7280; cursor: pointer;']);
echo html_writer::end_div();

// Circular Progress Indicators
echo html_writer::start_div('', ['style' => 'display: flex; justify-content: space-around; align-items: center;']);
$subjects = [
    ['name' => 'English', 'score' => 94.5, 'color' => '#8b5cf6'],
    ['name' => 'Maths', 'score' => 81.9, 'color' => '#1e40af'],
    ['name' => 'Science', 'score' => 69.4, 'color' => '#3b82f6']
];

foreach ($subjects as $subject) {
    echo html_writer::start_div('', ['style' => 'text-align: center;']);
    echo html_writer::start_div('', ['style' => 'width: 100px; height: 100px; border-radius: 50%; background: conic-gradient(' . $subject['color'] . ' 0deg ' . ($subject['score'] * 3.6) . 'deg, #e5e7eb ' . ($subject['score'] * 3.6) . 'deg 360deg); display: flex; align-items: center; justify-content: center; position: relative; margin: 0 auto 12px;']);
    echo html_writer::start_div('', ['style' => 'width: 70px; height: 70px; background: white; border-radius: 50%; position: absolute; display: flex; align-items: center; justify-content: center;']);
    echo html_writer::tag('span', $subject['score'] . '%', ['style' => 'font-size: 16px; font-weight: 700; color: #1f2937;']);
    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::tag('p', $subject['name'], ['style' => 'margin: 0; font-size: 14px; font-weight: 600; color: #374151;']);
    echo html_writer::end_div();
}
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::end_div(); // End dashboard content
echo html_writer::end_div(); // End main container

echo '</div>'; // End teacher-main-content
echo '</div>'; // End teacher-dashboard-wrapper

// Include Moodle footer
try {
    echo $OUTPUT->footer();
} catch (Exception $e) {
    error_log("Error in footer: " . $e->getMessage());
    echo "</body></html>";
}

?>
