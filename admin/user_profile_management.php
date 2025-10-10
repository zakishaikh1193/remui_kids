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
 * User Profile Management Page
 *
 * @package    theme_remui_kids
 * @copyright  2024 Kodeit
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir.'/adminlib.php');

// Check if user is logged in
require_login();

// Check if user has admin capabilities
$context = context_system::instance();
require_capability('moodle/site:config', $context);

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'get_user_details':
            $user_id = intval($_POST['user_id']);
            $user = $DB->get_record('user', ['id' => $user_id, 'deleted' => 0]);
            
            if ($user) {
                // Get user's enrolled courses
                $courses = $DB->get_records_sql(
                    "SELECT c.id, c.fullname, c.shortname, c.summary, 
                            cc.name as category_name,
                            ue.timecreated as enrolled_date,
                            ue.status as enrollment_status
                     FROM {course} c
                     LEFT JOIN {course_categories} cc ON c.category = cc.id
                     LEFT JOIN {user_enrolments} ue ON c.id = ue.userid
                     LEFT JOIN {enrol} e ON ue.enrolid = e.id
                     WHERE e.courseid = c.id AND ue.userid = ?
                     AND c.visible = 1 AND c.id > 1
                     ORDER BY c.fullname ASC",
                    [$user_id]
                );
                
                echo json_encode([
                    'status' => 'success',
                    'user' => $user,
                    'courses' => array_values($courses)
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'User not found']);
            }
            exit;
            
        case 'search_users':
            $search_term = $_POST['search_term'];
            $users = $DB->get_records_sql(
                "SELECT u.id, u.username, u.firstname, u.lastname, u.email, u.suspended, u.lastaccess
                 FROM {user} u
                 WHERE u.deleted = 0 
                 AND (u.firstname LIKE ? OR u.lastname LIKE ? OR u.email LIKE ? OR u.username LIKE ?)
                 ORDER BY u.firstname, u.lastname
                 LIMIT 20",
                ["%$search_term%", "%$search_term%", "%$search_term%", "%$search_term%"]
            );
            
            echo json_encode(['status' => 'success', 'users' => array_values($users)]);
            exit;
    }
}

// Set up page
$PAGE->set_context($context);
$PAGE->set_url('/theme/remui_kids/admin/user_profile_management.php');
$PAGE->set_title('User Profile Management');
$PAGE->set_heading('User Profile Management');
$PAGE->set_pagelayout('admin');

echo $OUTPUT->header();

// Add custom CSS for the user profile management with admin sidebar
echo "<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
    
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    body {
        font-family: 'Inter', sans-serif;
        background: linear-gradient(135deg, #fef7f7 0%, #f0f9ff 50%, #f0fdf4 100%);
        min-height: 100vh;
        overflow-x: hidden;
    }
    
    /* Admin Sidebar Navigation - Sticky on all pages */
    .admin-sidebar {
        position: fixed !important;
        top: 0;
        left: 0;
        width: 280px;
        height: 100vh;
        background: white;
        border-right: 1px solid #e9ecef;
        z-index: 1000;
        overflow-y: auto;
        box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        will-change: transform;
        backface-visibility: hidden;
    }
    
    .admin-sidebar .sidebar-content {
        padding: 6rem 0 2rem 0;
    }
    
    .admin-sidebar .sidebar-section {
        margin-bottom: 2rem;
    }
    
    .admin-sidebar .sidebar-category {
        font-size: 0.75rem;
        font-weight: 700;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 1rem;
        padding: 0 2rem;
        margin-top: 0;
    }
    
    .admin-sidebar .sidebar-menu {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .admin-sidebar .sidebar-item {
        margin-bottom: 0.25rem;
    }
    
    .admin-sidebar .sidebar-link {
        display: flex;
        align-items: center;
        padding: 0.75rem 2rem;
        color: #495057;
        text-decoration: none;
        transition: all 0.3s ease;
        border-left: 3px solid transparent;
    }
    
    .admin-sidebar .sidebar-link:hover {
        background-color: #f8f9fa;
        color: #2c3e50;
        text-decoration: none;
        border-left-color: #667eea;
    }
    
    .admin-sidebar .sidebar-icon {
        width: 20px;
        height: 20px;
        margin-right: 1rem;
        font-size: 1rem;
        color: #6c757d;
        text-align: center;
    }
    
    .admin-sidebar .sidebar-text {
        font-size: 0.9rem;
        font-weight: 500;
    }
    
    .admin-sidebar .sidebar-item.active .sidebar-link {
        background-color: #e3f2fd;
        color: #1976d2;
        border-left-color: #1976d2;
    }
    
    .admin-sidebar .sidebar-item.active .sidebar-icon {
        color: #1976d2;
    }
    
    /* Scrollbar styling */
    .admin-sidebar::-webkit-scrollbar {
        width: 6px;
    }
    
    .admin-sidebar::-webkit-scrollbar-track {
        background: #f1f1f1;
    }
    
    .admin-sidebar::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 3px;
    }
    
    .admin-sidebar::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }

    /* Main content area with sidebar - FULL SCREEN */
    .admin-main-content {
        position: fixed;
        top: 0;
        left: 280px;
        width: calc(100vw - 280px);
        height: 100vh;
        background-color: #ffffff;
        overflow-y: auto;
        z-index: 99;
        will-change: transform;
        backface-visibility: hidden;
        padding-top: 80px; /* Add padding to account for topbar */
    }
    
    /* Mobile responsive */
    @media (max-width: 768px) {
        .admin-sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 280px;
            height: 100vh;
            transform: translateX(-100%);
            transition: transform 0.3s ease;
            z-index: 1001;
        }
        
        .admin-sidebar.sidebar-open {
            transform: translateX(0);
        }
        
        .admin-main-content {
            position: relative;
            left: 0;
            width: 100vw;
            height: auto;
            min-height: 100vh;
            padding-top: 20px;
        }
    }
