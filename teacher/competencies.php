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
 * Teacher Competencies page - minimal UI to browse course competencies
 *
 * @package   theme_remui_kids
 * @copyright 2025 Kodeit
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');

require_login();
$context = context_system::instance();

// Restrict to teachers/admins.
if (!has_capability('moodle/course:update', $context) && !has_capability('moodle/site:config', $context)) {
    throw new moodle_exception('nopermissions', 'error', '', 'access teacher competencies page');
}

// Page setup.
$PAGE->set_context($context);
$PAGE->set_url('/theme/remui_kids/teacher/competencies.php');
$PAGE->set_pagelayout('admin');
$PAGE->set_title('Competencies');
$PAGE->add_body_class('quizzes-page'); // Reuse page styling

// Breadcrumb.
$PAGE->navbar->add('Competencies');

// Teacher courses.
$teachercourses = enrol_get_my_courses('id, fullname, shortname', 'visible DESC, sortorder ASC');

// Output start.
echo $OUTPUT->header();

// Layout wrapper and sidebar (same as other teacher pages).
echo '<div class="teacher-css-wrapper">';
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
echo '<h1 class="students-page-title">Competencies</h1>';
echo '<p class="students-page-subtitle">Browse and manage competencies linked to your courses</p>';
echo '</div>';

// Overall stats across teacher courses
$courseids = array_keys($teachercourses);
if (!empty($courseids)) {
    list($insql, $params) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED);

    // Total linked competencies across teacher's courses
    $totalcompetencies = $DB->count_records_sql(
        "SELECT COUNT(DISTINCT cc.competencyid)
           FROM {competency_coursecomp} cc
          WHERE cc.courseid $insql",
        $params
    );

    // Total course-competency links (can be > competencies due to reuse across courses)
    $totallinks = $DB->count_records_sql(
        "SELECT COUNT(1)
           FROM {competency_coursecomp} cc
          WHERE cc.courseid $insql",
        $params
    );

    // Number of courses that have at least one competency
    $courseswithcomps = $DB->count_records_sql(
        "SELECT COUNT(DISTINCT cc.courseid)
           FROM {competency_coursecomp} cc
          WHERE cc.courseid $insql",
        $params
    );

    echo '<div class="stats-grid">';
    echo '<div class="stat-card"><div class="stat-icon"><i class="fa fa-sitemap"></i></div><div class="stat-content"><div class="stat-value">' . (int)$totalcompetencies . '</div><div class="stat-label">Unique Competencies</div></div></div>';
    echo '<div class="stat-card"><div class="stat-icon"><i class="fa fa-link"></i></div><div class="stat-content"><div class="stat-value">' . (int)$totallinks . '</div><div class="stat-label">Total Links</div></div></div>';
    echo '<div class="stat-card"><div class="stat-icon"><i class="fa fa-book"></i></div><div class="stat-content"><div class="stat-value">' . (int)$courseswithcomps . '</div><div class="stat-label">Courses with Competencies</div></div></div>';
    echo '<div class="stat-card"><div class="stat-icon"><i class="fa fa-database"></i></div><div class="stat-content"><div class="stat-value">' . count($teachercourses) . '</div><div class="stat-label">Total Courses</div></div></div>';
    echo '</div>';
}

// Course selector
echo '<div class="course-selector">';
echo '<div class="course-dropdown-wrapper">';
echo '<label for="compCourseSelect" class="course-dropdown-label">Select Course</label>';
echo '<select id="compCourseSelect" class="course-dropdown" onchange="window.location.href=this.value">';
echo '<option value="">Choose a course...</option>';

$currentcourseid = optional_param('courseid', 0, PARAM_INT);
foreach ($teachercourses as $course) {
    $selected = ($currentcourseid == $course->id) ? 'selected' : '';
    $url = new moodle_url('/theme/remui_kids/teacher/competencies.php', array('courseid' => $course->id));
    echo '<option value="' . $url->out() . '" ' . $selected . '>' . $course->shortname . ' - ' . $course->fullname . '</option>';
}
echo '</select>';
echo '</div>';

// View Toggle Button
echo '<div class="view-toggle-wrapper">';
echo '<label class="view-toggle-label">View:</label>';
echo '<div class="view-toggle">';
echo '<input type="radio" id="view-competency" name="view-type" value="competency" checked>';
echo '<label for="view-competency" class="toggle-option">';
echo '<i class="fa fa-sitemap"></i>';
echo '<span>Competency First</span>';
echo '</label>';
echo '<input type="radio" id="view-student" name="view-type" value="student">';
echo '<label for="view-student" class="toggle-option">';
echo '<i class="fa fa-users"></i>';
echo '<span>Student First</span>';
echo '</label>';
echo '</div>';
echo '</div>';

