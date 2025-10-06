<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * RemUI Kids theme functions
 *
 * @package    theme_remui_kids
 * @copyright  2025 Kodeit
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Get SCSS to prepend.
 *
 * @param theme_config $theme The theme config object.
 * @return string
 */
function theme_remui_kids_get_pre_scss($theme) {
    $scss = '';
    // Kids-friendly color overrides
    $scss .= '
        // Override parent theme colors with kids-friendly palette
        $primary: #FF6B35 !default;        // Bright Orange
        $secondary: #4ECDC4 !default;      // Teal
        $success: #96CEB4 !default;        // Soft Green
        $info: #45B7D1 !default;           // Sky Blue
        $warning: #FFEAA7 !default;        // Light Yellow
        $danger: #DDA0DD !default;         // Light Purple
        
        // Using default RemUI fonts (no custom typography overrides)
        
        // Rounded corners for playful look
        $border-radius: 1rem;
        $border-radius-lg: 1.5rem;
        $border-radius-sm: 0.5rem;
    ';
    return $scss;
}

/**
 * Inject additional SCSS.
 *
 * @param theme_config $theme The theme config object.
 * @return string
 */
function theme_remui_kids_get_extra_scss($theme) {
    $content = '';
    // Add our custom kids-friendly styles
    $content .= file_get_contents($theme->dir . '/scss/post.scss');
    return $content;
}

/**
 * Returns the main SCSS content.
 *
 * @param theme_config $theme The theme config object.
 * @return string
 */
function theme_remui_kids_get_main_scss_content($theme) {
    global $CFG;

    $scss = '';
    $filename = !empty($theme->settings->preset) ? $theme->settings->preset : null;
    $fs = get_file_storage();

    $context = context_system::instance();
    $scss .= file_get_contents($theme->dir . '/scss/preset/default.scss');

    if ($filename && ($filename !== 'default.scss')) {
        $presetfile = $fs->get_file($context->id, 'theme_remui_kids', 'preset', 0, '/', $filename);
        if ($presetfile) {
            $scss .= $presetfile->get_content();
        } else {
            // Safety fallback - maybe the preset is on the file system.
            $filename = $theme->dir . '/scss/preset/' . $filename;
            if (file_exists($filename)) {
                $scss .= file_get_contents($filename);
            }
        }
    }

    // Prepend variables first.
    $scss = theme_remui_kids_get_pre_scss($theme) . $scss;
    return $scss;
}

/**
 * Serves any files associated with the theme settings.
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return bool
 */
function theme_remui_kids_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {
    if ($context->contextlevel == CONTEXT_SYSTEM && ($filearea === 'logo' || $filearea === 'backgroundimage')) {
        $theme = theme_config::load('remui_kids');
        // By default, theme files must be cache-able by both browsers and proxies.
        if (!array_key_exists('cacheability', $options)) {
            $options['cacheability'] = 'public';
        }
        return $theme->setting_file_serve($filearea, $args, $forcedownload, $options);
    } else {
        send_file_not_found();
    }
}

/**
 * Get course sections data for professional card display
 *
 * @param object $course The course object
 * @return array Array of section data
 */
function theme_remui_kids_get_course_sections_data($course) {
    global $CFG, $USER;
    
    require_once($CFG->dirroot . '/course/lib.php');
    require_once($CFG->dirroot . '/completion/criteria/completion_criteria.php');
    
    $modinfo = get_fast_modinfo($course);
    $sections = $modinfo->get_section_info_all();
    $completion = new \completion_info($course);
    
    $sections_data = [];
    
    foreach ($sections as $section) {
        if ($section->section == 0) {
            // Skip the general section (section 0) as it's usually announcements
            continue;
        }
        
        // Skip sections that are modules (subsections) - they should only be accessible within their parent sections
        if ($section->component === 'mod_subsection') {
            continue;
        }
        
        $section_data = [
            'id' => $section->id,
            'section' => $section->section,
            'name' => get_section_name($course, $section),
            'summary' => $section->summary,
            'visible' => $section->visible,
            'available' => $section->available,
            'uservisible' => $section->uservisible,
            'activities' => [],
            'progress' => 0,
            'total_activities' => 0,
            'completed_activities' => 0,
            'has_started' => false,
            'is_completed' => false
        ];
        
        // Get activities in this section
        if (isset($modinfo->sections[$section->section])) {
            foreach ($modinfo->sections[$section->section] as $cmid) {
                $cm = $modinfo->cms[$cmid];
                if ($cm->uservisible) {
                    $section_data['total_activities']++;
                    
                    // Check completion if enabled
                    if ($completion->is_enabled($cm)) {
                        $completiondata = $completion->get_data($cm, false, $USER->id);
                        if ($completiondata->completionstate == COMPLETION_COMPLETE || 
                            $completiondata->completionstate == COMPLETION_COMPLETE_PASS) {
                            $section_data['completed_activities']++;
                        }
                        
                        // Check if user has started this activity
                        if ($completiondata->timestarted > 0) {
                            $section_data['has_started'] = true;
                        }
                    }
                    
                    $section_data['activities'][] = [
                        'id' => $cm->id,
                        'name' => $cm->name,
                        'modname' => $cm->modname,
                        'url' => $cm->url,
                        'icon' => $cm->get_icon_url(),
                        'completion' => $completion->is_enabled($cm) ? $completion->get_data($cm, false, $USER->id)->completionstate : null
                    ];
                }
            }
        }
        
        // Calculate progress percentage
        if ($section_data['total_activities'] > 0) {
            $section_data['progress'] = round(($section_data['completed_activities'] / $section_data['total_activities']) * 100);
        }
        
        // Determine if section is completed
        $section_data['is_completed'] = ($section_data['progress'] == 100 && $section_data['total_activities'] > 0);
        
        // Add professional card data
        $section_data['section_image'] = theme_remui_kids_get_section_image($section->section);
        $section_data['url'] = new moodle_url('/course/view.php', ['id' => $course->id, 'section' => $section->section]);
        
        $sections_data[] = $section_data;
    }
    
    return $sections_data;
}

/**
 * Get default section image
 *
 * @param int $sectionnum Section number
 * @return string Image URL
 */
function theme_remui_kids_get_section_image($sectionnum) {
    global $CFG;

    // Default course section images - you can customize these
    $default_images = [
        1 => 'https://img.freepik.com/free-photo/copy-space-boy-with-books-showing-ok-sign_23-2148469950.jpg',
        2 => 'https://img.freepik.com/free-photo/young-people-row-with-thumbs-up_1098-2557.jpg',
        3 => 'https://img.freepik.com/free-photo/pleased-little-schoolboy-holding-book-points-side-isolated-purple-wall-with-copy-space_141793-75006.jpg',
        4 => 'https://img.freepik.com/free-photo/cheerful-student-writing-holding-books_1098-3439.jpg',
        5 => 'https://img.freepik.com/free-photo/copy-space-boy-with-backpack_23-2148601395.jpg',
        6 => 'https://img.freepik.com/free-photo/sideways-school-boy-copy-space_23-2148764003.jpg',
        7 => 'https://img.freepik.com/free-photo/little-girl-t-shirt-jumpsuit-pointing-up-looking-attentive_176474-39979.jpg',
        8 => 'https://img.freepik.com/free-photo/smiling-schoolgirl-holding-books-looking_171337-271.jpg',
    ];

    $count = count($default_images);
    $index = ($sectionnum - 1) % $count + 1;
    return $default_images[$index] ?? reset($default_images);
}

/**
 * Get default activity image based on activity type
 *
 * @param string $modname Activity module name
 * @return string Image URL
 */
function theme_remui_kids_get_activity_image($modname) {
    $activity_images = [
        'assign' => 'https://images.unsplash.com/photo-1434030216411-0b793f4b4173?q=80&w=400&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D',
        'quiz' => 'https://images.unsplash.com/photo-1434030216411-0b793f4b4173?q=80&w=400&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D',
        'page' => 'https://images.unsplash.com/photo-1434030216411-0b793f4b4173?q=80&w=400&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D',
        'scorm' => 'https://images.unsplash.com/photo-1434030216411-0b793f4b4173?q=80&w=400&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D',
        'forum' => 'https://images.unsplash.com/photo-1434030216411-0b793f4b4173?q=80&w=400&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D',
        'url' => 'https://images.unsplash.com/photo-1434030216411-0b793f4b4173?q=80&w=400&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D',
        'book' => 'https://images.unsplash.com/photo-1434030216411-0b793f4b4173?q=80&w=400&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D',
        'lesson' => 'https://images.unsplash.com/photo-1434030216411-0b793f4b4173?q=80&w=400&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D',
        'workshop' => 'https://images.unsplash.com/photo-1434030216411-0b793f4b4173?q=80&w=400&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D',
        'choice' => 'https://images.unsplash.com/photo-1434030216411-0b793f4b4173?q=80&w=400&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D',
    ];
    
    return $activity_images[$modname] ?? $activity_images['page']; // Default to page image
}

/**
 * Get comprehensive course header data for the beautiful course header
 *
 * @param object $course The course object
 * @return array Array of course header data
 */
function theme_remui_kids_get_course_header_data($course) {
    global $CFG, $DB, $USER;
    
    require_once($CFG->dirroot . '/course/lib.php');
    require_once($CFG->dirroot . '/enrol/locallib.php');
    
    $coursecontext = context_course::instance($course->id);
    
    // Get course image
    $courseimage = theme_remui_kids_get_course_image($course);
    
    // Get enrolled students count (users with 'trainee' role)
    $traineerole = $DB->get_record('role', ['shortname' => 'trainee']);
    $enrolledstudentscount = 0;
    if ($traineerole) {
        $enrolledstudentscount = $DB->count_records_sql(
            "SELECT COUNT(DISTINCT u.id) 
             FROM {user} u 
             JOIN {role_assignments} ra ON u.id = ra.userid 
             JOIN {context} ctx ON ra.contextid = ctx.id 
             WHERE ctx.contextlevel = ? AND ctx.instanceid = ? AND ra.roleid = ? AND u.deleted = 0",
            [CONTEXT_COURSE, $course->id, $traineerole->id]
        );
    }
    
    // Get teachers count (users with 'teacher' or 'editingteacher' role)
    $teacherroles = $DB->get_records_list('role', 'shortname', ['teacher', 'editingteacher']);
    $teacherscount = 0;
    $teacherslist = [];
    
    if (!empty($teacherroles)) {
        $teacherroleids = array_keys($teacherroles);
        $teacherscount = $DB->count_records_sql(
            "SELECT COUNT(DISTINCT u.id) 
             FROM {user} u 
             JOIN {role_assignments} ra ON u.id = ra.userid 
             JOIN {context} ctx ON ra.contextid = ctx.id 
             WHERE ctx.contextlevel = ? AND ctx.instanceid = ? AND ra.roleid IN (" . implode(',', $teacherroleids) . ") AND u.deleted = 0",
            [CONTEXT_COURSE, $course->id]
        );
        
        // Get teacher details
        $teachers = $DB->get_records_sql(
            "SELECT DISTINCT u.id, u.firstname, u.lastname, u.email 
             FROM {user} u 
             JOIN {role_assignments} ra ON u.id = ra.userid 
             JOIN {context} ctx ON ra.contextid = ctx.id 
             WHERE ctx.contextlevel = ? AND ctx.instanceid = ? AND ra.roleid IN (" . implode(',', $teacherroleids) . ") AND u.deleted = 0 
             LIMIT 3",
            [CONTEXT_COURSE, $course->id]
        );
        
        foreach ($teachers as $user) {
            $teacherslist[] = [
                'id' => $user->id,
                'fullname' => fullname($user),
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'email' => $user->email,
                'profileimageurl' => new moodle_url('/user/pix.php/' . $user->id . '/f1.jpg')
            ];
        }
    }
    
    // Get course start and end dates
    $startdate = $course->startdate ? date('d/m/Y', $course->startdate) : 'No Start Date';
    $enddate = $course->enddate ? date('d/m/Y', $course->enddate) : 'No End Date';
    
    // Calculate duration in weeks
    $duration = '';
    if ($course->startdate && $course->enddate) {
        $days = ($course->enddate - $course->startdate) / (60 * 60 * 24);
        $weeks = round($days / 7);
        if ($weeks > 0) {
            $duration = $weeks . ' Week' . ($weeks > 1 ? 's' : '');
        } else {
            $duration = '1 Week'; // Default to 1 week if less than 7 days
        }
    } else {
        $duration = '10 Weeks'; // Default duration
    }
    
    // Get sections count (excluding general section)
    $modinfo = get_fast_modinfo($course);
    $sections = $modinfo->get_section_info_all();
    $sectionscount = 0;
    $lessonscount = 0;
    
    foreach ($sections as $section) {
        if ($section->section > 0) { // Skip general section
            // Skip sections that are modules (subsections) - they should only be accessible within their parent sections
            if ($section->component !== 'mod_subsection') {
                $sectionscount++;
                if (isset($modinfo->sections[$section->section])) {
                    $lessonscount += count($modinfo->sections[$section->section]);
                }
            }
        }
    }
    
    // Get course category name
    $category = $DB->get_record('course_categories', ['id' => $course->category]);
    $categoryname = $category ? $category->name : 'General';
    
    return [
        'course' => $course,
        'courseimage' => $courseimage,
        'enrolledstudentscount' => $enrolledstudentscount,
        'teachers' => $teacherslist,
        'teacherscount' => $teacherscount,
        'startdate' => $startdate,
        'enddate' => $enddate,
        'duration' => $duration,
        'sectionscount' => $sectionscount,
        'lessonscount' => $lessonscount,
        'categoryname' => $categoryname,
        'courseurl' => new moodle_url('/course/view.php', ['id' => $course->id])
    ];
}

