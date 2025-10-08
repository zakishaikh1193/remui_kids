<?php
require_once(__DIR__ . '/../../../config.php');

require_login();

$userid = required_param('userid', PARAM_INT);
$competencyid = required_param('competencyid', PARAM_INT);
$courseid = required_param('courseid', PARAM_INT);

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/theme/remui_kids/teacher/student_competency_evidence.php', array('userid' => $userid, 'competencyid' => $competencyid, 'courseid' => $courseid));
$PAGE->set_pagelayout('admin');
$PAGE->add_body_class('quizzes-page'); // Reuse page styling
$PAGE->set_title('Student Competency Evidence');
$PAGE->navbar->add('Teacher Dashboard', new moodle_url('/theme/remui_kids/teacher/dashboard.php'));
$PAGE->navbar->add('Competencies', new moodle_url('/theme/remui_kids/teacher/competencies.php', array('courseid' => $courseid)));
$PAGE->navbar->add('Student Evidence');

echo $OUTPUT->header();

// Layout wrapper and sidebar (same as other teacher pages)
echo '<div class="teacher-dashboard-wrapper">';
echo '<button class="sidebar-toggle" onclick="toggleTeacherSidebar()">';
echo '    <i class="fa fa-bars"></i>';
echo '</button>';

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
// Students section
echo '    <div class="sidebar-section">';
echo '      <h3 class="sidebar-category">STUDENTS</h3>';
echo '      <ul class="sidebar-menu">';
echo '        <li class="sidebar-item"><a href="' . $CFG->wwwroot . '/theme/remui_kids/teacher/students.php" class="sidebar-link"><i class="fa fa-users sidebar-icon"></i><span class="sidebar-text">All Students</span></a></li>';
echo '        <li class="sidebar-item"><a href="' . $CFG->wwwroot . '/enrol/users.php" class="sidebar-link"><i class="fa fa-user-plus sidebar-icon"></i><span class="sidebar-text">Enroll Students</span></a></li>';
echo '        <li class="sidebar-item"><a href="' . $CFG->wwwroot . '/report/progress/index.php" class="sidebar-link"><i class="fa fa-chart-line sidebar-icon"></i><span class="sidebar-text">Progress Reports</span></a></li>';
echo '      </ul>';
echo '    </div>';
// Assessments + Competencies
echo '    <div class="sidebar-section">';
echo '      <h3 class="sidebar-category">ASSESSMENTS</h3>';
echo '      <ul class="sidebar-menu">';
echo '        <li class="sidebar-item"><a href="' . $CFG->wwwroot . '/mod/assign/index.php" class="sidebar-link"><i class="fa fa-tasks sidebar-icon"></i><span class="sidebar-text">Assignments</span></a></li>';
echo '        <li class="sidebar-item"><a href="' . $CFG->wwwroot . '/theme/remui_kids/teacher/quizzes.php" class="sidebar-link"><i class="fa fa-question-circle sidebar-icon"></i><span class="sidebar-text">Quizzes</span></a></li>';
echo '        <li class="sidebar-item active"><a href="' . $CFG->wwwroot . '/theme/remui_kids/teacher/competencies.php" class="sidebar-link"><i class="fa fa-sitemap sidebar-icon"></i><span class="sidebar-text">Competencies</span></a></li>';
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

echo '<div class="teacher-main-content">';
echo '<div class="students-page-wrapper">';

// Header
echo '<div class="students-page-header">';
echo '<div class="header-content">';
echo '<a href="' . new moodle_url('/theme/remui_kids/teacher/competency_details.php', array('competencyid' => $competencyid, 'courseid' => $courseid)) . '" class="back-button" title="Back to Competency">';
echo '<i class="fa fa-arrow-left"></i> Back to Competency';
echo '</a>';
echo '<div class="header-text">';
echo '<h1 class="students-page-title">Student Competency Evidence</h1>';
echo '<p class="students-page-subtitle">Evidence for ' . s($user->firstname . ' ' . $user->lastname) . ' - ' . s($competency->shortname) . '</p>';
echo '</div>';
echo '</div>';
echo '</div>';

// Get user and competency info
$user = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);
$competency = $DB->get_record('competency', array('id' => $competencyid), '*', MUST_EXIST);
$course = get_course($courseid);

