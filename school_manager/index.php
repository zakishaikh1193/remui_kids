<?php
/**
 * School Manager Module - Index/Redirect
 * Redirects to the main dashboard
 * 
 * @package   theme_remui_kids
 * @copyright 2024
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../../config.php');

// Redirect to dashboard
redirect(new moodle_url('/theme/remui_kids/school_manager/dashboard.php'));


