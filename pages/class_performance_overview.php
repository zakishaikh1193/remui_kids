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

// Fetch real class performance data
$performance_data = theme_remui_kids_get_class_performance_data($courseid);
error_log("Class Performance Overview - Data loaded: " . json_encode($performance_data));

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
$course = get_course($courseid);
echo html_writer::tag('p', $course->fullname . ' - Performance Dashboard', ['style' => 'margin: 0; font-size: 14px; opacity: 0.9;']);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::start_div('', ['style' => 'display: flex; align-items: center; gap: 16px;']);
echo html_writer::tag('i', '', ['class' => 'fas fa-bell', 'style' => 'font-size: 18px; color: white; cursor: pointer;']);
echo html_writer::tag('i', '', ['class' => 'fas fa-cloud', 'style' => 'font-size: 18px; color: white; cursor: pointer;']);
echo html_writer::tag('i', '', ['class' => 'fas fa-play', 'style' => 'font-size: 18px; color: white; cursor: pointer;']);
echo html_writer::tag('i', '', ['class' => 'fas fa-cog', 'style' => 'font-size: 18px; color: white; cursor: pointer;']);
echo html_writer::start_div('', ['style' => 'display: flex; align-items: center; gap: 8px; margin-left: 16px;']);
echo html_writer::tag('span', 'Last updated ' . date('M j, Y H:i'), ['style' => 'font-size: 12px; opacity: 0.8;']);
echo html_writer::tag('i', '', ['class' => 'fas fa-sync-alt', 'style' => 'font-size: 12px; opacity: 0.8;']);
echo html_writer::end_div();
echo html_writer::start_div('', ['style' => 'display: flex; align-items: center; gap: 8px; margin-left: 16px;']);
echo html_writer::tag('span', 'Signed in as ' . $USER->firstname . ' ' . $USER->lastname, ['style' => 'font-size: 12px; opacity: 0.8;']);
echo html_writer::img('/user/pix.php/' . $USER->id . '/f1', 'Profile', ['style' => 'width: 32px; height: 32px; border-radius: 50%; border: 2px solid white;']);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

// Top Row - Filters and Summary Cards
echo html_writer::start_div('', ['style' => 'display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 24px; margin-bottom: 24px;']);

// Filter Dropdowns with Real Data
echo html_writer::start_div('', ['style' => 'display: flex; flex-direction: column; gap: 12px;']);
echo html_writer::start_div('', ['style' => 'display: flex; flex-direction: column; gap: 4px;']);
echo html_writer::tag('label', 'Select Year', ['style' => 'font-size: 14px; font-weight: 600; color: #374151;']);
$current_year = date('Y');
$year_options = '';
for ($i = $current_year; $i >= $current_year - 5; $i--) {
    $selected = ($i == $current_year) ? ' selected' : '';
    $year_options .= '<option value="' . $i . '"' . $selected . '>' . $i . '</option>';
}
echo html_writer::tag('select', $year_options, ['style' => 'padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; background: white;', 'onchange' => 'filterByYear(this.value)']);
echo html_writer::end_div();
echo html_writer::start_div('', ['style' => 'display: flex; flex-direction: column; gap: 4px;']);
echo html_writer::tag('label', 'Select Course', ['style' => 'font-size: 14px; font-weight: 600; color: #374151;']);
$course_options = '<option value="' . $courseid . '" selected>' . $course->fullname . '</option>';
echo html_writer::tag('select', $course_options, ['style' => 'padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; background: white;', 'onchange' => 'filterByCourse(this.value)']);
echo html_writer::end_div();
echo html_writer::end_div();

// Student Count Card
echo html_writer::start_div('', ['style' => 'background: white; border-radius: 12px; padding: 24px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); text-align: center;']);
echo html_writer::tag('i', '', ['class' => 'fas fa-users', 'style' => 'font-size: 32px; color: #8b5cf6; margin-bottom: 12px;']);
echo html_writer::tag('h3', number_format($performance_data['student_count']), ['style' => 'margin: 0; font-size: 32px; font-weight: 700; color: #1f2937;']);
echo html_writer::tag('p', 'Student Count', ['style' => 'margin: 0; font-size: 14px; color: #6b7280;']);
echo html_writer::end_div();