// Get competency scale for proper labels
require_once($CFG->dirroot . '/competency/classes/api.php');
$competencyobj = new \core_competency\competency($competencyid);
$scale = $competencyobj->get_scale();
$scaleitems = $scale->scale_items;

// Get user's competency status using Moodle's competency API (same way we save it)
try {
    $usercompetencycourse = \core_competency\api::get_user_competency_in_course($courseid, $userid, $competencyid);
    $usercompetency = null;
    
    if ($usercompetencycourse) {
        // Convert the API object to a simple object for easier handling
        $usercompetency = new stdClass();
        $usercompetency->grade = $usercompetencycourse->get_grade();
        $usercompetency->proficiency = $usercompetencycourse->get_proficiency();
        $usercompetency->note = $usercompetencycourse->get_note();
    }
} catch (Exception $e) {
    // Fallback to direct database query if API fails
    $usercompetency = $DB->get_record('competency_usercompcourse', array('userid' => $userid, 'competencyid' => $competencyid, 'courseid' => $courseid));
    
    // If not found in course table, check global table as fallback
    if (!$usercompetency) {
        $usercompetency = $DB->get_record('competency_usercomp', array('userid' => $userid, 'competencyid' => $competencyid));
    }
}

// Get competency evidence (notes) using direct database query
$competencyevidence = array();
if ($usercompetency) {
    // Try to find evidence for the course-specific user competency first
    $evidence = $DB->get_records('competency_evidence', array(
        'usercompetencyid' => $usercompetency->id
    ), 'timecreated DESC');
    
    // If no evidence found in course-specific table, check global user competency table
    if (empty($evidence)) {
        $global_usercompetency = $DB->get_record('competency_usercomp', array(
            'userid' => $userid, 
            'competencyid' => $competencyid
        ));
        
        if ($global_usercompetency) {
            $evidence = $DB->get_records('competency_evidence', array(
                'usercompetencyid' => $global_usercompetency->id
            ), 'timecreated DESC');
        }
    }
    
    // Process evidence records
    foreach ($evidence as $ev) {
        if (!empty($ev->note)) {
            $competencyevidence[] = $ev;
        }
    }
    
    // If we found evidence with notes, use the most recent one
    if (!empty($competencyevidence)) {
        $latestevidence = $competencyevidence[0];
        $usercompetency->note = $latestevidence->note;
        $usercompetency->evidence_id = $latestevidence->id;
        $usercompetency->evidence_timecreated = $latestevidence->timecreated;
    }
    
}

// Get linked activities for this competency
$linkedactivities = array();
$hasmodulecomp = $DB->get_manager()->table_exists('competency_modulecomp');
$hasactivity = $DB->get_manager()->table_exists('competency_activity');

