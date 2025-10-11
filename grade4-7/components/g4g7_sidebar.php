<?php
/**
 * Grade 4-7 Student Dashboard Sidebar Component
 * Reusable sidebar for G4G7 Dashboard Learning Platform
 */

// Include this file in any page that needs the G4G7 sidebar
// Usage: include_once('components/g4g7_sidebar.php');
?>

<!-- G4G7 Student Dashboard Sidebar -->
<style>
/* G4G7 Sidebar Styles */
.g4g7-sidebar {
    position: fixed;
    left: 0;
    top: 0;
    width: 280px;
    height: 100vh;
    background: #ffffff;
    border-right: 1px solid #e5e7eb;
    box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
    z-index: 1000;
    overflow-y: auto;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
}

/* Header Section */
.g4g7-header {
    background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
    padding: 25px 20px;
    color: white;
    position: relative;
    overflow: hidden;
}

.g4g7-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20px;
    width: 100px;
    height: 100px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
}

.g4g7-logo-container {
    display: flex;
    align-items: center;
    gap: 12px;
    position: relative;
    z-index: 2;
}

.g4g7-logo {
    width: 45px;
    height: 45px;
    background: linear-gradient(45deg, #10b981, #f59e0b, #3b82f6);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow: hidden;
}

.g4g7-logo::before {
    content: 'G4G7';
    font-size: 12px;
    font-weight: bold;
    color: white;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
}

.g4g7-brand {
    flex: 1;
}

.g4g7-brand-name {
    font-size: 18px;
    font-weight: 700;
    margin: 0;
    line-height: 1.2;
}

.g4g7-brand-subtitle {
    font-size: 12px;
    font-weight: 400;
    margin: 0;
    opacity: 0.9;
    line-height: 1.3;
}

/* Navigation Content */
.g4g7-nav-content {
    padding: 0;
    background: #ffffff;
}

/* Section Headers */
.g4g7-section-header {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 20px 20px 12px 20px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #3b82f6;
    position: relative;
}

.g4g7-section-dot {
    width: 4px;
    height: 4px;
    background: #3b82f6;
    border-radius: 50%;
}

/* Navigation Items */
.g4g7-nav-item {
    margin: 0 12px;
    border-radius: 8px;
    overflow: hidden;
}

.g4g7-nav-link {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    text-decoration: none;
    color: #374151;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.2s ease;
    border-radius: 8px;
    position: relative;
}

.g4g7-nav-link:hover {
    background: #f3f4f6;
    color: #1f2937;
    text-decoration: none;
}

.g4g7-nav-link.active {
    background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
    color: white;
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
}

.g4g7-nav-link.active .g4g7-nav-icon {
    color: white;
}

.g4g7-nav-icon {
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6b7280;
    font-size: 16px;
}

.g4g7-nav-text {
    flex: 1;
}

/* Quick Actions Section */
.g4g7-quick-actions {
    padding: 20px 12px;
}

.g4g7-action-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 12px;
    transition: all 0.2s ease;
    cursor: pointer;
}

.g4g7-action-card:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
    transform: translateY(-1px);
}

.g4g7-action-content {
    display: flex;
    align-items: center;
    gap: 12px;
}

.g4g7-action-icon {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6366f1;
    font-size: 18px;
}

.g4g7-action-info {
    flex: 1;
}

.g4g7-action-title {
    font-size: 14px;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 4px 0;
}

.g4g7-action-desc {
    font-size: 12px;
    color: #6b7280;
    margin: 0;
    line-height: 1.3;
}

.g4g7-action-arrow {
    width: 24px;
    height: 24px;
    background: #e5e7eb;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6b7280;
    font-size: 12px;
    transition: all 0.2s ease;
}

.g4g7-action-card:hover .g4g7-action-arrow {
    background: #3b82f6;
    color: white;
}

/* Responsive Design */
@media (max-width: 768px) {
    .g4g7-sidebar {
        transform: translateX(-100%);
        transition: transform 0.3s ease;
    }
    
    .g4g7-sidebar.sidebar-open {
        transform: translateX(0);
    }
    
    .g4g7-sidebar-toggle {
        position: fixed;
        top: 20px;
        left: 20px;
        z-index: 1001;
        background: #3b82f6;
        color: white;
        border: none;
        border-radius: 8px;
        padding: 12px;
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    }
}