// Student Attendance Card
echo html_writer::start_div('', ['style' => 'background: white; border-radius: 12px; padding: 24px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); text-align: center;']);
echo html_writer::tag('i', '', ['class' => 'fas fa-clipboard-check', 'style' => 'font-size: 32px; color: #10b981; margin-bottom: 12px;']);
echo html_writer::tag('h3', $performance_data['attendance_rate'] . '%', ['style' => 'margin: 0; font-size: 32px; font-weight: 700; color: #1f2937;']);
echo html_writer::tag('p', 'Student Attendance', ['style' => 'margin: 0; font-size: 14px; color: #6b7280;']);
echo html_writer::end_div();

// Real Data Trend Indicators
echo html_writer::start_div('', ['style' => 'display: flex; flex-direction: column; gap: 12px;']);
echo html_writer::start_div('', ['style' => 'display: flex; align-items: center; gap: 8px; padding: 8px 12px; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);']);
echo html_writer::tag('i', '', ['class' => 'fas fa-tasks', 'style' => 'font-size: 14px; color: white; background: #3b82f6; padding: 4px; border-radius: 50%;']);
echo html_writer::tag('span', 'Activities: ' . $performance_data['course_stats']->total_activities, ['style' => 'font-size: 12px; font-weight: 600; color: #374151;']);
echo html_writer::end_div();
echo html_writer::start_div('', ['style' => 'display: flex; align-items: center; gap: 8px; padding: 8px 12px; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);']);
echo html_writer::tag('i', '', ['class' => 'fas fa-check-circle', 'style' => 'font-size: 14px; color: white; background: #10b981; padding: 4px; border-radius: 50%;']);
echo html_writer::tag('span', 'Completed: ' . $performance_data['course_stats']->completed_activities, ['style' => 'font-size: 12px; font-weight: 600; color: #374151;']);
echo html_writer::end_div();
echo html_writer::start_div('', ['style' => 'display: flex; align-items: center; gap: 8px; padding: 8px 12px; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);']);
echo html_writer::tag('i', '', ['class' => 'fas fa-graduation-cap', 'style' => 'font-size: 14px; color: white; background: #8b5cf6; padding: 4px; border-radius: 50%;']);
echo html_writer::tag('span', 'Graded: ' . $performance_data['course_stats']->students_with_grades, ['style' => 'font-size: 12px; font-weight: 600; color: #374151;']);
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::end_div(); // End top row

// Main Dashboard Grid
echo html_writer::start_div('', ['style' => 'display: grid; grid-template-columns: 2fr 1fr; gap: 24px; margin-bottom: 24px; position: relative; z-index: 0;']);

// Left Column - Student Count and Examination Results
echo html_writer::start_div('', ['style' => 'display: flex; flex-direction: column; gap: 24px;']);

// Student Count Section
echo html_writer::start_div('', ['style' => 'background: white; border-radius: 16px; padding: 24px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);']);
echo html_writer::start_div('', ['style' => 'display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;']);
echo html_writer::tag('h3', 'Student Count', ['style' => 'margin: 0; font-size: 18px; font-weight: 600; color: #1f2937;']);
echo html_writer::start_div('', ['style' => 'display: flex; gap: 8px; align-items: center;']);
echo html_writer::tag('button', '<i class="fas fa-download"></i> Export', ['style' => 'padding: 6px 12px; background: #10b981; color: white; border: none; border-radius: 4px; font-size: 12px; cursor: pointer;', 'onclick' => 'exportStudentData()']);
echo html_writer::tag('button', '<i class="fas fa-chart-bar"></i> Analytics', ['style' => 'padding: 6px 12px; background: #3b82f6; color: white; border: none; border-radius: 4px; font-size: 12px; cursor: pointer;', 'onclick' => 'openAnalytics()']);
echo html_writer::tag('i', '', ['class' => 'fas fa-sync-alt', 'style' => 'font-size: 14px; color: #6b7280; cursor: pointer; margin-left: 8px;', 'onclick' => 'refreshData()']);
echo html_writer::end_div();
echo html_writer::end_div();