if ($hasmodulecomp) {
    $linkedactivities = $DB->get_records_sql(
        "SELECT cm.id, cm.course, cm.module, cm.instance, cm.section, 
                m.name as modname, m.visible as modvisible,
                CASE 
                    WHEN m.name = 'quiz' THEN q.name
                    WHEN m.name = 'assign' THEN a.name
                    WHEN m.name = 'forum' THEN f.name
                    WHEN m.name = 'scorm' THEN s.name
                    WHEN m.name = 'edwiservideoactivity' THEN ev.name
                    WHEN m.name = 'edwiservideo' THEN ev.name
                    WHEN m.name = 'lesson' THEN l.name
                    WHEN m.name = 'h5pactivity' THEN h.name
                    ELSE CONCAT('Activity ', cm.instance)
                END as activityname
           FROM {competency_modulecomp} mc
           JOIN {course_modules} cm ON cm.id = mc.cmid
           JOIN {modules} m ON m.id = cm.module
           LEFT JOIN {quiz} q ON q.id = cm.instance AND m.name = 'quiz'
           LEFT JOIN {assign} a ON a.id = cm.instance AND m.name = 'assign'
           LEFT JOIN {forum} f ON f.id = cm.instance AND m.name = 'forum'
           LEFT JOIN {scorm} s ON s.id = cm.instance AND m.name = 'scorm'
           LEFT JOIN {edwiservideoactivity} ev ON ev.id = cm.instance AND m.name IN ('edwiservideoactivity', 'edwiservideo')
           LEFT JOIN {lesson} l ON l.id = cm.instance AND m.name = 'lesson'
           LEFT JOIN {h5pactivity} h ON h.id = cm.instance AND m.name = 'h5pactivity'
          WHERE mc.competencyid = ? AND cm.course = ?
       ORDER BY cm.section, activityname",
        array($competencyid, $courseid)
    );
} elseif ($hasactivity) {
    $linkedactivities = $DB->get_records_sql(
        "SELECT cm.id, cm.course, cm.module, cm.instance, cm.section,
                m.name as modname, m.visible as modvisible,
                CASE 
                    WHEN m.name = 'quiz' THEN q.name
                    WHEN m.name = 'assign' THEN a.name
                    WHEN m.name = 'forum' THEN f.name
                    WHEN m.name = 'scorm' THEN s.name
                    WHEN m.name = 'edwiservideoactivity' THEN ev.name
                    WHEN m.name = 'edwiservideo' THEN ev.name
                    WHEN m.name = 'lesson' THEN l.name
                    WHEN m.name = 'h5pactivity' THEN h.name
                    ELSE CONCAT('Activity ', cm.instance)
                END as activityname
           FROM {competency_activity} ca
           JOIN {course_modules} cm ON cm.id = ca.cmid
           JOIN {modules} m ON m.id = cm.module
           LEFT JOIN {quiz} q ON q.id = cm.instance AND m.name = 'quiz'
           LEFT JOIN {assign} a ON a.id = cm.instance AND m.name = 'assign'
           LEFT JOIN {forum} f ON f.id = cm.instance AND m.name = 'forum'
           LEFT JOIN {scorm} s ON s.id = cm.instance AND m.name = 'scorm'
           LEFT JOIN {edwiservideoactivity} ev ON ev.id = cm.instance AND m.name IN ('edwiservideoactivity', 'edwiservideo')
           LEFT JOIN {lesson} l ON l.id = cm.instance AND m.name = 'lesson'
           LEFT JOIN {h5pactivity} h ON h.id = cm.instance AND m.name = 'h5pactivity'
          WHERE ca.competencyid = ? AND cm.course = ?
       ORDER BY cm.section, activityname",
        array($competencyid, $courseid)
    );
}

// Get activity completion status for this user
$activitystatus = array();
foreach ($linkedactivities as $activity) {
    $completion = $DB->get_record('course_modules_completion', array('coursemoduleid' => $activity->id, 'userid' => $userid));
    $activitystatus[$activity->id] = $completion ? $completion->completionstate : 0;
}

// Get activity grades for this user
$activitygrades = array();
foreach ($linkedactivities as $activity) {
    $grade = $DB->get_record_sql(
        "SELECT gg.finalgrade, gg.rawgrade, gi.grademax
           FROM {grade_grades} gg
           JOIN {grade_items} gi ON gi.id = gg.itemid
          WHERE gi.itemmodule = ? AND gi.iteminstance = ? AND gg.userid = ? AND gi.courseid = ?",
        array($activity->modname, $activity->instance, $userid, $courseid)
    );
    $activitygrades[$activity->id] = $grade;
}


echo '<div class="student-competency-overview">';
echo '<div class="student-header">';
echo '<div class="student-avatar">';
echo strtoupper(substr($user->firstname, 0, 1) . substr($user->lastname, 0, 1));
echo '</div>';
echo '<div class="student-info">';
echo '<h2>' . s($user->firstname . ' ' . $user->lastname) . '</h2>';
echo '<p class="student-email">' . s($user->email) . '</p>';
echo '</div>';
echo '<div class="competency-status">';
$status = 'Not Yet Competent';
$statusclass = 'status-not-competent';

if ($usercompetency && $usercompetency->grade !== null) {
    // Use actual scale item labels
    $gradeindex = $usercompetency->grade - 1; // Scale items are 0-indexed
    if (isset($scaleitems[$gradeindex])) {
        $status = $scaleitems[$gradeindex];
        
        // Determine status class based on proficiency or grade
        if ($usercompetency->proficiency) {
            $statusclass = 'status-competent';
        } elseif ($usercompetency->grade > 1) {
            $statusclass = 'status-in-progress';
        } else {
            $statusclass = 'status-not-competent';
        }
    }
}
echo '<span class="status-badge ' . $statusclass . '">' . $status . '</span>';
echo '</div>';
echo '</div>';
echo '</div>';

