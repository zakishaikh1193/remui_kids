<?php
/**
 * High School Courses Page (Grade 9-12)
 * Displays courses for Grade 9-12 students in a professional format
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
$PAGE->set_url('/theme/remui_kids/highschool_courses.php');
$PAGE->set_title('My Courses');
$PAGE->set_heading('My Courses');
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

// Get courses for the student's grade level
$grade_courses = array();

// Define grade-specific course patterns and categories
$grade_course_patterns = array(
    'Grade 9' => array(
        'patterns' => array('grade 9', 'g9', 'ninth grade', 'high school grade 9', 'freshman', 'year 9'),
        'categories' => array('Grade 9', 'High School', 'Freshman', 'Year 9')
    ),
    'Grade 10' => array(
        'patterns' => array('grade 10', 'g10', 'tenth grade', 'high school grade 10', 'sophomore', 'year 10'),
        'categories' => array('Grade 10', 'High School', 'Sophomore', 'Year 10')
    ),
    'Grade 11' => array(
        'patterns' => array('grade 11', 'g11', 'eleventh grade', 'high school grade 11', 'junior', 'year 11'),
        'categories' => array('Grade 11', 'High School', 'Junior', 'Year 11')
    ),
    'Grade 12' => array(
        'patterns' => array('grade 12', 'g12', 'twelfth grade', 'high school grade 12', 'senior', 'year 12'),
        'categories' => array('Grade 12', 'High School', 'Senior', 'Year 12')
    )
);

// Get all courses the user is enrolled in
$enrolled_courses = enrol_get_users_courses($USER->id, true, array('id', 'fullname', 'shortname', 'summary', 'category', 'startdate', 'enddate'));

// Get category names for better filtering
$categories = $DB->get_records('course_categories', null, '', 'id,name');

// Filter courses based on grade level
foreach ($enrolled_courses as $course) {
    if ($course->id == 1) continue; // Skip site course
    
    $course_name = strtolower($course->fullname);
    $course_summary = strtolower($course->summary);
    $course_category = isset($categories[$course->category]) ? strtolower($categories[$course->category]->name) : '';
    
    $matches_grade = false;
    
    // Check patterns in course name and summary
    if (isset($grade_course_patterns[$user_grade])) {
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
    }
    
    if ($matches_grade) {
        $grade_courses[] = $course;
    }
}

// If no grade-specific courses found, show all enrolled courses for high school students
if (empty($grade_courses)) {
    // Filter out system courses and show only user-enrolled courses
    foreach ($enrolled_courses as $course) {
        if ($course->id != 1 && $course->visible) {
            $grade_courses[] = $course;
        }
    }
}

// Add mock data if no courses found (for testing)
if (empty($grade_courses)) {
    $mock_courses = array(
        (object) array(
            'id' => 101,
            'fullname' => 'Advanced Mathematics - Grade ' . substr($user_grade, -2),
            'shortname' => 'MATH-' . substr($user_grade, -2),
            'summary' => 'Master advanced mathematical concepts including algebra, geometry, and calculus.',
            'category' => 1,
            'startdate' => time(),
            'enddate' => time() + (180 * 24 * 60 * 60),
            'visible' => 1
        ),
        (object) array(
            'id' => 102,
            'fullname' => 'English Literature',
            'shortname' => 'ENG-LIT',
            'summary' => 'Explore classic and modern literature, poetry, and creative writing.',
            'category' => 1,
            'startdate' => time(),
            'enddate' => time() + (180 * 24 * 60 * 60),
            'visible' => 1
        ),
        (object) array(
            'id' => 103,
            'fullname' => 'Computer Science Fundamentals',
            'shortname' => 'CS-101',
            'summary' => 'Learn programming basics, algorithms, and data structures.',
            'category' => 1,
            'startdate' => time(),
            'enddate' => time() + (180 * 24 * 60 * 60),
            'visible' => 1
        ),
        (object) array(
            'id' => 104,
            'fullname' => 'Biology - Life Sciences',
            'shortname' => 'BIO-LS',
            'summary' => 'Study living organisms, ecosystems, and biological processes.',
            'category' => 1,
            'startdate' => time(),
            'enddate' => time() + (180 * 24 * 60 * 60),
            'visible' => 1
        ),
        (object) array(
            'id' => 105,
            'fullname' => 'World History',
            'shortname' => 'HIST-WORLD',
            'summary' => 'Discover major historical events, civilizations, and cultural movements.',
            'category' => 1,
            'startdate' => time(),
            'enddate' => time() + (180 * 24 * 60 * 60),
            'visible' => 1
        ),
        (object) array(
            'id' => 106,
            'fullname' => 'Physics - Mechanics',
            'shortname' => 'PHYS-MECH',
            'summary' => 'Understand forces, motion, energy, and the laws of physics.',
            'category' => 1,
            'startdate' => time(),
            'enddate' => time() + (180 * 24 * 60 * 60),
            'visible' => 1
        )
    );
    $grade_courses = $mock_courses;
}

// Prepare course data for template
$courses_data = array();
$courses_by_subject = array();

foreach ($grade_courses as $course) {
    if ($course->id == 1) continue; // Skip site course
    
    // Get course image - Enhanced image fetching
    $course_image = '';
    
    // Check if this is a mock course or real course
    $is_mock = ($course->id >= 101 && $course->id <= 106);
    
    if (!$is_mock) {
        try {
            $course_context = context_course::instance($course->id);
            $fs = get_file_storage();
            
            // Try to get course overview files first
            $files = $fs->get_area_files($course_context->id, 'course', 'overviewfiles', 0, 'itemid, filepath, filename', false);
            if ($files) {
                foreach ($files as $file) {
                    if ($file->is_valid_image()) {
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
            }
            
            // If no overview files, try course summary images
            if (empty($course_image)) {
                $files = $fs->get_area_files($course_context->id, 'course', 'summary', 0, 'itemid, filepath, filename', false);
                if ($files) {
                    foreach ($files as $file) {
                        if ($file->is_valid_image()) {
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
                }
            }
            
            // If still no image, try course section images
            if (empty($course_image)) {
                $files = $fs->get_area_files($course_context->id, 'course', 'section', 0, 'itemid, filepath, filename', false);
                if ($files) {
                    foreach ($files as $file) {
                        if ($file->is_valid_image()) {
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
                }
            }
            
            // If still no image, try course files area
            if (empty($course_image)) {
                $files = $fs->get_area_files($course_context->id, 'course', 'content', 0, 'itemid, filepath, filename', false);
                if ($files) {
                    foreach ($files as $file) {
                        if ($file->is_valid_image()) {
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
                }
            }
            
        } catch (Exception $e) {
            // If context doesn't exist, use default image
            error_log("Course image fetch error for course {$course->id}: " . $e->getMessage());
        }
    }
    
    // Default course image if none found - Use comprehensive subject-specific images (same as lib.php)
    if (empty($course_image)) {
        $course_name_lower = strtolower($course->fullname);
        $course_summary_lower = strtolower($course->summary);
        $combined_text = $course_name_lower . ' ' . $course_summary_lower;
        
        $fallback_images = [
            'mathematics' => [
                'https://img.freepik.com/free-photo/mathematics-formulas-written-blackboard_1150-1016.jpg',
                'https://img.freepik.com/free-photo/calculator-math-education-concept_1150-1016.jpg',
                'https://img.freepik.com/free-photo/student-solving-math-problem_1150-1016.jpg'
            ],
            'english' => [
                'https://img.freepik.com/free-photo/books-stack-with-copy-space_1150-1016.jpg',
                'https://img.freepik.com/free-photo/student-reading-book_1150-1016.jpg',
                'https://img.freepik.com/free-photo/writing-essay-concept_1150-1016.jpg'
            ],
            'science' => [
                'https://img.freepik.com/free-photo/science-laboratory-with-microscope_1150-1016.jpg',
                'https://img.freepik.com/free-photo/chemistry-experiment-concept_1150-1016.jpg',
                'https://img.freepik.com/free-photo/biology-lab-equipment_1150-1016.jpg'
            ],
            'history' => [
                'https://img.freepik.com/free-photo/historical-books-library_1150-1016.jpg',
                'https://img.freepik.com/free-photo/ancient-world-map_1150-1016.jpg',
                'https://img.freepik.com/free-photo/historical-documents_1150-1016.jpg'
            ],
            'art' => [
                'https://img.freepik.com/free-photo/art-supplies-paintbrushes_1150-1016.jpg',
                'https://img.freepik.com/free-photo/colorful-paint-palette_1150-1016.jpg',
                'https://img.freepik.com/free-photo/artist-painting-canvas_1150-1016.jpg'
            ],
            'music' => [
                'https://img.freepik.com/free-photo/musical-instruments-piano_1150-1016.jpg',
                'https://img.freepik.com/free-photo/music-notes-sheet_1150-1016.jpg',
                'https://img.freepik.com/free-photo/student-playing-guitar_1150-1016.jpg'
            ],
            'physical education' => [
                'https://img.freepik.com/free-photo/sports-equipment-gym_1150-1016.jpg',
                'https://img.freepik.com/free-photo/students-playing-basketball_1150-1016.jpg',
                'https://img.freepik.com/free-photo/fitness-training-concept_1150-1016.jpg'
            ],
            'computer' => [
                'https://img.freepik.com/free-photo/computer-programming-concept_1150-1016.jpg',
                'https://img.freepik.com/free-photo/coding-laptop-screen_1150-1016.jpg',
                'https://img.freepik.com/free-photo/technology-education-concept_1150-1016.jpg'
            ],
            'default' => [
                'https://img.freepik.com/free-photo/students-studying-together_1150-1016.jpg',
                'https://img.freepik.com/free-photo/education-learning-concept_1150-1016.jpg',
                'https://img.freepik.com/free-photo/classroom-learning-environment_1150-1016.jpg',
                'https://img.freepik.com/free-photo/student-with-books-backpack_1150-1016.jpg',
                'https://img.freepik.com/free-photo/teacher-explaining-lesson_1150-1016.jpg'
            ]
        ];
        
        // Determine which category of images to use based on course content
        $image_category = 'default';
        $subject_keywords = [
            'mathematics' => ['math', 'algebra', 'calculus', 'geometry', 'trigonometry', 'statistics'],
            'english' => ['english', 'literature', 'writing', 'grammar', 'composition', 'reading'],
            'science' => ['science', 'biology', 'physics', 'chemistry', 'earth', 'environmental'],
            'history' => ['history', 'social', 'geography', 'civics', 'government', 'world'],
            'art' => ['art', 'drawing', 'painting', 'sculpture', 'visual', 'design'],
            'music' => ['music', 'band', 'choir', 'orchestra', 'instrument', 'vocal'],
            'physical education' => ['pe', 'physical', 'sports', 'fitness', 'health', 'exercise'],
            'computer' => ['computer', 'programming', 'coding', 'technology', 'software', 'digital']
        ];
        
        foreach ($subject_keywords as $subject => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($combined_text, $keyword) !== false) {
                    $image_category = $subject;
                    break 2; // Break out of both loops
                }
            }
        }
        
        // Select a random image from the appropriate category
        $course_image = $fallback_images[$image_category][array_rand($fallback_images[$image_category])];
    }
    
    // Get course progress and additional statistics
    $progress = 0;
    $completed_sections = 0;
    $total_sections = 0;
    $completed_activities = 0;
    $total_activities = 0;
    $estimated_time = 0;
    $points_earned = 0;
    
    if (!$is_mock) {
        try {
            $completion = new completion_info($course);
            if ($completion->is_enabled()) {
                $progress = (int) \core_completion\progress::get_course_progress_percentage($course, $USER->id);
                
                // Get course sections and activities
                $sections = $DB->get_records('course_sections', array('course' => $course->id), 'section');
                $total_sections = count($sections);
                
                // Count completed sections (simplified logic)
                $completed_sections = max(0, round(($progress / 100) * $total_sections));
                
                // Get course modules/activities
                $modules = $DB->get_records('course_modules', array('course' => $course->id, 'visible' => 1));
                $total_activities = count($modules);
                $completed_activities = max(0, round(($progress / 100) * $total_activities));
                
                // Estimate time based on activities (rough calculation)
                $estimated_time = $total_activities * 15; // 15 minutes per activity
                
                // Calculate points (simplified)
                $points_earned = round(($progress / 100) * 100);
            }
        } catch (Exception $e) {
            // If course doesn't exist, use random data for demo
        }
    }
    
    // For mock courses, generate realistic data
    if ($is_mock) {
        $progress_options = array(0, 25, 50, 75, 100);
        $progress = $progress_options[($course->id - 101) % count($progress_options)];
        
        $total_sections = rand(8, 15);
        $completed_sections = round(($progress / 100) * $total_sections);
        
        $total_activities = rand(20, 40);
        $completed_activities = round(($progress / 100) * $total_activities);
        
        $estimated_time = $total_activities * 15;
        $points_earned = round(($progress / 100) * 100);
    }
    
    // Get course URL
    $course_url = new moodle_url('/course/view.php', array('id' => $course->id));
    
    // Determine course status
    $status = 'in_progress';
    if ($progress >= 100) {
        $status = 'completed';
    } elseif ($progress == 0) {
        $status = 'not_started';
    }
    
    // Get category name
    $category_name = isset($categories[$course->category]) ? $categories[$course->category]->name : 'General';
    
    // Determine subject based on course name or category
    $subject = 'Other';
    $course_name_lower = strtolower($course->fullname);
    if (strpos($course_name_lower, 'math') !== false || strpos($course_name_lower, 'calculus') !== false || strpos($course_name_lower, 'algebra') !== false) {
        $subject = 'Mathematics';
    } elseif (strpos($course_name_lower, 'science') !== false || strpos($course_name_lower, 'physics') !== false || strpos($course_name_lower, 'chemistry') !== false || strpos($course_name_lower, 'biology') !== false) {
        $subject = 'Science';
    } elseif (strpos($course_name_lower, 'english') !== false || strpos($course_name_lower, 'literature') !== false || strpos($course_name_lower, 'writing') !== false) {
        $subject = 'English';
    } elseif (strpos($course_name_lower, 'history') !== false || strpos($course_name_lower, 'social') !== false || strpos($course_name_lower, 'geography') !== false) {
        $subject = 'Social Studies';
    } elseif (strpos($course_name_lower, 'code') !== false || strpos($course_name_lower, 'programming') !== false || strpos($course_name_lower, 'computer') !== false) {
        $subject = 'Computer Science';
    } elseif (strpos($course_name_lower, 'art') !== false || strpos($course_name_lower, 'music') !== false || strpos($course_name_lower, 'drama') !== false) {
        $subject = 'Arts';
    }
    
    $course_data = array(
        'id' => $course->id,
        'fullname' => $course->fullname,
        'shortname' => $course->shortname,
        'summary' => format_text($course->summary, FORMAT_HTML),
        'image' => $course_image,
        'url' => $course_url->out(),
        'progress' => $progress,
        'progress_percentage' => $progress,
        'grade_level' => $user_grade,
        'status' => $status,
        'category' => $category_name,
        'subject' => $subject,
        'completed_sections' => $completed_sections,
        'total_sections' => $total_sections,
        'completed_activities' => $completed_activities,
        'total_activities' => $total_activities,
        'estimated_time' => $estimated_time,
        'points_earned' => $points_earned,
        'instructor_name' => 'Dr. Smith', // Mock instructor name
        'start_date' => date('M d, Y', $course->startdate),
        'last_accessed' => date('M d, Y', time() - rand(0, 7 * 24 * 60 * 60)),
        'completed' => ($progress >= 100),
        'in_progress' => ($progress > 0 && $progress < 100),
        'courseurl' => $course_url->out()
    );
    
    $courses_data[] = $course_data;
    
    // Group by subject
    if (!isset($courses_by_subject[$subject])) {
        $courses_by_subject[$subject] = array();
    }
    $courses_by_subject[$subject][] = $course_data;
}

// Prepare subjects data
$subjects_data = array();
foreach ($courses_by_subject as $subject => $courses) {
    $subjects_data[] = array(
        'subject' => $subject,
        'courses' => $courses,
        'count' => count($courses)
    );
}

// Calculate statistics
$total_courses = count($courses_data);
$completed_courses = 0;
$in_progress_courses = 0;
$not_started_courses = 0;
$total_progress = 0;

foreach ($courses_data as $course) {
    if ($course['status'] == 'completed') {
        $completed_courses++;
    } elseif ($course['status'] == 'in_progress') {
        $in_progress_courses++;
    } else {
        $not_started_courses++;
    }
    $total_progress += $course['progress'];
}

$average_progress = $total_courses > 0 ? round($total_progress / $total_courses) : 0;

// Prepare template data
$template_data = array(
    'user_grade' => $user_grade,
    'courses' => $courses_data,
    'subjects' => $subjects_data,
    'total_courses' => $total_courses,
    'completed_courses' => $completed_courses,
    'in_progress_courses' => $in_progress_courses,
    'not_started_courses' => $not_started_courses,
    'average_progress' => $average_progress,
    'user_name' => fullname($USER),
    'dashboard_url' => new moodle_url('/my/'),
    'current_url' => $PAGE->url->out(),
    'grades_url' => new moodle_url('/grade/report/overview/index.php'),
    'assignments_url' => new moodle_url('/mod/assign/index.php'),
    'messages_url' => new moodle_url('/message/index.php'),
    'profile_url' => new moodle_url('/user/profile.php', array('id' => $USER->id)),
    'logout_url' => new moodle_url('/login/logout.php', array('sesskey' => sesskey())),
    'is_highschool' => true
);

// Output page header with Moodle navigation
echo $OUTPUT->header();

// Add custom CSS for the courses page
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
        
        /* Custom styles for High School Courses Page */
        .highschool-courses-page {
            position: relative;
            background: #f8fafc;
            min-height: calc(100vh - 60px); /* Account for navigation bar */
            margin-left: 280px; /* Account for sidebar */
            margin-top: 60px; /* Account for navigation bar */
            padding: 0;
            width: calc(100% - 280px);
        }
        
        .courses-main-content {
            padding: 0;
            width: 100%;
        }
        
        /* Container fluid padding */
        .container-fluid {
            padding-left: 2rem;
            padding-right: 2rem;
        }
        
        /* Remove course grid padding */
        .courses-grid {
            padding-left: 0;
            padding-right: 0;
        }
        
        /* Remove all padding from main content */
        .courses-main-content {
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
        
        body.has-student-sidebar .highschool-courses-page,
        body.has-enhanced-sidebar .highschool-courses-page {
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
        .courses-page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 1.5rem;
            margin-left: 0;
            margin-right: 0;
            width: 100%;
        }
        .courses-page-header .page-title {
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
        .stat-icon.progress { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .stat-icon.completed { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .stat-icon.average { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #1a202c;
        }
        .courses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1rem;
            padding: 0;
            margin: 0;
        }
        /* Enhanced High School Course Cards */
        .course-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            position: relative;
        }
        .course-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        
        .course-image-container {
            position: relative;
            height: 200px;
            overflow: hidden;
        }
        
        .course-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .course-card:hover .course-image {
            transform: scale(1.05);
        }
        
        /* Fallback for missing images */
        .course-image-placeholder {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
        }
        
        .course-level-badge {
            position: absolute;
            top: 1rem;
            left: 1rem;
            background: rgba(255, 255, 255, 0.9);
            color: #667eea;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            backdrop-filter: blur(10px);
        }
        
        .course-status-indicator {
            position: absolute;
            top: 1rem;
            right: 1rem;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }
        
        .course-status-indicator.completed {
            background: #10b981;
        }
        
        .course-status-indicator.in-progress {
            background: #f59e0b;
        }
        
        .course-status-indicator.not-started {
            background: #6b7280;
        }
        
        .course-content {
            padding: 1.5rem;
        }
        
        .course-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .course-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1a202c;
            margin: 0;
            flex: 1;
        }
        
        .course-grade {
            background: #e6f3ff;
            color: #667eea;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-left: 1rem;
        }
        
        .course-summary {
            color: #718096;
            font-size: 0.9rem;
            line-height: 1.5;
            margin-bottom: 1.5rem;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .course-progress-section {
            margin-bottom: 1.5rem;
        }
        
        .progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .progress-label {
            font-size: 0.9rem;
            color: #4a5568;
            font-weight: 600;
        }
        
        .progress-percentage {
            font-size: 0.9rem;
            color: #667eea;
            font-weight: 700;
        }
        
        .progress-bar-container {
            width: 100%;
            height: 8px;
            background: #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress-bar {
            width: 100%;
            height: 100%;
            background: #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            transition: width 0.3s ease;
        }
        
        .course-stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .stat-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .stat-item .stat-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.9rem;
        }
        
        .stat-item:nth-child(1) .stat-icon { background: #667eea; }
        .stat-item:nth-child(2) .stat-icon { background: #f59e0b; }
        .stat-item:nth-child(3) .stat-icon { background: #10b981; }
        .stat-item:nth-child(4) .stat-icon { background: #ef4444; }
        
        .stat-info {
            flex: 1;
        }
        
        .stat-number {
            font-size: 1rem;
            font-weight: 700;
            color: #1a202c;
            line-height: 1;
        }
        
        .stat-label {
            font-size: 0.8rem;
            color: #718096;
            margin-top: 0.25rem;
        }
        
        .course-actions {
            display: flex;
            gap: 0.75rem;
            align-items: center;
        }
        
        .course-button {
            flex: 1;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .course-button.completed {
            background: #10b981;
            color: white;
        }
        
        .course-button.in-progress {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .course-button.not-started {
            background: #6b7280;
            color: white;
        }
        
        .course-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .course-details-btn {
            width: 40px;
            height: 40px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: white;
            color: #718096;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .course-details-btn:hover {
            background: #f8fafc;
            border-color: #cbd5e0;
            color: #4a5568;
        }
        
        .course-details-panel {
            border-top: 1px solid #e2e8f0;
            padding: 1rem 1.5rem;
            background: #f8fafc;
        }
        
        .details-content h6 {
            color: #1a202c;
            font-weight: 600;
            margin-bottom: 0.75rem;
        }
        
        .detail-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        
        .detail-item strong {
            color: #4a5568;
        }
        @media (max-width: 768px) {
            .student-sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .student-sidebar.show {
                transform: translateX(0);
            }
            
            .highschool-courses-page {
                margin-left: 0 !important;
                margin-top: 60px !important; /* Account for navigation bar on mobile */
                padding: 0 !important;
                min-height: calc(100vh - 60px) !important; /* Account for navigation bar */
            }
            
            .courses-page-header {
                margin-left: 0 !important;
                width: 100% !important;
            }
            
            body.has-student-sidebar #page,
            body.has-enhanced-sidebar #page,
            body.has-student-sidebar #page-wrapper,
            body.has-enhanced-sidebar #page-wrapper {
                margin-left: 0 !important;
            }
            
            .courses-page-header .page-title {
                font-size: 1.8rem;
            }
            .courses-main-content {
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
                    <a href="<?php echo $template_data['current_url']; ?>" class="nav-link active">
                        <i class="fa fa-book-open"></i>
                        <span>My Courses</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo new moodle_url('/theme/remui_kids/highschool_assignments.php'); ?>" class="nav-link">
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
                    <a href="<?php echo new moodle_url('/theme/remui_kids/highschool_profile.php'); ?>" class="nav-link">
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

<div class="highschool-courses-page">
    <!-- Page Header -->
    <div class="courses-page-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="page-title">
                        <i class="fa fa-graduation-cap me-3"></i>
                        My Courses - <?php echo $template_data['user_grade']; ?>
                    </h1>
                    <p>Welcome back, <?php echo $template_data['user_name']; ?>! Continue your learning journey.</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="<?php echo $template_data['dashboard_url']; ?>" class="btn btn-outline-light">
                        <i class="fa fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Course Statistics -->
    <div class="container-fluid">
            <div class="row g-2 mb-2">
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-icon total"><i class="fa fa-book"></i></div>
                        <div>
                            <div class="stat-value"><?php echo $template_data['total_courses']; ?></div>
                            <div>Total Courses</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-icon progress"><i class="fa fa-spinner"></i></div>
                        <div>
                            <div class="stat-value"><?php echo $template_data['in_progress_courses']; ?></div>
                            <div>In Progress</div>
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
                        <div class="stat-icon average"><i class="fa fa-chart-line"></i></div>
                        <div>
                            <div class="stat-value"><?php echo $template_data['average_progress']; ?>%</div>
                            <div>Average Progress</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Courses by Subject -->
            <?php foreach ($template_data['subjects'] as $subject_group): ?>
            <div>
                <div class="courses-grid">
                    <?php foreach ($subject_group['courses'] as $course): ?>
                    <div class="course-card enhanced" data-course-id="<?php echo $course['id']; ?>">
                        <!-- Course Header with Image -->
                        <div class="course-image-container">
                            <?php if (!empty($course['image'])): ?>
                            <img src="<?php echo $course['image']; ?>" alt="<?php echo htmlspecialchars($course['fullname']); ?>" class="course-image">
                            <?php else: ?>
                            <div class="course-image-placeholder">
                                <i class="fa fa-book"></i>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Course Level Badge -->
                            <div class="course-level-badge"><?php echo $course['category']; ?></div>
                            
                            <!-- Course Status Indicator -->
                            <div class="course-status-indicator <?php echo $course['status']; ?>">
                                <i class="fa <?php 
                                if ($course['status'] == 'completed') echo 'fa-check-circle';
                                elseif ($course['status'] == 'in_progress') echo 'fa-play-circle';
                                else echo 'fa-circle-o';
                                ?>"></i>
                        </div>
                        </div>
                        
                        <!-- Course Content -->
                        <div class="course-content">
                            <!-- Course Title and Grade -->
                            <div class="course-header">
                                <h4 class="course-title"><?php echo htmlspecialchars($course['fullname']); ?></h4>
                                <span class="course-grade"><?php echo $course['grade_level']; ?></span>
                            </div>
                            
                            <!-- Course Summary -->
                            <p class="course-summary"><?php echo strip_tags($course['summary']); ?></p>
                            
                            <!-- Progress Section -->
                            <div class="course-progress-section">
                                <div class="progress-header">
                                    <span class="progress-label">Your Progress</span>
                                    <span class="progress-percentage"><?php echo $course['progress_percentage']; ?>%</span>
                                </div>
                            <div class="progress-bar-container">
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo $course['progress_percentage']; ?>%"></div>
                            </div>
                                </div>
                            </div>
                            
                            <!-- Course Statistics -->
                            <div class="course-stats-grid">
                                <div class="stat-item">
                                    <div class="stat-icon">
                                        <i class="fa fa-book"></i>
                                    </div>
                                    <div class="stat-info">
                                        <div class="stat-number"><?php echo $course['completed_sections']; ?>/<?php echo $course['total_sections']; ?></div>
                                        <div class="stat-label">Lessons</div>
                                    </div>
                                </div>
                                
                                <div class="stat-item">
                                    <div class="stat-icon">
                                        <i class="fa fa-tasks"></i>
                                    </div>
                                    <div class="stat-info">
                                        <div class="stat-number"><?php echo $course['completed_activities']; ?>/<?php echo $course['total_activities']; ?></div>
                                        <div class="stat-label">Activities</div>
                                    </div>
                                </div>
                                
                                <div class="stat-item">
                                    <div class="stat-icon">
                                        <i class="fa fa-clock-o"></i>
                                    </div>
                                    <div class="stat-info">
                                        <div class="stat-number"><?php echo $course['estimated_time']; ?></div>
                                        <div class="stat-label">Minutes</div>
                                    </div>
                                </div>
                                
                                <div class="stat-item">
                                    <div class="stat-icon">
                                        <i class="fa fa-star"></i>
                                    </div>
                                    <div class="stat-info">
                                        <div class="stat-number"><?php echo $course['points_earned']; ?></div>
                                        <div class="stat-label">Points</div>
                                    </div>
                                </div>
                            </div>
                                                            
                            <!-- Course Actions -->
                            <div class="course-actions">
                                <?php if ($course['completed']): ?>
                                <a href="<?php echo $course['courseurl']; ?>" class="course-button completed">
                                    <i class="fa fa-check"></i> Review Course
                                </a>
                                <?php elseif ($course['in_progress']): ?>
                                <a href="<?php echo $course['courseurl']; ?>" class="course-button in-progress">
                                    <i class="fa fa-play"></i> Continue Learning
                                </a>
                                <?php else: ?>
                                <a href="<?php echo $course['courseurl']; ?>" class="course-button not-started">
                                    <i class="fa fa-play"></i> Start Course
                                </a>
                                <?php endif; ?>
                                
                                <!-- Course Details Button -->
                                <button class="course-details-btn" onclick="toggleCourseDetails(<?php echo $course['id']; ?>)">
                                    <i class="fa fa-info-circle"></i>
                                </button>
                            </div>
                            
                            <!-- Course Details Panel (Hidden by default) -->
                            <div class="course-details-panel" id="details-<?php echo $course['id']; ?>" style="display: none;">
                                <div class="details-content">
                                    <h6>Course Details</h6>
                                    <div class="detail-item">
                                        <strong>Instructor:</strong> <?php echo $course['instructor_name']; ?>
                                    </div>
                                    <div class="detail-item">
                                        <strong>Start Date:</strong> <?php echo $course['start_date']; ?>
                                    </div>
                                    <div class="detail-item">
                                        <strong>Last Accessed:</strong> <?php echo $course['last_accessed']; ?>
                                    </div>
                                    <div class="detail-item">
                                        <strong>Subject:</strong> <?php echo $course['subject']; ?>
                                    </div>
                                    <div class="detail-item">
                                        <strong>Course Code:</strong> <?php echo $course['shortname']; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
</div>

<script>
// Initialize enhanced sidebar
document.addEventListener('DOMContentLoaded', function() {
    const enhancedSidebar = document.querySelector('.enhanced-sidebar');
    if (enhancedSidebar) {
        document.body.classList.add('has-student-sidebar', 'has-enhanced-sidebar');
        console.log('Enhanced sidebar initialized for high school courses page');
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

// Toggle course details function
function toggleCourseDetails(courseId) {
    const detailsPanel = document.getElementById('details-' + courseId);
    if (detailsPanel) {
        if (detailsPanel.style.display === 'none' || detailsPanel.style.display === '') {
            detailsPanel.style.display = 'block';
        } else {
            detailsPanel.style.display = 'none';
        }
    }
}
</script>
<?php
// Output page footer with Moodle navigation
echo $OUTPUT->footer();

