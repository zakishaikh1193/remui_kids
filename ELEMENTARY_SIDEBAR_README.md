# Elementary Sidebar for Grade 1-3 Dashboard

This document explains how to use the reusable elementary sidebar across all Grade 1-3 pages.

## Files Created

### 1. `templates/elementary_sidebar.mustache`
The main sidebar template containing all navigation elements:
- DASHBOARD section (Dashboard, My Courses, Lessons, Activities, etc.)
- TOOLS & RESOURCES section
- SETTINGS & PROFILE section  
- QUICK ACTIONS section (E-books, Ask Teacher, Share with Class, Scratch Editor)

### 2. `templates/elementary_sidebar_styles.mustache`
CSS styles for the sidebar including:
- Sidebar positioning and layout
- Navigation link styling
- Quick action cards
- Active state highlighting
- Responsive design

### 3. `lib/sidebar_helper.php`
PHP helper functions for easy sidebar integration:
- `theme_remui_kids_get_elementary_sidebar_context($current_page, $USER)` - Gets sidebar context
- `theme_remui_kids_render_elementary_sidebar($current_page, $USER)` - Renders sidebar HTML
- `theme_remui_kids_get_elementary_sidebar_styles()` - Gets sidebar styles

## How to Use

### Method 1: Using Helper Functions (Recommended)

```php
<?php
require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/theme/remui_kids/lib/sidebar_helper.php');

// Set up your page
$PAGE->set_context(context_system::instance());
$PAGE->set_url('/your/page/url.php');
$PAGE->set_pagelayout('base');
$PAGE->set_title('Your Page Title', false);

// Get sidebar context for current page
$sidebar_context = theme_remui_kids_get_elementary_sidebar_context('your_page_name', $USER);

// Prepare your template context
$templatecontext = [
    // Include sidebar context
    ...$sidebar_context,
    
    // Your page-specific content
    'your_data' => $your_data,
];

echo $OUTPUT->header();

// Include sidebar styles
echo theme_remui_kids_get_elementary_sidebar_styles();

// Render your page
echo '<div class="main-content-with-sidebar">';
echo $OUTPUT->render_from_template('your_template_name', $templatecontext);
echo '</div>';

echo $OUTPUT->footer();
?>
```

### Method 2: Manual Template Inclusion

```php
<?php
// In your PHP file, prepare context
$templatecontext = [
    // URLs for sidebar navigation
    'dashboardurl' => (new moodle_url('/my/'))->out(),
    'mycoursesurl' => (new moodle_url('/theme/remui_kids/moodle_mycourses.php'))->out(),
    'lessonsurl' => (new moodle_url('/theme/remui_kids/elementary_lessons.php'))->out(),
    'activitiesurl' => (new moodle_url('/theme/remui_kids/elementary_activities.php'))->out(),
    // ... other URLs
    
    // Active page flags
    'is_mycourses_page' => false,
    'is_lessons_page' => true, // Set to true for current page
    'is_activities_page' => false,
    // ... other flags
    
    // Your page content
    'your_data' => $your_data,
];

echo $OUTPUT->header();
echo theme_remui_kids_get_elementary_sidebar_styles();
echo '<div class="main-content-with-sidebar">';
echo $OUTPUT->render_from_template('your_template_name', $templatecontext);
echo '</div>';
echo $OUTPUT->footer();
?>
```

### In Your Mustache Template

```mustache
<!-- Include Elementary Sidebar -->
{{> theme_remui_kids/elementary_sidebar}}

<div class="page-content">
    <!-- Your page content goes here -->
    <h1>Your Page Title</h1>
    <p>Your content...</p>
</div>
```

## Page Names for Active States

Use these page names when calling `get_elementary_sidebar_context()`:

- `'dashboard'` - For main dashboard
- `'mycourses'` - For My Courses page
- `'lessons'` - For Lessons page
- `'activities'` - For Activities page
- `'achievements'` - For Achievements page
- `'competencies'` - For Competencies page
- `'schedule'` - For Schedule page
- `'settings'` - For Settings page
- `'profile'` - For Profile page
- `'scratch_emulator'` - For Scratch Editor page
- `'code_editor'` - For Code Editor page

## CSS Classes

### Main Content Area
- `.main-content-with-sidebar` - Use this class for your main content wrapper when sidebar is present

### Sidebar Elements
- `.student-sidebar` - Main sidebar container
- `.nav-link.active` - Active navigation link
- `.quick-action-card` - Quick action cards
- `.quick-action-card.active` - Active quick action card

## Example Implementation

See `examples/sidebar_usage_example.php` for a complete working example.

## Benefits

1. **Consistency** - Same sidebar across all elementary pages
2. **Maintainability** - Update sidebar in one place
3. **Reusability** - Easy to include in any page
4. **Active States** - Automatic highlighting of current page
5. **Responsive** - Works on all device sizes

## Integration with Existing Pages

To add the sidebar to existing pages:

1. Include the sidebar helper file
2. Get sidebar context for your page
3. Add sidebar styles
4. Wrap your content with `.main-content-with-sidebar`
5. Include the sidebar template in your mustache file

This ensures consistent navigation and user experience across all Grade 1-3 pages.
