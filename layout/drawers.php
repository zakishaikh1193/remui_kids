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
 * A drawer based layout for the remui theme.
 *
 * @package   theme_remui
 * @copyright (c) 2023 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG, $PAGE, $COURSE, $USER, $DB, $OUTPUT;

require_once($CFG->dirroot . '/theme/remui_kids/layout/common.php');

// Check if this is a dashboard page and use our custom dashboard
if ($PAGE->pagelayout == 'mydashboard' && $PAGE->pagetype == 'my-index') {
    // Check if user is admin first
    $isadmin = is_siteadmin($USER) || has_capability('moodle/site:config', context_system::instance(), $USER);
    
    if ($isadmin) {
        // Show admin dashboard
        $templatecontext['custom_dashboard'] = true;
        $templatecontext['dashboard_type'] = 'admin';
        $templatecontext['admin_dashboard'] = true;
        $templatecontext['admin_stats'] = theme_remui_kids_get_admin_dashboard_stats();
        $templatecontext['admin_user_stats'] = theme_remui_kids_get_admin_user_stats();
        $templatecontext['admin_course_stats'] = theme_remui_kids_get_admin_course_stats();
        $templatecontext['admin_course_categories'] = theme_remui_kids_get_admin_course_categories();
        $templatecontext['student_enrollments'] = theme_remui_kids_get_recent_student_enrollments();
        $templatecontext['admin_student_activity_stats'] = theme_remui_kids_get_admin_student_activity_stats();
        $templatecontext['admin_recent_activity'] = theme_remui_kids_get_admin_recent_activity();
        
        // Must be called before rendering the template.
        require_once($CFG->dirroot . '/theme/remui/layout/common_end.php');
        
        // Render our custom admin dashboard template
        echo $OUTPUT->render_from_template('theme_remui_kids/admin_dashboard', $templatecontext);
        return; // Exit early to prevent normal rendering
    }
    
    // Check if user is a teacher (editingteacher, teacher, or has teacher capabilities)
    $isteacher = false;
    $context = context_system::instance();
    
    // Check for teacher roles in any course context
    $teacherroles = $DB->get_records_sql(
        "SELECT DISTINCT r.shortname 
         FROM {role} r 
         JOIN {role_assignments} ra ON r.id = ra.roleid 
         JOIN {context} ctx ON ra.contextid = ctx.id 
         WHERE ra.userid = ? 
         AND ctx.contextlevel = ? 
         AND r.shortname IN ('editingteacher', 'teacher')",
        [$USER->id, CONTEXT_COURSE]
    );
    
    if (!empty($teacherroles)) {
        $isteacher = true;
    }
    
    // Also check for teacher capabilities in system context
    if (!$isteacher && (has_capability('moodle/course:create', $context, $USER) || 
                       has_capability('moodle/course:manageactivities', $context, $USER))) {
        $isteacher = true;
    }
    
    if ($isteacher) {
        // Show teacher dashboard
        $templatecontext['custom_dashboard'] = true;
        $templatecontext['dashboard_type'] = 'teacher';
        $templatecontext['teacher_dashboard'] = true;
        $templatecontext['teacher_stats'] = theme_remui_kids_get_teacher_dashboard_stats();
        $templatecontext['teacher_courses'] = theme_remui_kids_get_teacher_courses();
        $templatecontext['teacher_students'] = theme_remui_kids_get_teacher_students();
        
        // New Advanced Analytics - Replace Recent Students and Recent Assignments
        $templatecontext['class_performance'] = theme_remui_kids_get_class_performance_overview();
        $templatecontext['student_insights'] = theme_remui_kids_get_student_insights();
        $templatecontext['assignment_analytics'] = theme_remui_kids_get_assignment_analytics();
        
        // Top Courses (real data, with mock fallback for layout preview)
        $templatecontext['top_courses'] = theme_remui_kids_get_top_courses_by_enrollment(5);
        if (empty($templatecontext['top_courses'])) {
            $templatecontext['top_courses'] = [
                ['id' => 0, 'name' => 'Mathematics 101', 'enrollment_count' => 34, 'element_count' => 56, 'url' => '#'],
                ['id' => 0, 'name' => 'Science Basics', 'enrollment_count' => 28, 'element_count' => 41, 'url' => '#'],
                ['id' => 0, 'name' => 'English Grammar', 'enrollment_count' => 22, 'element_count' => 39, 'url' => '#'],
                ['id' => 0, 'name' => 'Art & Design', 'enrollment_count' => 18, 'element_count' => 24, 'url' => '#'],
                ['id' => 0, 'name' => 'History Overview', 'enrollment_count' => 15, 'element_count' => 31, 'url' => '#']
            ];
        }
        
        // Real data sections - Recent Student Activity and Course Overview
        $templatecontext['recent_student_activity'] = theme_remui_kids_get_recent_student_activity();
        if (empty($templatecontext['recent_student_activity'])) {
            error_log("No recent student activity found - using mock data");
            $templatecontext['recent_student_activity'] = [
                [
                    'student_name' => 'Sarah Johnson',
                    'activity_name' => 'Mathematics Quiz - Chapter 5',
                    'activity_type' => 'Quiz Attempt',
                    'course_name' => 'Advanced Mathematics',
                    'time' => '2 hours ago',
                    'icon' => 'fa-star',
                    'color' => '#FF9800'
                ],
                [
                    'student_name' => 'Michael Chen',
                    'activity_name' => 'Essay Assignment Submission',
                    'activity_type' => 'Assignment Submitted',
                    'course_name' => 'English Literature',
                    'time' => '4 hours ago',
                    'icon' => 'fa-file-text',
                    'color' => '#4CAF50'
                ],
                [
                    'student_name' => 'Emily Rodriguez',
                    'activity_name' => 'Discussion: Climate Change',
                    'activity_type' => 'Forum Post',
                    'course_name' => 'Environmental Science',
                    'time' => '6 hours ago',
                    'icon' => 'fa-comments',
                    'color' => '#2196F3'
                ],
                [
                    'student_name' => 'David Kim',
                    'activity_name' => 'Physics Lab Report',
                    'activity_type' => 'Assignment Submitted',
                    'course_name' => 'Physics 101',
                    'time' => '8 hours ago',
                    'icon' => 'fa-file-text',
                    'color' => '#4CAF50'
                ],
                [
                    'student_name' => 'Lisa Thompson',
                    'activity_name' => 'History Quiz - World War II',
                    'activity_type' => 'Quiz Attempt',
                    'course_name' => 'World History',
                    'time' => '1 day ago',
                    'icon' => 'fa-star',
                    'color' => '#FF9800'
                ],
                [
                    'student_name' => 'James Wilson',
                    'activity_name' => 'Programming Exercise 3',
                    'activity_type' => 'Assignment Submitted',
                    'course_name' => 'Computer Science',
                    'time' => '1 day ago',
                    'icon' => 'fa-file-text',
                    'color' => '#4CAF50'
                ],
                [
                    'student_name' => 'Maria Garcia',
                    'activity_name' => 'Art Portfolio Review',
                    'activity_type' => 'Assignment Submitted',
                    'course_name' => 'Fine Arts',
                    'time' => '2 days ago',
                    'icon' => 'fa-file-text',
                    'color' => '#4CAF50'
                ],
                [
                    'student_name' => 'Alex Brown',
                    'activity_name' => 'Chemistry Lab Safety Quiz',
                    'activity_type' => 'Quiz Attempt',
                    'course_name' => 'Chemistry',
                    'time' => '2 days ago',
                    'icon' => 'fa-star',
                    'color' => '#FF9800'
                ]
            ];
        } else {
            error_log("Loaded " . count($templatecontext['recent_student_activity']) . " recent activities");
        }

        // Student Questions System - Integrated with Moodle messaging and forums
        $integrated_questions = theme_remui_kids_get_student_questions_integrated($USER->id);
        
        if (!empty($integrated_questions)) {
            // Use real data from Moodle messaging and forums
            $templatecontext['student_questions'] = $integrated_questions;
            error_log("Loaded " . count($integrated_questions) . " integrated questions from Moodle systems");
        } else {
            // Fallback to mock data if no real questions found
            $templatecontext['student_questions'] = [
            [
                'id' => 1,
                'title' => 'What wrong in this code',
                'content' => 'I am getting an error when trying to run this JavaScript function. Can someone help me understand what is wrong?',
                'student_name' => 'Zaki',
                'grade' => 'Grade 9',
                'course' => 'Mathematics',
                'date' => '14 Apr 2025',
                'status' => 'MENTOR REPLIED',
                'status_class' => 'mentor-replied',
                'upvotes' => 0,
                'replies' => 1
            ],
            [
                'id' => 2,
                'title' => 'What wrong in this code',
                'content' => 'I have been working on this problem for hours but cannot figure out the solution. Please help!',
                'student_name' => 'Zaki',
                'grade' => 'Grade 10',
                'course' => 'Science',
                'date' => '28 Mar 2025',
                'status' => 'MENTOR REPLIED',
                'status_class' => 'mentor-replied',
                'upvotes' => 0,
                'replies' => 1
            ],
            [
                'id' => 3,
                'title' => 'What wrong in this code',
                'content' => 'This is a follow-up question to my previous post. I still need help with the same issue.',
                'student_name' => 'Zaki',
                'grade' => 'Grade 11',
                'course' => 'English',
                'date' => '28 Mar 2025',
                'status' => 'MENTOR REPLIED',
                'status_class' => 'mentor-replied',
                'upvotes' => 0,
                'replies' => 1
            ],
            [
                'id' => 4,
                'title' => 'Some tests are not getting passed.',
                'content' => 'I have written several test cases but some of them are failing. Can you help me debug this issue?',
                'student_name' => 'Sujith',
                'grade' => 'Grade 12',
                'course' => 'Mathematics',
                'date' => '19 Sep 2024',
                'status' => 'Clarified',
                'status_class' => 'clarified',
                'upvotes' => 1,
                'replies' => 1
            ],
            [
                'id' => 5,
                'title' => 'CheckBox',
                'content' => 'I need help with implementing a checkbox functionality in my web application.',
                'student_name' => 'Daveed',
                'grade' => 'Grade 9',
                'course' => 'Science',
                'date' => '16 Dec 2023',
                'status' => 'MENTOR REPLIED',
                'status_class' => 'mentor-replied',
                'upvotes' => 1,
                'replies' => 3
            ],
            [
                'id' => 6,
                'title' => 'How to solve quadratic equations?',
                'content' => 'I am struggling with the quadratic formula. Can someone explain it step by step?',
                'student_name' => 'Emma Wilson',
                'grade' => 'Grade 10',
                'course' => 'Mathematics',
                'date' => '2 days ago',
                'status' => 'Pending',
                'status_class' => 'pending',
                'upvotes' => 0,
                'replies' => 0
            ],
            [
                'id' => 7,
                'title' => 'Physics lab experiment help',
                'content' => 'I need assistance with the pendulum experiment. The results are not matching the expected values.',
                'student_name' => 'Ryan Chen',
                'grade' => 'Grade 11',
                'course' => 'Science',
                'date' => '1 day ago',
                'status' => 'MENTOR REPLIED',
                'status_class' => 'mentor-replied',
                'upvotes' => 2,
                'replies' => 1
            ],
            [
                'id' => 8,
                'title' => 'Essay writing structure',
                'content' => 'Can someone help me understand the proper structure for a persuasive essay?',
                'student_name' => 'Sophia Martinez',
                'grade' => 'Grade 12',
                'course' => 'English',
                'date' => '3 days ago',
                'status' => 'Clarified',
                'status_class' => 'clarified',
                'upvotes' => 1,
                'replies' => 2
            ]
            ];
        }

        $templatecontext['course_overview'] = theme_remui_kids_get_course_overview();
        if (empty($templatecontext['course_overview'])) {
            error_log("No courses found for overview");
            $templatecontext['course_overview'] = [
                ['id' => 0, 'name' => 'No courses yet', 'shortname' => '-', 'student_count' => 0, 
                 'activity_count' => 0, 'assignment_count' => 0, 'url' => '#']
            ];
        } else {
            error_log("Loaded " . count($templatecontext['course_overview']) . " courses for overview");
        }
        
        // Additional real data for teacher dashboard
        $templatecontext['teaching_progress'] = theme_remui_kids_get_teaching_progress_data();
        if (empty($templatecontext['teaching_progress']) || !isset($templatecontext['teaching_progress']['progress_percentage'])) {
            $templatecontext['teaching_progress'] = [
                'progress_percentage' => 68,
                'progress_label' => '34 of 50 activities completed'
            ];
        }
        $templatecontext['student_feedback'] = theme_remui_kids_get_student_feedback_data();
        $templatecontext['recent_feedback'] = theme_remui_kids_get_recent_feedback_data();
        if (empty($templatecontext['recent_feedback'])) {
            $templatecontext['recent_feedback'] = [
                ['student_name' => 'John Smith', 'date' => '2 days ago', 'grade_percent' => 95, 'item_name' => 'Quiz 1', 'course_name' => 'Mathematics 101'],
                ['student_name' => 'Sarah Johnson', 'date' => '3 days ago', 'grade_percent' => 82, 'item_name' => 'Assignment 1', 'course_name' => 'Science Basics'],
                ['student_name' => 'Mike Davis', 'date' => '5 days ago', 'grade_percent' => 76, 'item_name' => 'Midterm', 'course_name' => 'English Grammar']
            ];
        }

        // Assignments mock fallback
        if (empty($templatecontext['teacher_assignments'])) {
            $templatecontext['teacher_assignments'] = [
                ['id' => 0, 'name' => 'Essay: My Summer', 'course_name' => 'English Grammar', 'course_id' => 0, 'due_date' => 'Nov 20, 2025', 'submission_count' => 12, 'graded_count' => 5, 'status' => 'pending', 'url' => '#'],
                ['id' => 0, 'name' => 'Lab Report #2', 'course_name' => 'Science Basics', 'course_id' => 0, 'due_date' => 'Nov 18, 2025', 'submission_count' => 18, 'graded_count' => 10, 'status' => 'due_soon', 'url' => '#'],
                ['id' => 0, 'name' => 'Unit Test', 'course_name' => 'Mathematics 101', 'course_id' => 0, 'due_date' => 'Nov 10, 2025', 'submission_count' => 22, 'graded_count' => 22, 'status' => 'overdue', 'url' => '#']
            ];
        }

        // Calendar Events (REAL Moodle data ONLY - NO mock fallback)
        $templatecontext['calendar_events'] = theme_remui_kids_get_teacher_calendar();
        
        if (empty($templatecontext['calendar_events'])) {
            error_log("No calendar events found in Moodle database");
        } else {
            error_log("Loaded " . count($templatecontext['calendar_events']) . " real calendar events from Moodle");
        }

        // Grades overview fallback
        if (empty($templatecontext['student_feedback']) || !isset($templatecontext['student_feedback']['average_percent'])) {
            $templatecontext['student_feedback'] = [
                'average_percent' => 84,
                'total_graded' => 120,
                'distribution' => [
                    '80_100' => 50, '60_79' => 40, '40_59' => 18, '20_39' => 8, '0_19' => 4,
                    '80_100_percent' => 42, '60_79_percent' => 33, '40_59_percent' => 15, '20_39_percent' => 7, '0_19_percent' => 3
                ]
            ];
        }
        
        // Must be called before rendering the template.
        require_once($CFG->dirroot . '/theme/remui/layout/common_end.php');
        
        // Render our custom teacher dashboard template
        echo $OUTPUT->render_from_template('theme_remui_kids/teacher_dashboard', $templatecontext);
        return; // Exit early to prevent normal rendering
    }
    
    // Get user's cohort information for non-admin users
    $usercohorts = $DB->get_records_sql(
        "SELECT c.name, c.id 
         FROM {cohort} c 
         JOIN {cohort_members} cm ON c.id = cm.cohortid 
         WHERE cm.userid = ?",
        [$USER->id]
    );

    $usercohortname = '';
    $usercohortid = 0;

    if (!empty($usercohorts)) {
        // Get the first cohort (assuming user is in one main cohort)
        $cohort = reset($usercohorts);
        $usercohortname = $cohort->name;
        $usercohortid = $cohort->id;
    }

    // Determine which dashboard layout to show based on cohort
    $dashboardtype = 'default'; // Default dashboard

    if (!empty($usercohortname)) {
        // Check for Grade 8-12 (High School) - Check this first to avoid conflicts
        if (preg_match('/grade\s*(?:1[0-2]|[8-9])/i', $usercohortname)) {
            $dashboardtype = 'highschool';
        }
        // Check for Grade 4-7 (Middle)
        elseif (preg_match('/grade\s*[4-7]/i', $usercohortname)) {
            $dashboardtype = 'middle';
        }
        // Check for Grade 1-3 (Elementary) - Check this last
        elseif (preg_match('/grade\s*[1-3]/i', $usercohortname)) {
            $dashboardtype = 'elementary';
        }
    }

    // Add custom dashboard data to template context
    $templatecontext['custom_dashboard'] = true;
    $templatecontext['dashboard_type'] = $dashboardtype;
    $templatecontext['user_cohort_name'] = $usercohortname;
    $templatecontext['user_cohort_id'] = $usercohortid;
    $templatecontext['student_name'] = $USER->firstname;
    $templatecontext['hello_message'] = "Hello " . $USER->firstname . "!";
    
    // Set My Courses URL based on dashboard type
    if ($dashboardtype === 'highschool') {
        $templatecontext['mycoursesurl'] = (new moodle_url('/theme/remui_kids/highschool_courses.php'))->out();
        $templatecontext['assignmentsurl'] = (new moodle_url('/theme/remui_kids/highschool_assignments.php'))->out();
        $templatecontext['profileurl'] = (new moodle_url('/theme/remui_kids/highschool_profile.php'))->out();
        $templatecontext['messagesurl'] = (new moodle_url('/theme/remui_kids/highschool_messages.php'))->out();
        $templatecontext['gradesurl'] = (new moodle_url('/theme/remui_kids/highschool_grades.php'))->out();
        $templatecontext['calendarurl'] = (new moodle_url('/theme/remui_kids/highschool_calendar.php'))->out();
    } else {
        $templatecontext['mycoursesurl'] = (new moodle_url('/my/courses.php'))->out();
        $templatecontext['assignmentsurl'] = (new moodle_url('/mod/assign/index.php'))->out();
        $templatecontext['profileurl'] = (new moodle_url('/user/profile.php', array('id' => $USER->id)))->out();
        $templatecontext['messagesurl'] = (new moodle_url('/message/index.php'))->out();
        $templatecontext['gradesurl'] = (new moodle_url('/grade/report/overview/index.php'))->out();
        $templatecontext['calendarurl'] = (new moodle_url('/calendar/view.php'))->out();
    }
    
    $templatecontext['dashboardurl'] = (new moodle_url('/my/'))->out();
    $templatecontext['codeeditorurl'] = (new moodle_url('/mod/lti/view.php', ['id' => 1]))->out(); // Adjust ID as needed
    $templatecontext['scratchurl'] = (new moodle_url('/mod/lti/view.php', ['id' => 2]))->out(); // Adjust ID as needed
    $templatecontext['logouturl'] = (new moodle_url('/login/logout.php', ['sesskey' => sesskey()]))->out();
    $templatecontext['profileurl'] = (new moodle_url('/user/profile.php', ['id' => $USER->id]))->out();
    
    // Add custom body class for dashboard styling
    $templatecontext['bodyattributes'] = 'class="custom-dashboard-page has-student-sidebar"';
    
    // Ensure parent theme navigation context is properly set up
    $templatecontext['navlayout'] = \theme_remui\toolbox::get_setting('header-primary-layout-desktop');
    $templatecontext['applylatestuserpref'] = apply_latest_user_pref();
    
    // Set up drawer preferences for parent theme navigation
    user_preference_allow_ajax_update('drawer-open-nav', PARAM_ALPHA);
    user_preference_allow_ajax_update('drawer-open-index', PARAM_BOOL);
    user_preference_allow_ajax_update('drawer-open-block', PARAM_BOOL);
    
    $navdraweropen = (get_user_preferences('drawer-open-nav', true) == true);
    $templatecontext['navdraweropen'] = $navdraweropen;
    
    // Add parent theme navigation context
    $templatecontext['applylatestdrawerjs'] = (get_moodle_release_version_branch() > '402');
    
    // Ensure parent theme navigation JavaScript is loaded
    $PAGE->requires->data_for_js('applylatestuserpref', $templatecontext['applylatestuserpref']);
    
    // Set individual dashboard type flags for Mustache template
    $templatecontext['elementary'] = ($dashboardtype === 'elementary');
    $templatecontext['middle'] = ($dashboardtype === 'middle');
    $templatecontext['highschool'] = ($dashboardtype === 'highschool');
    $templatecontext['default'] = ($dashboardtype === 'default');
    
    // Add Grade 1-3 specific statistics and courses for elementary students
    if ($dashboardtype === 'elementary') {
        $templatecontext['elementary_stats'] = theme_remui_kids_get_elementary_dashboard_stats($USER->id);
        $courses = theme_remui_kids_get_elementary_courses($USER->id);
        $templatecontext['elementary_courses'] = array_slice($courses, 0, 3); // Show only first 3 courses
        $templatecontext['has_elementary_courses'] = !empty($courses);
        $templatecontext['total_courses_count'] = count($courses);
        $templatecontext['show_view_all_button'] = count($courses) > 3;
        
        // Add active sections data
        $activesections = theme_remui_kids_get_elementary_active_sections($USER->id);
        $templatecontext['elementary_active_sections'] = $activesections;
        $templatecontext['has_elementary_active_sections'] = !empty($activesections);
        
        // Add active lessons data
        $activelessons = theme_remui_kids_get_elementary_active_lessons($USER->id);
        $templatecontext['elementary_active_lessons'] = $activelessons;
        $templatecontext['has_elementary_active_lessons'] = !empty($activelessons);
        
    }
    
    // Add Grade 4-7 specific statistics and courses for middle school students
    if ($dashboardtype === 'middle') {
        $templatecontext['middle_stats'] = theme_remui_kids_get_elementary_dashboard_stats($USER->id); // Reuse the same stats function
        $courses = theme_remui_kids_get_elementary_courses($USER->id); // Reuse the same courses function
        $templatecontext['middle_courses'] = array_slice($courses, 0, 3); // Show only first 3 courses
        $templatecontext['has_middle_courses'] = !empty($courses);
        $templatecontext['total_courses_count'] = count($courses);
        $templatecontext['show_view_all_button'] = count($courses) > 3;
        
        // Add course sections data for modal preview
        $coursesectionsdata = [];
        foreach ($courses as $course) {
            $sectionsdata = theme_remui_kids_get_course_sections_for_modal($course['id']);
            $coursesectionsdata[$course['id']] = $sectionsdata;
            // Debug: Log the data for each course
            error_log("Course {$course['id']} ({$course['fullname']}) sections data: " . print_r($sectionsdata, true));
        }
        $templatecontext['middle_courses_sections'] = json_encode($coursesectionsdata);
        // Debug: Log the final JSON data
        error_log("Final courses sections JSON: " . $templatecontext['middle_courses_sections']);
        
        // Add active sections data (limit to 3 for Current Lessons section)
        $activesections = theme_remui_kids_get_elementary_active_sections($USER->id);
        $templatecontext['middle_active_sections'] = array_slice($activesections, 0, 3); // Show only first 3 sections
        $templatecontext['has_middle_active_sections'] = !empty($activesections);
        
        // Add active lessons data (limit to 3 like elementary dashboard)
        $activelessons = theme_remui_kids_get_elementary_active_lessons($USER->id);
        $templatecontext['middle_active_lessons'] = array_slice($activelessons, 0, 3); // Show only first 3 lessons
        $templatecontext['has_middle_active_lessons'] = !empty($activelessons);
        
        // Add calendar and sidebar data
        $templatecontext['calendar_week'] = theme_remui_kids_get_calendar_week_data($USER->id);
        $templatecontext['upcoming_events'] = theme_remui_kids_get_upcoming_events($USER->id);
        $templatecontext['learning_stats'] = theme_remui_kids_get_learning_progress_stats($USER->id);
        $templatecontext['achievements'] = theme_remui_kids_get_achievements_data($USER->id);
        $templatecontext['calendarurl'] = (new moodle_url('/calendar/view.php'))->out();
    }
    
    // Add Grade 8-12 specific statistics and courses for high school students
    if ($dashboardtype === 'highschool') {
        $templatecontext['highschool_stats'] = theme_remui_kids_get_highschool_dashboard_stats($USER->id);
        $templatecontext['highschool_metrics'] = theme_remui_kids_get_highschool_dashboard_metrics($USER->id);
        $courses = theme_remui_kids_get_highschool_courses($USER->id);
        $templatecontext['highschool_courses'] = array_slice($courses, 0, 3); // Show only first 3 courses
        $templatecontext['has_highschool_courses'] = !empty($courses);
        $templatecontext['total_courses_count'] = count($courses);
        $templatecontext['show_view_all_button'] = count($courses) > 3;
        
        
        // Add active sections data (limit to 3 for Current Lessons section)
        $activesections = theme_remui_kids_get_highschool_active_sections($USER->id);
        $templatecontext['highschool_active_sections'] = array_slice($activesections, 0, 3);
        $templatecontext['has_highschool_active_sections'] = !empty($activesections);
        
        // Add active lessons data (limit to 3)
        $activelessons = theme_remui_kids_get_highschool_active_lessons($USER->id);
        $templatecontext['highschool_active_lessons'] = array_slice($activelessons, 0, 3);
        $templatecontext['has_highschool_active_lessons'] = !empty($activelessons);
        
        // Add calendar and sidebar data
        $templatecontext['calendar_week'] = theme_remui_kids_get_calendar_week_data($USER->id);
        $templatecontext['upcoming_events'] = theme_remui_kids_get_upcoming_events($USER->id);
        $templatecontext['learning_stats'] = theme_remui_kids_get_learning_progress_stats($USER->id);
        $templatecontext['achievements'] = theme_remui_kids_get_achievements_data($USER->id);
        $templatecontext['calendarurl'] = (new moodle_url('/calendar/view.php'))->out();
    }

    // Add cohort-specific data
    switch ($dashboardtype) {
        case 'elementary':
            $templatecontext['dashboard_title'] = 'Elementary Dashboard (Grades 1-3)';
            $templatecontext['dashboard_color'] = '#FF6B6B'; // Red
            break;
        case 'middle':
            $templatecontext['dashboard_title'] = 'Middle School Dashboard (Grades 4-7)';
            $templatecontext['dashboard_color'] = '#4ECDC4'; // Teal
            break;
        case 'highschool':
            $templatecontext['dashboard_title'] = 'High School Dashboard (Grades 8-12)';
            $templatecontext['dashboard_color'] = '#45B7D1'; // Blue
            break;
        default:
            $templatecontext['dashboard_title'] = 'Default Dashboard';
            $templatecontext['dashboard_color'] = '#95A5A6'; // Gray
            break;
    }

    // Must be called before rendering the template.
    require_once($CFG->dirroot . '/theme/remui/layout/common_end.php');
    
    // Render our clean dashboard template instead of the sidebar layout
    echo $OUTPUT->render_from_template('theme_remui_kids/clean_dashboard', $templatecontext);
    return; // Exit early to prevent normal rendering
}

// For non-dashboard pages, use the original logic
$coursecontext = context_course::instance($COURSE->id);
if (!is_guest($coursecontext, $USER) &&
    \theme_remui\toolbox::get_setting('enabledashboardcoursestats') &&
    $PAGE->pagelayout == 'mydashboard' && $PAGE->pagetype == 'my-index') {
    $templatecontext['isdashboardstatsshow'] = true;
    $setupstatus = get_config("theme_remui","setupstatus");
    if(get_config("theme_remui","dashboardpersonalizerinfo") == "show" && ( $setupstatus == "final" || $setupstatus == 'finished' )) {
        $templatecontext['showpersonlizerinfo'] = true;
    }
}

// Must be called before rendering the template.
// This will ease us to add body classes directly to the array.
require_once($CFG->dirroot . '/theme/remui/layout/common_end.php');
echo $OUTPUT->render_from_template('theme_remui/drawers', $templatecontext);