echo '</div>';

// If course selected, show overview table for that course
if ($currentcourseid) {
    $course = get_course($currentcourseid);
    $coursecontext = context_course::instance($course->id);
    echo '<div class="students-container">';

    // Controls
    echo '<div class="students-controls">';
    echo '<div class="search-box">';
    echo '<i class="fa fa-search search-icon"></i>';
    echo '<input type="text" id="compSearch" class="search-input" placeholder="Search competencies..." onkeyup="filterComps()">';
    echo '</div>';
    echo '</div>';

    // Student First View Container
    echo '<div id="studentFirstView" class="view-content" style="display: none;">';
    
    // Get enrolled students
    $students = get_enrolled_users($coursecontext, '', 0, 'u.id, u.firstname, u.lastname, u.email', 'u.lastname, u.firstname');
    
    if (empty($students)) {
        echo '<div class="empty-state">';
        echo '<div class="empty-state-icon"><i class="fa fa-users"></i></div>';
        echo '<div class="empty-state-title">No Students Enrolled</div>';
        echo '<div class="empty-state-text">There are no enrolled students in this course.</div>';
        echo '</div>';
    } else {
        // Get all competencies linked to this course for progress calculation
        $linkedcompetencies = $DB->get_records_sql(
            "SELECT DISTINCT cc.competencyid
               FROM {competency_coursecomp} cc
              WHERE cc.courseid = ?",
            array($currentcourseid)
        );
        $totalcompetencies = count($linkedcompetencies);
        
        echo '<div class="students-table-wrapper">';
        echo '<table class="students-table">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>Student</th>';
        echo '<th>Email</th>';
        echo '<th>Progress</th>';
        echo '<th>Actions</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        foreach ($students as $student) {
            $fullname = $student->firstname . ' ' . $student->lastname;
            $initials = strtoupper(substr($student->firstname, 0, 1) . substr($student->lastname, 0, 1));
            
            // Calculate student's competency progress
            $proficientcount = 0;
            foreach ($linkedcompetencies as $comp) {
                $usercomp = $DB->get_record('competency_usercompcourse', array(
                    'userid' => $student->id,
                    'competencyid' => $comp->competencyid,
                    'courseid' => $currentcourseid
                ));
                
                // If not found in course table, check global table as fallback
                if (!$usercomp) {
                    $usercomp = $DB->get_record('competency_usercomp', array(
                        'userid' => $student->id,
                        'competencyid' => $comp->competencyid
                    ));
                }
                
                if ($usercomp && $usercomp->proficiency) {
                    $proficientcount++;
                }
            }
            
            echo '<tr>';
            echo '<td class="student-name">';
            echo '<div class="student-avatar">' . $initials . '</div>';
            echo '<span>' . s($fullname) . '</span>';
            echo '</td>';
            echo '<td class="student-email">' . s($student->email) . '</td>';
            echo '<td class="progress-cell">';
            echo '<div class="progress-info">';
            echo '<span class="progress-text">' . $proficientcount . ' / ' . $totalcompetencies . '</span>';
            echo '<div class="progress-bar">';
            $percentage = $totalcompetencies > 0 ? ($proficientcount / $totalcompetencies) * 100 : 0;
            echo '<div class="progress-fill" style="width: ' . round($percentage, 1) . '%"></div>';
            echo '</div>';
            echo '</div>';
            echo '</td>';
            echo '<td class="student-actions">';
            echo '<a href="' . new moodle_url('/theme/remui_kids/teacher/student_competencies.php', array('userid' => $student->id, 'courseid' => $currentcourseid)) . '" class="filter-btn">';
            echo '<i class="fa fa-sitemap"></i> View Competencies';
            echo '</a>';
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
    }
    
    echo '</div>'; // end studentFirstView

    // Competency First View Container
    echo '<div id="competencyFirstView" class="view-content">';

    // Fetch frameworks that have competencies linked in this course
    $frameworks = $DB->get_records_sql(
        "SELECT DISTINCT f.id, f.shortname, f.idnumber
           FROM {competency_coursecomp} cc
           JOIN {competency} c ON c.id = cc.competencyid
           JOIN {competency_framework} f ON f.id = c.competencyframeworkid
          WHERE cc.courseid = ?
       ORDER BY f.shortname ASC",
        array($currentcourseid)
    );

    // Fetch all competencies for those frameworks that are linked to this course
    $comps = $DB->get_records_sql(
        "SELECT DISTINCT c.id, c.shortname, c.idnumber, c.parentid, c.competencyframeworkid AS frameworkid
           FROM {competency_coursecomp} cc
           JOIN {competency} c ON c.id = cc.competencyid
          WHERE cc.courseid = ?
       ORDER BY c.sortorder, c.shortname",
        array($currentcourseid)
    );

    // Build hierarchy: framework -> parents -> children
    $byframework = array();
    foreach ($frameworks as $f) { $byframework[$f->id] = array('framework' => $f, 'nodes' => array(), 'children' => array()); }
    foreach ($comps as $c) {
        if (!isset($byframework[$c->frameworkid])) { continue; }
        $byframework[$c->frameworkid]['nodes'][$c->id] = $c;
        $byframework[$c->frameworkid]['children'][$c->parentid ?? 0][] = $c->id;
    }

    // Helper to count linked activities per competency
    $hasmodulecomp = $DB->get_manager()->table_exists('competency_modulecomp');
    $hasactivity = $DB->get_manager()->table_exists('competency_activity');
    $countlinked = function(int $competencyid) use ($DB, $currentcourseid, $hasmodulecomp, $hasactivity): int {
        if ($hasmodulecomp) {
            return (int)$DB->get_field_sql(
                "SELECT COUNT(1) FROM {competency_modulecomp} mc JOIN {course_modules} cm ON cm.id = mc.cmid WHERE mc.competencyid = ? AND cm.course = ?",
                array($competencyid, $currentcourseid)
            );
        }
        if ($hasactivity) {
            return (int)$DB->get_field_sql(
                "SELECT COUNT(1) FROM {competency_activity} ca JOIN {course_modules} cm ON cm.id = ca.cmid WHERE ca.competencyid = ? AND cm.course = ?",
                array($competencyid, $currentcourseid)
            );
        }
        return 0;
    };

    // Enrolled student count once
    $students = get_enrolled_users($coursecontext, 'moodle/course:view');
    $numstudents = count($students);

    // Render tree
    echo '<div id="compTree" class="comp-tree">';
    if (empty($frameworks)) {
        echo '<div class="empty-state">No competencies linked to this course yet.</div>';
    } else {
        foreach ($byframework as $fwid => $bundle) {
            $f = $bundle['framework'];
            echo '<div class="tree-framework">';
            echo '<div class="tree-header" onclick="toggleNode(this)"><span class="caret">▶</span> ' . format_string($f->shortname) . '</div>';
            echo '<ul class="tree-level" style="display:none">';

            // Render nodes recursively starting at parentid 0/null
            $render = function($parentid, $bundle, $render) use ($countlinked, $numstudents, $currentcourseid) {
                $children = $bundle['children'][$parentid ?? 0] ?? array();
                foreach ($children as $cid) {
                    $c = $bundle['nodes'][$cid];
                    $linked = $countlinked($c->id);
                    echo '<li class="tree-item">';
                    $hasgrand = !empty($bundle['children'][$c->id]);
                    echo '<div class="tree-row"' . ($hasgrand ? ' onclick="toggleNode(this)"' : '') . '>';
                    if ($hasgrand) { echo '<span class="caret">▶</span> '; }
                    echo '<span class="tree-name">' . format_string($c->shortname) . '</span>';
                    echo '<span class="tree-meta">' . $linked . ' activities · ' . $numstudents . ' students</span>';
                    echo '<span class="tree-actions">';
                    echo '<a href="' . new moodle_url('/theme/remui_kids/teacher/competency_details.php', array('competencyid' => $c->id, 'courseid' => $currentcourseid)) . '" class="filter-btn" title="View Details"><i class="fa fa-info-circle"></i> Details</a>';
                    echo '<a href="' . new moodle_url('/admin/tool/lp/coursecompetencies.php', array('courseid' => $currentcourseid)) . '" target="_blank" class="filter-btn" title="Manage Competency"><i class="fa fa-cog"></i> Manage</a>';
                    echo '</span>';
                    echo '</div>';
                    if ($hasgrand) {
                        echo '<ul class="tree-level" style="display:none">';
                        $render($c->id, $bundle, $render);
                        echo '</ul>';
                    }
                    echo '</li>';
                }
            };

            // Render top-level competencies (both parentid = 0 and parentid = null)
            $topLevelIds = array_merge(
                $bundle['children'][0] ?? array(),
                $bundle['children'][null] ?? array()
            );
            $topLevelIds = array_unique($topLevelIds); // Remove duplicates
            
            foreach ($topLevelIds as $cid) {
                $c = $bundle['nodes'][$cid];
                $linked = $countlinked($c->id);
                echo '<li class="tree-item">';
                $hasgrand = !empty($bundle['children'][$c->id]);
                echo '<div class="tree-row"' . ($hasgrand ? ' onclick="toggleNode(this)"' : '') . '>';
                if ($hasgrand) { echo '<span class="caret">▶</span> '; }
                echo '<span class="tree-name">' . format_string($c->shortname) . '</span>';
                echo '<span class="tree-meta">' . $linked . ' activities · ' . $numstudents . ' students</span>';
                echo '<span class="tree-actions">';
                echo '<a href="' . new moodle_url('/theme/remui_kids/teacher/competency_details.php', array('competencyid' => $c->id, 'courseid' => $currentcourseid)) . '" class="filter-btn" title="View Details"><i class="fa fa-info-circle"></i> Details</a>';
                echo '<a href="' . new moodle_url('/admin/tool/lp/coursecompetencies.php', array('courseid' => $currentcourseid)) . '" target="_blank" class="filter-btn" title="Manage Competency"><i class="fa fa-cog"></i> Manage</a>';
                echo '</span>';
                echo '</div>';
                if ($hasgrand) {
                    echo '<ul class="tree-level" style="display:none">';
                    $render($c->id, $bundle, $render);
                    echo '</ul>';
                }
                echo '</li>';
            }

            echo '</ul>';
            echo '</div>';
        }
    }
    echo '</div>';

    echo '</div>'; // end competencyFirstView
    echo '</div>';
}