// Donut Chart and Bar Chart
echo html_writer::start_div('', ['style' => 'display: grid; grid-template-columns: 1fr 1fr; gap: 24px;']);
// Donut Chart (Real Data)
echo html_writer::start_div('', ['style' => 'text-align: center;']);
if (!empty($performance_data['grade_distribution'])) {
    // Find the category with the most students
    $max_category = null;
    $max_count = 0;
    foreach ($performance_data['grade_distribution'] as $grade) {
        if ($grade->student_count > $max_count) {
            $max_count = $grade->student_count;
            $max_category = $grade;
        }
    }
    
    if ($max_category) {
        $percentage = $total_students > 0 ? round(($max_category->student_count / $total_students) * 100, 1) : 0;
        $colors = ['#8b5cf6', '#3b82f6', '#7c3aed', '#10b981', '#f59e0b'];
        $color = $colors[array_rand($colors)];
        
        echo html_writer::start_div('', ['style' => 'width: 120px; height: 120px; border-radius: 50%; background: conic-gradient(' . $color . ' 0deg ' . ($percentage * 3.6) . 'deg, #e5e7eb ' . ($percentage * 3.6) . 'deg 360deg); display: flex; align-items: center; justify-content: center; position: relative; margin: 0 auto 16px;']);
        echo html_writer::start_div('', ['style' => 'width: 80px; height: 80px; background: white; border-radius: 50%; position: absolute; display: flex; flex-direction: column; align-items: center; justify-content: center;']);
        echo html_writer::tag('span', $percentage . '%', ['style' => 'font-size: 16px; font-weight: 700; color: #1f2937;']);
        echo html_writer::tag('span', strtoupper($max_category->grade_name), ['style' => 'font-size: 10px; font-weight: 600; color: #6b7280;']);
        echo html_writer::end_div();
        echo html_writer::end_div();
    } else {
        // Fallback if no grade distribution
        echo html_writer::start_div('', ['style' => 'width: 120px; height: 120px; border-radius: 50%; background: conic-gradient(#8b5cf6 0deg 360deg); display: flex; align-items: center; justify-content: center; position: relative; margin: 0 auto 16px;']);
        echo html_writer::start_div('', ['style' => 'width: 80px; height: 80px; background: white; border-radius: 50%; position: absolute; display: flex; flex-direction: column; align-items: center; justify-content: center;']);
        echo html_writer::tag('span', '100%', ['style' => 'font-size: 16px; font-weight: 700; color: #1f2937;']);
        echo html_writer::tag('span', 'ALL', ['style' => 'font-size: 10px; font-weight: 600; color: #6b7280;']);
        echo html_writer::end_div();
        echo html_writer::end_div();
    }
} else {
    // Fallback if no grade distribution data
    echo html_writer::start_div('', ['style' => 'width: 120px; height: 120px; border-radius: 50%; background: conic-gradient(#8b5cf6 0deg 360deg); display: flex; align-items: center; justify-content: center; position: relative; margin: 0 auto 16px;']);
    echo html_writer::start_div('', ['style' => 'width: 80px; height: 80px; background: white; border-radius: 50%; position: absolute; display: flex; flex-direction: column; align-items: center; justify-content: center;']);
    echo html_writer::tag('span', '100%', ['style' => 'font-size: 16px; font-weight: 700; color: #1f2937;']);
    echo html_writer::tag('span', 'ALL', ['style' => 'font-size: 10px; font-weight: 600; color: #6b7280;']);
    echo html_writer::end_div();
    echo html_writer::end_div();
}
echo html_writer::end_div();

// Bar Chart (Real Data)
echo html_writer::start_div('', ['style' => 'display: flex; flex-direction: column; gap: 12px;']);
$colors = ['#8b5cf6', '#3b82f6', '#7c3aed', '#10b981', '#f59e0b'];
$total_students = $performance_data['student_count'];
$color_index = 0;