</style>";

// Admin Sidebar Navigation
echo "<div class='admin-sidebar'>";
echo "<div class='sidebar-content'>";
echo "<!-- DASHBOARD Section -->";
echo "<div class='sidebar-section'>";
echo "<h3 class='sidebar-category'>DASHBOARD</h3>";
echo "<ul class='sidebar-menu'>";
echo "<li class='sidebar-item'>";
echo "<a href='{$CFG->wwwroot}/my/' class='sidebar-link'>";
echo "<i class='fa fa-th-large sidebar-icon'></i>";
echo "<span class='sidebar-text'>Admin Dashboard</span>";
echo "</a>";
echo "</li>";
echo "<li class='sidebar-item'>";
echo "<a href='{$CFG->wwwroot}/admin/search.php' class='sidebar-link'>";
echo "<i class='fa fa-cog sidebar-icon'></i>";
echo "<span class='sidebar-text'>Site Administration</span>";
echo "</a>";
echo "</li>";
echo "<li class='sidebar-item'>";
echo "<a href='#' class='sidebar-link'>";
echo "<i class='fa fa-users sidebar-icon'></i>";
echo "<span class='sidebar-text'>Community</span>";
echo "</a>";
echo "</li>";
echo "<li class='sidebar-item'>";
echo "<a href='{$CFG->wwwroot}/theme/remui_kids/admin/enrollments.php' class='sidebar-link'>";
echo "<i class='fa fa-graduation-cap sidebar-icon'></i>";
echo "<span class='sidebar-text'>Enrollments</span>";
echo "</a>";
echo "</li>";
echo "</ul>";
echo "</div>";

echo "<!-- TEACHERS Section -->";
echo "<div class='sidebar-section'>";
echo "<h3 class='sidebar-category'>TEACHERS</h3>";
echo "<ul class='sidebar-menu'>";
echo "<li class='sidebar-item'>";
echo "<a href='{$CFG->wwwroot}/theme/remui_kids/admin/teachers_list.php' class='sidebar-link'>";
echo "<i class='fa fa-users sidebar-icon'></i>";
echo "<span class='sidebar-text'>Teachers</span>";
echo "</a>";
echo "</li>";
echo "<li class='sidebar-item'>";
echo "<a href='#' class='sidebar-link'>";
echo "<i class='fa fa-medal sidebar-icon'></i>";
echo "<span class='sidebar-text'>Master Trainers</span>";
echo "</a>";
echo "</li>";
echo "</ul>";
echo "</div>";

