<?php
/**
 * Student Courses Page
 * Displays courses for Grade 1-3 students in the student dashboard
 */

require_once('../../config.php');
require_login();

// Get current user
global $USER, $DB, $OUTPUT, $PAGE;

// Get view parameter (my_courses or all_courses)
$view = optional_param('view', 'my_courses', PARAM_ALPHA);

// Set page context
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/theme/remui_kids/student_courses.php', array('view' => $view));
$PAGE->set_title($view === 'all_courses' ? 'All Courses' : 'My Courses');
$PAGE->set_heading($view === 'all_courses' ? 'All Courses' : 'My Courses');

// Check if user is a student (has student role)
$user_roles = get_user_roles($context, $USER->id);
$is_student = false;
foreach ($user_roles as $role) {
    if ($role->shortname === 'student') {
        $is_student = true;
        break;
    }
}

// Redirect if not a student
if (!$is_student) {
    redirect(new moodle_url('/'));
}

// Get user's grade level from profile or cohort
$user_grade = 'Grade 1'; // Default grade
$user_cohorts = cohort_get_user_cohorts($USER->id);

// Check user profile custom field for grade
$user_profile_fields = profile_user_record($USER->id);
if (isset($user_profile_fields->grade)) {
    $user_grade = $user_profile_fields->grade;
} else {
    // Fallback to cohort-based detection
    foreach ($user_cohorts as $cohort) {
        $cohort_name = strtolower($cohort->name);
        if (strpos($cohort_name, 'grade 1') !== false || strpos($cohort_name, 'elementary grade 1') !== false) {
            $user_grade = 'Grade 1';
            break;
        } elseif (strpos($cohort_name, 'grade 2') !== false || strpos($cohort_name, 'elementary grade 2') !== false) {
            $user_grade = 'Grade 2';
            break;
        } elseif (strpos($cohort_name, 'grade 3') !== false || strpos($cohort_name, 'elementary grade 3') !== false) {
            $user_grade = 'Grade 3';
            break;
        }
    }
}

// Get courses for the student's grade level
$grade_courses = array();

// Define grade-specific course patterns and categories
$grade_course_patterns = array(
    'Grade 1' => array(
        'patterns' => array('grade 1', 'g1', 'first grade', 'elementary grade 1', 'beginner', 'foundation', 'level 1'),
        'categories' => array('Grade 1', 'Elementary', 'Foundation', 'Beginner')
    ),
    'Grade 2' => array(
        'patterns' => array('grade 2', 'g2', 'second grade', 'elementary grade 2', 'intermediate', 'level 2'),
        'categories' => array('Grade 2', 'Elementary', 'Intermediate')
    ),
    'Grade 3' => array(
        'patterns' => array('grade 3', 'g3', 'third grade', 'elementary grade 3', 'advanced', 'level 3'),
        'categories' => array('Grade 3', 'Elementary', 'Advanced')
    )
);

// Get category names for better filtering
$categories = $DB->get_records('course_categories', null, '', 'id,name');

// Determine which courses to fetch based on view
if ($view === 'all_courses') {
    // Get all visible courses from categories matching the student's grade level
    $all_courses = $DB->get_records('course', array('visible' => 1), '', 'id,fullname,shortname,summary,category');
    
    // Filter courses by grade-specific categories
    foreach ($all_courses as $course) {
        if ($course->id == 1) continue; // Skip site course
        
        $course_name = strtolower($course->fullname);
        $course_summary = strtolower($course->summary);
        $course_category = isset($categories[$course->category]) ? strtolower($categories[$course->category]->name) : '';
        
        $matches_grade = false;
        
        // Check patterns in course name and summary
        foreach ($grade_course_patterns[$user_grade]['patterns'] as $pattern) {
            if (strpos($course_name, $pattern) !== false || strpos($course_summary, $pattern) !== false) {
                $matches_grade = true;
                break;
            }
        }
        
        // Check category name
        if (!$matches_grade) {
            foreach ($grade_course_patterns[$user_grade]['categories'] as $category_pattern) {
                if (strpos($course_category, strtolower($category_pattern)) !== false) {
                    $matches_grade = true;
                    break;
                }
            }
        }
        
        if ($matches_grade) {
            $grade_courses[] = $course;
        }
    }
} else {
    // Get only courses the user is enrolled in (My Courses view)
    $enrolled_courses = enrol_get_users_courses($USER->id, true, array('id', 'fullname', 'shortname', 'summary', 'category'));
    
    // Filter courses based on grade level
    foreach ($enrolled_courses as $course) {
        if ($course->id == 1) continue; // Skip site course
        
        $course_name = strtolower($course->fullname);
        $course_summary = strtolower($course->summary);
        $course_category = isset($categories[$course->category]) ? strtolower($categories[$course->category]->name) : '';
        
        $matches_grade = false;
        
        // Check patterns in course name and summary
        foreach ($grade_course_patterns[$user_grade]['patterns'] as $pattern) {
            if (strpos($course_name, $pattern) !== false || strpos($course_summary, $pattern) !== false) {
                $matches_grade = true;
                break;
            }
        }
        
        // Check category name
        if (!$matches_grade) {
            foreach ($grade_course_patterns[$user_grade]['categories'] as $category_pattern) {
                if (strpos($course_category, strtolower($category_pattern)) !== false) {
                    $matches_grade = true;
                    break;
                }
            }
        }
        
        if ($matches_grade) {
            $grade_courses[] = $course;
        }
    }
    
    // If no grade-specific courses found, show all enrolled courses for elementary students
    if (empty($grade_courses) && in_array($user_grade, array('Grade 1', 'Grade 2', 'Grade 3'))) {
        // Filter out system courses and show only user-enrolled courses
        foreach ($enrolled_courses as $course) {
            if ($course->id != 1 && $course->visible) {
                $grade_courses[] = $course;
            }
        }
    }
}

