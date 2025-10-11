<?php
/**
 * School Manager - Advanced Analytics
 * Advanced analytics and insights for school managers
 * 
 * @package   theme_remui_kids
 * @copyright 2024
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

// Require login
require_login();
$context = context_system::instance();

// Check if user is a department manager (school manager)
global $USER, $DB;

// Get user's company assignment
$company_user = $DB->get_record('company_users', ['userid' => $USER->id]);

if (!$company_user || $company_user->managertype != 2) {
    throw new moodle_exception('nopermissions', 'error', '', 'access school manager analytics page - you must be a department/school manager');
}

// Get department/school information
$department = $DB->get_record('department', ['id' => $company_user->departmentid]);
$company = $DB->get_record('company', ['id' => $company_user->companyid]);

// Set up the page
$PAGE->set_context($context);
$PAGE->set_url('/theme/remui_kids/school_manager/analytics.php');
$PAGE->set_pagelayout('admin');
$PAGE->set_title('Advanced Analytics');
$PAGE->set_heading('Advanced Analytics');

echo $OUTPUT->header();

// Include sidebar
include('includes/sidebar.php');

echo '<div class="school-manager-main-content">';
?>

<div class="analytics-page" style="max-width: 1400px; margin: 0 auto; padding: 2rem;">
    <div style="background: white; border-radius: 12px; padding: 3rem; text-align: center; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);">
        <div style="font-size: 4rem; color: #667eea; margin-bottom: 1rem;">
            <i class="fa fa-chart-line"></i>
        </div>
        <h1 style="font-size: 2rem; color: #2c3e50; margin-bottom: 1rem;">Advanced Analytics</h1>
        <p style="font-size: 1.1rem; color: #6c757d; margin-bottom: 2rem;">
            This feature is coming soon! Advanced analytics will provide deep insights into your school's performance.
        </p>
        <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
            <a href="<?php echo $CFG->wwwroot; ?>/theme/remui_kids/school_manager/reports.php" 
               style="padding: 0.75rem 1.5rem; background: #667eea; color: white; border-radius: 8px; text-decoration: none; font-weight: 600;">
                <i class="fa fa-chart-bar"></i> View Basic Reports
            </a>
            <a href="<?php echo $CFG->wwwroot; ?>/theme/remui_kids/school_manager/dashboard.php" 
               style="padding: 0.75rem 1.5rem; background: #6c757d; color: white; border-radius: 8px; text-decoration: none; font-weight: 600;">
                <i class="fa fa-home"></i> Back to Dashboard
            </a>
        </div>
    </div>
</div>

<?php
echo '</div>'; // Close school-manager-main-content
echo $OUTPUT->footer();
?>