echo "<!-- COURSES & PROGRAMS Section -->";
echo "<div class='sidebar-section'>";
echo "<h3 class='sidebar-category'>COURSES & PROGRAMS</h3>";
echo "<ul class='sidebar-menu'>";
echo "<li class='sidebar-item'>";
echo "<a href='{$CFG->wwwroot}/theme/remui_kids/admin/courses.php' class='sidebar-link'>";
echo "<i class='fa fa-book sidebar-icon'></i>";
echo "<span class='sidebar-text'>Courses & Programs</span>";
echo "</a>";
echo "</li>";
echo "<li class='sidebar-item'>";
echo "<a href='#' class='sidebar-link'>";
echo "<i class='fa fa-graduation-cap sidebar-icon'></i>";
echo "<span class='sidebar-text'>Certifications</span>";
echo "</a>";
echo "</li>";
echo "<li class='sidebar-item'>";
echo "<a href='#' class='sidebar-link'>";
echo "<i class='fa fa-clipboard-list sidebar-icon'></i>";
echo "<span class='sidebar-text'>Assessments</span>";
echo "</a>";
echo "</li>";
echo "<li class='sidebar-item'>";
echo "<a href='#' class='sidebar-link'>";
echo "<i class='fa fa-school sidebar-icon'></i>";
echo "<span class='sidebar-text'>Schools</span>";
echo "</a>";
echo "</li>";
echo "</ul>";
echo "</div>";

echo "<!-- INSIGHTS Section -->";
echo "<div class='sidebar-section'>";
echo "<h3 class='sidebar-category'>INSIGHTS</h3>";
echo "<ul class='sidebar-menu'>";
echo "<li class='sidebar-item'>";
echo "<a href='{$CFG->wwwroot}/local/edwiserreports/index.php' class='sidebar-link'>";
echo "<i class='fa fa-chart-bar sidebar-icon'></i>";
echo "<span class='sidebar-text'>Analytics</span>";
echo "</a>";
echo "</li>";
echo "<li class='sidebar-item'>";
echo "<a href='#' class='sidebar-link'>";
echo "<i class='fa fa-chart-line sidebar-icon'></i>";
echo "<span class='sidebar-text'>Predictive Models</span>";
echo "</a>";
echo "</li>";
echo "<li class='sidebar-item'>";
echo "<a href='#' class='sidebar-link'>";
echo "<i class='fa fa-file-alt sidebar-icon'></i>";
echo "<span class='sidebar-text'>Reports</span>";
echo "</a>";
echo "</li>";
echo "<li class='sidebar-item'>";
echo "<a href='#' class='sidebar-link'>";
echo "<i class='fa fa-map sidebar-icon'></i>";
echo "<span class='sidebar-text'>Competencies Map</span>";
echo "</a>";
echo "</li>";
echo "</ul>";
echo "</div>";

echo "<!-- SETTINGS Section -->";
echo "<div class='sidebar-section'>";
echo "<h3 class='sidebar-category'>SETTINGS</h3>";
echo "<ul class='sidebar-menu'>";
echo "<li class='sidebar-item active'>";
echo "<a href='{$CFG->wwwroot}/theme/remui_kids/admin/user_profile_management.php' class='sidebar-link'>";
echo "<i class='fa fa-cog sidebar-icon'></i>";
echo "<span class='sidebar-text'>System Settings</span>";
echo "</a>";
echo "</li>";
echo "<li class='sidebar-item'>";
echo "<a href='{$CFG->wwwroot}/theme/remui_kids/admin/users_management_dashboard.php' class='sidebar-link'>";
echo "<i class='fa fa-user-friends sidebar-icon'></i>";
echo "<span class='sidebar-text'>User Management</span>";
echo "</a>";
echo "</li>";
echo "<li class='sidebar-item'>";
echo "<a href='#' class='sidebar-link'>";
echo "<i class='fa fa-users-cog sidebar-icon'></i>";
echo "<span class='sidebar-text'>Cohort Navigation</span>";
echo "</a>";
echo "</li>";
echo "</ul>";
echo "</div>";
echo "</div>";
echo "</div>";

// Main Content
echo "<div class='admin-main-content'>";
echo "<div class='profile-container'>";

