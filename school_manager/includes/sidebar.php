<?php
/**
 * School Manager Sidebar Navigation
 * Shared sidebar for all school manager pages
 * 
 * @package   theme_remui_kids
 * @copyright 2024
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG, $PAGE;

// Get current page URL to highlight active menu item
$current_url = $PAGE->url->out_as_local_url(false);

?>

<!-- School Manager Sidebar -->
<div class="school-manager-sidebar">
    <div class="sidebar-header">
        <div class="school-info">
            <h3 class="school-name"><?php echo format_string($department->name ?? 'My School'); ?></h3>
            <p class="manager-role">School Manager</p>
        </div>
    </div>
    
    <div class="sidebar-content">
        <!-- DASHBOARD Section -->
        <div class="sidebar-section">
            <h3 class="sidebar-category">DASHBOARD</h3>
            <ul class="sidebar-menu">
                <li class="sidebar-item <?php echo strpos($current_url, 'dashboard.php') !== false ? 'active' : ''; ?>">
                    <a href="<?php echo $CFG->wwwroot; ?>/theme/remui_kids/school_manager/dashboard.php" class="sidebar-link">
                        <i class="fa fa-th-large sidebar-icon"></i>
                        <span class="sidebar-text">Overview</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="<?php echo $CFG->wwwroot; ?>/theme/remui_kids/school_manager/reports.php" class="sidebar-link">
                        <i class="fa fa-chart-bar sidebar-icon"></i>
                        <span class="sidebar-text">Reports</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="<?php echo $CFG->wwwroot; ?>/theme/remui_kids/school_manager/analytics.php" class="sidebar-link">
                        <i class="fa fa-chart-line sidebar-icon"></i>
                        <span class="sidebar-text">Analytics</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- STUDENTS Section -->
        <div class="sidebar-section">
            <h3 class="sidebar-category">STUDENTS</h3>
            <ul class="sidebar-menu">
                <li class="sidebar-item <?php echo strpos($current_url, 'students.php') !== false ? 'active' : ''; ?>">
                    <a href="<?php echo $CFG->wwwroot; ?>/theme/remui_kids/school_manager/students.php" class="sidebar-link">
                        <i class="fa fa-users sidebar-icon"></i>
                        <span class="sidebar-text">All Students</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="<?php echo $CFG->wwwroot; ?>/theme/remui_kids/school_manager/student_enrollment.php" class="sidebar-link">
                        <i class="fa fa-user-plus sidebar-icon"></i>
                        <span class="sidebar-text">Enroll Students</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="<?php echo $CFG->wwwroot; ?>/theme/remui_kids/school_manager/student_progress.php" class="sidebar-link">
                        <i class="fa fa-chart-pie sidebar-icon"></i>
                        <span class="sidebar-text">Student Progress</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- TEACHERS Section -->
        <div class="sidebar-section">
            <h3 class="sidebar-category">TEACHERS</h3>
            <ul class="sidebar-menu">
                <li class="sidebar-item <?php echo strpos($current_url, 'teachers.php') !== false ? 'active' : ''; ?>">
                    <a href="<?php echo $CFG->wwwroot; ?>/theme/remui_kids/school_manager/teachers.php" class="sidebar-link">
                        <i class="fa fa-chalkboard-teacher sidebar-icon"></i>
                        <span class="sidebar-text">All Teachers</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="<?php echo $CFG->wwwroot; ?>/theme/remui_kids/school_manager/assign_teachers.php" class="sidebar-link">
                        <i class="fa fa-user-plus sidebar-icon"></i>
                        <span class="sidebar-text">Assign Teachers</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="<?php echo $CFG->wwwroot; ?>/theme/remui_kids/school_manager/teacher_performance.php" class="sidebar-link">
                        <i class="fa fa-award sidebar-icon"></i>
                        <span class="sidebar-text">Performance</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- COURSES Section -->
        <div class="sidebar-section">
            <h3 class="sidebar-category">COURSES</h3>
            <ul class="sidebar-menu">
                <li class="sidebar-item <?php echo strpos($current_url, 'courses.php') !== false ? 'active' : ''; ?>">
                    <a href="<?php echo $CFG->wwwroot; ?>/theme/remui_kids/school_manager/courses.php" class="sidebar-link">
                        <i class="fa fa-book sidebar-icon"></i>
                        <span class="sidebar-text">All Courses</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="<?php echo $CFG->wwwroot; ?>/theme/remui_kids/school_manager/course_assignments.php" class="sidebar-link">
                        <i class="fa fa-tasks sidebar-icon"></i>
                        <span class="sidebar-text">Course Assignments</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="<?php echo $CFG->wwwroot; ?>/theme/remui_kids/school_manager/course_completion.php" class="sidebar-link">
                        <i class="fa fa-graduation-cap sidebar-icon"></i>
                        <span class="sidebar-text">Completion Rates</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- SETTINGS Section -->
        <div class="sidebar-section">
            <h3 class="sidebar-category">SETTINGS</h3>
            <ul class="sidebar-menu">
                <li class="sidebar-item">
                    <a href="<?php echo $CFG->wwwroot; ?>/theme/remui_kids/school_manager/school_settings.php" class="sidebar-link">
                        <i class="fa fa-cog sidebar-icon"></i>
                        <span class="sidebar-text">School Settings</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="<?php echo $CFG->wwwroot; ?>/user/profile.php" class="sidebar-link">
                        <i class="fa fa-user-circle sidebar-icon"></i>
                        <span class="sidebar-text">My Profile</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>

<!-- Sidebar Toggle Button for Mobile -->
<button class="sidebar-toggle" onclick="toggleSchoolManagerSidebar()">
    <i class="fa fa-bars"></i>
</button>

<!-- Sidebar CSS -->
<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
    
    .school-manager-sidebar {
        position: fixed;
        top: 0;
        left: 0;
        width: 280px;
        height: 100vh;
        background: #ffffff;
        border-right: 1px solid #e9ecef;
        z-index: 1000;
        overflow-y: auto;
        box-shadow: 2px 0 10px rgba(0, 0, 0, 0.08);
    }
    
    .sidebar-header {
        padding: 2rem 1.5rem 1.5rem;
        border-bottom: 1px solid #e9ecef;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .school-info .school-name {
        font-size: 1.1rem;
        font-weight: 700;
        margin: 0 0 0.5rem 0;
        color: white;
    }
    
    .school-info .manager-role {
        font-size: 0.85rem;
        margin: 0;
        opacity: 0.9;
        color: rgba(255, 255, 255, 0.9);
    }
    
    .sidebar-content {
        padding: 1.5rem 0;
    }
    
    .sidebar-section {
        margin-bottom: 2rem;
    }
    
    .sidebar-category {
        font-size: 0.7rem;
        font-weight: 700;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 1.2px;
        margin-bottom: 0.75rem;
        padding: 0 1.5rem;
        margin-top: 0;
    }
    
    .sidebar-menu {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .sidebar-item {
        margin-bottom: 0.25rem;
    }
    
    .sidebar-link {
        display: flex;
        align-items: center;
        padding: 0.75rem 1.5rem;
        color: #495057;
        text-decoration: none;
        transition: all 0.2s ease;
        border-left: 3px solid transparent;
        font-size: 0.9rem;
    }
    
    .sidebar-link:hover {
        background-color: #f8f9fa;
        color: #667eea;
        text-decoration: none;
        border-left-color: #667eea;
    }
    
    .sidebar-icon {
        width: 20px;
        margin-right: 0.875rem;
        font-size: 1rem;
        color: #6c757d;
        transition: color 0.2s ease;
    }
    
    .sidebar-link:hover .sidebar-icon {
        color: #667eea;
    }
    
    .sidebar-item.active .sidebar-link {
        background-color: #f0f4ff;
        color: #667eea;
        border-left-color: #667eea;
        font-weight: 600;
    }
    
    .sidebar-item.active .sidebar-icon {
        color: #667eea;
    }
    
    /* Scrollbar styling */
    .school-manager-sidebar::-webkit-scrollbar {
        width: 6px;
    }
    
    .school-manager-sidebar::-webkit-scrollbar-track {
        background: #f1f1f1;
    }
    
    .school-manager-sidebar::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 3px;
    }
    
    .school-manager-sidebar::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }
    
    /* Main content area */
    .school-manager-main-content {
        margin-left: 280px;
        min-height: 100vh;
        background-color: #f8f9fa;
        padding: 2rem;
    }
    
    /* Sidebar toggle button */
    .sidebar-toggle {
        display: none;
        position: fixed;
        top: 1rem;
        left: 1rem;
        z-index: 1001;
        background: #667eea;
        color: white;
        border: none;
        width: 45px;
        height: 45px;
        border-radius: 50%;
        cursor: pointer;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
        font-size: 1.2rem;
    }
    
    /* Mobile responsive */
    @media (max-width: 768px) {
        .school-manager-sidebar {
            transform: translateX(-100%);
            transition: transform 0.3s ease;
        }
        
        .school-manager-sidebar.sidebar-open {
            transform: translateX(0);
        }
        
        .school-manager-main-content {
            margin-left: 0;
        }
        
        .sidebar-toggle {
            display: flex;
            align-items: center;
            justify-content: center;
        }
    }
</style>

<!-- Sidebar JavaScript -->
<script>
function toggleSchoolManagerSidebar() {
    const sidebar = document.querySelector('.school-manager-sidebar');
    if (sidebar) {
        sidebar.classList.toggle('sidebar-open');
    }
}

// Close sidebar when clicking outside on mobile
document.addEventListener('click', function(event) {
    const sidebar = document.querySelector('.school-manager-sidebar');
    const toggleButton = document.querySelector('.sidebar-toggle');
    
    if (!sidebar || !toggleButton) return;
    
    if (window.innerWidth <= 768 && 
        !sidebar.contains(event.target) && 
        !toggleButton.contains(event.target)) {
        sidebar.classList.remove('sidebar-open');
    }
});

// Handle window resize
window.addEventListener('resize', function() {
    const sidebar = document.querySelector('.school-manager-sidebar');
    if (!sidebar) return;
    
    if (window.innerWidth > 768) {
        sidebar.classList.remove('sidebar-open');
    }
});
</script>


