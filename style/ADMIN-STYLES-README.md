# Admin Shared Styles Documentation

## Overview
This document explains the shared CSS styling system for all admin pages in the Remui Kids theme.

## What Was Created

### 1. **admin-shared.css** - Main Shared Stylesheet
**Location:** `/theme/remui_kids/style/admin-shared.css`

This file contains all the consistent styling for admin pages including:
- ✅ **Pastel Light Purple Color Scheme** (#f3e5f5, #ce93d8, #ba68c8, #7b1fa2)
- ✅ **No Animations** (all keyframe animations removed or simplified)
- ✅ **Wider Wrappers** (max-width increased to 1600px)
- ✅ **Consistent Components** (buttons, forms, tables, cards, etc.)

### 2. **admin_shared_styles.mustache** - Include Template
**Location:** `/theme/remui_kids/templates/admin_shared_styles.mustache`

This is a Mustache partial template that includes the shared CSS file and can be easily added to any admin template.

## Color Palette

### Primary Colors
- **Background Gradient:** `#f3e5f5` → `#e1bee7` (Light Purple)
- **Header Gradient:** `#ce93d8` → `#ba68c8` (Medium Purple)
- **Primary Text:** `#7b1fa2` (Deep Purple)
- **Secondary Text:** `#9575cd` (Medium Purple)

### Status Colors
- **Success:** `#c8e6c9` / `#2e7d32` (Light/Dark Green)
- **Warning:** `#fff9c4` / `#f57c00` (Light/Dark Orange)
- **Error:** `#ffcdd2` / `#c62828` (Light/Dark Red)
- **Info:** `#e1bee7` / `#7b1fa2` (Light/Dark Purple)

## Components Included

### Layout
- `.create-user-page`, `.detail-container`, `.upload-users-container`, etc.
- `.page-header`, `.detail-header`
- `.form-container`, `.detail-content`

### Forms
- `.form-input`, `.form-label`, `.form-section`
- Input fields (text, email, password, number, date)
- Textareas and select dropdowns
- Radio buttons and checkboxes

### Buttons
- `.btn-primary` - Purple gradient button
- `.btn-secondary` - Solid purple button
- `.btn-back` - Transparent back button

### Cards & Stats
- `.stats-card`, `.card`, `.user-card`, `.event-card`
- `.stat-icon`, `.stat-value`, `.stat-label`

### Tables
- `.data-table` with purple gradient headers
- Hover effects on rows
- Rounded corners

### UI Elements
- `.badge` - Status badges (success, warning, error, info)
- `.modal-overlay`, `.modal-content` - Modal dialogs
- `.alert` - Alert messages
- `.pagination` - Page navigation
- `.tabs` - Tab navigation
- `.dropdown` - Dropdown menus

## Files Updated

The following template files now include the shared styles:

1. ✅ **create_user.mustache** - User creation page
2. ✅ **detail_active_users.mustache** - Active users details
3. ✅ **detail_department_managers.mustache** - Department managers details
4. ✅ **detail_pending_approvals.mustache** - Pending approvals details
5. ✅ **detail_recent_uploads.mustache** - Recent uploads details
6. ✅ **upload_users.mustache** - Upload users page
7. ✅ **bulk_download.mustache** - Bulk download page
8. ✅ **assign_school.mustache** - Assign users to school
9. ✅ **training_events.mustache** - Training events management

## How to Use

### Adding Shared Styles to a New Template

Simply add this line at the very top of your Mustache template file:

```mustache
{{> theme_remui_kids/admin_shared_styles }}
```

### Example

```mustache
{{> theme_remui_kids/admin_shared_styles }}

<div class="my-admin-page">
    <div class="page-header">
        <div class="header-content">
            <div class="header-icon">
                <i class="fa fa-users"></i>
            </div>
            <div class="header-text">
                <h1 class="page-title">My Admin Page</h1>
                <p class="page-subtitle">Description goes here</p>
            </div>
        </div>
    </div>
    
    <div class="form-container">
        <!-- Your content here -->
    </div>
</div>
```

## Customization

### Overriding Styles

If you need to override any shared styles for a specific page, add inline styles AFTER the shared styles include:

```mustache
{{> theme_remui_kids/admin_shared_styles }}

<style>
/* Your custom overrides here */
.page-header {
    background: linear-gradient(135deg, #custom1, #custom2);
}
</style>
```

### Adding New Components

To add new component styles that all pages should use:

1. Open `/theme/remui_kids/style/admin-shared.css`
2. Add your new styles at the bottom
3. All templates will automatically get the new styles

## Benefits

✅ **Consistency** - All admin pages have the same look and feel
✅ **Maintainability** - Change one file to update all pages
✅ **Performance** - Shared CSS file is cached by browsers
✅ **Scalability** - Easy to add new admin pages with consistent styling
✅ **Professional** - Clean pastel purple theme without distracting animations
✅ **Responsive** - Mobile-friendly design included

## Responsive Design

The shared styles include responsive breakpoints:

- **Desktop:** Full width up to 1600px
- **Tablet:** Adjusted layouts at 768px
- **Mobile:** Optimized for small screens below 768px

## Browser Support

- ✅ Chrome/Edge (latest)
- ✅ Firefox (latest)
- ✅ Safari (latest)
- ✅ Mobile browsers

## Updates & Maintenance

When making changes to the shared styles:

1. Edit `/theme/remui_kids/style/admin-shared.css`
2. Clear Moodle cache: **Site administration → Development → Purge all caches**
3. Hard refresh your browser: `Ctrl+F5` (Windows) or `Cmd+Shift+R` (Mac)

## Support

If you need to add custom styling to a specific page that shouldn't affect others:

1. Add a unique class to your page container
2. Use that class as a prefix for your custom styles
3. This ensures your changes only affect that specific page

Example:
```css
.my-unique-page .page-header {
    /* Custom styling just for this page */
}
```

---

**Created:** Today
**Version:** 1.0
**Theme:** Remui Kids
**Purpose:** Unified admin interface styling