// Header Section
echo "<div class='page-header'>";
echo "<h1 class='page-title'>Profile Setting</h1>";
echo "<p class='page-subtitle'>Manage and view detailed user profiles, course enrollments, and activity reports</p>";
echo "</div>";

// User Search Section (Hidden by default, shown when needed)
echo "<div id='search-section' class='search-section' style='display: none;'>";
echo "<div class='search-container'>";
echo "<div class='search-input-group'>";
echo "<i class='fa fa-search search-icon'></i>";
echo "<input type='text' id='user-search' placeholder='Search users by name, email, or username...' class='search-input'>";
echo "<button id='search-btn' class='search-btn'>Search</button>";
echo "<button id='close-search' class='close-search-btn'>×</button>";
echo "</div>";
echo "</div>";
echo "</div>";

// User Selection Results
echo "<div id='search-results' class='search-results' style='display: none;'>";
echo "<h3>Search Results</h3>";
echo "<div id='users-list' class='users-list'></div>";
echo "</div>";

// Main Profile Display (Default view)
echo "<div id='main-profile' class='main-profile'>";
echo "<div class='profile-layout'>";

// Left Column (Wider)
echo "<div class='profile-left-column'>";

// User Details Card
echo "<div class='profile-card user-details-card'>";
echo "<div class='card-header'>";
echo "<h3>User details</h3>";
echo "<a href='#' class='edit-link'>Edit profile</a>";
echo "</div>";
echo "<div class='card-content'>";
echo "<div class='detail-item'>";
echo "<label>Email address:</label>";
echo "<span id='user-email' class='detail-value'>zaki.byline@gmail.com</span>";
echo "<small class='visibility-note'>(Visible to everyone)</small>";
echo "</div>";
echo "<div class='detail-item'>";
echo "<label>Timezone:</label>";
echo "<span id='user-timezone' class='detail-value'>Europe/London</span>";
echo "</div>";
echo "<div class='detail-item'>";
echo "<label>Account Status:</label>";
echo "<span id='user-status' class='detail-value'>Active</span>";
echo "</div>";
echo "<div class='detail-item'>";
echo "<label>Last Access:</label>";
echo "<span id='user-lastaccess' class='detail-value'>Today</span>";
echo "</div>";
echo "</div>";
echo "</div>";

// Privacy and Policies Card
echo "<div class='profile-card privacy-card'>";
echo "<div class='card-header'>";
echo "<h3>Privacy and policies</h3>";
echo "</div>";
echo "<div class='card-content'>";
echo "<div class='policy-links'>";
echo "<a href='#' class='policy-link'>Data retention summary</a>";
echo "</div>";
echo "</div>";
echo "</div>";

// Course Details Card
echo "<div class='profile-card course-details-card'>";
echo "<div class='card-header'>";
echo "<h3>Course details</h3>";
echo "</div>";
echo "<div class='card-content'>";
echo "<div class='course-profiles'>";
echo "<h4>Course profiles</h4>";
echo "<div id='enrolled-courses' class='course-list'>";
echo "<a href='#' class='course-link'>tetsts</a>";
echo "<a href='#' class='course-link'>testenroll</a>";
echo "<a href='#' class='course-link'>Digital Foundations</a>";
echo "<a href='#' class='course-link'>Communication and Safety</a>";
echo "<a href='#' class='course-link'>Hardware, Networking and Internet</a>";
echo "<a href='#' class='course-link'>Coding Foundations</a>";
echo "<a href='#' class='course-link'>Multimedia, Cybersecurity and Ethics</a>";
echo "</div>";
echo "</div>";
echo "</div>";
echo "</div>";

echo "</div>"; // End Left Column

// Right Column (Narrower)
echo "<div class='profile-right-column'>";

// Miscellaneous Card
echo "<div class='profile-card miscellaneous-card'>";
echo "<div class='card-header'>";
echo "<h3>Miscellaneous</h3>";
echo "</div>";
echo "<div class='card-content'>";
echo "<div class='misc-links'>";
echo "<a href='#' class='misc-link'>Blog entries</a>";
echo "<a href='#' class='misc-link'>Notes</a>";
echo "<a href='#' class='misc-link'>Forum posts</a>";
echo "<a href='#' class='misc-link'>Forum discussions</a>";
echo "<a href='#' class='misc-link'>Learning plans</a>";
echo "</div>";
echo "</div>";
echo "</div>";

