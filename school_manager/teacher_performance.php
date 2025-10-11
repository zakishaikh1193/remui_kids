<?php
/**
 * School Manager - Teacher Performance
 * View teacher performance metrics
 * 
 * @package   theme_remui_kids
 * @copyright 2024
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();
$context = context_system::instance();

global $USER, $DB;

$company_user = $DB->get_record('company_users', ['userid' => $USER->id]);

if (!$company_user || $company_user->managertype != 2) {
    throw new moodle_exception('nopermissions', 'error', '', 'access school manager teacher performance page');
}

$department = $DB->get_record('department', ['id' => $company_user->departmentid]);

$PAGE->set_context($context);
$PAGE->set_url('/theme/remui_kids/school_manager/teacher_performance.php');
$PAGE->set_pagelayout('admin');
$PAGE->set_title('Teacher Performance');
$PAGE->set_heading('Teacher Performance');

echo $OUTPUT->header();
include('includes/sidebar.php');

echo '<div class="school-manager-main-content">';
?>

<div style="max-width: 1400px; margin: 0 auto; padding: 2rem;">
    <div style="background: white; border-radius: 12px; padding: 3rem; text-align: center; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);">
        <div style="font-size: 4rem; color: #667eea; margin-bottom: 1rem;">
            <i class="fa fa-award"></i>
        </div>
        <h1 style="font-size: 2rem; color: #2c3e50; margin-bottom: 1rem;">Teacher Performance</h1>
        <p style="font-size: 1.1rem; color: #6c757d; margin-bottom: 2rem;">
            Coming soon! View detailed teacher performance metrics and analytics.
        </p>
        <a href="<?php echo $CFG->wwwroot; ?>/theme/remui_kids/school_manager/teachers.php" 
           style="padding: 0.75rem 1.5rem; background: #667eea; color: white; border-radius: 8px; text-decoration: none; font-weight: 600; display: inline-block;">
            <i class="fa fa-chalkboard-teacher"></i> View Teachers
        </a>
    </div>
</div>

<?php
echo '</div>';
echo $OUTPUT->footer();
?>