/* Scrollbar Styling */
.g4g7-sidebar::-webkit-scrollbar {
    width: 4px;
}

.g4g7-sidebar::-webkit-scrollbar-track {
    background: #f1f5f9;
}

.g4g7-sidebar::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 2px;
}

.g4g7-sidebar::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
</style>

<!-- Sidebar Toggle Button for Mobile -->
<button class="g4g7-sidebar-toggle" onclick="toggleG4G7Sidebar()" style="display: none;">
    <i class="fa fa-bars"></i>
</button>

<!-- Main Sidebar -->
<div class="g4g7-sidebar" id="g4g7-sidebar">
    <!-- Header -->
    <div class="g4g7-header">
        <div class="g4g7-logo-container">
            <div class="g4g7-logo"></div>
            <div class="g4g7-brand">
                <h1 class="g4g7-brand-name">G4G7</h1>
                <p class="g4g7-brand-subtitle">Dashboard</p>
                <p class="g4g7-brand-subtitle">Learning Platform</p>
            </div>
        </div>
    </div>

    <!-- Navigation Content -->
    <div class="g4g7-nav-content">
        <!-- Dashboard Section -->
        <div class="g4g7-section-header">
            <div class="g4g7-section-dot"></div>
            <span>DASHBOARD</span>
        </div>
        
        <div class="g4g7-nav-item">
            <a href="<?php echo $CFG->wwwroot; ?>/my/" class="g4g7-nav-link active">
                <div class="g4g7-nav-icon">
                    <i class="fa fa-th-large"></i>
                </div>
                <span class="g4g7-nav-text">Dashboard</span>
            </a>
        </div>
        
        <div class="g4g7-nav-item">
            <a href="<?php echo $CFG->wwwroot; ?>/course/" class="g4g7-nav-link">
                <div class="g4g7-nav-icon">
                    <i class="fa fa-book"></i>
                </div>
                <span class="g4g7-nav-text">My Courses</span>
            </a>
        </div>
        
        <div class="g4g7-nav-item">
            <a href="<?php echo $CFG->wwwroot; ?>/mod/lesson/" class="g4g7-nav-link">
                <div class="g4g7-nav-icon">
                    <i class="fa fa-play-circle"></i>
                </div>
                <span class="g4g7-nav-text">Lessons</span>
            </a>
        </div>
        
        <div class="g4g7-nav-item">
            <a href="<?php echo $CFG->wwwroot; ?>/mod/quiz/" class="g4g7-nav-link">
                <div class="g4g7-nav-icon">
                    <i class="fa fa-chart-line"></i>
                </div>
                <span class="g4g7-nav-text">Activities</span>
            </a>
        </div>
        
        <div class="g4g7-nav-item">
            <a href="<?php echo $CFG->wwwroot; ?>/badges/mybadges.php" class="g4g7-nav-link">
                <div class="g4g7-nav-icon">
                    <i class="fa fa-trophy"></i>
                </div>
                <span class="g4g7-nav-text">Achievements</span>
            </a>
        </div>
        
        <div class="g4g7-nav-item">
            <a href="<?php echo $CFG->wwwroot; ?>/admin/tool/lp/" class="g4g7-nav-link">
                <div class="g4g7-nav-icon">
                    <i class="fa fa-bullseye"></i>
                </div>
                <span class="g4g7-nav-text">Competencies</span>
            </a>
        </div>
        
        <div class="g4g7-nav-item">
            <a href="<?php echo $CFG->wwwroot; ?>/grade/" class="g4g7-nav-link">
                <div class="g4g7-nav-icon">
                    <i class="fa fa-graduation-cap"></i>
                </div>
                <span class="g4g7-nav-text">Grades</span>
            </a>
        </div>
        
        <div class="g4g7-nav-item">
            <a href="<?php echo $CFG->wwwroot; ?>/badges/" class="g4g7-nav-link">
                <div class="g4g7-nav-icon">
                    <i class="fa fa-shield-alt"></i>
                </div>
                <span class="g4g7-nav-text">Badges</span>
            </a>
        </div>
        
        <div class="g4g7-nav-item">
            <a href="<?php echo $CFG->wwwroot; ?>/calendar/" class="g4g7-nav-link">
                <div class="g4g7-nav-icon">
                    <i class="fa fa-calendar"></i>
                </div>
                <span class="g4g7-nav-text">Schedule</span>
            </a>
        </div>
        
        <div class="g4g7-nav-item">
            <a href="<?php echo $CFG->wwwroot; ?>/user/preferences.php" class="g4g7-nav-link">
                <div class="g4g7-nav-icon">
                    <i class="fa fa-cog"></i>
                </div>
                <span class="g4g7-nav-text">Settings</span>
            </a>
        </div>

        <!-- Tools & Resources Section -->
        <div class="g4g7-section-header">
            <div class="g4g7-section-dot"></div>
            <span>TOOLS & RESOURCES</span>
        </div>
        
        <div class="g4g7-nav-item">
            <a href="<?php echo $CFG->wwwroot; ?>/blocks/treeview/" class="g4g7-nav-link">
                <div class="g4g7-nav-icon">
                    <i class="fa fa-sitemap"></i>
                </div>
                <span class="g4g7-nav-text">Tree View</span>
            </a>
        </div>

        <!-- Quick Actions Section -->
        <div class="g4g7-section-header">
            <div class="g4g7-section-dot"></div>
            <span>QUICK ACTIONS</span>
        </div>
        
        <div class="g4g7-quick-actions">
            <div class="g4g7-action-card" onclick="window.location.href='<?php echo $CFG->wwwroot; ?>/mod/resource/'">
                <div class="g4g7-action-content">
                    <div class="g4g7-action-icon">
                        <i class="fa fa-book-open"></i>
                    </div>
                    <div class="g4g7-action-info">
                        <h4 class="g4g7-action-title">E-books</h4>
                        <p class="g4g7-action-desc">Access digital learning materials</p>
                    </div>
                    <div class="g4g7-action-arrow">
                        <i class="fa fa-chevron-right"></i>
                    </div>
                </div>
            </div>
            
            <div class="g4g7-action-card" onclick="window.location.href='<?php echo $CFG->wwwroot; ?>/message/'">
                <div class="g4g7-action-content">
                    <div class="g4g7-action-icon">
                        <i class="fa fa-comments"></i>
                    </div>
                    <div class="g4g7-action-info">
                        <h4 class="g4g7-action-title">Ask Teacher</h4>
                        <p class="g4g7-action-desc">Get help from your teachers</p>
                    </div>
                    <div class="g4g7-action-arrow">
                        <i class="fa fa-chevron-right"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for Sidebar Functionality -->