// Reports Card
echo "<div class='profile-card reports-card'>";
echo "<div class='card-header'>";
echo "<h3>Reports</h3>";
echo "</div>";
echo "<div class='card-content'>";
echo "<div class='report-links'>";
echo "<a href='#' class='report-link'>Today's logs</a>";
echo "<a href='#' class='report-link'>All logs</a>";
echo "<a href='#' class='report-link'>Outline report</a>";
echo "<a href='#' class='report-link'>Complete report</a>";
echo "<a href='#' class='report-link'>Browser sessions</a>";
echo "<a href='#' class='report-link'>Grades overview</a>";
echo "<a href='#' class='report-link'>Grades</a>";
echo "</div>";
echo "</div>";
echo "</div>";

// Login Activity Card
echo "<div class='profile-card login-activity-card'>";
echo "<div class='card-header'>";
echo "<h3>Login activity</h3>";
echo "</div>";
echo "<div class='card-content'>";
echo "<div id='login-activity' class='activity-content'>";
echo "<div class='activity-item'>";
echo "<label>Last Login:</label>";
echo "<span id='last-login' class='activity-value'>Today at 2:30 PM</span>";
echo "</div>";
echo "<div class='activity-item'>";
echo "<label>Account Created:</label>";
echo "<span id='account-created' class='activity-value'>January 15, 2024</span>";
echo "</div>";
echo "<div class='activity-item'>";
echo "<label>Last IP:</label>";
echo "<span id='last-ip' class='activity-value'>192.168.1.100</span>";
echo "</div>";
echo "</div>";
echo "</div>";
echo "</div>";

echo "</div>"; // End Right Column

echo "</div>"; // End Profile Layout
echo "</div>"; // End Main Profile

// Floating Search Button


echo "</div>"; // End Profile Container
echo "</div>"; // End Admin Main Content