echo '</div>'; // students-page-wrapper
echo '</div>'; // teacher-main-content
echo '</div>'; // teacher-dashboard-wrapper
echo '</div>'; // teacher-css-wrapper
// Sidebar + page JS
echo '<script>
function toggleTeacherSidebar() {
  const sidebar = document.querySelector(".teacher-sidebar");
  sidebar.classList.toggle("sidebar-open");
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

function filterComps() {
  const term = (document.getElementById("compSearch")?.value || "").toLowerCase();
  const frameworks = document.querySelectorAll("#compTree .tree-framework");
  
  if (!term) {
    // Show all items when search is empty
    frameworks.forEach(fw => fw.style.display = "");
    const items = document.querySelectorAll("#compTree .tree-item");
    items.forEach(item => item.style.display = "");
    return;
  }
  
  frameworks.forEach(function(framework) {
    const items = framework.querySelectorAll(".tree-item .tree-name");
    let hasMatches = false;
    
    items.forEach(function(span) {
      const row = span.closest(".tree-item");
      const txt = span.textContent.toLowerCase();
      const matches = txt.includes(term);
      
      if (matches) {
        hasMatches = true;
        row.style.display = "";
        // Show parent framework
        framework.style.display = "";
        // Expand parent levels to show this item
        let parent = row.closest(".tree-level");
        while (parent && parent !== framework) {
          parent.style.display = "block";
          const parentRow = parent.previousElementSibling;
          if (parentRow && parentRow.querySelector(".caret")) {
            parentRow.querySelector(".caret").textContent = "▼";
          }
          parent = parent.closest(".tree-level");
        }
      } else {
        row.style.display = "none";
      }
    });
    
    // Hide framework if no matches
    if (!hasMatches) {
      framework.style.display = "none";
    }
  });
}

function toggleNode(el) {
  const row = el.classList.contains("tree-row") ? el : el.nextElementSibling;
  const list = el.classList.contains("tree-row") ? el.nextElementSibling : el.parentElement.querySelector(".tree-level");
  if (!list) return;
  const caret = (el.querySelector && el.querySelector(".caret")) || (el.previousElementSibling && el.previousElementSibling.querySelector && el.previousElementSibling.querySelector(".caret"));
  const isOpen = list.style.display !== "none";
  list.style.display = isOpen ? "none" : "block";
  if (caret) caret.textContent = isOpen ? "▶" : "▼";
}

// View toggle functionality
function toggleView() {
  const competencyView = document.getElementById("competencyFirstView");
  const studentView = document.getElementById("studentFirstView");
  const competencyRadio = document.getElementById("view-competency");
  const studentRadio = document.getElementById("view-student");
  
  if (competencyRadio.checked) {
    competencyView.style.display = "block";
    studentView.style.display = "none";
    // Update search placeholder
    const searchInput = document.getElementById("compSearch");
    if (searchInput) {
      searchInput.placeholder = "Search competencies...";
    }
  } else if (studentRadio.checked) {
    competencyView.style.display = "none";
    studentView.style.display = "block";
    // Update search placeholder
    const searchInput = document.getElementById("compSearch");
    if (searchInput) {
      searchInput.placeholder = "Search students...";
    }
  }
}

// Add event listeners to radio buttons
document.addEventListener("DOMContentLoaded", function() {
  const competencyRadio = document.getElementById("view-competency");
  const studentRadio = document.getElementById("view-student");
  
  if (competencyRadio) {
    competencyRadio.addEventListener("change", toggleView);
  }
  if (studentRadio) {
    studentRadio.addEventListener("change", toggleView);
  }
  
  // Initialize view on page load
  toggleView();
});

</script>';

echo $OUTPUT->footer();