echo '<div class="evidence-section">';
echo '<h3><i class="fa fa-clipboard-check"></i> Activity Evidence</h3>';

if (empty($linkedactivities)) {
    echo '<div class="empty-state">';
    echo '<div class="empty-state-icon"><i class="fa fa-link"></i></div>';
    echo '<div class="empty-state-title">No Activities Linked</div>';
    echo '<div class="empty-state-text">This competency is not linked to any activities yet.</div>';
    echo '</div>';
} else {
    echo '<div class="evidence-grid">';
    foreach ($linkedactivities as $activity) {
        $completion = $activitystatus[$activity->id] ?? 0;
        $grade = $activitygrades[$activity->id] ?? null;
        
        // Determine activity status
        $activitystatusclass = 'status-not-started';
        $activitystatustext = 'Not Started';
        
        if ($completion == 1) {
            $activitystatusclass = 'status-completed';
            $activitystatustext = 'Completed';
        } elseif ($completion == 2) {
            $activitystatusclass = 'status-completed';
            $activitystatustext = 'Completed';
        } elseif ($completion > 0) {
            $activitystatusclass = 'status-in-progress';
            $activitystatustext = 'In Progress';
        }
        
        // Get activity icon with proper mapping and defaults
        $iconclass = 'fa-file-alt'; // Default icon
        switch ($activity->modname) {
            case 'assign':
                $iconclass = 'fa-tasks';
                break;
            case 'quiz':
                $iconclass = 'fa-question-circle';
                break;
            case 'forum':
                $iconclass = 'fa-comments';
                break;
            case 'scorm':
                $iconclass = 'fa-play-circle';
                break;
            case 'edwiservideoactivity':
            case 'edwiservideo':
                $iconclass = 'fa-video';
                break;
            case 'lesson':
                $iconclass = 'fa-book-open';
                break;
            case 'h5pactivity':
                $iconclass = 'fa-puzzle-piece';
                break;
            case 'resource':
                $iconclass = 'fa-file';
                break;
            case 'url':
                $iconclass = 'fa-link';
                break;
            case 'folder':
                $iconclass = 'fa-folder';
                break;
            case 'page':
                $iconclass = 'fa-file-text';
                break;
            default:
                $iconclass = 'fa-file-alt';
                break;
        }
        
        echo '<div class="evidence-card">';
        echo '<div class="evidence-header">';
        echo '<div class="evidence-icon">';
        echo '<i class="fa ' . $iconclass . '"></i>';
        echo '</div>';
        echo '<div class="evidence-info">';
        echo '<h4>' . s($activity->activityname) . '</h4>';
        echo '<span class="evidence-type">' . ucfirst($activity->modname) . '</span>';
        echo '</div>';
        echo '<div class="evidence-status">';
        echo '<span class="status-badge ' . $activitystatusclass . '">' . $activitystatustext . '</span>';
        echo '</div>';
        echo '</div>';
        
        echo '<div class="evidence-details">';
        if ($grade && $grade->finalgrade !== null) {
            echo '<div class="evidence-grade">';
            echo '<strong>Grade:</strong> ';
            echo round($grade->finalgrade, 1);
            if ($grade->grademax) {
                echo ' / ' . round($grade->grademax, 1);
            }
            echo '</div>';
        }
        
        echo '<div class="evidence-actions">';
        echo '<a href="' . new moodle_url('/mod/' . $activity->modname . '/view.php', array('id' => $activity->id)) . '" class="btn btn-sm btn-secondary" target="_blank">';
        echo '<i class="fa fa-eye"></i> View Activity';
        echo '</a>';
        
        // Check if this activity type supports grading
        $hasgrading = false;
        $gradeurl = '';
        
        switch ($activity->modname) {
            case 'quiz':
                $gradeurl = new moodle_url('/mod/quiz/report.php', array('id' => $activity->id));
                $hasgrading = true;
                break;
            case 'scorm':
                $gradeurl = new moodle_url('/mod/scorm/grade.php', array('id' => $activity->id));
                $hasgrading = true;
                break;
            case 'assign':
                $gradeurl = new moodle_url('/mod/assign/view.php', array('id' => $activity->id, 'action' => 'grading'));
                $hasgrading = true;
                break;
            case 'lesson':
                $gradeurl = new moodle_url('/mod/lesson/report.php', array('id' => $activity->id));
                $hasgrading = true;
                break;
            case 'edwiservideoactivity':
            case 'edwiservideo':
                // Edwiser video activities don't have grade.php, so no grading support
                $hasgrading = false;
                break;
            default:
                // Check if grade.php exists for this activity type
                $gradepath = $CFG->dirroot . '/mod/' . $activity->modname . '/grade.php';
                if (file_exists($gradepath)) {
                    $gradeurl = new moodle_url('/mod/' . $activity->modname . '/grade.php', array('id' => $activity->id));
                    $hasgrading = true;
                } else {
                    $hasgrading = false;
                }
                break;
        }
        
        // Only show View Grades button if the activity supports grading
        if ($hasgrading) {
            echo '<a href="' . $gradeurl . '" class="btn btn-sm btn-primary" target="_blank">';
            echo '<i class="fa fa-chart-bar"></i> View Grades';
            echo '</a>';
        }
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }
    echo '</div>';
}
echo '</div>';