/**
 * Get course image URL
 *
 * @param object $course The course object
 * @return string Image URL
 */
function theme_remui_kids_get_course_image($course) {
    global $CFG;
    
    // Try to get course image from course files
    $fs = get_file_storage();
    $context = context_course::instance($course->id);
    
    $files = $fs->get_area_files($context->id, 'course', 'overviewfiles', 0, 'timemodified DESC', false);
    
    if (!empty($files)) {
        $file = reset($files);
        return moodle_url::make_pluginfile_url(
            $file->get_contextid(),
            $file->get_component(),
            $file->get_filearea(),
            null, // Changed from $file->get_itemid() to null to remove extra /0/ in URL
            $file->get_filepath(),
            $file->get_filename()
        )->out();
    }
    
    // Default course images based on category or course name
    $default_images = [
        'https://img.freepik.com/free-photo/copy-space-boy-with-books-showing-ok-sign_23-2148469950.jpg',
        'https://img.freepik.com/free-photo/young-people-row-with-thumbs-up_1098-2557.jpg',
        'https://img.freepik.com/free-photo/pleased-little-schoolboy-holding-book-points-side-isolated-purple-wall-with-copy-space_141793-75006.jpg',
        'https://img.freepik.com/free-photo/cheerful-student-writing-holding-books_1098-3439.jpg',
        'https://img.freepik.com/free-photo/copy-space-boy-with-backpack_23-2148601395.jpg',
        'https://img.freepik.com/free-photo/sideways-school-boy-copy-space_23-2148764003.jpg',
        'https://img.freepik.com/free-photo/little-girl-t-shirt-jumpsuit-pointing-up-looking-attentive_176474-39979.jpg',
    ];
    
    // Use course ID to consistently select the same image for the same course
    $index = $course->id % count($default_images);
    return $default_images[$index];
}

/**
 * Get user's cohort information for dashboard customization
 *
 * @param int $userid User ID
 * @return array Array containing cohort information
 */
function theme_remui_kids_get_user_cohort_info($userid) {
    global $DB;
    
    $usercohorts = $DB->get_records_sql(
        "SELECT c.name, c.id, c.description
         FROM {cohort} c 
         JOIN {cohort_members} cm ON c.id = cm.cohortid 
         WHERE cm.userid = ?",
        [$userid]
    );
    
    $cohortinfo = [
        'cohorts' => $usercohorts,
        'primary_cohort' => null,
        'grade_level' => 'default'
    ];
    
    if (!empty($usercohorts)) {
        // Get the first cohort as primary
        $cohortinfo['primary_cohort'] = reset($usercohorts);
        
        // Determine grade level based on cohort name
        $cohortname = strtolower($cohortinfo['primary_cohort']->name);
        
        if (preg_match('/grade\s*[1-3]/i', $cohortname)) {
            $cohortinfo['grade_level'] = 'elementary';
        } elseif (preg_match('/grade\s*[4-7]/i', $cohortname)) {
            $cohortinfo['grade_level'] = 'middle';
        } elseif (preg_match('/grade\s*[8-9]|grade\s*1[0-2]/i', $cohortname)) {
            $cohortinfo['grade_level'] = 'highschool';
        }
    }
    
    return $cohortinfo;
}

/**
 * Check if current page is dashboard
 *
 * @return bool True if current page is dashboard
 */
function theme_remui_kids_is_dashboard_page() {
    global $PAGE;
    
    // Check if we're on the dashboard page
    $pagetype = $PAGE->pagetype;
    $url = $PAGE->url;
    
    // Dashboard pages typically have these patterns
    $dashboardpatterns = [
        'my-index',
        'my-dashboard',
        'user-dashboard'
    ];
    
    // Check pagetype
    foreach ($dashboardpatterns as $pattern) {
        if (strpos($pagetype, $pattern) !== false) {
            return true;
        }
    }
    
    // Check URL path
    if ($url && strpos($url->get_path(), '/my/') !== false) {
        return true;
    }
    
    return false;
}

/**
 * Get Grade 1-3 specific dashboard statistics
 *
 * @param int $userid User ID
 * @return array Array containing Grade 1-3 dashboard statistics
 */
function theme_remui_kids_get_elementary_dashboard_stats($userid) {
    global $DB;
    
    try {
        // Get total courses enrolled
        $totalcourses = $DB->count_records_sql(
            "SELECT COUNT(DISTINCT c.id) 
             FROM {course} c 
             JOIN {enrol} e ON c.id = e.courseid 
             JOIN {user_enrolments} ue ON e.id = ue.enrolid 
             WHERE ue.userid = ? AND c.visible = 1 AND c.id > 1",
            [$userid]
        );
        
        // Get lessons completed (activities completed)
        $lessonscompleted = $DB->count_records_sql(
            "SELECT COUNT(DISTINCT cmc.coursemoduleid) 
             FROM {course_modules_completion} cmc 
             JOIN {course_modules} cm ON cmc.coursemoduleid = cm.id 
             JOIN {course} c ON cm.course = c.id 
             WHERE cmc.userid = ? AND cmc.completionstate > 0 AND c.visible = 1 AND c.id > 1",
            [$userid]
        );
        
        // Get activities completed (all completion records)
        $activitiescompleted = $DB->count_records_sql(
            "SELECT COUNT(*) 
             FROM {course_modules_completion} cmc 
             JOIN {course_modules} cm ON cmc.coursemoduleid = cm.id 
             JOIN {course} c ON cm.course = c.id 
             WHERE cmc.userid = ? AND cmc.completionstate > 0 AND c.visible = 1 AND c.id > 1",
            [$userid]
        );
        
        // Calculate overall progress percentage
        $totalactivities = $DB->count_records_sql(
            "SELECT COUNT(*) 
             FROM {course_modules} cm 
             JOIN {course} c ON cm.course = c.id 
             WHERE c.visible = 1 AND c.id > 1 AND cm.completion > 0",
            []
        );
        
        $overallprogress = 0;
        if ($totalactivities > 0) {
            $overallprogress = round(($activitiescompleted / $totalactivities) * 100);
        }
        
        return [
            'total_courses' => $totalcourses,
            'lessons_completed' => $lessonscompleted,
            'activities_completed' => $activitiescompleted,
            'overall_progress' => $overallprogress,
            'total_activities' => $totalactivities
        ];
    } catch (Exception $e) {
        // Return default values if there's an error
        return [
            'total_courses' => 0,
            'lessons_completed' => 0,
            'activities_completed' => 0,
            'overall_progress' => 0,
            'total_activities' => 0
        ];
    }
}

/**
 * Get assigned courses for Grade 1-3 students
 *
 * @param int $userid User ID
 * @return array Array containing assigned courses
 */