// CSS Styles with Pastel Colors
echo "<style>
    /* Override any conflicting styles */
    .profile-container {
        max-width: 1600px;
        margin: 0 auto;
        padding: 20px;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        min-height: 100vh;
    }

    /* Page Header */
    .page-header {
        background: linear-gradient(135deg, #e1f5fe 0%, #f3e5f5 100%);
        padding: 2rem;
        border-radius: 15px;
        margin-bottom: 2rem;
        text-align: center;
        box-shadow: 0 4px 20px rgba(225, 245, 254, 0.3);
        border: 1px solid #b3e5fc;
    }

    .page-title {
        margin: 0 0 0.5rem 0;
        font-size: 2.5rem;
        font-weight: 700;
        color: #1976d2;
        text-shadow: 2px 2px 4px rgba(25, 118, 210, 0.1);
    }

    .page-subtitle {
        margin: 0;
        font-size: 1.1rem;
        color: #546e7a;
        opacity: 0.9;
        font-weight: 400;
    }

/* Search Section */
.search-section {
    background: linear-gradient(135deg, #e1f5fe 0%, #f3e5f5 100%);
    padding: 1.5rem;
    border-radius: 15px;
    margin-bottom: 2rem;
    box-shadow: 0 4px 20px rgba(225, 245, 254, 0.3);
    border: 1px solid #b3e5fc;
}

.search-container {
    max-width: 600px;
    margin: 0 auto;
}

.search-input-group {
    display: flex;
    align-items: center;
    background: white;
    border-radius: 25px;
    padding: 0.5rem;
    border: 2px solid #e1f5fe;
    transition: all 0.3s ease;
    box-shadow: 0 2px 10px rgba(225, 245, 254, 0.2);
}

.search-input-group:focus-within {
    border-color: #81d4fa;
    box-shadow: 0 0 0 3px rgba(129, 212, 250, 0.1);
}

.search-icon {
    color: #4fc3f7;
    margin: 0 1rem;
    font-size: 1.1rem;
}

.search-input {
    flex: 1;
    border: none;
    background: transparent;
    padding: 0.8rem 0;
    font-size: 1rem;
    outline: none;
    color: #37474f;
}

.search-btn {
    background: linear-gradient(135deg, #81d4fa 0%, #4fc3f7 100%);
    color: white;
    border: none;
    padding: 0.8rem 1.5rem;
    border-radius: 20px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-right: 0.5rem;
}

.search-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(129, 212, 250, 0.4);
}

.close-search-btn {
    background: linear-gradient(135deg, #ffcdd2 0%, #ef9a9a 100%);
    color: white;
    border: none;
    width: 35px;
    height: 35px;
    border-radius: 50%;
    font-size: 1.2rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.close-search-btn:hover {
    transform: scale(1.1);
    box-shadow: 0 4px 15px rgba(255, 205, 210, 0.4);
}

/* Search Results */
.search-results {
    background: linear-gradient(135deg, #f3e5f5 0%, #e8eaf6 100%);
    padding: 1.5rem;
    border-radius: 15px;
    margin-bottom: 2rem;
    box-shadow: 0 4px 20px rgba(243, 229, 245, 0.3);
    border: 1px solid #ce93d8;
}

.search-results h3 {
    margin: 0 0 1rem 0;
    color: #4a148c;
    font-size: 1.3rem;
    font-weight: 600;
}

.users-list {
    display: flex;
    flex-direction: column;
    gap: 0.8rem;
}

.user-item {
    display: flex;
    align-items: center;
    padding: 1rem;
    background: white;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
    border: 2px solid transparent;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.user-item:hover {
    background: #f8f9fa;
    border-color: #81d4fa;
    transform: translateX(5px);
    box-shadow: 0 4px 15px rgba(129, 212, 250, 0.2);
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #81d4fa 0%, #4fc3f7 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    margin-right: 1rem;
    box-shadow: 0 2px 8px rgba(129, 212, 250, 0.3);
}

.user-info h4 {
    margin: 0 0 0.2rem 0;
    color: #2c3e50;
    font-size: 1rem;
    font-weight: 600;
}

.user-info p {
    margin: 0;
    color: #6c757d;
    font-size: 0.9rem;
}

.user-status {
    margin-left: auto;
    padding: 0.3rem 0.8rem;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: 500;
}

.status-active {
    background: linear-gradient(135deg, #c8e6c9 0%, #a5d6a7 100%);
    color: #2e7d32;
    border: 1px solid #81c784;
}

.status-suspended {
    background: linear-gradient(135deg, #ffcdd2 0%, #ef9a9a 100%);
    color: #c62828;
    border: 1px solid #e57373;
}

/* Profile Layout */
.profile-layout {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 2rem;
    margin-top: 2rem;
}

/* Profile Cards with Pastel Colors */
.profile-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    margin-bottom: 1.5rem;
    overflow: hidden;
    transition: all 0.3s ease;
    border: 1px solid rgba(0, 0, 0, 0.05);
}

.profile-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
}

/* User Details Card - Pastel Blue */
.user-details-card {
    border-left: 4px solid #81d4fa;
}

.user-details-card .card-header {
    background: linear-gradient(135deg, #e1f5fe 0%, #f3e5f5 100%);
    border-bottom: 1px solid #b3e5fc;
}

/* Privacy Card - Pastel Purple */
.privacy-card {
    border-left: 4px solid #ce93d8;
}

.privacy-card .card-header {
    background: linear-gradient(135deg, #f3e5f5 0%, #e8eaf6 100%);
    border-bottom: 1px solid #ce93d8;
}

/* Course Details Card - Pastel Green */
.course-details-card {
    border-left: 4px solid #a5d6a7;
}

.course-details-card .card-header {
    background: linear-gradient(135deg, #e8f5e8 0%, #f1f8e9 100%);
    border-bottom: 1px solid #a5d6a7;
}

/* Miscellaneous Card - Pastel Orange */
.miscellaneous-card {
    border-left: 4px solid #ffcc02;
}

.miscellaneous-card .card-header {
    background: linear-gradient(135deg, #fff3e0 0%, #fce4ec 100%);
    border-bottom: 1px solid #ffcc02;
}

/* Reports Card - Pastel Pink */
.reports-card {
    border-left: 4px solid #f8bbd9;
}

.reports-card .card-header {
    background: linear-gradient(135deg, #fce4ec 0%, #f3e5f5 100%);
    border-bottom: 1px solid #f8bbd9;
}

/* Login Activity Card - Pastel Teal */
.login-activity-card {
    border-left: 4px solid #80cbc4;
}

.login-activity-card .card-header {
    background: linear-gradient(135deg, #e0f2f1 0%, #e8f5e8 100%);
    border-bottom: 1px solid #80cbc4;
}

.card-header {
    padding: 1.2rem 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.card-header h3 {
    margin: 0;
    color: #37474f;
    font-size: 1.1rem;
    font-weight: 600;
    text-transform: lowercase;
}

.edit-link {
    color: #4fc3f7;
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 500;
    transition: color 0.3s ease;
}

.edit-link:hover {
    color: #29b6f6;
}

.card-content {
    padding: 1.5rem;
}

/* Detail Items */
.detail-item {
    margin-bottom: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
}

.detail-item label {
    font-weight: 600;
    color: #546e7a;
    font-size: 0.9rem;
}

.detail-value {
    color: #37474f;
    font-size: 1rem;
}

.visibility-note {
    color: #78909c;
    font-size: 0.8rem;
    font-style: italic;
}

/* Links with Pastel Colors */
.policy-links, .misc-links, .report-links {
    display: flex;
    flex-direction: column;
    gap: 0.8rem;
}

.policy-link, .misc-link, .report-link {
    color: #4fc3f7;
    text-decoration: none;
    padding: 0.5rem 0;
    border-bottom: 1px solid #e1f5fe;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 500;
}

.policy-link:hover, .misc-link:hover, .report-link:hover {
    color: #29b6f6;
    padding-left: 0.5rem;
    background: linear-gradient(135deg, #e1f5fe 0%, #f3e5f5 100%);
    border-radius: 8px;
    padding: 0.5rem;
}

/* Course List */
.course-profiles h4 {
    margin: 0 0 1rem 0;
    color: #37474f;
    font-size: 1rem;
    font-weight: 600;
}

.course-list {
    display: flex;
    flex-direction: column;
    gap: 0.8rem;
}

.course-link {
    color: #4fc3f7;
    text-decoration: none;
    padding: 0.8rem;
    background: linear-gradient(135deg, #e1f5fe 0%, #f3e5f5 100%);
    border-radius: 10px;
    transition: all 0.3s ease;
    display: block;
    font-weight: 500;
    border-left: 3px solid #81d4fa;
}

.course-link:hover {
    background: linear-gradient(135deg, #b3e5fc 0%, #e1f5fe 100%);
    transform: translateX(5px);
    color: #0277bd;
    box-shadow: 0 2px 10px rgba(129, 212, 250, 0.3);
}

/* Activity Items */
.activity-content {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.activity-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.8rem;
    background: linear-gradient(135deg, #e0f2f1 0%, #e8f5e8 100%);
    border-radius: 10px;
    border-left: 3px solid #80cbc4;
}

.activity-item label {
    font-weight: 600;
    color: #546e7a;
}

.activity-value {
    color: #37474f;
    font-weight: 500;
}

/* Floating Search Button */
.floating-search-btn {
    position: fixed;
    bottom: 30px;
    right: 30px;
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #81d4fa 0%, #4fc3f7 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
    cursor: pointer;
    box-shadow: 0 4px 20px rgba(129, 212, 250, 0.4);
    transition: all 0.3s ease;
    z-index: 1000;
}

.floating-search-btn:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 25px rgba(129, 212, 250, 0.6);
}

/* Loading States */
.loading {
    text-align: center;
    padding: 2rem;
    color: #78909c;
}

.loading i {
    font-size: 2rem;
    margin-bottom: 1rem;
    animation: spin 1s linear infinite;
    color: #4fc3f7;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Responsive Design */
@media (max-width: 768px) {
    .profile-layout {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .profile-container {
        padding: 15px;
    }
    
    .floating-search-btn {
        bottom: 20px;
        right: 20px;
        width: 50px;
        height: 50px;
        font-size: 1.2rem;
    }
}
</style>";

// JavaScript
echo "<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('user-search');
    const searchBtn = document.getElementById('search-btn');
    const closeSearchBtn = document.getElementById('close-search');
    const searchSection = document.getElementById('search-section');
    const searchResults = document.getElementById('search-results');
    const usersList = document.getElementById('users-list');
    const mainProfile = document.getElementById('main-profile');
    
    let searchTimeout;
    
    // Toggle search function
    window.toggleSearch = function() {
        if (searchSection.style.display === 'none') {
            searchSection.style.display = 'block';
            searchInput.focus();
        } else {
            searchSection.style.display = 'none';
            searchResults.style.display = 'none';
            mainProfile.style.display = 'block';
        }
    };
    
    // Close search
    closeSearchBtn.addEventListener('click', function() {
        searchSection.style.display = 'none';
        searchResults.style.display = 'none';
        mainProfile.style.display = 'block';
    });
    
    // Search functionality
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const query = this.value.trim();
        
        if (query.length < 2) {
            searchResults.style.display = 'none';
            return;
        }
        
        searchTimeout = setTimeout(() => {
            searchUsers(query);
        }, 300);
    });
    
    searchBtn.addEventListener('click', function() {
        const query = searchInput.value.trim();
        if (query.length >= 2) {
            searchUsers(query);
        }
    });
    
    function searchUsers(query) {
        usersList.innerHTML = '<div class=\"loading\"><i class=\"fa fa-spinner\"></i><br>Searching users...</div>';
        searchResults.style.display = 'block';
        mainProfile.style.display = 'none';
        
        fetch('', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=search_users&search_term=' + encodeURIComponent(query)
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                displaySearchResults(data.users);
            } else {
                usersList.innerHTML = '<div class=\"loading\">No users found</div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            usersList.innerHTML = '<div class=\"loading\">Error searching users</div>';
        });
    }
    
    function displaySearchResults(users) {
        if (users.length === 0) {
            usersList.innerHTML = '<div class=\"loading\">No users found</div>';
            return;
        }
        
        usersList.innerHTML = users.map(user => {
            const initials = (user.firstname.charAt(0) + user.lastname.charAt(0)).toUpperCase();
            const statusClass = user.suspended ? 'status-suspended' : 'status-active';
            const statusText = user.suspended ? 'Suspended' : 'Active';
            const lastAccess = user.lastaccess ? new Date(user.lastaccess * 1000).toLocaleDateString() : 'Never';
            
            return '<div class=\"user-item\" onclick=\"loadUserProfile(' + user.id + ')\">' +
                '<div class=\"user-avatar\">' + initials + '</div>' +
                '<div class=\"user-info\">' +
                    '<h4>' + user.firstname + ' ' + user.lastname + '</h4>' +
                    '<p>' + user.email + ' • Last access: ' + lastAccess + '</p>' +
                '</div>' +
                '<div class=\"user-status ' + statusClass + '\">' + statusText + '</div>' +
            '</div>';
        }).join('');
    }
    
    window.loadUserProfile = function(userId) {
        // Update the profile with selected user data
        fetch('', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=get_user_details&user_id=' + userId
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                updateUserProfile(data);
                searchSection.style.display = 'none';
                searchResults.style.display = 'none';
                mainProfile.style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    };
    
    function updateUserProfile(data) {
        const user = data.user;
        const courses = data.courses;
        
        // Update user details
        document.getElementById('user-email').textContent = user.email;
        document.getElementById('user-timezone').textContent = user.timezone || 'Not set';
        document.getElementById('user-status').textContent = user.suspended ? 'Suspended' : 'Active';
        document.getElementById('user-lastaccess').textContent = user.lastaccess ? new Date(user.lastaccess * 1000).toLocaleString() : 'Never';
        
        // Update login activity
        document.getElementById('last-login').textContent = user.lastlogin ? new Date(user.lastlogin * 1000).toLocaleString() : 'Never';
        document.getElementById('account-created').textContent = new Date(user.timecreated * 1000).toLocaleString();
        document.getElementById('last-ip').textContent = user.lastip || 'Unknown';
        
        // Update enrolled courses
        const coursesList = document.getElementById('enrolled-courses');
        if (courses.length > 0) {
            coursesList.innerHTML = courses.map(course => 
                '<a href=\"#\" class=\"course-link\">' + course.fullname + '</a>'
            ).join('');
        } else {
            coursesList.innerHTML = '<div class=\"loading\">No enrolled courses</div>';
        }
    }
});
</script>";

echo $OUTPUT->footer();
?>