<script>
function toggleG4G7Sidebar() {
    const sidebar = document.getElementById('g4g7-sidebar');
    sidebar.classList.toggle('sidebar-open');
}

// Show/hide toggle button based on screen size
function checkScreenSize() {
    const toggleBtn = document.querySelector('.g4g7-sidebar-toggle');
    const sidebar = document.getElementById('g4g7-sidebar');
    
    if (window.innerWidth <= 768) {
        toggleBtn.style.display = 'block';
        sidebar.classList.remove('sidebar-open');
    } else {
        toggleBtn.style.display = 'none';
        sidebar.classList.add('sidebar-open');
    }
}

// Check screen size on load and resize
window.addEventListener('load', checkScreenSize);
window.addEventListener('resize', checkScreenSize);

// Close sidebar when clicking outside on mobile
document.addEventListener('click', function(event) {
    const sidebar = document.getElementById('g4g7-sidebar');
    const toggleBtn = document.querySelector('.g4g7-sidebar-toggle');
    
    if (window.innerWidth <= 768) {
        if (!sidebar.contains(event.target) && !toggleBtn.contains(event.target)) {
            sidebar.classList.remove('sidebar-open');
        }
    }
});

// Add active class to current page
document.addEventListener('DOMContentLoaded', function() {
    const currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll('.g4g7-nav-link');
    
    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href && currentPath.includes(href.replace('<?php echo $CFG->wwwroot; ?>', ''))) {
            // Remove active class from all links
            navLinks.forEach(l => l.classList.remove('active'));
            // Add active class to current link
            link.classList.add('active');
        }
    });
});
</script>
