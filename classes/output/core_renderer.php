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
 * Core renderer for remui_kids theme
 *
 * @package    theme_remui_kids
 * @copyright  2025 Kodeit
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_remui_kids\output;

defined('MOODLE_INTERNAL') || die();

/**
 * Core renderer for remui_kids theme
 */
class core_renderer extends \theme_remui\output\core_renderer {

    /**
     * Render student courses template
     */
    public function render_student_courses($context) {
        return $this->render_from_template('theme_remui_kids/student_courses', $context);
    }

    /**
     * Render my courses template
     */
    public function render_my_courses($context) {
        return $this->render_from_template('theme_remui_kids/my_courses', $context);
    }

    /**
     * Ensure dropdown JavaScript is loaded on admin pages
     */
    public function ensure_dropdown_js() {
        global $PAGE;
        
        // Load dropdown fixes on admin pages
        if (strpos($PAGE->url->get_path(), '/admin/') !== false || 
            strpos($PAGE->url->get_path(), '/theme/remui_kids/admin/') !== false) {
            
            $PAGE->requires->js_call_amd('theme_remui_kids/admin_dropdown_fix', 'init');
            $PAGE->requires->js_call_amd('theme_remui_kids/bootstrap_compatibility', 'init');
        }
    }

    /**
     * Override the render method to add admin sidebar data
     */
    public function render_from_template($template, $context) {
        try {
            // Add admin sidebar data to the context
            $admin_sidebar_data = \theme_remui_kids_get_admin_sidebar_template_data();
            
            // Only proceed if we have valid data
            if (is_array($admin_sidebar_data) && !empty($admin_sidebar_data)) {
                // Convert context to array if it's an object
                if (is_object($context)) {
                    $context_array = (array) $context;
                } else {
                    $context_array = $context;
                }
                
                // Merge admin sidebar data
                $context_array = array_merge($context_array, $admin_sidebar_data);
                
                // Convert back to object if original was object
                if (is_object($context)) {
                    $context = (object) $context_array;
                } else {
                    $context = $context_array;
                }
            }
        } catch (Exception $e) {
            // Log error but don't break the page
            debugging('Admin sidebar data error: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
        
        return parent::render_from_template($template, $context);
    }
}
