<?php
/**
 * Moodle-integrated My Courses page for remui_kids theme
 * This page is properly integrated within Moodle and will inherit favicon and all settings
 *
 * @package    theme_remui_kids
 * @copyright  2024 KodeIt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/lib/completionlib.php');
require_once($CFG->dirroot . '/theme/remui_kids/lib.php');

// Require login
require_login();

// Set up the page properly within Moodle
global $USER, $DB, $PAGE, $OUTPUT, $CFG;

// Set page context and properties
$PAGE->set_context(context_system::instance());
$PAGE->set_url('/theme/remui_kids/moodle_mycourses.php');
$PAGE->set_pagelayout('base'); // Use Moodle's base layout to inherit favicon
$PAGE->set_title('My Courses', false); // Set to false to prevent site name concatenation
$PAGE->set_heading('My Courses');

// Get user's cohort information
$usercohorts = $DB->get_records_sql(
    "SELECT c.name, c.id 
     FROM {cohort} c 
     JOIN {cohort_members} cm ON c.id = cm.cohortid 
     WHERE cm.userid = ?",
    [$USER->id]
);

$usercohortname = '';
$usercohortid = 0;
$dashboardtype = 'default';

if (!empty($usercohorts)) {
    $cohort = reset($usercohorts);
    $usercohortname = $cohort->name;
    $usercohortid = $cohort->id;
    
    // Determine dashboard type based on cohort
    if (preg_match('/grade\s*(?:1[0-2]|[8-9])/i', $usercohortname)) {
        $dashboardtype = 'highschool';
    } elseif (preg_match('/grade\s*[4-7]/i', $usercohortname)) {
        $dashboardtype = 'middle';
    } elseif (preg_match('/grade\s*[1-3]/i', $usercohortname)) {
        $dashboardtype = 'elementary';
    }
}

// Get student's courses based on dashboard type
$studentcourses = [];
if ($dashboardtype === 'elementary') {
    $studentcourses = theme_remui_kids_get_elementary_courses($USER->id);
} elseif ($dashboardtype === 'middle') {
    $studentcourses = theme_remui_kids_get_elementary_courses($USER->id);
} elseif ($dashboardtype === 'highschool') {
    $studentcourses = theme_remui_kids_get_highschool_courses($USER->id);
} else {
    // Default: get all enrolled courses
    $courses = enrol_get_all_users_courses($USER->id, true);
    foreach ($courses as $course) {
        $coursecontext = context_course::instance($course->id);
        
        // Get course image
        $courseimage = '';
        $fs = get_file_storage();
        $files = $fs->get_area_files($coursecontext->id, 'course', 'overviewfiles', 0, 'timemodified DESC', false);
        
        if (!empty($files)) {
            $file = reset($files);
            $courseimage = moodle_url::make_pluginfile_url(
                $coursecontext->id,
                'course',
                'overviewfiles',
                null,
                '/',
                $file->get_filename()
            )->out();
        }
        
        // Get course category
        $category = $DB->get_record('course_categories', ['id' => $course->category]);
        $categoryname = $category ? $category->name : 'General';
        
        // Get progress
        $progress = theme_remui_kids_get_course_progress($USER->id, $course->id);
        
        $studentcourses[] = [
            'id' => $course->id,
            'fullname' => $course->fullname,
            'shortname' => $course->shortname,
            'summary' => $course->summary,
            'courseimage' => $courseimage,
            'categoryname' => $categoryname,
            'progress_percentage' => $progress['percentage'],
            'completed_activities' => $progress['completed'],
            'total_activities' => $progress['total'],
            'courseurl' => (new moodle_url('/course/view.php', ['id' => $course->id]))->out(),
            'completed' => $progress['percentage'] >= 100,
            'in_progress' => $progress['percentage'] > 0 && $progress['percentage'] < 100,
            'not_started' => $progress['percentage'] == 0,
            'grade_level' => $categoryname
        ];
    }
}

// Start output buffering to capture content
ob_start();
?>

<!DOCTYPE html>
<html <?php echo $OUTPUT->htmlattributes(); ?>>
<head>
    <title><?php echo $OUTPUT->page_title(); ?></title>
    <link rel="shortcut icon" href="<?php echo $OUTPUT->favicon(); ?>" />
    <?php echo $OUTPUT->standard_head_html() ?>
    
    <!-- Custom CSS for My Courses Page -->
    <style>
        /* Main Content Area - Full Width */
        .main-content-full {
            width: 100%;
            min-height: 100vh;
            background: #f8f9fa;
            padding: 0;
        }
        body {
            margin: 0;
            padding: 0;
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .moodle-mycourses-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px 20px;
            width: 100%;
        }
        
        .page-header {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .page-title {
            font-size: 2rem;
            color: #2c3e50;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .page-title i {
            color: #3498db;
        }
        
        .page-subtitle {
            color: #6c757d;
            margin: 5px 0 0 0;
            font-size: 1rem;
        }
        
        .courses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .course-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .course-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .course-image-container {
            position: relative;
            height: 180px;
            overflow: hidden;
        }
        
        .course-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .course-image-placeholder {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #3498db, #2980b9);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
        }
        
        .course-category-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background: rgba(0, 123, 255, 0.9);
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .course-content {
            padding: 20px;
        }
        
        .course-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #2c3e50;
            margin: 0 0 10px 0;
        }
        
        .course-grade {
            background: #ff6b35;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 15px;
        }
        
        .course-summary {
            color: #6c757d;
            font-size: 0.9rem;
            margin: 0 0 15px 0;
            line-height: 1.4;
        }
        
        .progress-section {
            margin-bottom: 20px;
        }
        
        .progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        
        .progress-label {
            font-size: 0.9rem;
            color: #495057;
            font-weight: 500;
        }
        
        .progress-percentage {
            font-size: 0.9rem;
            color: #28a745;
            font-weight: 600;
        }
        
        .progress-bar {
            height: 6px;
            background: #e9ecef;
            border-radius: 3px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #28a745, #20c997);
            border-radius: 3px;
            transition: width 0.3s ease;
        }
        
        .course-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .stat-item {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 8px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 1rem;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .stat-label {
            font-size: 0.7rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .course-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-primary {
            flex: 1;
            background: #007bff;
            color: white;
            border: none;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background: #0056b3;
            color: white;
            text-decoration: none;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 8px;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        
        .btn-secondary:hover {
            background: #495057;
            color: white;
            text-decoration: none;
        }
        
        .no-courses {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .no-courses i {
            font-size: 4rem;
            color: #6c757d;
            margin-bottom: 20px;
        }
        
        .no-courses h3 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .no-courses p {
            color: #6c757d;
            margin-bottom: 20px;
        }
        
        .back-to-dashboard {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #3498db;
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .back-to-dashboard:hover {
            background: #2980b9;
            color: white;
            text-decoration: none;
            transform: translateY(-2px);
        }
    </style>
</head>

<body class="<?php echo $OUTPUT->body_attributes(['class' => 'moodle-mycourses-page']); ?>">
    <div class="main-content-full">
        <div class="moodle-mycourses-container">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fa fa-book"></i>
                My Courses
            </h1>
            <p class="page-subtitle">
                <?php if ($usercohortname): ?>
                    Welcome to your <?php echo htmlspecialchars($usercohortname); ?> courses
                <?php else: ?>
                    Manage and access your enrolled courses
                <?php endif; ?>
            </p>
        </div>
        
        <?php if (!empty($studentcourses)): ?>
            <!-- Courses Grid -->
            <div class="courses-grid">
                <?php foreach ($studentcourses as $course): ?>
                    <div class="course-card">
                        <!-- Course Image -->
                        <div class="course-image-container">
                            <?php if (!empty($course['courseimage'])): ?>
                                <img src="<?php echo htmlspecialchars($course['courseimage']); ?>" 
                                     alt="<?php echo htmlspecialchars($course['fullname']); ?>" 
                                     class="course-image">
                            <?php else: ?>
                                <div class="course-image-placeholder">
                                    <i class="fa fa-book"></i>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Category Badge -->
                            <div class="course-category-badge">
                                <?php echo htmlspecialchars($course['categoryname']); ?>
                            </div>
                        </div>
                        
                        <!-- Course Content -->
                        <div class="course-content">
                            <!-- Course Title -->
                            <h3 class="course-title"><?php echo htmlspecialchars($course['fullname']); ?></h3>
                            
                            <!-- Grade Badge -->
                            <div class="course-grade"><?php echo htmlspecialchars($course['grade_level']); ?></div>
                            
                            <!-- Course Summary -->
                            <p class="course-summary">
                                <?php echo htmlspecialchars($course['summary'] ?: 'No description available'); ?>
                            </p>
                            
                            <!-- Progress Section -->
                            <div class="progress-section">
                                <div class="progress-header">
                                    <span class="progress-label">Your Progress</span>
                                    <span class="progress-percentage"><?php echo $course['progress_percentage']; ?>%</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $course['progress_percentage']; ?>%"></div>
                                </div>
                            </div>
                            
                            <!-- Course Statistics -->
                            <div class="course-stats">
                                <div class="stat-item">
                                    <div class="stat-number"><?php echo $course['completed_activities']; ?>/<?php echo $course['total_activities']; ?></div>
                                    <div class="stat-label">Activities</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-number"><?php echo $course['progress_percentage']; ?>%</div>
                                    <div class="stat-label">Complete</div>
                                </div>
                            </div>
                            
                            <!-- Course Actions -->
                            <div class="course-actions">
                                <?php if ($course['completed']): ?>
                                    <a href="<?php echo htmlspecialchars($course['courseurl']); ?>" class="btn-primary">
                                        <i class="fa fa-check"></i> Review Course
                                    </a>
                                <?php elseif ($course['in_progress']): ?>
                                    <a href="<?php echo htmlspecialchars($course['courseurl']); ?>" class="btn-primary">
                                        <i class="fa fa-play"></i> Continue Learning
                                    </a>
                                <?php else: ?>
                                    <a href="<?php echo htmlspecialchars($course['courseurl']); ?>" class="btn-primary">
                                        <i class="fa fa-play"></i> Start Course
                                    </a>
                                <?php endif; ?>
                                
                                <a href="<?php echo htmlspecialchars($course['courseurl']); ?>" class="btn-secondary">
                                    <i class="fa fa-info-circle"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- No Courses Message -->
            <div class="no-courses">
                <i class="fa fa-book-open"></i>
                <h3>No Courses Available</h3>
                <p>You are not currently enrolled in any courses.</p>
                <a href="<?php echo (new moodle_url('/my/'))->out(); ?>" class="back-to-dashboard">
                    <i class="fa fa-arrow-left"></i>
                    Back to Dashboard
                </a>
            </div>
        <?php endif; ?>
        </div>
    </div>
    
    <?php echo $OUTPUT->standard_end_of_body_html() ?>
</body>
</html>

<?php
// End output buffering and output the content
$content = ob_get_clean();
echo $content;
?>