if (!empty($performance_data['grade_distribution'])) {
    foreach ($performance_data['grade_distribution'] as $grade) {
        $percent = $total_students > 0 ? round(($grade->student_count / $total_students) * 100, 1) : 0;
        $color = $colors[$color_index % count($colors)];
        $color_index++;
        
        echo html_writer::start_div('', ['style' => 'display: flex; align-items: center; gap: 12px;']);
        echo html_writer::tag('span', $grade->grade_name, ['style' => 'width: 60px; font-size: 12px; font-weight: 600; color: #374151;']);
        echo html_writer::start_div('', ['style' => 'flex: 1; height: 20px; background: #f3f4f6; border-radius: 10px; overflow: hidden;']);
        echo html_writer::tag('div', '', ['style' => 'height: 100%; width: ' . $percent . '%; background: ' . $color . '; border-radius: 10px;']);
        echo html_writer::end_div();
        echo html_writer::tag('span', $percent . '% (' . $grade->student_count . ' students)', ['style' => 'font-size: 11px; color: #6b7280; min-width: 120px; text-align: right;']);
        echo html_writer::end_div();
    }
} else {
    // Fallback if no grade distribution data
    echo html_writer::start_div('', ['style' => 'display: flex; align-items: center; gap: 12px;']);
    echo html_writer::tag('span', 'All Students', ['style' => 'width: 60px; font-size: 12px; font-weight: 600; color: #374151;']);
    echo html_writer::start_div('', ['style' => 'flex: 1; height: 20px; background: #f3f4f6; border-radius: 10px; overflow: hidden;']);
    echo html_writer::tag('div', '', ['style' => 'height: 100%; width: 100%; background: #8b5cf6; border-radius: 10px;']);
    echo html_writer::end_div();
    echo html_writer::tag('span', '100% (' . $total_students . ' students)', ['style' => 'font-size: 11px; color: #6b7280; min-width: 120px; text-align: right;']);
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
echo html_writer::tag('button', '<i class="fas fa-file-pdf"></i> Report', ['style' => 'padding: 4px 8px; background: #ef4444; color: white; border: none; border-radius: 4px; font-size: 12px; cursor: pointer;', 'onclick' => 'generateReport()']);
echo html_writer::tag('button', '<i class="fas fa-eye"></i> Details', ['style' => 'padding: 4px 8px; background: #8b5cf6; color: white; border: none; border-radius: 4px; font-size: 12px; cursor: pointer;', 'onclick' => 'viewExamDetails()']);
echo html_writer::end_div();
echo html_writer::end_div();

// Bar Chart (Real Data)
echo html_writer::start_div('', ['style' => 'height: 200px; display: flex; align-items: end; gap: 16px; padding: 16px 0;']);
if (!empty($performance_data['exam_results'])) {
    foreach ($performance_data['exam_results'] as $subject) {
        $max = max($subject->pass_count, $subject->average_count, $subject->fail_count);
        if ($max > 0) {
            echo html_writer::start_div('', ['style' => 'flex: 1; display: flex; flex-direction: column; align-items: center; gap: 4px;']);
            echo html_writer::start_div('', ['style' => 'display: flex; flex-direction: column; gap: 2px; width: 100%;']);
            echo html_writer::tag('div', '', ['style' => 'height: ' . ($subject->pass_count / $max * 120) . 'px; background: #1e40af; border-radius: 2px 2px 0 0;']);
            echo html_writer::tag('div', '', ['style' => 'height: ' . ($subject->average_count / $max * 120) . 'px; background: #3b82f6;']);
            echo html_writer::tag('div', '', ['style' => 'height: ' . ($subject->fail_count / $max * 120) . 'px; background: #f59e0b; border-radius: 0 0 2px 2px;']);
            echo html_writer::end_div();
            echo html_writer::tag('span', ucfirst($subject->module_name), ['style' => 'font-size: 10px; color: #6b7280; text-align: center;']);
            echo html_writer::end_div();
        }
    }
} else {
    // Fallback if no exam results data
    echo html_writer::start_div('', ['style' => 'flex: 1; display: flex; flex-direction: column; align-items: center; gap: 4px;']);
    echo html_writer::start_div('', ['style' => 'display: flex; flex-direction: column; gap: 2px; width: 100%;']);
    echo html_writer::tag('div', '', ['style' => 'height: 80px; background: #1e40af; border-radius: 2px 2px 0 0;']);
    echo html_writer::tag('div', '', ['style' => 'height: 40px; background: #3b82f6;']);
    echo html_writer::tag('div', '', ['style' => 'height: 20px; background: #f59e0b; border-radius: 0 0 2px 2px;']);
    echo html_writer::end_div();
    echo html_writer::tag('span', 'No Data', ['style' => 'font-size: 10px; color: #6b7280; text-align: center;']);
    echo html_writer::end_div();
}
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::end_div(); // End left column

// Right Column - Best In Section and Student Details
echo html_writer::start_div('', ['style' => 'display: flex; flex-direction: column; gap: 24px;']);

// Best In Section (Real Data)
echo html_writer::start_div('', ['style' => 'display: grid; grid-template-columns: 1fr 1fr; gap: 16px;']);
$colors = ['#10b981', '#f59e0b', '#8b5cf6', '#3b82f6'];
$color_index = 0;

if (!empty($performance_data['top_performers'])) {
    foreach (array_slice($performance_data['top_performers'], 0, 4) as $performer) {
        $color = $colors[$color_index % count($colors)];
        $color_index++;
        
        echo html_writer::start_div('', ['style' => 'background: white; border-radius: 12px; padding: 16px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); text-align: center;']);
        echo html_writer::start_div('', ['style' => 'width: 60px; height: 60px; border-radius: 50%; background: ' . $color . '; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px;']);
        echo html_writer::tag('i', '', ['class' => 'fas fa-user', 'style' => 'font-size: 24px; color: white;']);
        echo html_writer::end_div();
        echo html_writer::tag('h4', round($performer->avg_grade, 1) . '%', ['style' => 'margin: 0 0 4px 0; font-size: 18px; font-weight: 700; color: #1f2937;']);
        echo html_writer::tag('p', $performer->firstname . ' ' . $performer->lastname, ['style' => 'margin: 0 0 8px 0; font-size: 14px; font-weight: 600; color: #374151;']);
        echo html_writer::tag('p', 'Activities: ' . $performer->completed_activities, ['style' => 'margin: 0 0 2px 0; font-size: 12px; color: #6b7280;']);
        echo html_writer::tag('p', 'Avg Grade: ' . round($performer->avg_grade, 1) . '%', ['style' => 'margin: 0; font-size: 12px; color: #6b7280;']);
        echo html_writer::end_div();
    }
} else {
    // Fallback if no top performers data
    for ($i = 0; $i < 4; $i++) {
        $color = $colors[$i % count($colors)];
        echo html_writer::start_div('', ['style' => 'background: white; border-radius: 12px; padding: 16px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); text-align: center;']);
        echo html_writer::start_div('', ['style' => 'width: 60px; height: 60px; border-radius: 50%; background: ' . $color . '; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px;']);
        echo html_writer::tag('i', '', ['class' => 'fas fa-user', 'style' => 'font-size: 24px; color: white;']);
        echo html_writer::end_div();
        echo html_writer::tag('h4', 'N/A', ['style' => 'margin: 0 0 4px 0; font-size: 18px; font-weight: 700; color: #1f2937;']);
        echo html_writer::tag('p', 'No Data', ['style' => 'margin: 0 0 8px 0; font-size: 14px; font-weight: 600; color: #374151;']);
        echo html_writer::tag('p', 'No students found', ['style' => 'margin: 0; font-size: 12px; color: #6b7280;']);
        echo html_writer::end_div();
    }
}

echo html_writer::end_div(); // End Best In grid

// Student Details Section
echo html_writer::start_div('', ['style' => 'background: white; border-radius: 16px; padding: 24px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);']);
echo html_writer::start_div('', ['style' => 'display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;']);
echo html_writer::tag('h3', 'Student Details', ['style' => 'margin: 0; font-size: 18px; font-weight: 600; color: #1f2937;']);
echo html_writer::start_div('', ['style' => 'display: flex; gap: 8px; align-items: center;']);
echo html_writer::tag('button', '<i class="fas fa-plus"></i> Add Student', ['style' => 'padding: 4px 8px; background: #10b981; color: white; border: none; border-radius: 4px; font-size: 12px; cursor: pointer;', 'onclick' => 'addStudent()']);
echo html_writer::tag('button', '<i class="fas fa-list"></i> View All', ['style' => 'padding: 4px 8px; background: #3b82f6; color: white; border: none; border-radius: 4px; font-size: 12px; cursor: pointer;', 'onclick' => 'viewAllStudents()']);
echo html_writer::tag('i', '', ['class' => 'fas fa-sync-alt', 'style' => 'font-size: 14px; color: #6b7280; cursor: pointer;', 'onclick' => 'refreshStudents()']);
echo html_writer::end_div();
echo html_writer::end_div();

// Student Cards (Real Data)
$avatar_colors = ['#8b5cf6', '#ec4899', '#10b981', '#f59e0b', '#3b82f6'];
$color_index = 0;

if (!empty($performance_data['students'])) {
    foreach ($performance_data['students'] as $student) {
        $color = $avatar_colors[$color_index % count($avatar_colors)];
        $color_index++;
        
        // Get student's last access status
        $last_access = $student->lastaccess ? userdate($student->lastaccess, '%b %e, %Y') : 'Never';
        $is_active = $student->lastaccess && ($student->lastaccess > (time() - (30 * 24 * 60 * 60)));
        
        echo html_writer::start_div('', ['style' => 'display: flex; align-items: center; gap: 16px; padding: 16px; background: #f9fafb; border-radius: 12px; margin-bottom: 12px;']);
        echo html_writer::start_div('', ['style' => 'width: 50px; height: 50px; border-radius: 50%; background: ' . $color . '; display: flex; align-items: center; justify-content: center;']);
        echo html_writer::tag('i', '', ['class' => 'fas fa-user', 'style' => 'font-size: 20px; color: white;']);
        echo html_writer::end_div();
        echo html_writer::start_div('', ['style' => 'flex: 1;']);
        echo html_writer::tag('p', $is_active ? 'Active' : 'Inactive', ['style' => 'margin: 0 0 4px 0; font-size: 12px; color: ' . ($is_active ? '#10b981' : '#6b7280') . ';']);
        echo html_writer::tag('h4', $student->firstname . ' ' . $student->lastname, ['style' => 'margin: 0 0 8px 0; font-size: 16px; font-weight: 600; color: #1f2937;']);
        echo html_writer::start_div('', ['style' => 'display: flex; gap: 12px;']);
        echo html_writer::tag('span', 'Email: ' . $student->email, ['style' => 'font-size: 12px; color: #374151;']);
        echo html_writer::tag('span', 'Last Access: ' . $last_access, ['style' => 'font-size: 12px; color: #374151;']);
        echo html_writer::end_div();
        echo html_writer::end_div();
        echo html_writer::end_div();
    }
} else {
    // Fallback if no students data
    echo html_writer::start_div('', ['style' => 'display: flex; align-items: center; gap: 16px; padding: 16px; background: #f9fafb; border-radius: 12px; margin-bottom: 12px;']);
    echo html_writer::start_div('', ['style' => 'width: 50px; height: 50px; border-radius: 50%; background: #6b7280; display: flex; align-items: center; justify-content: center;']);
    echo html_writer::tag('i', '', ['class' => 'fas fa-user', 'style' => 'font-size: 20px; color: white;']);
    echo html_writer::end_div();
    echo html_writer::start_div('', ['style' => 'flex: 1;']);
    echo html_writer::tag('p', 'No Data', ['style' => 'margin: 0 0 4px 0; font-size: 12px; color: #6b7280;']);
    echo html_writer::tag('h4', 'No Students Found', ['style' => 'margin: 0 0 8px 0; font-size: 16px; font-weight: 600; color: #1f2937;']);
    echo html_writer::tag('span', 'No enrolled students in this course', ['style' => 'font-size: 12px; color: #374151;']);
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

// Circular Progress Indicators (Real Data)
echo html_writer::start_div('', ['style' => 'display: flex; justify-content: space-around; align-items: center;']);
$colors = ['#8b5cf6', '#1e40af', '#3b82f6'];
$color_index = 0;

if (!empty($performance_data['subject_averages'])) {
    foreach ($performance_data['subject_averages'] as $subject) {
        $color = $colors[$color_index % count($colors)];
        $color_index++;
        $score = round($subject->avg_score, 1);
        
        echo html_writer::start_div('', ['style' => 'text-align: center;']);
        echo html_writer::start_div('', ['style' => 'width: 100px; height: 100px; border-radius: 50%; background: conic-gradient(' . $color . ' 0deg ' . ($score * 3.6) . 'deg, #e5e7eb ' . ($score * 3.6) . 'deg 360deg); display: flex; align-items: center; justify-content: center; position: relative; margin: 0 auto 12px;']);
        echo html_writer::start_div('', ['style' => 'width: 70px; height: 70px; background: white; border-radius: 50%; position: absolute; display: flex; align-items: center; justify-content: center;']);
        echo html_writer::tag('span', $score . '%', ['style' => 'font-size: 16px; font-weight: 700; color: #1f2937;']);
        echo html_writer::end_div();
        echo html_writer::end_div();
        echo html_writer::tag('p', ucfirst($subject->subject_name), ['style' => 'margin: 0; font-size: 14px; font-weight: 600; color: #374151;']);
        echo html_writer::end_div();
    }
} else {
    // Fallback if no subject averages data
    $fallback_subjects = ['English', 'Maths', 'Science'];
    foreach ($fallback_subjects as $subject_name) {
        $color = $colors[$color_index % count($colors)];
        $color_index++;
        
        echo html_writer::start_div('', ['style' => 'text-align: center;']);
        echo html_writer::start_div('', ['style' => 'width: 100px; height: 100px; border-radius: 50%; background: conic-gradient(' . $color . ' 0deg 0deg, #e5e7eb 0deg 360deg); display: flex; align-items: center; justify-content: center; position: relative; margin: 0 auto 12px;']);
        echo html_writer::start_div('', ['style' => 'width: 70px; height: 70px; background: white; border-radius: 50%; position: absolute; display: flex; align-items: center; justify-content: center;']);
        echo html_writer::tag('span', 'N/A', ['style' => 'font-size: 16px; font-weight: 700; color: #1f2937;']);
        echo html_writer::end_div();
        echo html_writer::end_div();
        echo html_writer::tag('p', $subject_name, ['style' => 'margin: 0; font-size: 14px; font-weight: 600; color: #374151;']);
        echo html_writer::end_div();
    }
}
echo html_writer::end_div();
echo html_writer::end_div();

// Clear separation and proper spacing for additional sections
echo html_writer::start_div('additional-sections', ['style' => '']);

// Visual separator
echo html_writer::start_div('', ['style' => 'width: 100%; height: 1px; background: linear-gradient(to right, transparent, #e5e7eb, transparent); margin: 20px 0;']);

// Section Title for Additional Data
echo html_writer::start_div('section-title', ['style' => '']);
echo html_writer::tag('h2', 'Course Activities & Assessments', ['style' => 'margin: 0; font-size: 20px; font-weight: 600; color: #1f2937;']);
echo html_writer::end_div();

// Additional Real Data Sections
echo html_writer::start_div('', ['style' => 'display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;']);

// Assignment Statistics Section
echo html_writer::start_div('activity-card', ['style' => '']);
echo html_writer::start_div('', ['style' => 'display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;']);
echo html_writer::tag('h3', 'Assignment Statistics', ['style' => 'margin: 0; font-size: 18px; font-weight: 600; color: #1f2937;']);
echo html_writer::tag('button', '<i class="fas fa-plus"></i> New Assignment', ['style' => 'padding: 6px 12px; background: #10b981; color: white; border: none; border-radius: 4px; font-size: 12px; cursor: pointer;', 'onclick' => 'createAssignment()']);
echo html_writer::end_div();

if (!empty($performance_data['assignment_stats'])) {
    foreach ($performance_data['assignment_stats'] as $assignment) {
        echo html_writer::start_div('', ['style' => 'display: flex; justify-content: space-between; align-items: center; padding: 12px; background: #f9fafb; border-radius: 8px; margin-bottom: 8px;']);
        echo html_writer::start_div('', ['style' => 'flex: 1;']);
        echo html_writer::tag('h4', $assignment->name, ['style' => 'margin: 0 0 4px 0; font-size: 14px; font-weight: 600; color: #1f2937;']);
        echo html_writer::tag('p', 'Submissions: ' . $assignment->submissions . ' | Avg: ' . round($assignment->avg_grade, 1), ['style' => 'margin: 0; font-size: 12px; color: #6b7280;']);
        echo html_writer::end_div();
        echo html_writer::tag('button', '<i class="fas fa-eye"></i>', ['style' => 'padding: 4px 8px; background: #3b82f6; color: white; border: none; border-radius: 4px; font-size: 12px; cursor: pointer;', 'onclick' => 'viewAssignment(\'' . $assignment->name . '\')']);
        echo html_writer::end_div();
    }
} else {
    echo html_writer::tag('p', 'No assignments found', ['style' => 'text-align: center; color: #6b7280; font-style: italic;']);
}
echo html_writer::end_div();

// Quiz Statistics Section
echo html_writer::start_div('activity-card', ['style' => '']);
echo html_writer::start_div('', ['style' => 'display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;']);
echo html_writer::tag('h3', 'Quiz Statistics', ['style' => 'margin: 0; font-size: 18px; font-weight: 600; color: #1f2937;']);
echo html_writer::tag('button', '<i class="fas fa-plus"></i> New Quiz', ['style' => 'padding: 6px 12px; background: #8b5cf6; color: white; border: none; border-radius: 4px; font-size: 12px; cursor: pointer;', 'onclick' => 'createQuiz()']);
echo html_writer::end_div();

if (!empty($performance_data['quiz_stats'])) {
    foreach ($performance_data['quiz_stats'] as $quiz) {
        echo html_writer::start_div('', ['style' => 'display: flex; justify-content: space-between; align-items: center; padding: 12px; background: #f9fafb; border-radius: 8px; margin-bottom: 8px;']);
        echo html_writer::start_div('', ['style' => 'flex: 1;']);
        echo html_writer::tag('h4', $quiz->name, ['style' => 'margin: 0 0 4px 0; font-size: 14px; font-weight: 600; color: #1f2937;']);
        echo html_writer::tag('p', 'Attempts: ' . $quiz->attempts . ' | Avg: ' . round($quiz->avg_score, 1), ['style' => 'margin: 0; font-size: 12px; color: #6b7280;']);
        echo html_writer::end_div();
        echo html_writer::tag('button', '<i class="fas fa-eye"></i>', ['style' => 'padding: 4px 8px; background: #8b5cf6; color: white; border: none; border-radius: 4px; font-size: 12px; cursor: pointer;', 'onclick' => 'viewQuiz(\'' . $quiz->name . '\')']);
        echo html_writer::end_div();
    }
} else {
    echo html_writer::tag('p', 'No quizzes found', ['style' => 'text-align: center; color: #6b7280; font-style: italic;']);
}
echo html_writer::end_div();

echo html_writer::end_div(); // End additional sections

echo html_writer::end_div(); // End dashboard content
echo html_writer::end_div(); // End main container

echo '</div>'; // End teacher-main-content
echo '</div>'; // End teacher-dashboard-wrapper

// Add CSS for proper layout and spacing
echo '<style>';
echo '.dashboard-section {';
echo '    margin-bottom: 32px;';
echo '    position: relative;';
echo '}';
echo '.additional-sections {';
echo '    clear: both;';
echo '    margin-top: 40px;';
echo '    position: relative;';
echo '    z-index: 2;';
echo '}';
echo '.activity-card {';
echo '    background: white;';
echo '    border-radius: 16px;';
echo '    padding: 24px;';
echo '    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);';
echo '    margin-bottom: 0;';
echo '    position: relative;';
echo '    z-index: 1;';
echo '    min-height: 300px;';
echo '}';
echo '.section-title {';
echo '    margin-bottom: 24px;';
echo '    border-bottom: 2px solid #e5e7eb;';
echo '    padding-bottom: 8px;';
echo '}';
echo '@media (max-width: 768px) {';
echo '    .additional-sections {';
echo '        margin-top: 20px;';
echo '    }';
echo '    .activity-card {';
echo '        min-height: 250px;';
echo '        margin-bottom: 16px;';
echo '    }';
echo '    .section-title h2 {';
echo '        font-size: 18px;';
echo '    }';
echo '}';
echo '</style>';

// Add JavaScript for interactive functionality
echo '<script>';
echo 'function filterByYear(year) {';
echo '    console.log("Filtering by year:", year);';
echo '    // Add year filtering logic here';
echo '}';
echo 'function filterByCourse(courseId) {';
echo '    window.location.href = "?id=" + courseId;';
echo '}';
echo 'function exportStudentData() {';
echo '    alert("Exporting student data...");';
echo '    // Add export functionality here';
echo '}';
echo 'function openAnalytics() {';
echo '    window.open("' . $CFG->wwwroot . '/theme/remui_kids/pages/analytics.php?id=' . $courseid . '", "_blank");';
echo '}';
echo 'function refreshData() {';
echo '    location.reload();';
echo '}';
echo 'function generateReport() {';
echo '    window.open("' . $CFG->wwwroot . '/theme/remui_kids/pages/report.php?id=' . $courseid . '", "_blank");';
echo '}';
echo 'function viewExamDetails() {';
echo '    window.open("' . $CFG->wwwroot . '/theme/remui_kids/pages/exam_details.php?id=' . $courseid . '", "_blank");';
echo '}';
echo 'function addStudent() {';
echo '    window.open("' . $CFG->wwwroot . '/enrol/users.php?id=' . $courseid . '", "_blank");';
echo '}';
echo 'function viewAllStudents() {';
echo '    window.open("' . $CFG->wwwroot . '/theme/remui_kids/teacher/students.php?id=' . $courseid . '", "_blank");';
echo '}';
echo 'function refreshStudents() {';
echo '    location.reload();';
echo '}';
echo 'function createAssignment() {';
echo '    window.open("' . $CFG->wwwroot . '/mod/assign/view.php?id=' . $courseid . '&action=add", "_blank");';
echo '}';
echo 'function viewAssignment(name) {';
echo '    alert("Viewing assignment: " + name);';
echo '}';
echo 'function createQuiz() {';
echo '    window.open("' . $CFG->wwwroot . '/mod/quiz/view.php?id=' . $courseid . '&action=add", "_blank");';
echo '}';
echo 'function viewQuiz(name) {';
echo '    alert("Viewing quiz: " + name);';
echo '}';
echo '</script>';

// Include Moodle footer
try {
    echo $OUTPUT->footer();
} catch (Exception $e) {
    error_log("Error in footer: " . $e->getMessage());
    echo "</body></html>";
}

?>
