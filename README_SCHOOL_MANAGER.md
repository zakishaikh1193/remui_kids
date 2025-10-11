# School Manager Dashboard System

This document explains how to use the shared sidebar system for school manager pages in the remui_kids theme.

## Overview

The school manager dashboard system provides a consistent sidebar navigation across all school manager pages. The sidebar includes:

- Green header with school branding
- Main navigation items (Dashboard, Teacher Management, Student Management)
- Management section (Course Management, Enrollments, Add Users, Bulk Upload)
- Reports & Analytics section (Analytics Dashboard, User Reports)

## Files Structure

```
iomad/theme/remui_kids/
├── templates/
│   ├── school_manager_dashboard.mustache          # Main dashboard page
│   ├── school_manager_sidebar.mustache           # Shared sidebar component
│   └── school_manager_teacher_management.mustache # Example page
├── style/
│   ├── school-manager-dashboard.css              # Dashboard specific styles
│   └── school-manager-sidebar.css                # Shared sidebar styles
├── school_manager/
│   └── teacher_management.php                    # Example PHP page
└── layout/
    └── drawers.php                               # Main layout logic
```

## Creating a New School Manager Page

### 1. Create the Mustache Template

Create a new template file in `templates/` directory:

```mustache
{{!
    Your Page Template for remui_kids theme
}}

{{> theme_remui_kids/common_start}}

<!-- Include shared sidebar styles -->
<style>
@import url('school-manager-sidebar.css');

/* Your page specific styles */
.your-page-wrapper {
    display: flex;
    min-height: 100vh;
    background-color: #f5f5f5;
    position: relative;
    margin: 0;
    padding: 0;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    overflow-x: hidden;
    z-index: 1;
}

.your-page-wrapper * {
    box-sizing: border-box;
}

.page-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 30px 20px;
}

/* Add your specific styles here */
</style>

<!-- Your Page Wrapper -->
<div class="your-page-wrapper">
    <!-- Mobile Sidebar Toggle Button -->
    <button class="sidebar-toggle" onclick="toggleSchoolManagerSidebar()">
        <i class="fa fa-bars"></i>
    </button>

    {{> theme_remui_kids/school_manager_sidebar}}

    <!-- Main Content Area with Sidebar -->
    <div class="school-manager-main-content">
        <div class="page-container">
            <!-- Your page content here -->
        </div>
    </div>
</div>

{{> theme_remui/common_end}}

{{#js}}
// Your page JavaScript
require(['jquery'], function($) {
    $(document).ready(function() {
        initializeSidebar();
        // Your page specific initialization
    });
    
    function initializeSidebar() {
        // Standard sidebar functionality
        $('.sidebar-toggle').on('click', function() {
            $('.school-manager-sidebar').toggleClass('sidebar-open');
        });
        
        $(document).on('click', function(event) {
            if (window.innerWidth <= 768) {
                if (!$(event.target).closest('.school-manager-sidebar').length && 
                    !$(event.target).closest('.sidebar-toggle').length) {
                    $('.school-manager-sidebar').removeClass('sidebar-open');
                }
            }
        });
        
        $(window).on('resize', function() {
            if (window.innerWidth > 768) {
                $('.school-manager-sidebar').removeClass('sidebar-open');
            }
        });
        
        $('.school-manager-sidebar .sidebar-link').on('click', function(e) {
            var $link = $(this);
            var href = $link.attr('href');
            
            $('.school-manager-sidebar .sidebar-item').removeClass('active');
            $link.closest('.sidebar-item').addClass('active');
            
            if (href === '#' || !href) {
                e.preventDefault();
                console.log('Navigation to:', $link.find('.sidebar-text').text());
            }
        });
    }
});
{{/js}}
```

### 2. Create the PHP File

Create a PHP file in the `school_manager/` directory:

```php
<?php
require_once('../../../config.php');
require_login();

// Check if user has school manager capabilities
$context = context_system::instance();
$isschoolmanager = false;

// Check for school manager role
$schoolmanagerroles = $DB->get_records_sql(
    "SELECT DISTINCT r.shortname
     FROM {role} r
     JOIN {role_assignments} ra ON r.id = ra.roleid
     JOIN {context} ctx ON ra.contextid = ctx.id
     WHERE ra.userid = ?
     AND ctx.contextlevel = ?
     AND r.shortname IN ('school_manager', 'manager')",
    [$USER->id, CONTEXT_SYSTEM]
);

if (!empty($schoolmanagerroles)) {
    $isschoolmanager = true;
}

// Also check for school manager capabilities
if (!$isschoolmanager && (has_capability('moodle/site:config', $context, $USER) ||
                         has_capability('moodle/user:create', $context, $USER))) {
    $isschoolmanager = true;
}

// Redirect if not a school manager
if (!$isschoolmanager) {
    redirect(new moodle_url('/my/'));
}

// Set up the page
$PAGE->set_context($context);
$PAGE->set_url('/theme/remui_kids/school_manager/your_page.php');
$PAGE->set_title('Your Page - School Manager');
$PAGE->set_heading('Your Page');

// Prepare template context
$templatecontext = [
    'your_page_active' => true, // This will make your menu item active
    'config' => [
        'wwwroot' => $CFG->wwwroot
    ]
];

// Must be called before rendering the template
require_once($CFG->dirroot . '/theme/remui/layout/common_end.php');

// Render your template
echo $OUTPUT->render_from_template('theme_remui_kids/your_template', $templatecontext);
```

### 3. Set Active State

To make a sidebar menu item active, set the corresponding variable in your template context:

- `dashboard_active` - School Admin Dashboard
- `teacher_management_active` - Teacher Management
- `student_management_active` - Student Management
- `course_management_active` - Course Management
- `enrollments_active` - Enrollments
- `add_users_active` - Add Users
- `bulk_upload_active` - Bulk Upload
- `analytics_dashboard_active` - Analytics Dashboard
- `user_reports_active` - User Reports

## Dashboard Features

The main dashboard includes:

1. **Welcome Banner** - Shows school name and user info with refresh button
2. **Statistics Cards** - Display key metrics (Total Teachers, Enrolled Teachers, Available Courses, Active Enrollments)
3. **Quick Actions** - Four action cards for common tasks
4. **Recent Activity** - Shows recent activities and updates

## Responsive Design

The sidebar automatically adapts to different screen sizes:

- **Desktop**: Sidebar is always visible
- **Tablet/Mobile**: Sidebar is hidden by default, can be toggled with hamburger menu

## Customization

### Adding New Menu Items

To add new menu items to the sidebar, edit `templates/school_manager_sidebar.mustache`:

```mustache
<li class="sidebar-item {{#your_new_item_active}}active{{/your_new_item_active}}">
    <a href="{{config.wwwroot}}/theme/remui_kids/school_manager/your_new_page.php" class="sidebar-link">
        <i class="fa fa-your-icon sidebar-icon"></i>
        <span class="sidebar-text">Your New Item</span>
    </a>
</li>
```

### Styling

- Main sidebar styles: `style/school-manager-sidebar.css`
- Dashboard specific styles: `style/school-manager-dashboard.css`
- Page specific styles: Add to your template's `<style>` section

## JavaScript Functions

The sidebar includes these JavaScript functions:

- `toggleSchoolManagerSidebar()` - Toggle sidebar visibility on mobile
- `initializeSidebar()` - Initialize sidebar functionality
- Standard event handlers for navigation and responsive behavior

## Example Usage

See `templates/school_manager_teacher_management.mustache` and `school_manager/teacher_management.php` for a complete example of how to create a new school manager page with the shared sidebar.
