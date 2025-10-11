<?php
require_once('config.php');
require_login();

global $USER, $DB, $CFG, $OUTPUT, $PAGE;

// Set up page context
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/theme/remui_kids/student_dashboard.php');
$PAGE->set_title('G4G7 Student Dashboard');
$PAGE->set_heading('G4G7 Dashboard Learning Platform');

echo $OUTPUT->header();

// Include the G4G7 Sidebar Component
include_once('components/g4g7_sidebar.php');
?>

<!-- Main Content Area -->
<div class="g4g7-main-content" style="margin-left: 280px; padding: 20px; min-height: 100vh; background: #f8fafc;">
    <div class="g4g7-content-container" style="max-width: 1200px; margin: 0 auto;">
        
        <!-- Welcome Section -->
        <div class="g4g7-welcome-section" style="background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%); color: white; padding: 40px; border-radius: 16px; margin-bottom: 30px;">
            <h1 style="font-size: 2.5rem; font-weight: 700; margin: 0 0 10px 0;">Welcome back, <?php echo htmlspecialchars($USER->firstname); ?>!</h1>
            <p style="font-size: 1.2rem; opacity: 0.9; margin: 0;">Ready to continue your learning journey?</p>
        </div>

        <!-- Quick Stats Cards -->
        <div class="g4g7-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
            
            <div class="g4g7-stat-card" style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); border-left: 4px solid #10b981;">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #10b981, #059669); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; font-size: 20px;">
                        <i class="fa fa-book"></i>
                    </div>
                    <div>
                        <h3 style="font-size: 2rem; font-weight: 700; margin: 0; color: #10b981;">5</h3>
                        <p style="color: #6b7280; margin: 0; font-weight: 500;">Active Courses</p>
                    </div>
                </div>
            </div>

            <div class="g4g7-stat-card" style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); border-left: 4px solid #f59e0b;">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #f59e0b, #d97706); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; font-size: 20px;">
                        <i class="fa fa-trophy"></i>
                    </div>
                    <div>
                        <h3 style="font-size: 2rem; font-weight: 700; margin: 0; color: #f59e0b;">12</h3>
                        <p style="color: #6b7280; margin: 0; font-weight: 500;">Achievements</p>
                    </div>
                </div>
            </div>

            <div class="g4g7-stat-card" style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); border-left: 4px solid #8b5cf6;">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #8b5cf6, #7c3aed); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; font-size: 20px;">
                        <i class="fa fa-chart-line"></i>
                    </div>
                    <div>
                        <h3 style="font-size: 2rem; font-weight: 700; margin: 0; color: #8b5cf6;">85%</h3>
                        <p style="color: #6b7280; margin: 0; font-weight: 500;">Progress</p>
                    </div>
                </div>
            </div>

            <div class="g4g7-stat-card" style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); border-left: 4px solid #ef4444;">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #ef4444, #dc2626); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; font-size: 20px;">
                        <i class="fa fa-clock"></i>
                    </div>
                    <div>
                        <h3 style="font-size: 2rem; font-weight: 700; margin: 0; color: #ef4444;">24</h3>
                        <p style="color: #6b7280; margin: 0; font-weight: 500;">Study Hours</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity Section -->
        <div class="g4g7-activity-section" style="background: white; border-radius: 16px; padding: 30px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);">
            <h2 style="font-size: 1.5rem; font-weight: 600; margin: 0 0 20px 0; color: #1f2937;">Recent Activity</h2>
            
            <div class="g4g7-activity-list">
                <div class="g4g7-activity-item" style="display: flex; align-items: center; gap: 15px; padding: 15px 0; border-bottom: 1px solid #f3f4f6;">
                    <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #10b981, #059669); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white;">
                        <i class="fa fa-check"></i>
                    </div>
                    <div style="flex: 1;">
                        <h4 style="margin: 0 0 5px 0; font-size: 14px; font-weight: 600; color: #1f2937;">Completed Math Quiz</h4>
                        <p style="margin: 0; font-size: 12px; color: #6b7280;">Mathematics - Grade 5 • 2 hours ago</p>
                    </div>
                    <span style="background: #10b981; color: white; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 500;">Completed</span>
                </div>

                <div class="g4g7-activity-item" style="display: flex; align-items: center; gap: 15px; padding: 15px 0; border-bottom: 1px solid #f3f4f6;">
                    <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #f59e0b, #d97706); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white;">
                        <i class="fa fa-book-open"></i>
                    </div>
                    <div style="flex: 1;">
                        <h4 style="margin: 0 0 5px 0; font-size: 14px; font-weight: 600; color: #1f2937;">Read Science Chapter 3</h4>
                        <p style="margin: 0; font-size: 12px; color: #6b7280;">Science - Grade 4 • 5 hours ago</p>
                    </div>
                    <span style="background: #f59e0b; color: white; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 500;">In Progress</span>
                </div>

                <div class="g4g7-activity-item" style="display: flex; align-items: center; gap: 15px; padding: 15px 0; border-bottom: 1px solid #f3f4f6;">
                    <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #8b5cf6, #7c3aed); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white;">
                        <i class="fa fa-trophy"></i>
                    </div>
                    <div style="flex: 1;">
                        <h4 style="margin: 0 0 5px 0; font-size: 14px; font-weight: 600; color: #1f2937;">Earned New Badge</h4>
                        <p style="margin: 0; font-size: 12px; color: #6b7280;">Reading Master • Yesterday</p>
                    </div>
                    <span style="background: #8b5cf6; color: white; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 500;">Achievement</span>
                </div>

                <div class="g4g7-activity-item" style="display: flex; align-items: center; gap: 15px; padding: 15px 0;">
                    <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #3b82f6, #2563eb); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white;">
                        <i class="fa fa-play"></i>
                    </div>
                    <div style="flex: 1;">
                        <h4 style="margin: 0 0 5px 0; font-size: 14px; font-weight: 600; color: #1f2937;">Started English Lesson</h4>
                        <p style="margin: 0; font-size: 12px; color: #6b7280;">English - Grade 6 • 2 days ago</p>
                    </div>
                    <span style="background: #3b82f6; color: white; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 500;">Started</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Responsive CSS for Main Content -->
<style>
@media (max-width: 768px) {
    .g4g7-main-content {
        margin-left: 0 !important;
        padding: 20px 15px !important;
    }
    
    .g4g7-welcome-section {
        padding: 25px 20px !important;
    }
    
    .g4g7-welcome-section h1 {
        font-size: 1.8rem !important;
    }
    
    .g4g7-stats-grid {
        grid-template-columns: 1fr !important;
        gap: 15px !important;
    }
    
    .g4g7-activity-section {
        padding: 20px !important;
    }
}
</style>

<?php
echo $OUTPUT->footer();
?>