// Get user's enrolled course IDs for checking enrollment status
$user_enrolled_course_ids = array();
if ($view === 'all_courses') {
    $enrolled_courses_check = enrol_get_users_courses($USER->id, true, array('id'));
    foreach ($enrolled_courses_check as $enrolled_course) {
        $user_enrolled_course_ids[] = $enrolled_course->id;
    }
}

// Prepare course data for template
$courses_data = array();
foreach ($grade_courses as $course) {
    if ($course->id == 1) continue; // Skip site course
    
    // Check if user is enrolled (important for "All Courses" view)
    $is_enrolled = ($view === 'my_courses') || in_array($course->id, $user_enrolled_course_ids);
    
    // Get course image
    $course_image = '';
    $course_context = context_course::instance($course->id);
    $fs = get_file_storage();
    $files = $fs->get_area_files($course_context->id, 'course', 'overviewfiles', 0, 'itemid, filepath, filename', false);
    if ($files) {
        foreach ($files as $file) {
            $course_image = moodle_url::make_pluginfile_url(
                $file->get_contextid(),
                $file->get_component(),
                $file->get_filearea(),
                $file->get_itemid(),
                $file->get_filepath(),
                $file->get_filename()
            )->out();
            break;
        }
    }
    
    // Default course image if none found
    if (empty($course_image)) {
        $course_image = $OUTPUT->image_url('default_course', 'theme');
    }
    
    // Get course progress (only if enrolled)
    $progress = 0;
    if ($is_enrolled) {
        $completion = new completion_info($course);
        if ($completion->is_enabled()) {
            $progress = (int) \core_completion\progress::get_course_progress_percentage($course, $USER->id);
        }
    }
    
    // Determine if course is in progress or completed
    $in_progress = $is_enrolled && $progress > 0 && $progress < 100;
    $completed = $is_enrolled && $progress >= 100;
    
    // Get course URL
    $course_url = new moodle_url('/course/view.php', array('id' => $course->id));
    
    // Get enroll URL if not enrolled
    $enroll_url = '';
    if (!$is_enrolled) {
        // Try to find guest/self enrolment
        $enroll_url = new moodle_url('/enrol/index.php', array('id' => $course->id));
    }
    
    $courses_data[] = array(
        'id' => $course->id,
        'fullname' => $course->fullname,
        'shortname' => $course->shortname,
        'summary' => format_text($course->summary, FORMAT_HTML),
        'image' => $course_image,
        'url' => $course_url->out(),
        'enroll_url' => $enroll_url ? $enroll_url->out() : '',
        'progress' => $progress,
        'grade_level' => $user_grade,
        'is_enrolled' => $is_enrolled,
        'in_progress' => $in_progress,
        'completed' => $completed,
        'not_enrolled' => !$is_enrolled
    );
}

// Prepare template data
$template_data = array(
    'user_grade' => $user_grade,
    'courses' => $courses_data,
    'total_courses' => count($courses_data),
    'user_name' => fullname($USER),
    'dashboard_url' => new moodle_url('/my/'),
    'current_url' => $PAGE->url->out(),
    'view' => $view,
    'is_my_courses' => ($view === 'my_courses'),
    'is_all_courses' => ($view === 'all_courses'),
    'my_courses_url' => new moodle_url('/theme/remui_kids/student_courses.php', array('view' => 'my_courses')),
    'all_courses_url' => new moodle_url('/theme/remui_kids/student_courses.php', array('view' => 'all_courses')),
    'page_title' => ($view === 'all_courses' ? 'All Courses' : 'My Courses')
);

// Render the page
echo $OUTPUT->header();

// Use the theme's course template or create a custom one
$renderer = $PAGE->get_renderer('theme_remui_kids');
echo $renderer->render_from_template('theme_remui_kids/student_courses', $template_data);

echo $OUTPUT->footer();