function theme_remui_kids_get_elementary_courses($userid) {
    global $DB;
    
    try {
        // First, let's check if user has any enrollments at all
        $enrollments = $DB->get_records_sql(
            "SELECT COUNT(*) as count FROM {user_enrolments} WHERE userid = ?",
            [$userid]
        );
        
        $courses = $DB->get_records_sql(
            "SELECT DISTINCT c.id, c.fullname, c.shortname, c.summary, c.startdate, c.enddate,
                    c.category, cat.name as categoryname
             FROM {course} c 
             JOIN {enrol} e ON c.id = e.courseid 
             JOIN {user_enrolments} ue ON e.id = ue.enrolid 
             LEFT JOIN {course_categories} cat ON c.category = cat.id
             WHERE ue.userid = ? AND c.visible = 1 AND c.id > 1
             ORDER BY c.timecreated DESC",
            [$userid]
        );
        
        $formattedcourses = [];
        foreach ($courses as $course) {
            // Get course image from files table
            $courseimage = '';
            $coursecontext = context_course::instance($course->id);
            
            // Get course overview files (course images)
            $fs = get_file_storage();
            $files = $fs->get_area_files($coursecontext->id, 'course', 'overviewfiles', 0, 'timemodified DESC', false);
            
            if (!empty($files)) {
                $file = reset($files); // Get the first (most recent) file
                $courseimage = moodle_url::make_pluginfile_url(
                    $coursecontext->id,
                    'course',
                    'overviewfiles',
                    null,
                    '/',
                    $file->get_filename()
                )->out();
            } else {
                // Fallback to default course images from Unsplash
                $defaultimages = [
         'https://img.freepik.com/free-photo/copy-space-boy-with-books-showing-ok-sign_23-2148469950.jpg',
        'https://img.freepik.com/free-photo/young-people-row-with-thumbs-up_1098-2557.jpg',
        'https://img.freepik.com/free-photo/pleased-little-schoolboy-holding-book-points-side-isolated-purple-wall-with-copy-space_141793-75006.jpg',
        'https://img.freepik.com/free-photo/cheerful-student-writing-holding-books_1098-3439.jpg',
        'https://img.freepik.com/free-photo/copy-space-boy-with-backpack_23-2148601395.jpg',
        'https://img.freepik.com/free-photo/sideways-school-boy-copy-space_23-2148764003.jpg',
        'https://img.freepik.com/free-photo/little-girl-t-shirt-jumpsuit-pointing-up-looking-attentive_176474-39979.jpg',
                ];
                $courseimage = $defaultimages[array_rand($defaultimages)];
            }
            
            // Calculate course progress
            $progress = 0;
            $totalactivities = 0;
            $completedactivities = 0;
            
            // Get course completion data using correct API
            try {
                $completion = new completion_info($course);
                if ($completion->is_enabled()) {
                    // Get all activities with completion tracking
                    $modules = $completion->get_activities();
                    $totalactivities = count($modules);
                    
                    // Count completed activities
                    foreach ($modules as $module) {
                        $data = $completion->get_data($module, true, $userid);
                        if ($data->completionstate == COMPLETION_COMPLETE || 
                            $data->completionstate == COMPLETION_COMPLETE_PASS) {
                            $completedactivities++;
                        }
                    }
                    
                    // Calculate progress percentage
                    if ($totalactivities > 0) {
                        $progress = ($completedactivities / $totalactivities) * 100;
                    }
                }
            } catch (Exception $e) {
                // If completion is not available, use default values
                $progress = 0;
                $totalactivities = 0;
                $completedactivities = 0;
            }
            
            // Calculate duration in weeks
            $duration = '';
            if ($course->startdate && $course->enddate) {
                $start = new DateTime();
                $start->setTimestamp($course->startdate);
                $end = new DateTime();
                $end->setTimestamp($course->enddate);
                $diff = $start->diff($end);
                $weeks = ceil($diff->days / 7);
                $duration = $weeks . ' week' . ($weeks > 1 ? 's' : '');
            } else {
                $duration = 'Ongoing';
            }
            
            // Use the same data from completion API for lessons count
            $totalsections = $totalactivities;
            $completedsections = $completedactivities;
            
            // Fallback: if completion is not enabled, get basic course module count
            if ($totalsections == 0) {
                $coursemodules = $DB->get_records('course_modules', [
                    'course' => $course->id, 
                    'visible' => 1,
                    'deletioninprogress' => 0
                ]);
                $totalsections = count($coursemodules);
                $completedsections = 0; // Can't determine completion without completion tracking
            }
            
            // Format dates
            $startdate = '';
            $enddate = '';
            if ($course->startdate) {
                $startdate = date('M d, Y', $course->startdate);
            }
            if ($course->enddate) {
                $enddate = date('M d, Y', $course->enddate);
            }
            
            $formattedcourses[] = [
                'id' => $course->id,
                'fullname' => $course->fullname,
                'shortname' => $course->shortname,
                'summary' => $course->summary,
                'startdate' => $startdate,
                'enddate' => $enddate,
                'courseimage' => $courseimage,
                'categoryname' => $course->categoryname ?: 'General',
                'courseurl' => (new moodle_url('/course/view.php', ['id' => $course->id]))->out(),
                'progress' => $progress,
                'progress_percentage' => round($progress),
                'duration' => $duration,
                'total_sections' => $totalsections,
                'completed_sections' => $completedsections,
                'remaining_sections' => $totalsections - $completedsections
            ];
        }
        
        return $formattedcourses;
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get active sections (sections with activities) for elementary students
 * @param int $userid User ID
 * @return array Array of sections with activities data (for debugging, shows all sections with activities)
 */
function theme_remui_kids_get_elementary_active_sections($userid) {
    global $DB;
    
    try {
        // Get user's enrolled courses
        $courses = $DB->get_records_sql(
            "SELECT DISTINCT c.id, c.fullname, c.shortname
             FROM {course} c 
             JOIN {enrol} e ON c.id = e.courseid 
             JOIN {user_enrolments} ue ON e.id = ue.enrolid 
             WHERE ue.userid = ? AND c.visible = 1 AND c.id > 1
             ORDER BY c.fullname ASC",
            [$userid]
        );
        
        
        $activesections = [];
        
        foreach ($courses as $course) {
            // Get course sections
            $modinfo = get_fast_modinfo($course->id);
            $sections = $modinfo->get_section_info_all();
            
            foreach ($sections as $section) {
                // Skip section 0 (general section) and hidden sections
                if ($section->section == 0 || !$section->visible) {
                    continue;
                }
                
                // Get section name and limit to 7 words
                $sectionname = $section->name ?: "Section " . $section->section;
                $words = explode(' ', $sectionname);
                if (count($words) > 7) {
                    $sectionname = implode(' ', array_slice($words, 0, 7)) . '...';
                }
                
                // Get activities in this section
                $modules = [];
                if (isset($modinfo->sections[$section->section])) {
                    foreach ($modinfo->sections[$section->section] as $cmid) {
                        $modules[] = $modinfo->cms[$cmid];
                    }
                }
                
                if (empty($modules)) {
                    continue; // Skip sections with no activities
                }
                
                // Calculate progress for this section
                $totalactivities = count($modules);
                $completedactivities = 0;
                
                // Check completion for each activity
                $completion = new completion_info($course);
                if ($completion->is_enabled()) {
                    foreach ($modules as $module) {
                        if ($module->completion != COMPLETION_TRACKING_NONE) {
                            $data = $completion->get_data($module, true, $userid);
                            if ($data->completionstate == COMPLETION_COMPLETE || 
                                $data->completionstate == COMPLETION_COMPLETE_PASS) {
                                $completedactivities++;
                            }
                        }
                    }
                }
                
                
                // Include sections with any activities (for debugging, we'll show all sections with activities)
                if ($totalactivities > 0) {
                    $progress = ($completedactivities / $totalactivities) * 100;
                    
                    // Get section summary (first 100 characters)
                    $summary = '';
                    if ($section->summary) {
                        $summary = strip_tags($section->summary);
                        $summary = strlen($summary) > 100 ? substr($summary, 0, 100) . '...' : $summary;
                    }
                    
                    $activesections[] = [
                        'id' => $section->id,
                        'section' => $section->section,
                        'name' => $sectionname,
                        'summary' => $summary,
                        'courseid' => $course->id,
                        'coursename' => $course->fullname,
                        'courseurl' => new moodle_url('/course/view.php', ['id' => $course->id, 'section' => $section->section]),
                        'total_activities' => $totalactivities,
                        'completed_activities' => $completedactivities,
                        'progress' => $progress,
                        'progress_percentage' => round($progress)
                    ];
                }
            }
        }
        
        // Sort by progress percentage (highest first)
        usort($activesections, function($a, $b) {
            return $b['progress'] <=> $a['progress'];
        });
        
        // Limit to top 3 sections
        return array_slice($activesections, 0, 3);
        
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get real active lessons/activities for elementary students from Moodle
 * @param int $userid User ID
 * @return array Array of real activities with completion data
 */
function theme_remui_kids_get_elementary_active_lessons($userid) {
    global $DB;
    
    try {
        // Get user's enrolled courses
        $courses = $DB->get_records_sql(
            "SELECT DISTINCT c.id, c.fullname, c.shortname
             FROM {course} c 
             JOIN {enrol} e ON c.id = e.courseid 
             JOIN {user_enrolments} ue ON e.id = ue.enrolid 
             WHERE ue.userid = ? AND c.visible = 1 AND c.id > 1
             ORDER BY c.fullname ASC",
            [$userid]
        );
        
        $activities = [];
        
        foreach ($courses as $course) {
            $modinfo = get_fast_modinfo($course->id);
            $completion = new completion_info($course);
            
            // Get all course modules (activities) from all sections
            foreach ($modinfo->sections as $sectionnum => $sectionmodules) {
                if ($sectionnum == 0) {
                    continue; // Skip general section
                }
                
                foreach ($sectionmodules as $cmid) {
                    $module = $modinfo->cms[$cmid];
                    
                    if (!$module->uservisible) {
                        continue;
                    }
                    
                    $status = 'future'; // Default status
                    $completiondata = null;
                    
                    // Get completion data if available
                    if ($completion->is_enabled()) {
                        $completiondata = $completion->get_data($module, true, $userid);
                        
                        // Determine activity status
                        if ($completiondata->completionstate == COMPLETION_COMPLETE || 
                            $completiondata->completionstate == COMPLETION_COMPLETE_PASS) {
                            $status = 'completed';
                        } elseif ($completiondata->timestarted > 0) {
                            $status = 'active';
                        }
                    }
                    
                    // Get activity icon and color based on status
                    $activitydata = theme_remui_kids_get_activity_playful_data($module->modname, $status);
                    
                    // Get activity description (first 100 characters)
                    $description = '';
                    if ($module->content) {
                        $description = strip_tags($module->content);
                        $description = strlen($description) > 100 ? substr($description, 0, 100) . '...' : $description;
                    }
                    
                    // Get section name for context
                    $section = $modinfo->get_section_info($sectionnum);
                    $sectionname = $section->name ?: "Section " . $sectionnum;
                    
                    $activities[] = [
                        'id' => $module->id,
                        'name' => $module->name,
                        'modname' => $module->modname,
                        'status' => $status,
                        'courseid' => $course->id,
                        'coursename' => $course->fullname,
                        'sectionname' => $sectionname,
                        'sectionnum' => $sectionnum,
                        'url' => $module->url,
                        'icon' => $activitydata['icon'],
                        'color' => $activitydata['color'],
                        'description' => $description,
                        'completion_state' => $completiondata ? $completiondata->completionstate : null,
                        'timestarted' => $completiondata ? $completiondata->timestarted : 0,
                        'timecompleted' => $completiondata ? $completiondata->timecompleted : 0,
                        'available' => $module->available,
                        'availablefrom' => $module->availablefrom,
                        'availableuntil' => $module->availableuntil
                    ];
                }
            }
        }
        
        // Sort activities: completed first, then active, then future
        usort($activities, function($a, $b) {
            $order = ['completed' => 1, 'active' => 2, 'future' => 3];
            $statusOrder = $order[$a['status']] <=> $order[$b['status']];
            
            // If same status, sort by course name, then by section number
            if ($statusOrder == 0) {
                $courseOrder = strcmp($a['coursename'], $b['coursename']);
                if ($courseOrder == 0) {
                    return $a['sectionnum'] <=> $b['sectionnum'];
                }
                return $courseOrder;
            }
            
            return $statusOrder;
        });
        
        // Limit to 8 activities for the display
        return array_slice($activities, 0, 8);
        
    } catch (Exception $e) {
        return [];
    }
}


/**
 * Get playful activity data (icon and color) based on module type and status
 * @param string $modname Module name
 * @param string $status Activity status (completed, active, future)
 * @return array Array with icon and color
 */
function theme_remui_kids_get_activity_playful_data($modname, $status) {
    $data = [
        'icon' => 'fa-star',
        'color' => '#4CAF50' // Default green
    ];
    
    // Define colors based on status
    $colors = [
        'completed' => '#4CAF50', // Green
        'active' => '#2196F3',    // Blue
        'future' => '#9E9E9E'     // Gray
    ];
    
    // Define icons based on module type
    $icons = [
        'quiz' => 'fa-star',
        'assign' => 'fa-play',
        'page' => 'fa-book',
        'lesson' => 'fa-graduation-cap',
        'forum' => 'fa-comments',
        'scorm' => 'fa-laptop',
        'book' => 'fa-book-open',
        'url' => 'fa-external-link'
    ];
    
    $data['color'] = $colors[$status] ?? $colors['future'];
    $data['icon'] = $icons[$modname] ?? 'fa-star';
    
    return $data;
}

/**
 * Get admin dashboard statistics
 *
 * @return array Array containing admin dashboard statistics
 */
function theme_remui_kids_get_admin_dashboard_stats() {
    global $DB;
    
    try {
        // Get total schools - improved logic to count actual school-like categories
        // Exclude system categories and count only meaningful school categories
        $totalschools = $DB->count_records_sql(
            "SELECT COUNT(*) 
             FROM {company} ",
             
            []
        );
        
        // If no meaningful categories found, fall back to all visible categories
        if ($totalschools == 0) {
            $totalschools = $DB->count_records_sql(
                "SELECT COUNT(*) FROM {course_categories} WHERE visible = 1 AND id > 1",
                []
            );
        }
        
        // Get total courses (excluding site course)
        $totalcourses = $DB->count_records_sql(
            "SELECT COUNT(*) FROM {course} WHERE visible = 1 AND id > 1",
            []
        );
        
        // Get total students - using trainee role for consistency with enrollment system
        $traineerole = $DB->get_record('role', ['shortname' => 'trainee']);
        $totalstudents = 0;
        if ($traineerole) {

            $totalstudents = $DB->count_records_sql(
                "SELECT COUNT(DISTINCT u.id) 
                 FROM {user} u 
                 JOIN {role_assignments} ra ON u.id = ra.userid 
                 JOIN {role} r ON ra.roleid = r.id 
                 WHERE r.shortname = 'trainee'||'student' AND u.deleted = 0 AND u.suspended = 0"
            );
        }
        
        // Get average course rating (mock data for now)
        $avgcourserating = 0; // Will be implemented when rating system is available
        
        return [
            'total_schools' => $totalschools,
            'total_courses' => $totalcourses,
            'total_students' => $totalstudents,
            'avg_course_rating' => $avgcourserating,
            'last_updated' => time() // Add timestamp for real-time tracking
        ];
    } catch (Exception $e) {
        return [
            'total_schools' => 0,
            'total_courses' => 0,
            'total_students' => 0,
            'avg_course_rating' => 0,
            'last_updated' => time()
        ];
    }
}

/**
 * Get admin user statistics
 *
 * @return array Array containing user statistics
 */
function theme_remui_kids_get_admin_user_stats() {
    global $DB;
    
    try {
        // Get total users
        $totalusers = $DB->count_records('user', ['deleted' => 0]);
        
        // Get teachers count
        $teacherrole = $DB->get_record('role', ['shortname' => 'teachers']);
        $teachers = 0;
        if ($teacherrole) {
            $teachers = $DB->count_records_sql(
                "SELECT COUNT(DISTINCT u.id) 
                 FROM {user} u 
                 JOIN {role_assignments} ra ON u.id = ra.userid 
                 JOIN {context} ctx ON ra.contextid = ctx.id 
                 WHERE ctx.contextlevel = ? AND ra.roleid = ? AND u.deleted = 0",
                [CONTEXT_SYSTEM, $teacherrole->id]
            );
        }
        
        // Get students count - using trainee role for consistency with enrollment system
        $traineerole = $DB->get_record('role', ['shortname' => 'trainee'||'student']);
        $students = 0;
        if ($traineerole) {
            $students = $DB->count_records_sql(
                "SELECT COUNT(DISTINCT u.id) 
                 FROM {user} u 
                 JOIN {role_assignments} ra ON u.id = ra.userid 
                 JOIN {role} r ON ra.roleid = r.id 
                 WHERE r.shortname = 'trainee' || 'student' AND u.deleted = 0"

            );
        }
        
        // Get admins count
        $adminrole = $DB->get_record('role', ['shortname' => 'manager']);
        $admins = 0;
        if ($adminrole) {
            $admins = $DB->count_records_sql(
                "SELECT COUNT(DISTINCT u.id) 
                 FROM {user} u 
                 JOIN {role_assignments} ra ON u.id = ra.userid 
                 JOIN {context} ctx ON ra.contextid = ctx.id 
                 WHERE ctx.contextlevel = ? AND ra.roleid = ? AND u.deleted = 0",
                [CONTEXT_SYSTEM, $adminrole->id]
            );
        }
        
        // Get active users (logged in within last 30 days)
        $activeusers = $DB->count_records_sql(
            "SELECT COUNT(DISTINCT u.id) FROM {user} u 
             JOIN {user_lastaccess} ul ON u.id = ul.userid 
             WHERE u.deleted = 0 AND ul.timeaccess > ?",
            [time() - (30 * 24 * 60 * 60)] // Last 30 days

        );
        
        // Get new users this month
        $newusers = $DB->count_records_sql(
            "SELECT COUNT(*) 
             FROM {user} 
             WHERE timecreated > ? AND deleted = 0",
            [strtotime('first day of this month')]
        );
        
        return [
            'total_users' => $totalusers,
            'teachers' => $teachers,
            'students' => $students,
            'admins' => $admins,
            'active_users' => $activeusers,
            'new_this_month' => $newusers
        ];
    } catch (Exception $e) {
        return [
            'total_users' => 0,
            'teachers' => 0,
            'students' => 0,
            'admins' => 0,
            'active_users' => 0,
            'new_this_month' => 0
        ];
    }
}

/**
 * Get admin course statistics
 *
 * @return array Array containing course statistics
 */
function theme_remui_kids_get_admin_course_stats() {
    global $DB;
    
    try {
        // Get total courses
        //$totalcourses = $DB->count_records('course', ['visible' => 1]);
        $totalcourses = $DB->count_records_sql(
            "SELECT COUNT(*) FROM {course} WHERE visible = 1 AND id > 1",
            []
        );
        
        // Get completion rate (mock data for now)
        $completionrate = 0; // Will be implemented when completion tracking is analyzed
        
        // Get average rating (mock data for now)
        $avgrating = 0; // Will be implemented when rating system is available
        
        // Get categories count
        $categories = $DB->count_records('course_categories', ['visible' => 1]);
        
        return [
            'total_courses' => $totalcourses,
            'completion_rate' => $completionrate,
            'avg_rating' => $avgrating,
            'categories' => $categories
        ];
    } catch (Exception $e) {
        return [
            'total_courses' => 0,
            'completion_rate' => 0,
            'avg_rating' => 0,
            'categories' => 0
        ];
    }
}

/**
 * Get admin recent activity
 *
 * @return array Array containing recent activity data
 */
function theme_remui_kids_get_admin_recent_activity() {
    global $DB;
    
    try {
        $activities = [];
        
        // Get recent course creation
        $recentcourses = $DB->get_records_sql(
            "SELECT c.id, c.fullname, c.timecreated, u.firstname, u.lastname
             FROM {course} c
             LEFT JOIN {user} u ON c.userid = u.id
             WHERE c.visible = 1
             ORDER BY c.timecreated DESC
             LIMIT 5",
            []
        );
        
        foreach ($recentcourses as $course) {
            $timeago = time() - $course->timecreated;
            $timeago = $timeago < 3600 ? round($timeago / 60) . 'm ago' : 
                      ($timeago < 86400 ? round($timeago / 3600) . 'h ago' : 
                      round($timeago / 86400) . 'd ago');
            
            $activities[] = [
                'type' => 'course_created',
                'title' => '"' . $course->fullname . '" course published',
                'time' => $timeago,
                'author' => $course->firstname . ' ' . $course->lastname,
                'icon' => 'fa-book',
                'color' => 'red'
            ];
        }
        
        // Get recent user registrations
        $recentusers = $DB->get_records_sql(
            "SELECT u.id, u.firstname, u.lastname, u.timecreated
             FROM {user} u
             WHERE u.deleted = 0
             ORDER BY u.timecreated DESC
             LIMIT 3",
            []
        );
        
        foreach ($recentusers as $user) {
            $timeago = time() - $user->timecreated;
            $timeago = $timeago < 3600 ? round($timeago / 60) . 'm ago' : 
                      ($timeago < 86400 ? round($timeago / 3600) . 'h ago' : 
                      round($timeago / 86400) . 'd ago');
            
            $activities[] = [
                'type' => 'user_registered',
                'title' => 'New user registered: ' . $user->firstname . ' ' . $user->lastname,
                'time' => $timeago,
                'author' => '',
                'icon' => 'fa-user',
                'color' => 'blue'
            ];
        }
        
        // Sort by time and limit to 5 most recent
        usort($activities, function($a, $b) {
            return strcmp($a['time'], $b['time']);
        });
        
        return array_slice($activities, 0, 5);
        
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get course sections for modal preview
 *
 * @param int $courseid Course ID
 * @return array Array containing course sections data
 */

function theme_remui_kids_get_course_sections_for_modal($courseid) {
    global $DB, $USER;
    
    try {
        // Get course object
        $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
        
        // Get course modules info
        $modinfo = get_fast_modinfo($course);
        $sections = $modinfo->get_section_info_all();
        
        // Get completion info
        $completion = new completion_info($course);
        
        $sectionsdata = [];
        
        foreach ($sections as $section) {
            // Skip section 0 (general section)
            if ($section->section == 0) {
                continue;
            }
            
            // Get section name
            $sectionname = $section->name ?: "Section " . $section->section;
            
            // Get activities in this section
            $activities = [];
            $totalactivities = 0;
            $completedactivities = 0;
            
            if (isset($modinfo->sections[$section->section])) {
                foreach ($modinfo->sections[$section->section] as $cmid) {
                    $module = $modinfo->cms[$cmid];
                    
                    if (!$module->uservisible) {
                        continue;
                    }
                    
                    $totalactivities++;
                    
                    // Check completion status
                    $iscompleted = false;
                    if ($completion->is_enabled() && $module->completion != COMPLETION_TRACKING_NONE) {
                        $completiondata = $completion->get_data($module, true, $USER->id);
                        if ($completiondata->completionstate == COMPLETION_COMPLETE || 
                            $completiondata->completionstate == COMPLETION_COMPLETE_PASS) {
                            $iscompleted = true;
                            $completedactivities++;
                        }
                    }
                    
                    $activities[] = [
                        'id' => $module->id,
                        'name' => $module->name,
                        'modname' => $module->modname,
                        'url' => (new moodle_url('/mod/' . $module->modname . '/view.php', ['id' => $module->id]))->out(),
                        'iscompleted' => $iscompleted,
                        'icon' => '/theme/image.php/remui_kids/' . $module->modname . '/1/icon'
                    ];
                }
            }
            
            // Calculate progress percentage
            $progress = 0;
            if ($totalactivities > 0) {
                $progress = round(($completedactivities / $totalactivities) * 100);
            }
            
            // Get section summary (first 150 characters)
            $summary = '';
            if ($section->summary) {
                $summary = strip_tags($section->summary);
                $summary = strlen($summary) > 150 ? substr($summary, 0, 150) . '...' : $summary;
            }
            
            $sectionsdata[] = [
                'id' => $section->id,
                'section' => $section->section,
                'name' => $sectionname,
                'summary' => $summary,
                'total_activities' => $totalactivities,
                'completed_activities' => $completedactivities,
                'progress' => $progress,
                'activities' => $activities,
                'url' => (new moodle_url('/course/view.php', ['id' => $courseid, 'section' => $section->section]))->out()
            ];
        }
        
        return [
            'course' => [
                'id' => $course->id,
                'fullname' => $course->fullname,
                'shortname' => $course->shortname
            ],
            'sections' => $sectionsdata
        ];
        
    } catch (Exception $e) {
        return [
            'course' => ['id' => $courseid, 'fullname' => 'Unknown Course', 'shortname' => ''],
            'sections' => []
        ];
    }
}

/**
 * Get calendar week data with events for the next 7 days using Moodle's Calendar API
 *
 * @param int $userid User ID
 * @return array Calendar week data with events
 */
function theme_remui_kids_get_calendar_week_data($userid) {
    global $DB, $CFG, $USER;
    
    require_once($CFG->dirroot . '/calendar/lib.php');
    
    $calendarweek = [];
    $today = time();
    $starttime = mktime(0, 0, 0, date('n', $today), date('j', $today), date('Y', $today));
    $endtime = $starttime + (7 * 86400); // Next 7 days
    
    // Get user's enrolled courses
    $courses = enrol_get_my_courses(['id', 'fullname'], 'fullname ASC');
    $courseids = array_keys($courses);
    
    // Get calendar events using Moodle's built-in function
    $events = calendar_get_events(
        $starttime,
        $endtime,
        $userid, // User events
        false,   // No group events
        $courseids, // Course events
        true,    // With duration
        true     // Ignore hidden
    );
    
    // Get next 7 days
    for ($i = 0; $i < 7; $i++) {
        $date = $starttime + ($i * 86400);
        $dayname = date('D', $date);
        $daynumber = date('j', $date);
        $datekey = date('Y-m-d', $date);
        
        // Check if there are events on this date
        $dayevents = [];
        foreach ($events as $event) {
            $eventdate = date('Y-m-d', $event->timestart);
            if ($eventdate === $datekey) {
                $dayevents[] = $event;
            }
        }
        
        $calendarweek[] = [
            'date' => $datekey,
            'day_name' => $dayname,
            'day_number' => $daynumber,
            'has_events' => !empty($dayevents),
            'events' => $dayevents
        ];
    }
    
    return $calendarweek;
}

/**
 * Get upcoming events for the next 7 days using Moodle's Calendar API
 *
 * @param int $userid User ID
 * @return array Upcoming events data
 */
function theme_remui_kids_get_upcoming_events($userid) {
    global $DB, $CFG, $USER;
    
    require_once($CFG->dirroot . '/calendar/lib.php');
    
    $upcomingevents = [];
    $today = time();
    $starttime = mktime(0, 0, 0, date('n', $today), date('j', $today), date('Y', $today));
    $endtime = $starttime + (7 * 86400); // Next 7 days
    
    // Get user's enrolled courses
    $courses = enrol_get_my_courses(['id', 'fullname'], 'fullname ASC');
    $courseids = array_keys($courses);
    
    // Get calendar events using Moodle's built-in function
    $events = calendar_get_events(
        $starttime,
        $endtime,
        $userid, // User events
        false,   // No group events
        $courseids, // Course events
        true,    // With duration
        true     // Ignore hidden
    );
    
    // Process events and format them
    foreach ($events as $event) {
        // Skip events that are not assignments, quizzes, or lessons
        if (!in_array($event->modulename, ['assign', 'quiz', 'lesson'])) {
            continue;
        }
        
        // Get course name
        $coursename = '';
        if ($event->courseid && isset($courses[$event->courseid])) {
            $coursename = $courses[$event->courseid]->fullname;
        }
        
        // Determine event type and icon
        $eventtype = $event->modulename;
        $eventicon = 'fa-star'; // Default icon
        
        switch ($event->modulename) {
            case 'assign':
                $eventicon = 'fa-file-text';
                break;
            case 'quiz':
                $eventicon = 'fa-question-circle';
                break;
            case 'lesson':
                $eventicon = 'fa-graduation-cap';
                break;
        }
        
        // Create event URL
        $eventurl = '';
        if ($event->modulename && $event->instance) {
            $eventurl = (new moodle_url('/mod/' . $event->modulename . '/view.php', ['id' => $event->instance]))->out();
        }
        
        $upcomingevents[] = [
            'event_title' => $event->name,
            'event_time' => date('g:i A', $event->timestart),
            'event_day' => date('j', $event->timestart),
            'event_month' => date('M', $event->timestart),
            'course_name' => $coursename,
            'event_type' => $eventtype,
            'event_icon' => $eventicon,
            'event_date' => $event->timestart,
            'event_url' => $eventurl,
            'event_description' => $event->description ?? ''
        ];
    }
    
    // Sort all events by date
    usort($upcomingevents, function($a, $b) {
        return $a['event_date'] - $b['event_date'];
    });
    
    // Return only the first 5 events
    return array_slice($upcomingevents, 0, 5);
}


/**
 * Get learning progress statistics
 *
 * @param int $userid User ID
 * @return array Learning progress data
 */
function theme_remui_kids_get_learning_progress_stats($userid) {
    global $DB;
    
    $today = time();
    $weekstart = $today - (date('w', $today) * 86400);
    $weekend = $weekstart + (7 * 86400);
    
    // Get lessons completed this week
    $lessonscompleted = $DB->get_field_sql(
        "SELECT COUNT(*)
         FROM {course_modules_completion} cmc
         JOIN {course_modules} cm ON cmc.coursemoduleid = cm.id
         WHERE cmc.userid = ? 
         AND cmc.completionstate IN (1, 2)
         AND cmc.timemodified >= ?
         AND cmc.timemodified <= ?",
        [$userid, $weekstart, $weekend]
    );
    
    // Get study time (estimated based on completed activities)
    $studytime = $lessonscompleted * 30; // Assume 30 minutes per lesson
    $studytimehours = round($studytime / 60, 1);
    
    // Get best quiz score
    $bestscoresql = "
        SELECT MAX(gg.finalgrade / gg.rawgrademax * 100) as bestscore
        FROM {grade_grades} gg
        JOIN {grade_items} gi ON gg.itemid = gi.id
        JOIN {course_modules} cm ON gi.iteminstance = cm.instance
        JOIN {modules} m ON cm.module = m.id
        WHERE gg.userid = ? 
        AND m.name = 'quiz'
        AND gg.finalgrade IS NOT NULL
        AND gg.rawgrademax > 0";
    
    $bestscore = $DB->get_field_sql($bestscoresql, [$userid]) ?: 0;
    
    // Calculate goal progress (based on weekly target)
    $weeklytarget = 5; // Target 5 lessons per week
    $goalprogress = min(100, round(($lessonscompleted / $weeklytarget) * 100));
    
    return [
        'lessons_this_week' => $lessonscompleted,
        'study_time' => $studytimehours . 'h',
        'best_score' => round($bestscore),
        'goal_progress' => $goalprogress
    ];
}

/**
 * Get achievements data
 *
 * @param int $userid User ID
 * @return array Achievements data
 */
function theme_remui_kids_get_achievements_data($userid) {
    global $DB;
    
    // Get study streak (consecutive days with activity)
    $streak = $DB->get_field_sql(
        "SELECT COUNT(DISTINCT DATE(FROM_UNIXTIME(timemodified))) as streak
         FROM {course_modules_completion}
         WHERE userid = ? 
         AND completionstate IN (1, 2)
         AND timemodified >= ?",
        [$userid, time() - (30 * 86400)] // Last 30 days
    ) ?: 0;
    
    // Get total points (based on completed activities)
    $points = $DB->get_field_sql(
        "SELECT COUNT(*) * 10
         FROM {course_modules_completion}
         WHERE userid = ? 
         AND completionstate IN (1, 2)",
        [$userid]
    ) ?: 0;
    
    // Get coins (bonus for high scores)
    $coins = $DB->get_field_sql(
        "SELECT COUNT(*) * 5
         FROM {grade_grades} gg
         JOIN {grade_items} gi ON gg.itemid = gi.id
         WHERE gg.userid = ? 
         AND gg.finalgrade / gg.rawgrademax >= 0.8
         AND gg.rawgrademax > 0",
        [$userid]
    ) ?: 0;
    
    return [
        'streaks' => $streak,
        'best_streak' => $streak,
        'goal_streak' => 7, // Goal of 7 day streak
        'points' => $points,
        'coins' => $coins
    ];
}

/**
 * Get high school dashboard statistics (Grades 8-12)
 *
 * @param int $userid User ID
 * @return array Dashboard statistics
 */
function theme_remui_kids_get_highschool_dashboard_stats($userid) {
    global $DB;
    
    // Get enrolled courses count
    $courses = $DB->get_field_sql(
        "SELECT COUNT(DISTINCT c.id)
         FROM {course} c
         JOIN {enrol} e ON c.id = e.courseid
         JOIN {user_enrolments} ue ON e.id = ue.enrolid
         WHERE ue.userid = ? 
         AND c.visible = 1
         AND c.id > 1",
        [$userid]
    ) ?: 0;
    
    // Get completed lessons count
    $lessons = $DB->get_field_sql(
        "SELECT COUNT(*)
         FROM {course_modules_completion} cmc
         JOIN {course_modules} cm ON cmc.coursemoduleid = cm.id
         WHERE cmc.userid = ? 
         AND cmc.completionstate IN (1, 2)
         AND cm.module IN (SELECT id FROM {modules} WHERE name IN ('lesson', 'page', 'book'))",
        [$userid]
    ) ?: 0;
    
    // Get completed activities count
    $activities = $DB->get_field_sql(
        "SELECT COUNT(*)
         FROM {course_modules_completion} cmc
         WHERE cmc.userid = ? 
         AND cmc.completionstate IN (1, 2)",
        [$userid]
    ) ?: 0;
    
    // Calculate overall progress percentage
    $total_activities = $DB->get_field_sql(
        "SELECT COUNT(*)
         FROM {course_modules} cm
         JOIN {enrol} e ON cm.course = e.courseid
         JOIN {user_enrolments} ue ON e.id = ue.enrolid
         WHERE ue.userid = ? 
         AND cm.completion > 0",
        [$userid]
    ) ?: 1;
    
    $progress = $total_activities > 0 ? round(($activities / $total_activities) * 100) : 0;
    
    return [
        'courses' => $courses,
        'lessons' => $lessons,
        'activities' => $activities,
        'progress' => $progress
    ];
}

/**
 * Get high school courses (Grades 8-12)
 *
 * @param int $userid User ID
 * @return array Course data
 */
function theme_remui_kids_get_highschool_courses($userid) {
    global $DB;
    
    $courses = $DB->get_records_sql(
        "SELECT c.id, c.fullname, c.shortname, c.summary, c.startdate, c.enddate
         FROM {course} c
         JOIN {enrol} e ON c.id = e.courseid
         JOIN {user_enrolments} ue ON e.id = ue.enrolid
         WHERE ue.userid = ? 
         AND c.visible = 1
         AND c.id > 1
         ORDER BY c.startdate DESC, c.fullname ASC",
        [$userid]
    );
    
    $coursedata = [];
    foreach ($courses as $course) {
        // Get course progress
        $total_activities = $DB->get_field_sql(
            "SELECT COUNT(*)
             FROM {course_modules} cm
             WHERE cm.course = ? 
             AND cm.completion > 0",
            [$course->id]
        ) ?: 1;
        
        $completed_activities = $DB->get_field_sql(
            "SELECT COUNT(*)
             FROM {course_modules_completion} cmc
             JOIN {course_modules} cm ON cmc.coursemoduleid = cm.id
             WHERE cmc.userid = ? 
             AND cm.course = ?
             AND cmc.completionstate IN (1, 2)",
            [$userid, $course->id]
        ) ?: 0;
        
        $progress = $total_activities > 0 ? round(($completed_activities / $total_activities) * 100) : 0;
        
        $coursedata[] = [
            'id' => $course->id,
            'fullname' => $course->fullname,
            'shortname' => $course->shortname,
            'summary' => $course->summary,
            'startdate' => $course->startdate,
            'enddate' => $course->enddate,
            'progress' => $progress,
            'courseurl' => (new moodle_url('/course/view.php', ['id' => $course->id]))->out()
        ];
    }
    
    return $coursedata;
}

/**
 * Get high school active sections (Grades 8-12)
 *
 * @param int $userid User ID
 * @return array Active sections data
 */
function theme_remui_kids_get_highschool_active_sections($userid) {
    global $DB;
    
    $sections = $DB->get_records_sql(
        "SELECT cs.id, cs.section, cs.name, cs.summary, c.id as courseid, c.fullname as coursename
         FROM {course_sections} cs
         JOIN {course} c ON cs.course = c.id
         JOIN {enrol} e ON c.id = e.courseid
         JOIN {user_enrolments} ue ON e.id = ue.enrolid
         WHERE ue.userid = ? 
         AND cs.section > 0
         AND c.visible = 1
         AND c.id > 1
         ORDER BY c.startdate DESC, cs.section ASC
         LIMIT 10",
        [$userid]
    );
    
    $sectionsdata = [];
    foreach ($sections as $section) {
        $sectionsdata[] = [
            'id' => $section->id,
            'section' => $section->section,
            'name' => $section->name ?: "Section {$section->section}",
            'summary' => $section->summary,
            'courseid' => $section->courseid,
            'coursename' => $section->coursename,
            'url' => (new moodle_url('/course/view.php', ['id' => $section->courseid, 'section' => $section->section]))->out()
        ];
    }
    
    return $sectionsdata;
}

/**
 * Get high school active lessons (Grades 8-12)
 *
 * @param int $userid User ID
 * @return array Active lessons data
 */
function theme_remui_kids_get_highschool_active_lessons($userid) {
    global $DB;
    
    $lessons = $DB->get_records_sql(
        "SELECT cm.id, cm.instance, m.name as modulename, c.id as courseid, c.fullname as coursename
         FROM {course_modules} cm
         JOIN {modules} m ON cm.module = m.id
         JOIN {course} c ON cm.course = c.id
         JOIN {enrol} e ON c.id = e.courseid
         JOIN {user_enrolments} ue ON e.id = ue.enrolid
         WHERE ue.userid = ? 
         AND m.name IN ('lesson', 'page', 'book', 'assign', 'quiz')
         AND c.visible = 1
         AND c.id > 1
         ORDER BY c.startdate DESC, cm.id ASC
         LIMIT 10",
        [$userid]
    );
    
    $lessonsdata = [];
    foreach ($lessons as $lesson) {
        $lessonsdata[] = [
            'id' => $lesson->id,
            'instance' => $lesson->instance,
            'modulename' => $lesson->modulename,
            'courseid' => $lesson->courseid,
            'coursename' => $lesson->coursename,
            'url' => (new moodle_url('/mod/' . $lesson->modulename . '/view.php', ['id' => $lesson->id]))->out()
        ];
    }
    
    return $lessonsdata;
}

/**
 * Get admin sidebar data with URLs and active states
 *
 * @param string $current_page Current page identifier
 * @return array Array containing sidebar navigation data
 */
function theme_remui_kids_get_admin_sidebar_data($current_page = 'dashboard') {
    global $CFG;
    
    // Base URLs
    $base_url = $CFG->wwwroot;
    
    // Define all sidebar URLs
    $urls = [
        'dashboard_url' => $base_url . '/my/'
    ];
    
    // Define active states based on current page
    $active_states = [
        'dashboard_active' => ($current_page === 'dashboard')
    ];
    
    // Merge URLs and active states
    return array_merge($urls, $active_states);
}

/**
 * Check if current page is an admin page
 *
 * @return bool True if current page is an admin page
 */
function theme_remui_kids_is_admin_page() {
    global $PAGE, $CFG;
    
    // Get current URL path
    $current_url = $PAGE->url->get_path();
    $current_pagetype = $PAGE->pagetype;
    
    // Admin page patterns
    $admin_patterns = [
        '/admin/',
        '/local/edwiserreports/',
        '/course/index.php',
        '/user/index.php',
        '/admin/user.php',
        '/admin/search.php',
        '/admin/settings.php',
        '/admin/tool/',
        '/admin/pluginfile.php',
        '/admin/upgradesettings.php',
        '/admin/plugins.php',
        '/admin/roles/',
        '/admin/capabilities/',
        '/admin/cohort/',
        '/admin/competency/',
        '/admin/analytics/',
        '/admin/backup/',
        '/admin/restore/',
        '/admin/webservice/',
        '/admin/registration/',
        '/admin/notification/',
        '/admin/upgrade.php',
        '/admin/index.php'
    ];
    
    // Check if current URL matches admin patterns
    foreach ($admin_patterns as $pattern) {
        if (strpos($current_url, $pattern) !== false) {
            return true;
        }
    }
    
    // Check pagetype for admin pages
    $admin_pagetypes = [
        'admin-',
        'course-index',
        'user-index',
        'admin-user',
        'admin-search',
        'admin-settings',
        'admin-tool-',
        'admin-roles-',
        'admin-capabilities-',
        'admin-cohort-',
        'admin-competency-',
        'admin-analytics-',
        'admin-backup-',
        'admin-restore-',
        'admin-webservice-',
        'admin-registration-',
        'admin-notification-',
        'admin-upgrade',
        'admin-plugins'
    ];
    
    foreach ($admin_pagetypes as $pagetype) {
        if (strpos($current_pagetype, $pagetype) !== false) {
            return true;
        }
    }
    
    return false;
}

/**
 * Check if current page is the home page
 *
 * @return bool True if current page is the home page
 */
function theme_remui_kids_is_home_page() {
    global $PAGE, $CFG;
    
    $current_url = $PAGE->url->get_path();
    $current_pagetype = $PAGE->pagetype;
    
    // Home page patterns
    $home_patterns = [
        '/my/',
        '/',
        '/index.php',
        '/course/view.php',
        '/user/profile.php',
        '/user/view.php'
    ];
    
    // Check if current URL matches home patterns
    foreach ($home_patterns as $pattern) {
        // Use exact match for root patterns, substring match for others
        if ($pattern === '/' || $pattern === '/index.php') {
            if ($current_url === $pattern || $current_url === $CFG->wwwroot . $pattern) {
                return true;
            }
        } else {
            if ($current_url === $pattern || $current_url === $CFG->wwwroot . $pattern || strpos($current_url, $pattern) !== false) {
                return true;
            }
        }
    }
    
    // Check pagetype for home page
    if (strpos($current_pagetype, 'my-index') !== false || 
        strpos($current_pagetype, 'site-index') !== false) {
        return true;
    }
    
    return false;
}

/**
 * Get admin sidebar data for template rendering
 *
 * @return array Array containing admin sidebar data
 */
function theme_remui_kids_get_admin_sidebar_template_data() {
    global $PAGE, $CFG;
    
    try {
        // Check if we should show admin sidebar
        $show_admin_sidebar = theme_remui_kids_is_admin_page() && !theme_remui_kids_is_home_page();
        
        if (!$show_admin_sidebar) {
            return ['show_admin_sidebar' => false];
        }
        
        // Get current page identifier for active state
        $current_url = $PAGE->url->get_path();
        $current_page = 'dashboard'; // default
        
        // Determine current page based on URL
        if (strpos($current_url, '/admin/search.php') !== false) {
            $current_page = 'site_admin';
        } elseif (strpos($current_url, '/local/edwiserreports/') !== false) {
            $current_page = 'analytics';
        } elseif (strpos($current_url, '/course/index.php') !== false) {
            $current_page = 'courses_programs';
        } elseif (strpos($current_url, '/user/index.php') !== false || strpos($current_url, '/admin/user.php') !== false) {
            $current_page = 'user_management';
        } elseif (strpos($current_url, '/admin/settings.php') !== false) {
            $current_page = 'system_settings';
        } elseif (strpos($current_url, '/admin/tool/') !== false) {
            $current_page = 'system_settings';
        } elseif (strpos($current_url, '/admin/roles/') !== false) {
            $current_page = 'user_management';
        } elseif (strpos($current_url, '/admin/cohort/') !== false) {
            $current_page = 'cohort_navigation';
        }
        
        // Get sidebar data
        $sidebar_data = theme_remui_kids_get_admin_sidebar_data($current_page);
        
        return array_merge([
            'show_admin_sidebar' => true
        ], $sidebar_data);
        
    } catch (Exception $e) {
        // Fallback: return minimal data to prevent crashes
        debugging('Admin sidebar template data error: ' . $e->getMessage(), DEBUG_DEVELOPER);
        return ['show_admin_sidebar' => false];
    }
}

/**
 * Test function to debug admin sidebar visibility
 * This can be called from any page to check if admin sidebar should show
 */
function theme_remui_kids_debug_admin_sidebar() {
    global $PAGE, $CFG;
    
    $is_admin = theme_remui_kids_is_admin_page();
    $is_home = theme_remui_kids_is_home_page();
    $should_show = $is_admin && !$is_home;
    
    $debug_info = [
        'current_url' => $PAGE->url->get_path(),
        'current_pagetype' => $PAGE->pagetype,
        'is_admin_page' => $is_admin,
        'is_home_page' => $is_home,
        'should_show_sidebar' => $should_show
    ];
    
    return $debug_info;
}

/**
 * Add admin sidebar test button to admin pages
 * This can be used to test if the sidebar is working
 */
function theme_remui_kids_add_admin_sidebar_test() {
    global $PAGE;
    
    // Only add test button on admin pages
    if (theme_remui_kids_is_admin_page() && !theme_remui_kids_is_home_page()) {
        $debug_info = theme_remui_kids_debug_admin_sidebar();
        
        $test_html = '<div style="position: fixed; top: 10px; right: 10px; background: #007bff; color: white; padding: 10px; border-radius: 5px; z-index: 9999; font-size: 12px;">
            <strong>Admin Sidebar Test</strong><br>
            URL: ' . htmlspecialchars($debug_info['current_url']) . '<br>
            Page Type: ' . htmlspecialchars($debug_info['current_pagetype']) . '<br>
            Is Admin: ' . ($debug_info['is_admin_page'] ? 'Yes' : 'No') . '<br>
            Is Home: ' . ($debug_info['is_home_page'] ? 'Yes' : 'No') . '<br>
            Show Sidebar: ' . ($debug_info['should_show_sidebar'] ? 'Yes' : 'No') . '
        </div>';
        
        return $test_html;
    }
    
    return '';
}

function theme_remui_kids_get_highschool_dashboard_metrics($userid) {
    global $DB;
    
    try {
        // Get enrolled courses count
        $enrolled_courses = $DB->count_records_sql(
            "SELECT COUNT(DISTINCT c.id)
             FROM {course} c
             JOIN {enrol} e ON c.id = e.courseid
             JOIN {user_enrolments} ue ON e.id = ue.enrolid
             WHERE ue.userid = ? 
             AND c.visible = 1
             AND c.id > 1",
            [$userid]
        ) ?: 0;
        
        // Get completed assignments count
        $completed_assignments = $DB->count_records_sql(
            "SELECT COUNT(DISTINCT cmc.coursemoduleid)
             FROM {course_modules_completion} cmc
             JOIN {course_modules} cm ON cmc.coursemoduleid = cm.id
             JOIN {modules} m ON cm.module = m.id
             JOIN {course} c ON cm.course = c.id
             WHERE cmc.userid = ? 
             AND cmc.completionstate IN (1, 2)
             AND m.name = 'assign'
             AND c.visible = 1
             AND c.id > 1",
            [$userid]
        ) ?: 0;
        
        // Get pending assignments count (assignments not completed)
        $total_assignments = $DB->count_records_sql(
            "SELECT COUNT(DISTINCT cm.id)
             FROM {course_modules} cm
             JOIN {modules} m ON cm.module = m.id
             JOIN {course} c ON cm.course = c.id
             JOIN {enrol} e ON c.id = e.courseid
             JOIN {user_enrolments} ue ON e.id = ue.enrolid
             WHERE ue.userid = ? 
             AND m.name = 'assign'
             AND c.visible = 1
             AND c.id > 1",
            [$userid]
        ) ?: 0;
        
        $pending_assignments = $total_assignments - $completed_assignments;
        
        // Get average grade from all graded activities
        $average_grade = $DB->get_field_sql(
            "SELECT AVG(gg.finalgrade / gg.rawgrademax * 100)
             FROM {grade_grades} gg
             JOIN {grade_items} gi ON gg.itemid = gi.id
             JOIN {course_modules} cm ON gi.iteminstance = cm.instance
             JOIN {modules} m ON cm.module = m.id
             JOIN {course} c ON cm.course = c.id
             WHERE gg.userid = ? 
             AND gg.finalgrade IS NOT NULL
             AND gg.rawgrademax > 0
             AND c.visible = 1
             AND c.id > 1",
            [$userid]
        ) ?: 0;
        
        // Calculate trends (comparing with previous quarter)
        $current_quarter_start = strtotime('first day of this month');
        $previous_quarter_start = strtotime('first day of -3 months');
        
        // Enrolled courses trend
        $previous_courses = $DB->count_records_sql(
            "SELECT COUNT(DISTINCT c.id)
             FROM {course} c
             JOIN {enrol} e ON c.id = e.courseid
             JOIN {user_enrolments} ue ON e.id = ue.enrolid
             WHERE ue.userid = ? 
             AND c.visible = 1
             AND c.id > 1
             AND ue.timecreated < ?",
            [$userid, $previous_quarter_start]
        ) ?: 0;
        
        $courses_trend = $previous_courses > 0 ? round((($enrolled_courses - $previous_courses) / $previous_courses) * 100) : 0;
        
        // Completed assignments trend
        $previous_completed = $DB->count_records_sql(
            "SELECT COUNT(DISTINCT cmc.coursemoduleid)
             FROM {course_modules_completion} cmc
             JOIN {course_modules} cm ON cmc.coursemoduleid = cm.id
             JOIN {modules} m ON cm.module = m.id
             JOIN {course} c ON cm.course = c.id
             WHERE cmc.userid = ? 
             AND cmc.completionstate IN (1, 2)
             AND m.name = 'assign'
             AND c.visible = 1
             AND c.id > 1
             AND cmc.timemodified < ?",
            [$userid, $previous_quarter_start]
        ) ?: 0;
        
        $assignments_trend = $previous_completed > 0 ? round((($completed_assignments - $previous_completed) / $previous_completed) * 100) : 0;
        
        // Average grade trend
        $previous_grade = $DB->get_field_sql(
            "SELECT AVG(gg.finalgrade / gg.rawgrademax * 100)
             FROM {grade_grades} gg
             JOIN {grade_items} gi ON gg.itemid = gi.id
             JOIN {course_modules} cm ON gi.iteminstance = cm.instance
             JOIN {modules} m ON cm.module = m.id
             JOIN {course} c ON cm.course = c.id
             WHERE gg.userid = ? 
             AND gg.finalgrade IS NOT NULL
             AND gg.rawgrademax > 0
             AND c.visible = 1
             AND c.id > 1
             AND gg.timemodified < ?",
            [$userid, $previous_quarter_start]
        ) ?: 0;
        
        $grade_trend = $previous_grade > 0 ? round($average_grade - $previous_grade) : 0;
        
        return [
            'enrolled_courses' => $enrolled_courses,
            'completed_assignments' => $completed_assignments,
            'pending_assignments' => $pending_assignments,
            'average_grade' => round($average_grade),
            'courses_trend' => $courses_trend,
            'assignments_trend' => $assignments_trend,
            'grade_trend' => $grade_trend,
            'pending_due_soon' => $pending_assignments > 0 // Simple logic for "Due soon"
        ];
        
    } catch (Exception $e) {
        return [
            'enrolled_courses' => 0,
            'completed_assignments' => 0,
            'pending_assignments' => 0,
            'average_grade' => 0,
            'courses_trend' => 0,
            'assignments_trend' => 0,
            'grade_trend' => 0,
            'pending_due_soon' => false
        ];
    }
}

/**
 * Get teacher dashboard statistics
 *
 * @return array Array containing teacher dashboard statistics
 */
function theme_remui_kids_get_teacher_dashboard_stats() {
    global $DB, $USER;

    try {
        // Determine teacher role ids
        $teacherroles = $DB->get_records_select('role', "shortname IN ('editingteacher','teacher')");
        $roleids = $teacherroles ? array_keys($teacherroles) : [];

        if (empty($roleids)) {
            return [
                'total_courses' => 0,
                'total_students' => 0,
                'pending_assignments' => 0,
                'upcoming_classes' => 0,
                'last_updated' => date('Y-m-d H:i:s')
            ];
        }

        // Get course ids where the user has a teacher role in the course context
        list($insql, $params) = $DB->get_in_or_equal($roleids, SQL_PARAMS_NAMED, 'r');
        $params['userid'] = $USER->id;
        $params['ctxlevel'] = CONTEXT_COURSE;

        $courseids = $DB->get_records_sql(
            "SELECT DISTINCT ctx.instanceid AS courseid
             FROM {role_assignments} ra
             JOIN {context} ctx ON ra.contextid = ctx.id
             WHERE ra.userid = :userid
             AND ctx.contextlevel = :ctxlevel
             AND ra.roleid {$insql}",
            $params
        );

        $courseidlist = [];
        foreach ($courseids as $row) {
            $courseidlist[] = $row->courseid;
        }

        if (empty($courseidlist)) {
            return [
                'total_courses' => 0,
                'total_students' => 0,
                'pending_assignments' => 0,
                'upcoming_classes' => 0,
                'last_updated' => date('Y-m-d H:i:s')
            ];
        }

        list($coursesql, $courseparams) = $DB->get_in_or_equal($courseidlist, SQL_PARAMS_NAMED, 'c');

        // Total visible courses for teacher
        $total_courses = $DB->count_records_select('course', "id {$coursesql} AND visible = 1", $courseparams);

        // Total distinct students across those courses
        $studentsql = "SELECT COUNT(DISTINCT ue.userid) FROM {user_enrolments} ue
                       JOIN {enrol} e ON ue.enrolid = e.id
                       WHERE e.courseid {$coursesql}";
        $total_students = $DB->count_records_sql($studentsql, $courseparams);

        // Pending assignments (assign with duedate in future) in these courses
        $pending_assignments = $DB->count_records_select('assign', "course {$coursesql} AND duedate > :now", array_merge($courseparams, ['now' => time()]));

        // Upcoming classes: courses modified in last 24 hours
        $upcoming_classes = $DB->count_records_select('course', "id {$coursesql} AND timemodified > :since", array_merge($courseparams, ['since' => (time() - 86400)]));

        // Total quizzes in teacher's courses
        $total_quizzes = $DB->count_records_select('quiz', "course {$coursesql}", $courseparams);

        return [
            'total_courses' => $total_courses,
            'total_students' => $total_students,
            'pending_assignments' => $pending_assignments,
            'upcoming_classes' => $upcoming_classes,
            'total_quizzes' => $total_quizzes,
            'last_updated' => date('Y-m-d H:i:s')
        ];

    } catch (Exception $e) {
        return [
            'total_courses' => 0,
            'total_students' => 0,
            'pending_assignments' => 0,
            'upcoming_classes' => 0,
            'total_quizzes' => 0,
            'last_updated' => date('Y-m-d H:i:s')
        ];
    }
}

/**
 * Get teacher's courses
 *
 * @return array Array containing teacher's courses
 */
function theme_remui_kids_get_teacher_courses() {
    global $DB, $USER;

    try {
        // Get teacher's course ids using context/role assignments
        $teacherroles = $DB->get_records_select('role', "shortname IN ('editingteacher','teacher')");
        $roleids = $teacherroles ? array_keys($teacherroles) : [];
        if (empty($roleids)) {
            return [];
        }

        list($insql, $params) = $DB->get_in_or_equal($roleids, SQL_PARAMS_NAMED, 'r');
        $params['userid'] = $USER->id;
        $params['ctxlevel'] = CONTEXT_COURSE;

        $courseids = $DB->get_records_sql(
            "SELECT DISTINCT ctx.instanceid as courseid
             FROM {role_assignments} ra
             JOIN {context} ctx ON ra.contextid = ctx.id
             WHERE ra.userid = :userid
             AND ctx.contextlevel = :ctxlevel
             AND ra.roleid {$insql}",
            $params
        );

        $ids = [];
        foreach ($courseids as $r) {
            $ids[] = $r->courseid;
        }
        if (empty($ids)) {
            return [];
        }

        list($coursesql, $courseparams) = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED, 'c');

        $sql = "SELECT c.id, c.fullname, c.shortname, c.summary, c.timemodified,
                       (SELECT COUNT(DISTINCT ue.userid) FROM {user_enrolments} ue JOIN {enrol} e ON ue.enrolid = e.id WHERE e.courseid = c.id) as student_count
                FROM {course} c
                WHERE c.id {$coursesql} AND c.visible = 1
                ORDER BY c.timemodified DESC
                LIMIT 5";

        $courses = $DB->get_records_sql($sql, $courseparams);

        $formatted_courses = [];
        foreach ($courses as $course) {
            $formatted_courses[] = [
                'id' => $course->id,
                'fullname' => $course->fullname,
                'shortname' => $course->shortname,
                'summary' => $course->summary,
                'student_count' => (int)$course->student_count,
                'last_modified' => date('M j, Y', $course->timemodified),
                'url' => (new moodle_url('/course/view.php', ['id' => $course->id]))->out()
            ];
        }

        return $formatted_courses;

    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get teacher's students
 *
 * @return array Array containing teacher's students
 */
function theme_remui_kids_get_teacher_students() {
    global $DB, $USER;

    try {
        // Get courses the teacher teaches
        $teacherroles = $DB->get_records_select('role', "shortname IN ('editingteacher','teacher')");
        $roleids = $teacherroles ? array_keys($teacherroles) : [];
        if (empty($roleids)) {
            return [];
        }

        list($insql, $params) = $DB->get_in_or_equal($roleids, SQL_PARAMS_NAMED, 'r');
        $params['userid'] = $USER->id;
        $params['ctxlevel'] = CONTEXT_COURSE;

        $courseids = $DB->get_records_sql(
            "SELECT DISTINCT ctx.instanceid as courseid
             FROM {role_assignments} ra
             JOIN {context} ctx ON ra.contextid = ctx.id
             WHERE ra.userid = :userid
             AND ctx.contextlevel = :ctxlevel
             AND ra.roleid {$insql}",
            $params
        );

        $ids = [];
        foreach ($courseids as $r) {
            $ids[] = $r->courseid;
        }
        if (empty($ids)) {
            return [];
        }

        list($coursesql, $courseparams) = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED, 'c');

        $sql = "SELECT DISTINCT u.id, u.firstname, u.lastname, u.email, u.lastaccess,
                       (SELECT COUNT(DISTINCT e.courseid) FROM {user_enrolments} ue2 JOIN {enrol} e ON ue2.enrolid = e.id WHERE ue2.userid = u.id) AS course_count
                FROM {user} u
                JOIN {user_enrolments} ue ON ue.userid = u.id
                JOIN {enrol} e ON ue.enrolid = e.id
                WHERE e.courseid {$coursesql}
                AND u.deleted = 0
                ORDER BY u.lastaccess DESC
                LIMIT 10";

        $students = $DB->get_records_sql($sql, $courseparams);

        $formatted_students = [];
        foreach ($students as $student) {
            // Get up to 3 of the teacher's courses this student is enrolled in
            $coursessql = "SELECT c.id, c.fullname
                           FROM {enrol} e
                           JOIN {user_enrolments} ue ON ue.enrolid = e.id
                           JOIN {course} c ON e.courseid = c.id
                           WHERE ue.userid = :uid
                           AND c.id {$coursesql}
                           ORDER BY c.fullname ASC
                           LIMIT 3";

            $courseparams_with_uid = array_merge($courseparams, ['uid' => $student->id]);
            $courserecs = $DB->get_records_sql($coursessql, $courseparams_with_uid);
            $coursenames = [];
            foreach ($courserecs as $cr) {
                $coursenames[] = $cr->fullname;
            }

            $formatted_students[] = [
                'id' => $student->id,
                'name' => $student->firstname . ' ' . $student->lastname,
                'email' => $student->email,
                'course_count' => (int)$student->course_count,
                'enrolled_courses' => $coursenames,
                'last_access' => $student->lastaccess ? date('M j, Y', $student->lastaccess) : 'Never',
                'profile_url' => (new moodle_url('/user/profile.php', ['id' => $student->id]))->out(),
                'avatar_url' => (new moodle_url('/user/pix.php/' . $student->id . '/f1.jpg'))->out()
            ];
        }

        return $formatted_students;

    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get teacher's assignments
 *
 * @return array Array containing teacher's assignments
 */
function theme_remui_kids_get_teacher_assignments() {
    global $DB, $USER;

    try {
        // Get teacher's course ids
        $teacherroles = $DB->get_records_select('role', "shortname IN ('editingteacher','teacher')");
        $roleids = $teacherroles ? array_keys($teacherroles) : [];
        if (empty($roleids)) {
            return [];
        }

        list($insql, $params) = $DB->get_in_or_equal($roleids, SQL_PARAMS_NAMED, 'r');
        $params['userid'] = $USER->id;
        $params['ctxlevel'] = CONTEXT_COURSE;

        $courseids = $DB->get_records_sql(
            "SELECT DISTINCT ctx.instanceid as courseid
             FROM {role_assignments} ra
             JOIN {context} ctx ON ra.contextid = ctx.id
             WHERE ra.userid = :userid
             AND ctx.contextlevel = :ctxlevel
             AND ra.roleid {$insql}",
            $params
        );

        $ids = [];
        foreach ($courseids as $r) {
            $ids[] = $r->courseid;
        }
        if (empty($ids)) {
            return [];
        }

        list($coursesql, $courseparams) = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED, 'c');

        $sql = "SELECT a.id, a.name, a.duedate, c.fullname as course_name, c.id as course_id,
                       (SELECT COUNT(DISTINCT s.id) FROM {assign_submission} s WHERE s.assignment = a.id) as submission_count,
                       (SELECT COUNT(DISTINCT g.id) FROM {assign_grades} g WHERE g.assignment = a.id AND g.grade IS NOT NULL) as graded_count
                FROM {assign} a
                JOIN {course} c ON a.course = c.id
                WHERE c.id {$coursesql}
                AND c.visible = 1
                ORDER BY a.duedate ASC
                LIMIT 10";

        $assignments = $DB->get_records_sql($sql, $courseparams);

        $formatted_assignments = [];
        foreach ($assignments as $assignment) {
            $status = 'pending';
            if ($assignment->duedate && $assignment->duedate < time()) {
                $status = 'overdue';
            } elseif ($assignment->duedate && $assignment->duedate < (time() + 86400)) {
                $status = 'due_soon';
            }

            $formatted_assignments[] = [
                'id' => $assignment->id,
                'name' => $assignment->name,
                'course_name' => $assignment->course_name,
                'course_id' => $assignment->course_id,
                'due_date' => $assignment->duedate ? date('M j, Y', $assignment->duedate) : 'No due date',
                'submission_count' => (int)$assignment->submission_count,
                'graded_count' => (int)$assignment->graded_count,
                'status' => $status,
                'url' => (new moodle_url('/mod/assign/view.php', ['id' => $assignment->id]))->out()
            ];
        }

        return $formatted_assignments;

    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get top courses by enrollment for the teacher
 *
 * @param int $limit
 * @return array
 */
function theme_remui_kids_get_top_courses_by_enrollment($limit = 5) {
    global $DB, $USER;

    try {
        // Get teacher course ids
        $teacherroles = $DB->get_records_select('role', "shortname IN ('editingteacher','teacher')");
        $roleids = $teacherroles ? array_keys($teacherroles) : [];
        if (empty($roleids)) {
            return [];
        }

        list($insql, $params) = $DB->get_in_or_equal($roleids, SQL_PARAMS_NAMED, 'r');
        $params['userid'] = $USER->id;
        $params['ctxlevel'] = CONTEXT_COURSE;

        $courseids = $DB->get_records_sql(
            "SELECT DISTINCT ctx.instanceid as courseid
             FROM {role_assignments} ra
             JOIN {context} ctx ON ra.contextid = ctx.id
             WHERE ra.userid = :userid
             AND ctx.contextlevel = :ctxlevel
             AND ra.roleid {$insql}",
            $params
        );

        $ids = array_map(function($r) { return $r->courseid; }, $courseids);
        if (empty($ids)) {
            return [];
        }

        list($coursesql, $courseparams) = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED, 'c');

        // Prefer counting users who hold the 'student' or 'trainee' role in the course context
        $studentroles = $DB->get_records_list('role', 'shortname', ['student', 'trainee']);
        $studentroleids = $studentroles ? array_keys($studentroles) : [];

        if (!empty($studentroleids)) {
            list($insqlr, $roleparams) = $DB->get_in_or_equal($studentroleids, SQL_PARAMS_NAMED, 'sr');

            $sql = "SELECT c.id, c.fullname as name,
                           (SELECT COUNT(DISTINCT ra.userid)
                            FROM {role_assignments} ra
                            JOIN {context} ctx2 ON ra.contextid = ctx2.id AND ctx2.contextlevel = " . CONTEXT_COURSE . "
                            WHERE ctx2.instanceid = c.id
                            AND ra.roleid {$insqlr}
                           ) AS enrollment_count
                    FROM {course} c
                    WHERE c.id {$coursesql} AND c.visible = 1
                    ORDER BY enrollment_count DESC
                    LIMIT :limit";

            // merge courseparams and roleparams and add limit
            $params = array_merge($courseparams, $roleparams);
            $params['limit'] = $limit;
            $records = $DB->get_records_sql($sql, $params);
        } else {
            // Fallback: count enrolments from enrol/user_enrolments if student roles are not defined
            $sql = "SELECT c.id, c.fullname as name,
                           (SELECT COUNT(DISTINCT ue.userid) FROM {user_enrolments} ue JOIN {enrol} e ON ue.enrolid = e.id WHERE e.courseid = c.id) as enrollment_count
                    FROM {course} c
                    WHERE c.id {$coursesql} AND c.visible = 1
                    ORDER BY enrollment_count DESC
                    LIMIT :limit";

            $courseparams['limit'] = $limit;
            $records = $DB->get_records_sql($sql, $courseparams);
        }

        $out = [];
        foreach ($records as $r) {
            $out[] = [
                'id' => $r->id,
                'name' => $r->name,
                'enrollment_count' => (int)$r->enrollment_count,
                // compute element count (visible course modules)
                'element_count' => (int)$DB->get_field_sql("SELECT COUNT(*) FROM {course_modules} cm WHERE cm.course = ? AND cm.visible = 1 AND cm.deletioninprogress = 0", [$r->id]),
                'url' => (new moodle_url('/course/view.php', ['id' => $r->id]))->out()
            ];
        }

        return $out;
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get top students for the teacher's courses by average grade (percent)
 *
 * @param int $limit
 * @return array
 */
function theme_remui_kids_get_top_students($limit = 5) {
    global $DB, $USER;

    try {
        // Get teacher course ids
        $teacherroles = $DB->get_records_select('role', "shortname IN ('editingteacher','teacher')");
        $roleids = $teacherroles ? array_keys($teacherroles) : [];
        if (empty($roleids)) {
            return [];
        }

        list($insql, $params) = $DB->get_in_or_equal($roleids, SQL_PARAMS_NAMED, 'r');
        $params['userid'] = $USER->id;
        $params['ctxlevel'] = CONTEXT_COURSE;

        $courseids = $DB->get_records_sql(
            "SELECT DISTINCT ctx.instanceid as courseid
             FROM {role_assignments} ra
             JOIN {context} ctx ON ra.contextid = ctx.id
             WHERE ra.userid = :userid
             AND ctx.contextlevel = :ctxlevel
             AND ra.roleid {$insql}",
            $params
        );

        $ids = array_map(function($r) { return $r->courseid; }, $courseids);
        if (empty($ids)) {
            return [];
        }

        // Compute average percentage grade per student across those courses
        list($coursesql, $courseparams) = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED, 'c');

        // Only include students who have been active recently (e.g., last 30 days)
        $active_since = time() - (30 * 24 * 60 * 60); // 30 days

        // Join role assignments to ensure we only pick users with student/trainee roles
        $sql = "SELECT u.id, u.firstname, u.lastname, u.lastaccess,
                       ROUND(AVG( (gg.finalgrade/NULLIF(gg.rawgrademax,0))*100 ),2) as avg_percent
                FROM {user} u
                JOIN {role_assignments} ra ON ra.userid = u.id
                JOIN {context} ctx ON ra.contextid = ctx.id AND ctx.contextlevel = " . CONTEXT_COURSE . "
                JOIN {role} r ON ra.roleid = r.id AND r.shortname IN ('student','trainee')
                JOIN {grade_grades} gg ON gg.userid = u.id
                JOIN {grade_items} gi ON gi.id = gg.itemid
                JOIN {course_modules} cm ON gi.iteminstance = cm.instance
                JOIN {course} c ON cm.course = c.id
                WHERE c.id {$coursesql}
                AND u.deleted = 0
                AND u.suspended = 0
                AND u.lastaccess > :activesince
                AND gg.finalgrade IS NOT NULL
                AND gg.rawgrademax > 0
                GROUP BY u.id, u.firstname, u.lastname, u.lastaccess
                ORDER BY avg_percent DESC
                LIMIT :limit";

        $courseparams['limit'] = $limit;
        $courseparams['activesince'] = $active_since;
        $students = $DB->get_records_sql($sql, $courseparams);

        $out = [];
        foreach ($students as $s) {
            $fullname = trim($s->firstname . ' ' . $s->lastname);
            $out[] = [
                'id' => $s->id,
                'name' => $fullname,
                'score' => (float)$s->avg_percent,
                'avatar_url' => (new moodle_url('/user/pix.php/' . $s->id . '/f1.jpg'))->out(),
                'profile_url' => (new moodle_url('/user/profile.php', ['id' => $s->id]))->out(),
                'last_access' => $s->lastaccess ? date('M j, Y', $s->lastaccess) : 'Never',
                'is_active' => ($s->lastaccess && $s->lastaccess > $active_since)
            ];
        }

        return $out;
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get performance chart data: average score per course for teacher's courses
 * Returns an array ready for JSON encoding: ['labels'=>[], 'data'=>[]]
 */
function theme_remui_kids_get_course_performance_chart_data() {
    global $DB, $USER;

    try {
        // Get teacher course ids
        $teacherroles = $DB->get_records_select('role', "shortname IN ('editingteacher','teacher')");
        $roleids = $teacherroles ? array_keys($teacherroles) : [];
        if (empty($roleids)) {
            return ['labels' => [], 'data' => []];
        }

        list($insql, $params) = $DB->get_in_or_equal($roleids, SQL_PARAMS_NAMED, 'r');
        $params['userid'] = $USER->id;
        $params['ctxlevel'] = CONTEXT_COURSE;

        $courseids = $DB->get_records_sql(
            "SELECT DISTINCT ctx.instanceid as courseid
             FROM {role_assignments} ra
             JOIN {context} ctx ON ra.contextid = ctx.id
             WHERE ra.userid = :userid
             AND ctx.contextlevel = :ctxlevel
             AND ra.roleid {$insql}",
            $params
        );

        $ids = array_map(function($r) { return $r->courseid; }, $courseids);
        if (empty($ids)) {
            return ['labels' => [], 'data' => []];
        }

        list($coursesql, $courseparams) = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED, 'c');

        // Use course completion rates as performance metric
        $sql = "SELECT c.id, c.shortname as course_name,
                       COUNT(DISTINCT ue.userid) as total_students,
                       COUNT(DISTINCT CASE WHEN cmc.completionstate = 1 THEN cmc.userid END) as completed_students,
                       ROUND(COUNT(DISTINCT CASE WHEN cmc.completionstate = 1 THEN cmc.userid END) * 100.0 / NULLIF(COUNT(DISTINCT ue.userid), 0), 1) as completion_rate
                FROM {course} c
                LEFT JOIN {enrol} e ON e.courseid = c.id
                LEFT JOIN {user_enrolments} ue ON ue.enrolid = e.id
                LEFT JOIN {course_modules_completion} cmc ON cmc.userid = ue.userid
                LEFT JOIN {course_modules} cm ON cm.id = cmc.coursemoduleid AND cm.course = c.id
                WHERE c.id {$coursesql}
                GROUP BY c.id, c.shortname
                HAVING total_students > 0
                ORDER BY completion_rate DESC
                LIMIT 6";

        $records = $DB->get_records_sql($sql, $courseparams);

        $labels = [];
        $data = [];
        $counts = [];

        foreach ($records as $r) {
            $labels[] = $r->course_name;
            $data[] = $r->completion_rate ?: 0;
            $counts[] = $r->total_students;
        }

        return ['labels' => $labels, 'data' => $data, 'counts' => $counts];

    } catch (Exception $e) {
        return ['labels' => [], 'data' => [], 'counts' => []];
    }
}

/**
 * Get course completion summary counts across teacher's courses
 * Returns ['completed'=>int,'inprogress'=>int,'not_started'=>int]
 */
function theme_remui_kids_get_course_completion_summary() {
    global $DB, $USER;

    try {
        // Get teacher course ids
        $teacherroles = $DB->get_records_select('role', "shortname IN ('editingteacher','teacher')");
        $roleids = $teacherroles ? array_keys($teacherroles) : [];
        if (empty($roleids)) {
            return ['completed' => 0, 'inprogress' => 0, 'not_started' => 0];
        }

        list($insql, $params) = $DB->get_in_or_equal($roleids, SQL_PARAMS_NAMED, 'r');
        $params['userid'] = $USER->id;
        $params['ctxlevel'] = CONTEXT_COURSE;

        $courseids = $DB->get_records_sql(
            "SELECT DISTINCT ctx.instanceid as courseid
             FROM {role_assignments} ra
             JOIN {context} ctx ON ra.contextid = ctx.id
             WHERE ra.userid = :userid
             AND ctx.contextlevel = :ctxlevel
             AND ra.roleid {$insql}",
            $params
        );

        $ids = array_map(function($r) { return $r->courseid; }, $courseids);
        if (empty($ids)) {
            return ['completed' => 0, 'inprogress' => 0, 'not_started' => 0];
        }

        list($coursesql, $courseparams) = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED, 'c');

        // Count completed modules, modules with some progress, and not started across these courses for enrolled students
        $completed = $DB->get_field_sql(
            "SELECT COUNT(DISTINCT cmc.userid) FROM {course_modules_completion} cmc
             JOIN {course_modules} cm ON cmc.coursemoduleid = cm.id
             JOIN {course} c ON cm.course = c.id
             WHERE cmc.completionstate > 0
             AND c.id {$coursesql}",
            $courseparams
        ) ?: 0;

        // For 'inprogress', approximate as users with timestarted > 0 but not completed all
        $inprogress = $DB->get_field_sql(
            "SELECT COUNT(DISTINCT cmc.userid) FROM {course_modules_completion} cmc
             JOIN {course_modules} cm ON cmc.coursemoduleid = cm.id
             JOIN {course} c ON cm.course = c.id
             WHERE cmc.timestarted > 0
             AND cmc.completionstate = 0
             AND c.id {$coursesql}",
            $courseparams
        ) ?: 0;

        // Not started: count distinct enrolled users in these courses minus the above two counts
        $enrolled = $DB->get_field_sql(
            "SELECT COUNT(DISTINCT ue.userid) FROM {user_enrolments} ue JOIN {enrol} e ON ue.enrolid = e.id WHERE e.courseid {$coursesql}",
            $courseparams
        ) ?: 0;

        $not_started = max(0, $enrolled - $completed - $inprogress);

        return ['completed' => (int)$completed, 'inprogress' => (int)$inprogress, 'not_started' => (int)$not_started];
    } catch (Exception $e) {
        return ['completed' => 0, 'inprogress' => 0, 'not_started' => 0];
    }
}

/**
 * Get teaching progress data for teacher dashboard
 *
 * @return array Teaching progress data
 */
function theme_remui_kids_get_teaching_progress_data() {
    global $DB, $USER;

    try {
        // Get teacher's course ids
        $teacherroles = $DB->get_records_select('role', "shortname IN ('editingteacher','teacher')");
        $roleids = $teacherroles ? array_keys($teacherroles) : [];
        if (empty($roleids)) {
            return ['progress_percentage' => 0, 'progress_label' => 'No courses assigned'];
        }

        list($insql, $params) = $DB->get_in_or_equal($roleids, SQL_PARAMS_NAMED, 'r');
        $params['userid'] = $USER->id;
        $params['ctxlevel'] = CONTEXT_COURSE;

        $courseids = $DB->get_records_sql(
            "SELECT DISTINCT ctx.instanceid AS courseid
             FROM {role_assignments} ra
             JOIN {context} ctx ON ra.contextid = ctx.id
             WHERE ra.userid = :userid
             AND ctx.contextlevel = :ctxlevel
             AND ra.roleid {$insql}",
            $params
        );

        $courseidlist = array_map(function($r) { return $r->courseid; }, $courseids);
        if (empty($courseidlist)) {
            return ['progress_percentage' => 0, 'progress_label' => 'No courses assigned'];
        }

        list($coursesql, $courseparams) = $DB->get_in_or_equal($courseidlist, SQL_PARAMS_NAMED, 'c');

        // Calculate progress based on completed activities vs total activities
        $total_activities = $DB->get_field_sql(
            "SELECT COUNT(*) FROM {course_modules} cm 
             WHERE cm.course {$coursesql} AND cm.visible = 1 AND cm.deletioninprogress = 0",
            $courseparams
        ) ?: 0;

        $completed_activities = $DB->get_field_sql(
            "SELECT COUNT(DISTINCT cmc.coursemoduleid) 
             FROM {course_modules_completion} cmc
             JOIN {course_modules} cm ON cmc.coursemoduleid = cm.id
             WHERE cm.course {$coursesql} AND cm.visible = 1 AND cm.deletioninprogress = 0
             AND cmc.completionstate = 1",
            $courseparams
        ) ?: 0;

        $progress_percentage = $total_activities > 0 ? round(($completed_activities / $total_activities) * 100) : 0;
        $progress_label = "{$completed_activities} of {$total_activities} activities completed";

        return [
            'progress_percentage' => $progress_percentage,
            'progress_label' => $progress_label
        ];

    } catch (Exception $e) {
        return ['progress_percentage' => 0, 'progress_label' => 'Error calculating progress'];
    }
}

/**
 * Get student feedback data for teacher dashboard
 *
 * @return array Student feedback data
 */
function theme_remui_kids_get_student_feedback_data() {
    global $DB, $USER;

    try {
        // Get teacher's course ids
        $teacherroles = $DB->get_records_select('role', "shortname IN ('editingteacher','teacher')");
        $roleids = $teacherroles ? array_keys($teacherroles) : [];
        if (empty($roleids)) {
            return [
                'average_rating' => 0,
                'total_reviews' => 0,
                'rating_breakdown' => [
                    '5_stars' => 0, '4_stars' => 0, '3_stars' => 0, '2_stars' => 0, '1_star' => 0
                ]
            ];
        }

        list($insql, $params) = $DB->get_in_or_equal($roleids, SQL_PARAMS_NAMED, 'r');
        $params['userid'] = $USER->id;
        $params['ctxlevel'] = CONTEXT_COURSE;

        $courseids = $DB->get_records_sql(
            "SELECT DISTINCT ctx.instanceid AS courseid
             FROM {role_assignments} ra
             JOIN {context} ctx ON ra.contextid = ctx.id
             WHERE ra.userid = :userid
             AND ctx.contextlevel = :ctxlevel
             AND ra.roleid {$insql}",
            $params
        );

        $courseidlist = array_map(function($r) { return $r->courseid; }, $courseids);
        if (empty($courseidlist)) {
            return [
                'average_rating' => 0,
                'total_reviews' => 0,
                'rating_breakdown' => [
                    '5_stars' => 0, '4_stars' => 0, '3_stars' => 0, '2_stars' => 0, '1_star' => 0
                ]
            ];
        }

        // Compute grade-based analytics as real data proxy for feedback
        // Get all graded items for teacher's courses and compute average and distribution
        list($coursesql, $courseparams) = $DB->get_in_or_equal($courseidlist, SQL_PARAMS_NAMED, 'c');

        $grades = $DB->get_records_sql(
            "SELECT gg.finalgrade, gi.grademax
             FROM {grade_grades} gg
             JOIN {grade_items} gi ON gi.id = gg.itemid
             WHERE gi.courseid {$coursesql}
               AND gg.finalgrade IS NOT NULL
               AND gi.grademax > 0",
            $courseparams
        );

        $total = 0; $sumPercent = 0.0;
        $buckets = [
            '80_100' => 0,
            '60_79' => 0,
            '40_59' => 0,
            '20_39' => 0,
            '0_19' => 0
        ];

        foreach ($grades as $g) {
            $pct = ($g->finalgrade / $g->grademax) * 100.0;
            $sumPercent += $pct;
            $total++;
            if ($pct >= 80) $buckets['80_100']++; else if ($pct >= 60) $buckets['60_79']++; else if ($pct >= 40) $buckets['40_59']++; else if ($pct >= 20) $buckets['20_39']++; else $buckets['0_19']++;
        }

        $average_percent = $total > 0 ? round($sumPercent / $total, 1) : 0;

        $percent_breakdown = [];
        foreach ($buckets as $k => $v) {
            $percent_breakdown[$k.'_percent'] = $total > 0 ? round(($v / $total) * 100) : 0;
        }

        return [
            'average_percent' => $average_percent,
            'total_graded' => $total,
            'distribution' => array_merge($buckets, $percent_breakdown)
        ];

    } catch (Exception $e) {
        return [
            'average_percent' => 0,
            'total_graded' => 0,
            'distribution' => [
                '80_100' => 0, '60_79' => 0, '40_59' => 0, '20_39' => 0, '0_19' => 0,
                '80_100_percent' => 0, '60_79_percent' => 0, '40_59_percent' => 0, '20_39_percent' => 0, '0_19_percent' => 0
            ]
        ];
    }
}

/**
 * Get recent feedback data for teacher dashboard
 *
 * @return array Recent feedback data
 */
function theme_remui_kids_get_recent_feedback_data() {
    global $DB, $USER;

    try {
        // Get teacher's course ids
        $teacherroles = $DB->get_records_select('role', "shortname IN ('editingteacher','teacher')");
        $roleids = $teacherroles ? array_keys($teacherroles) : [];
        if (empty($roleids)) {
            return [];
        }

        list($insql, $params) = $DB->get_in_or_equal($roleids, SQL_PARAMS_NAMED, 'r');
        $params['userid'] = $USER->id;
        $params['ctxlevel'] = CONTEXT_COURSE;

        $courseids = $DB->get_records_sql(
            "SELECT DISTINCT ctx.instanceid AS courseid
             FROM {role_assignments} ra
             JOIN {context} ctx ON ra.contextid = ctx.id
             WHERE ra.userid = :userid
             AND ctx.contextlevel = :ctxlevel
             AND ra.roleid {$insql}",
            $params
        );

        $courseidlist = array_map(function($r) { return $r->courseid; }, $courseids);
        if (empty($courseidlist)) {
            return [];
        }

        // Real data: recently graded items for teacher's courses
        list($coursesql, $courseparams) = $DB->get_in_or_equal($courseidlist, SQL_PARAMS_NAMED, 'c');

        $rows = $DB->get_records_sql(
            "SELECT u.id as userid, u.firstname, u.lastname, gg.timemodified, gg.finalgrade, gi.grademax, gi.itemname, c.fullname as coursename
             FROM {grade_grades} gg
             JOIN {grade_items} gi ON gi.id = gg.itemid
             JOIN {course} c ON c.id = gi.courseid
             JOIN {user} u ON u.id = gg.userid
             WHERE gi.courseid {$coursesql}
               AND gg.finalgrade IS NOT NULL
             ORDER BY gg.timemodified DESC
             LIMIT 8",
            $courseparams
        );

        $out = [];
        foreach ($rows as $r) {
            $pct = $r->grademax > 0 ? round(($r->finalgrade / $r->grademax) * 100) : 0;
            $out[] = [
                'student_name' => fullname((object)['firstname'=>$r->firstname,'lastname'=>$r->lastname]),
                'date' => userdate($r->timemodified, '%b %e, %Y'),
                'grade_percent' => $pct,
                'item_name' => $r->itemname ?: 'Graded item',
                'course_name' => $r->coursename
            ];
        }

        return $out;

    } catch (Exception $e) {
        return [];
    }
}
