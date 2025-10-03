<?php
/**
 * Teachers List Page - Display all teachers in a proper admin interface
 */

require_once('../../../config.php');
global $DB, $CFG, $OUTPUT, $PAGE;

// Set up the page
$PAGE->set_url('/theme/remui_kids/admin/teachers_list.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Teachers Management');
$PAGE->set_heading('Teachers Management');
$PAGE->set_pagelayout('admin');

// Check if user has admin capabilities
require_capability('moodle/site:config', context_system::instance());

// Handle status toggle AJAX requests
if (isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
    header('Content-Type: application/json');
    
    $userid = intval($_POST['userid']);
    if ($userid) {
        $user = $DB->get_record('user', ['id' => $userid]);
        if ($user) {
            $user->suspended = $user->suspended ? 0 : 1;
            if ($DB->update_record('user', $user)) {
                $status = $user->suspended ? 'suspended' : 'activated';
                echo json_encode(['status' => 'success', 'message' => "Teacher $status successfully"]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to update teacher status']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Teacher not found']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid teacher ID']);
    }
    exit;
}

echo $OUTPUT->header();

// Add custom CSS for the teachers list with admin sidebar
echo "<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
    
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    body {
        font-family: 'Inter', sans-serif;
        background: #f8f9fa;
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
    
    .teachers-container {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        padding: 20px;
        margin: 20px 0;
    }
    .teachers-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 2px solid #e9ecef;
    }
    .teachers-title {
        font-size: 28px;
        font-weight: 600;
        color: #2c3e50;
        margin: 0;
    }
    .teachers-subtitle {
        color: #6c757d;
        margin: 5px 0 0 0;
        font-size: 16px;
    }
    .teachers-stats {
        display: flex;
        gap: 20px;
        align-items: center;
    }
    .stat-item {
        text-align: center;
        padding: 10px 15px;
        background: #f8f9fa;
        border-radius: 6px;
        min-width: 80px;
    }
    .stat-number {
        font-size: 24px;
        font-weight: bold;
        color: #007bff;
        margin: 0;
    }
    .stat-label {
        font-size: 12px;
        color: #6c757d;
        margin: 0;
        text-transform: uppercase;
    }
    .teachers-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
        background: #fff;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .teachers-table th {
        background: #007bff;
        color: white;
        padding: 15px 12px;
        text-align: left;
        font-weight: 600;
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .teachers-table td {
        padding: 15px 12px;
        border-bottom: 1px solid #e9ecef;
        vertical-align: middle;
    }
    .teachers-table tr:hover {
        background: #f8f9fa;
    }
    .teacher-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #007bff;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 16px;
        margin-right: 10px;
    }
    .teacher-info {
        display: flex;
        align-items: center;
    }
    .teacher-name {
        font-weight: 600;
        color: #2c3e50;
        margin: 0;
    }
    .teacher-email {
        color: #6c757d;
        font-size: 14px;
        margin: 0;
    }
    .status-badge {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
    }
    .status-active {
        background: #d4edda;
        color: #155724;
    }
    .status-suspended {
        background: #f8d7da;
        color: #721c24;
    }
    .action-buttons {
        display: flex;
        gap: 8px;
    }
    .btn {
        padding: 6px 12px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .btn-primary {
        background: #007bff;
        color: white;
    }
    .btn-secondary {
        background: #6c757d;
        color: white;
    }
    .btn-danger {
        background: #dc3545;
        color: white;
    }
    .btn:hover {
        opacity: 0.9;
        text-decoration: none;
        color: inherit;
    }
    .search-filter-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 6px;
    }
    .search-box {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .search-input {
        padding: 8px 12px;
        border: 1px solid #ced4da;
        border-radius: 4px;
        width: 300px;
    }
    .filter-select {
        padding: 8px 12px;
        border: 1px solid #ced4da;
        border-radius: 4px;
        background: white;
    }
    .no-teachers {
        text-align: center;
        padding: 60px 20px;
        color: #6c757d;
    }
    .no-teachers i {
        font-size: 48px;
        margin-bottom: 20px;
        color: #dee2e6;
    }
    .breadcrumb {
        background: none;
        padding: 0;
        margin-bottom: 20px;
    }
    .breadcrumb-item {
        color: #6c757d;
    }
    .breadcrumb-item.active {
        color: #2c3e50;
        font-weight: 600;
    }
    .confirmation-modal {
        display: none;
        position: fixed;
        z-index: 10000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.8) 0%, rgba(118, 75, 162, 0.8) 100%);
        backdrop-filter: blur(10px);
        animation: modalFadeIn 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    }
    .modal-content {
        background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
        margin: 5% auto;
        padding: 0;
        border: none;
        border-radius: 24px;
        width: 90%;
        max-width: 500px;
        box-shadow: 
            0 30px 100px rgba(0, 0, 0, 0.3),
            0 0 0 1px rgba(255, 255, 255, 0.1),
            inset 0 1px 0 rgba(255, 255, 255, 0.2);
        animation: modalSlideIn 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
        overflow: hidden;
        position: relative;
        transform-style: preserve-3d;
    }
    .modal-content::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #667eea 0%, #764ba2 50%, #667eea 100%);
        background-size: 200% 100%;
        animation: shimmer 2s ease-in-out infinite;
    }
    @keyframes modalFadeIn {
        0% { 
            opacity: 0;
            backdrop-filter: blur(0px);
        }
        100% { 
            opacity: 1;
            backdrop-filter: blur(10px);
        }
    }
    @keyframes modalSlideIn {
        0% {
            opacity: 0;
            transform: translateY(-100px) scale(0.8) rotateX(20deg);
        }
        50% {
            opacity: 0.8;
            transform: translateY(10px) scale(1.02) rotateX(-5deg);
        }
        100% {
            opacity: 1;
            transform: translateY(0) scale(1) rotateX(0deg);
        }
    }
    @keyframes shimmer {
        0% { background-position: -200% 0; }
        100% { background-position: 200% 0; }
    }
    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }
    @keyframes bounce {
        0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
        40% { transform: translateY(-10px); }
        60% { transform: translateY(-5px); }
    }
    @keyframes bodySlideIn {
        0% {
            opacity: 0;
            transform: translateY(30px);
        }
        100% {
            opacity: 1;
            transform: translateY(0);
        }
    }
    @keyframes messageFadeIn {
        0% {
            opacity: 0;
            transform: scale(0.8);
        }
        100% {
            opacity: 1;
            transform: scale(1);
        }
    }
    @keyframes footerSlideUp {
        0% {
            opacity: 0;
            transform: translateY(20px);
        }
        100% {
            opacity: 1;
            transform: translateY(0);
        }
    }
    .modal-body {
        padding: 40px 45px 35px;
        text-align: center;
        position: relative;
        animation: bodySlideIn 0.8s ease-out 0.2s both;
    }
    .modal-message {
        font-size: 1.2rem;
        color: #2d3748;
        margin-bottom: 0;
        line-height: 1.7;
        font-weight: 500;
        position: relative;
        animation: messageFadeIn 1s ease-out 0.4s both;
    }
    .modal-message::before {
        content: '⚠️';
        display: block;
        font-size: 3rem;
        margin-bottom: 20px;
        animation: bounce 2s infinite;
    }
    .modal-footer {
        padding: 0 45px 40px;
        display: flex;
        gap: 20px;
        justify-content: center;
        animation: footerSlideUp 0.8s ease-out 0.6s both;
    }
    .modal-btn {
        padding: 16px 32px;
        border: none;
        border-radius: 16px;
        font-size: 1.1rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        min-width: 140px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        letter-spacing: 0.5px;
        position: relative;
        overflow: hidden;
        text-transform: uppercase;
        font-size: 0.9rem;
    }
    .modal-btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
        transition: left 0.6s;
    }
    .modal-btn:hover::before {
        left: 100%;
    }
    .modal-btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    }
    .modal-btn-primary:hover {
        transform: translateY(-4px) scale(1.05);
        box-shadow: 0 12px 35px rgba(102, 126, 234, 0.5);
    }
    .modal-btn-secondary {
        background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
        color: #4a5568;
        border: 2px solid #e2e8f0;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }
    .modal-btn-secondary:hover {
        background: linear-gradient(135deg, #edf2f7 0%, #e2e8f0 100%);
        transform: translateY(-4px) scale(1.05);
        box-shadow: 0 12px 35px rgba(0, 0, 0, 0.2);
    }
    .modal-btn-danger {
        background: linear-gradient(135deg, #e53e3e 0%, #c53030 100%);
        color: white;
        box-shadow: 0 6px 20px rgba(229, 62, 62, 0.4);
        border: 2px solid #e53e3e;
    }
    .modal-btn-danger:hover {
        transform: translateY(-4px) scale(1.05);
        box-shadow: 0 15px 40px rgba(229, 62, 62, 0.6);
        background: linear-gradient(135deg, #c53030 0%, #9c2626 100%);
    }
    .modal-btn-success {
        background: linear-gradient(135deg, #38a169 0%, #2f855a 100%);
        color: white;
        box-shadow: 0 6px 20px rgba(56, 161, 105, 0.4);
        border: 2px solid #38a169;
    }
    .modal-btn-success:hover {
        transform: translateY(-4px) scale(1.05);
        box-shadow: 0 15px 40px rgba(56, 161, 105, 0.6);
        background: linear-gradient(135deg, #2f855a 0%, #276749 100%);
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
echo "<li class='sidebar-item active'>";
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

// Main content area with sidebar
echo "<div class='admin-main-content'>";

try {
    // Get teachers role
    $teacherrole = $DB->get_record('role', ['shortname' => 'teachers'|| 'editingteacher' || 'teacher']);
    
    if (!$teacherrole) {
        echo "<div class='alert alert-warning'>";
        echo "<h4>⚠️ Teachers Role Not Found</h4>";
        echo "<p>The 'teachers' role does not exist in your system. Please create it first.</p>";
        echo "</div>";
        echo $OUTPUT->footer();
        exit;
    }
    
    // Get all teachers with their details
    $teachers = $DB->get_records_sql(
        "SELECT 
            u.id,
            u.username,
            u.firstname,
            u.lastname,
            u.email,
            u.suspended,
            u.deleted,
            u.lastaccess,
            u.timecreated,
            FROM_UNIXTIME(ra.timemodified) as role_assigned_date,
            ra.timemodified as role_timestamp
         FROM {user} u
         JOIN {role_assignments} ra ON u.id = ra.userid
         JOIN {context} ctx ON ra.contextid = ctx.id
         WHERE ra.roleid = ? AND ctx.contextlevel = ?
         AND u.deleted = 0
         ORDER BY u.firstname, u.lastname",
        [$teacherrole->id, CONTEXT_SYSTEM]
    );
    
    // Count statistics
    $total_teachers = count($teachers);
    $active_teachers = count(array_filter($teachers, function($t) { return !$t->suspended; }));
    $suspended_teachers = $total_teachers - $active_teachers;
    
    // Breadcrumb
    echo "<nav aria-label='breadcrumb'>";
    echo "<ol class='breadcrumb'>";
    echo "<li class='breadcrumb-item'><a href='{$CFG->wwwroot}/my/'>Dashboard</a></li>";
    echo "<li class='breadcrumb-item'><a href='{$CFG->wwwroot}/theme/remui_kids/admin/'>Administration</a></li>";
    echo "<li class='breadcrumb-item active'>Teachers Management</li>";
    echo "</ol>";
    echo "</nav>";
    
    // Main container
    echo "<div class='teachers-container'>";
    
    // Header
    echo "<div class='teachers-header'>";
    echo "<div>";
    echo "<h1 class='teachers-title'>Teachers Management</h1>";
    echo "<p class='teachers-subtitle'>Manage and view all teachers in your system</p>";
    echo "</div>";
    echo "<div class='teachers-stats'>";
    echo "<div class='stat-item'>";
    echo "<div class='stat-number'>$total_teachers</div>";
    echo "<div class='stat-label'>Total</div>";
    echo "</div>";
    echo "<div class='stat-item'>";
    echo "<div class='stat-number'>$active_teachers</div>";
    echo "<div class='stat-label'>Active</div>";
    echo "</div>";
    echo "<div class='stat-item'>";
    echo "<div class='stat-number'>$suspended_teachers</div>";
    echo "<div class='stat-label'>Suspended</div>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    
    // Search and filter bar
    echo "<div class='search-filter-bar'>";
    echo "<div class='search-box'>";
    echo "<input type='text' class='search-input' placeholder='Search teachers by name or email...' id='teacher-search'>";
    echo "<select class='filter-select' id='status-filter'>";
    echo "<option value='all'>All Teachers</option>";
    echo "<option value='active'>Active Only</option>";
    echo "<option value='suspended'>Suspended Only</option>";
    echo "</select>";
    echo "</div>";
    echo "<div>";
    echo "<a href='add_teacher.php' class='btn btn-primary'>";
    echo "<i class='fa fa-plus'></i> Add New Teacher";
    echo "</a>";
    echo "</div>";
    echo "</div>";
    
    if ($total_teachers > 0) {
        // Teachers table
        echo "<table class='teachers-table' id='teachers-table'>";
        echo "<thead>";
        echo "<tr>";
        echo "<th>Teacher</th>";
        echo "<th>Username</th>";
        echo "<th>Email</th>";
        echo "<th>Status</th>";
        echo "<th>Last Access</th>";
        echo "<th>Role Assigned</th>";
        echo "<th>Actions</th>";
        echo "</tr>";
        echo "</thead>";
        echo "<tbody>";
        
        foreach ($teachers as $teacher) {
            $status_class = $teacher->suspended ? 'status-suspended' : 'status-active';
            $status_text = $teacher->suspended ? 'Suspended' : 'Active';
            
            $last_access = $teacher->lastaccess ? date('M j, Y g:i A', $teacher->lastaccess) : 'Never';
            $role_assigned = date('M j, Y', $teacher->role_timestamp);
            
            // Get first letter of first name for avatar
            $avatar_letter = strtoupper(substr($teacher->firstname, 0, 1));
            
            echo "<tr>";
            echo "<td>";
            echo "<div class='teacher-info'>";
            echo "<div class='teacher-avatar'>$avatar_letter</div>";
            echo "<div>";
            echo "<div class='teacher-name'>{$teacher->firstname} {$teacher->lastname}</div>";
            echo "<div class='teacher-email'>ID: {$teacher->id}</div>";
            echo "</div>";
            echo "</div>";
            echo "</td>";
            echo "<td>{$teacher->username}</td>";
            echo "<td>{$teacher->email}</td>";
            echo "<td><span class='status-badge $status_class'>$status_text</span></td>";
            echo "<td>$last_access</td>";
            echo "<td>$role_assigned</td>";
            echo "<td>";
            echo "<div class='action-buttons'>";
            echo "<a href='view_teacher.php?id={$teacher->id}' class='btn btn-primary' title='View Profile'>";
            echo "<i class='fa fa-eye'></i> View";
            echo "</a>";
            echo "<a href='edit_teacher.php?id={$teacher->id}' class='btn btn-secondary' title='Edit Teacher'>";
            echo "<i class='fa fa-edit'></i> Edit";
            echo "</a>";
            if (!$teacher->suspended) {
                echo "<button onclick='toggleTeacherStatus({$teacher->id}, true)' class='btn btn-danger' title='Suspend Teacher'>";
                echo "<i class='fa fa-ban'></i> Suspend";
                echo "</button>";
            } else {
                echo "<button onclick='toggleTeacherStatus({$teacher->id}, false)' class='btn btn-primary' title='Activate Teacher'>";
                echo "<i class='fa fa-check'></i> Activate";
                echo "</button>";
            }
            echo "</div>";
            echo "</td>";
            echo "</tr>";
        }
        
        echo "</tbody>";
        echo "</table>";
        
    } else {
        // No teachers found
        echo "<div class='no-teachers'>";
        echo "<i class='fa fa-users'></i>";
        echo "<h3>No Teachers Found</h3>";
        echo "<p>There are no teachers with the 'teachers' role assigned at system level.</p>";
        echo "<p><a href='{$CFG->wwwroot}/admin/user.php' class='btn btn-primary'>Add Your First Teacher</a></p>";
        echo "</div>";
    }
    
    echo "</div>"; // End teachers-container
    
    // Confirmation Modal
    echo "<div id='confirmationModal' class='confirmation-modal'>";
    echo "<div class='modal-content'>";
    echo "<div class='modal-body'>";
    echo "<p class='modal-message' id='modalMessage'>Are you sure you want to perform this action?</p>";
    echo "</div>";
    echo "<div class='modal-footer'>";
    echo "<button class='modal-btn modal-btn-secondary' onclick='closeConfirmationModal()'>";
    echo "<i class='fa fa-times'></i> Cancel";
    echo "</button>";
    echo "<button class='modal-btn modal-btn-danger' id='confirmBtn' onclick='confirmAction()'>";
    echo "<i class='fa fa-check'></i> Confirm";
    echo "</button>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    echo "</div>"; // End admin-main-content
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>";
    echo "<h4>❌ Error</h4>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

// Add JavaScript for search and filter functionality
echo "<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('teacher-search');
    const statusFilter = document.getElementById('status-filter');
    const table = document.getElementById('teachers-table');
    
    if (table) {
        const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
        
        function filterTable() {
            const searchTerm = searchInput.value.toLowerCase();
            const statusValue = statusFilter.value;
            
            for (let i = 0; i < rows.length; i++) {
                const row = rows[i];
                const name = row.cells[0].textContent.toLowerCase();
                const email = row.cells[2].textContent.toLowerCase();
                const status = row.cells[3].textContent.toLowerCase();
                
                const matchesSearch = name.includes(searchTerm) || email.includes(searchTerm);
                const matchesStatus = statusValue === 'all' || 
                                    (statusValue === 'active' && status.includes('active')) ||
                                    (statusValue === 'suspended' && status.includes('suspended'));
                
                row.style.display = (matchesSearch && matchesStatus) ? '' : 'none';
            }
        }
        
        searchInput.addEventListener('input', filterTable);
        statusFilter.addEventListener('change', filterTable);
    }
    
});


// Global variables for modal
let currentUserId = null;
let currentAction = null;

function toggleTeacherStatus(userid, suspend) {
    currentUserId = userid;
    currentAction = suspend ? 'suspend' : 'activate';
    
    // Update modal content based on action
    const modalMessage = document.getElementById('modalMessage');
    const confirmBtn = document.getElementById('confirmBtn');
    
    if (suspend) {
        modalMessage.innerHTML = 'Are you sure you want to suspend this teacher?<br>They will not be able to access the system until reactivated.';
        confirmBtn.className = 'modal-btn modal-btn-danger';
        confirmBtn.innerHTML = '<i class=\"fa fa-ban\"></i> Suspend';
    } else {
        modalMessage.innerHTML = 'Are you sure you want to activate this teacher?<br>They will regain access to the system.';
        confirmBtn.className = 'modal-btn modal-btn-success';
        confirmBtn.innerHTML = '<i class=\"fa fa-check\"></i> Activate';
    }
    
    // Show modal
    document.getElementById('confirmationModal').style.display = 'block';
}

function closeConfirmationModal() {
    document.getElementById('confirmationModal').style.display = 'none';
    currentUserId = null;
    currentAction = null;
}

function confirmAction() {
    if (!currentUserId) return;
    
    const formData = new FormData();
    formData.append('action', 'toggle_status');
    formData.append('userid', currentUserId);
    
    // Show loading state
    const confirmBtn = document.getElementById('confirmBtn');
    const originalText = confirmBtn.innerHTML;
    confirmBtn.innerHTML = '<i class=\"fa fa-spinner fa-spin\"></i> Processing...';
    confirmBtn.disabled = true;
    
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // Show success message briefly
            confirmBtn.innerHTML = '<i class=\"fa fa-check\"></i> Success!';
            confirmBtn.className = 'modal-btn modal-btn-success';
            
            setTimeout(() => {
                closeConfirmationModal();
                location.reload();
            }, 1500);
        } else {
            // Show error
            confirmBtn.innerHTML = '<i class=\"fa fa-exclamation\"></i> Error';
            confirmBtn.className = 'modal-btn modal-btn-danger';
            setTimeout(() => {
                confirmBtn.innerHTML = originalText;
                confirmBtn.disabled = false;
            }, 2000);
        }
    })
    .catch(error => {
        confirmBtn.innerHTML = '<i class=\"fa fa-exclamation\"></i> Error';
        confirmBtn.className = 'modal-btn modal-btn-danger';
        setTimeout(() => {
            confirmBtn.innerHTML = originalText;
            confirmBtn.disabled = false;
        }, 2000);
    });
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('confirmationModal');
    if (event.target === modal) {
        closeConfirmationModal();
    }
}

</script>";

echo $OUTPUT->footer();
?>