// Current Competency Rating Section
echo '<div class="current-rating-section">';
echo '<div class="rating-section-header">';
echo '<h3><i class="fa fa-info-circle"></i> Current Competency Rating</h3>';
echo '<button class="rate-competency-btn" onclick="toggleRatingForm()">';
echo '<i class="fa fa-star"></i> Rate Competency';
echo '</button>';
echo '</div>';

if ($usercompetency && $usercompetency->grade !== null) {
    // Get the actual scale item label
    $gradeindex = $usercompetency->grade - 1;
    $currentrating = isset($scaleitems[$gradeindex]) ? $scaleitems[$gradeindex] : 'Unknown';
    
    // Determine status class and icon
    $statusclass = 'status-not-competent';
    $statusicon = 'fa-times-circle';
    
    if ($usercompetency->proficiency) {
        $statusclass = 'status-competent';
        $statusicon = 'fa-check-circle';
    } elseif ($usercompetency->grade > 1) {
        $statusclass = 'status-in-progress';
        $statusicon = 'fa-clock';
    }
    
    echo '<div class="current-rating-display">';
    echo '<div class="rating-info">';
    echo '<div class="rating-status">';
    echo '<i class="fa ' . $statusicon . '"></i>';
    echo '<span class="status-badge ' . $statusclass . '">' . s($currentrating) . '</span>';
    echo '</div>';
    
    if (!empty($usercompetency->note)) {
        // Get the user who added the note (from evidence record)
        $note_author = null;
        $note_date = null;
        
        if (isset($usercompetency->evidence_id)) {
            $evidence_record = $DB->get_record('competency_evidence', array('id' => $usercompetency->evidence_id));
            if ($evidence_record) {
                $note_date = $evidence_record->timecreated;
                $note_author = $DB->get_record('user', array('id' => $evidence_record->usermodified));
            }
        }
        
        echo '<div class="rating-notes">';
        echo '<div class="notes-content">';
        
        // Show note metadata (who and when)
        if ($note_author && $note_date) {
            echo '<div class="note-metadata">';
            echo '<div class="note-author">';
            echo '<strong>' . s(fullname($note_author)) . '</strong>';
            echo '</div>';
            echo '<div class="note-date">';
            echo userdate($note_date, get_string('strftimedatefullshort', 'langconfig') . ', ' . get_string('strftimetime', 'langconfig'));
            echo '</div>';
            echo '</div>';
        }
        
        // Show the actual note content
        echo '<div class="note-text">';
        echo '<p>' . nl2br(s($usercompetency->note)) . '</p>';
        echo '</div>';
        
        echo '</div>';
        echo '</div>';
        
    } else {
        echo '<div class="no-notes">';
        echo '<i class="fa fa-comment-slash"></i>';
        echo '<span>No notes added yet</span>';
        echo '</div>';
        
    }
    
    echo '</div>';
    echo '</div>';
} else {
    echo '<div class="no-rating">';
    echo '<div class="no-rating-icon">';
    echo '<i class="fa fa-star-o"></i>';
    echo '</div>';
    echo '<div class="no-rating-text">';
    echo '<h4>Not Yet Rated</h4>';
    echo '<p>This competency has not been rated yet. Use the form below to provide your assessment.</p>';
    echo '</div>';
    echo '</div>';
}

