<?php
require_once('../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_login();
require_capability('moodle/site:config', context_system::instance());

$PAGE = new moodle_page();
$PAGE->set_url('/theme/remui_kids/admin/detail_total_users.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Total Users Details');
$PAGE->set_heading('Total Users Details');

// Handle AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['action']) {
        case 'get_users':
            $page = $_GET['page'] ?? 1;
            $per_page = $_GET['per_page'] ?? 20;
            $search = $_GET['search'] ?? '';
            $sort = $_GET['sort'] ?? 'firstname';
            $order = $_GET['order'] ?? 'ASC';
            
            $offset = ($page - 1) * $per_page;
            
            try {
                $sql = "SELECT u.id, u.username, u.firstname, u.lastname, u.email, u.timecreated, u.lastaccess, u.suspended, u.deleted
                        FROM {user} u 
                        WHERE u.deleted = 0";
                $params = [];
                
                if (!empty($search)) {
                    $sql .= " AND (u.firstname LIKE ? OR u.lastname LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
                    $search_param = "%{$search}%";
                    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
                }
                
                $sql .= " ORDER BY u.{$sort} {$order} LIMIT {$per_page} OFFSET {$offset}";
                
                $users = $DB->get_records_sql($sql, $params);
                
                // Get total count
                $count_sql = "SELECT COUNT(*) FROM {user} u WHERE u.deleted = 0";
                $count_params = [];
                if (!empty($search)) {
                    $count_sql .= " AND (u.firstname LIKE ? OR u.lastname LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
                    $count_params = [$search_param, $search_param, $search_param, $search_param];
                }
                $total_count = $DB->count_records_sql($count_sql, $count_params);
                
                echo json_encode([
                    'status' => 'success',
                    'users' => array_values($users),
                    'total_count' => $total_count,
                    'page' => $page,
                    'per_page' => $per_page,
                    'total_pages' => ceil($total_count / $per_page)
                ]);
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
            exit;
            
        case 'get_stats':
            try {
                $total_users = $DB->count_records('user', ['deleted' => 0]);
                $active_users = $DB->count_records_sql(
                    "SELECT COUNT(DISTINCT u.id) FROM {user} u 
                     JOIN {user_lastaccess} ul ON u.id = ul.userid 
                     WHERE u.deleted = 0 AND ul.timeaccess > ?",
                    [time() - (30 * 24 * 60 * 60)]
                );
                $suspended_users = $DB->count_records('user', ['deleted' => 0, 'suspended' => 1]);
                $new_this_month = $DB->count_records_sql(
                    "SELECT COUNT(*) FROM {user} WHERE deleted = 0 AND timecreated > ?",
                    [strtotime('first day of this month')]
                );
                
                echo json_encode([
                    'status' => 'success',
                    'stats' => [
                        'total_users' => $total_users,
                        'active_users' => $active_users,
                        'suspended_users' => $suspended_users,
                        'new_this_month' => $new_this_month
                    ]
                ]);
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
            exit;
    }
}

// Render the page
echo $OUTPUT->header();

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
echo "<li class='sidebar-item'>";
echo "<a href='#' class='sidebar-link'>";
echo "<i class='fa fa-cog sidebar-icon'></i>";
echo "<span class='sidebar-text'>System Settings</span>";
echo "</a>";
echo "</li>";
echo "<li class='sidebar-item active'>";
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

// Sidebar toggle button for mobile
echo "<button class='sidebar-toggle' onclick='toggleSidebar()' aria-label='Toggle sidebar'>";
echo "<i class='fa fa-bars'></i>";
echo "</button>";

// Add CSS for sidebar
echo "<style>
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
        padding: 1rem 2rem;
        color: #495057;
        text-decoration: none;
        transition: all 0.3s ease;
        position: relative;
        font-weight: 500;
        font-size: 0.95rem;
    }
    
    .admin-sidebar .sidebar-link:hover {
        background: #f8f9fa;
        color: #2196F3;
        padding-left: 2.5rem;
    }
    
    .admin-sidebar .sidebar-item.active .sidebar-link {
        background: linear-gradient(90deg, rgba(33, 150, 243, 0.1) 0%, transparent 100%);
        color: #2196F3;
        border-left: 4px solid #2196F3;
        font-weight: 600;
    }
    
    .admin-sidebar .sidebar-icon {
        margin-right: 1rem;
        font-size: 1.1rem;
        width: 20px;
        text-align: center;
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
        padding-top: 80px;
    }
    
    /* Sidebar toggle button for mobile */
    .sidebar-toggle {
        display: none;
        position: fixed;
        top: 20px;
        left: 20px;
        z-index: 1001;
        background: #2196F3;
        color: white;
        border: none;
        width: 45px;
        height: 45px;
        border-radius: 50%;
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(33, 150, 243, 0.4);
        transition: all 0.3s ease;
    }
    
    .sidebar-toggle:hover {
        background: #1976D2;
        transform: scale(1.1);
    }
    
    /* Mobile responsive */
    @media (max-width: 768px) {
        .admin-sidebar {
            position: fixed;
            top: 0;
            left: -280px;
            transition: left 0.3s ease;
        }
        
        .admin-sidebar.sidebar-open {
            left: 0;
        }
        
        .admin-main-content {
            position: relative;
            left: 0;
            width: 100vw;
            height: auto;
            min-height: 100vh;
            padding-top: 20px;
        }
        
        .sidebar-toggle {
            display: flex;
            align-items: center;
            justify-content: center;
        }
    }
</style>";

// Add JavaScript for sidebar toggle
echo "<script>
function toggleSidebar() {
    const sidebar = document.querySelector('.admin-sidebar');
    sidebar.classList.toggle('sidebar-open');
}

// Close sidebar when clicking outside on mobile
document.addEventListener('click', function(event) {
    const sidebar = document.querySelector('.admin-sidebar');
    const toggleBtn = document.querySelector('.sidebar-toggle');
    
    if (window.innerWidth <= 768) {
        if (!sidebar.contains(event.target) && !toggleBtn.contains(event.target)) {
            sidebar.classList.remove('sidebar-open');
        }
    }
});

// Handle window resize
window.addEventListener('resize', function() {
    const sidebar = document.querySelector('.admin-sidebar');
    if (window.innerWidth > 768) {
        sidebar.classList.remove('sidebar-open');
    }
});
</script>";

// Main content wrapper
echo "<div class='admin-main-content'>";

$template_data = [
    'config' => [
        'wwwroot' => $CFG->wwwroot
    ]
];

echo $OUTPUT->render_from_template('theme_remui_kids/detail_total_users', $template_data);

echo "</div>"; // End admin-main-content
echo $OUTPUT->footer();
?>