echo '</div>';

echo '<div id="ratingModal" class="modal" style="display: none;">';
echo '<div class="modal-content">';
echo '<div class="modal-header">';
echo '<h3><i class="fa fa-star"></i> Rate Competency</h3>';
echo '<button class="modal-close" onclick="closeRatingModal()">&times;</button>';
echo '</div>';
echo '<div class="modal-body">';
echo '<div class="rating-form">';
echo '<form id="ratingForm" method="post" action="' . new moodle_url('/theme/remui_kids/teacher/save_competency_rating.php') . '">';
echo '<input type="hidden" name="userid" value="' . $userid . '">';
echo '<input type="hidden" name="competencyid" value="' . $competencyid . '">';
echo '<input type="hidden" name="courseid" value="' . $courseid . '">';

echo '<div class="rating-options">';
echo '<label for="competency_grade">Select Competency Rating:</label>';
echo '<select name="grade" id="competency_grade" class="rating-dropdown">';

// Show actual scale items
foreach ($scaleitems as $index => $scaleitem) {
    $value = $index + 1; // Scale values are 1-indexed
    $selected = '';
    
    if ($usercompetency && $usercompetency->grade == $value) {
        $selected = ' selected';
    }
    
    echo '<option value="' . $value . '"' . $selected . '>' . s($scaleitem) . '</option>';
}

echo '</select>';
echo '</div>';

echo '<div class="rating-comment">';
echo '<label for="comment">Comment (Optional):</label>';
echo '<textarea name="comment" id="comment" rows="3" placeholder="Add a comment about this competency rating..."></textarea>';
echo '</div>';

echo '</form>';
echo '</div>';
echo '</div>';
echo '<div class="modal-footer">';
echo '<button type="button" class="btn btn-secondary" onclick="closeRatingModal()">Cancel</button>';
echo '<button type="submit" form="ratingForm" class="btn btn-primary">';
echo '<i class="fa fa-save"></i> Save Rating';
echo '</button>';
echo '</div>';
echo '</div>';
echo '</div>';

echo '</div>'; // students-page-wrapper
echo '</div>'; // teacher-main-content
echo '</div>'; // teacher-dashboard-wrapper

// Simple sidebar JS
echo '<script>
function toggleTeacherSidebar() {
  const sidebar = document.querySelector(".teacher-sidebar");
  sidebar.classList.toggle("sidebar-open");
}

function toggleRatingForm() {
  const modal = document.getElementById("ratingModal");
  modal.style.display = "block";
  document.body.style.overflow = "hidden"; // Prevent background scrolling
}

function closeRatingModal() {
  const modal = document.getElementById("ratingModal");
  modal.style.display = "none";
  document.body.style.overflow = "auto"; // Restore scrolling
}

// Close modal when clicking outside of it
window.onclick = function(event) {
  const modal = document.getElementById("ratingModal");
  if (event.target === modal) {
    closeRatingModal();
  }
}

document.addEventListener("click", function(event) {
  const sidebar = document.querySelector(".teacher-sidebar");
  const toggleButton = document.querySelector(".sidebar-toggle");
  if (!sidebar || !toggleButton) return;
  if (window.innerWidth <= 768 && !sidebar.contains(event.target) && !toggleButton.contains(event.target)) {
    sidebar.classList.remove("sidebar-open");
  }
});

window.addEventListener("resize", function() {
  const sidebar = document.querySelector(".teacher-sidebar");
  if (!sidebar) return;
  if (window.innerWidth > 768) {
    sidebar.classList.remove("sidebar-open");
  }
});
</script>';

echo $OUTPUT->footer();
?>
