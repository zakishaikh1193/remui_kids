# School Manager Dashboard

This document explains how to access and use the School Manager Dashboard for the remui_kids theme.

## Access Information

### School Manager Credentials
Based on your provided data, you can use any of these credentials to log in as a School Manager:

| Username | Password | School Name | Role |
|----------|----------|-------------|------|
| faisal.mar | Manager@123 | Al-Faisaliah Islami | Company manager |
| laila.najec | Manager@123 | Riyadh Najed Sch | Company manager |
| omar.yara | Manager@123 | Yara International | Company manager |

### How to Access

1. **Start your WAMP server** (ensure Apache and MySQL are running)

2. **Navigate to your application:**
   ```
   http://localhost/kodeit/iomad
   ```

3. **Login with School Manager credentials:**
   - Use any of the usernames above with password `Manager@123`
   - The system will automatically detect you as a Company Manager

4. **Automatic Redirect:**
   - **School Managers are automatically redirected** to their dedicated dashboard when they access `/my/` or the main page
   - **No need to manually navigate** - the system handles this automatically
   - **Direct URL** (if needed): `http://localhost/kodeit/iomad/theme/remui_kids/school_manager_dashboard.php`

## Dashboard Features

### Sidebar Navigation
The School Manager Dashboard includes a clean white sidebar with a prominent school header and the following sections:

#### Main Navigation
- **School Admin Dashboard** - Main dashboard with statistics
- **Teacher Management** - Manage teachers in your school
- **Student Management** - Manage students and enrollments

#### Management Section
- **Course Management** - Create and manage courses
- **Enrollments** - Handle student enrollments
- **Add Users** - Create new users
- **Bulk Upload** - Upload users in bulk

#### Reports & Analytics
- **Analytics Dashboard** - View detailed reports
- **User Reports** - User management reports
- **Course Reports** - Course-related analytics

#### System
- **Settings** - System configuration
- **Help & Support** - Get assistance

### Dashboard Statistics
The main dashboard displays real-time data from the database:
- **Total Teachers** - Count of all teachers assigned to your school/company
- **Enrolled Teachers** - Count of teachers actively enrolled in courses
- **Available Courses** - Count of courses assigned to your school by super admin
- **Active Enrollments** - Count of active student enrollments in school courses

### Quick Actions
- **Add Teacher** - Complete form to add new teachers with automatic role assignment
- **Enroll Teacher** - Enroll teachers in courses
- **Create Course** - Create new courses for your school
- View Reports

## File Structure

```
remui_kids/
├── school_manager_dashboard.php          # Main dashboard page
├── school_manager_login.php              # Login redirect
├── templates/
│   ├── school_manager_dashboard.mustache # Dashboard template
│   └── school_manager_sidebar.mustache   # Sidebar template
└── SCHOOL_MANAGER_README.md              # This file
```

## Customization

The dashboard is fully customizable through:
- **Templates**: Edit `.mustache` files in the `templates/` folder
- **Styling**: CSS is embedded in the templates
- **Functionality**: Modify the PHP files for additional features

## Automatic Redirect Feature

### How It Works
- **Modified `/my/index.php`** - The main dashboard page now automatically detects Company Manager role
- **Automatic Detection** - When a school manager logs in and accesses `/my/` or the main page, they are automatically redirected to their dedicated dashboard
- **Seamless Experience** - No manual navigation required - the system handles everything automatically
- **Role-Based** - Only users with Company Manager role are redirected; other users see the normal dashboard

### Technical Details
- The redirect is implemented in `/my/index.php` right after the `require_login()` call
- Uses `user_has_role_assignment()` to check for Company Manager role
- Redirects to `/theme/remui_kids/school_manager_dashboard.php`
- A backup of the original file is saved as `index.php.backup`

## Design Features

### Clean White Sidebar
- **White background** instead of blue gradient for better readability
- **Prominent school header** with green gradient background showing school name clearly
- **Real school logo** automatically fetched from company data and displayed in circular frame
- **Fallback icon** (graduation cap) when no logo is available
- **Professional styling** with hover effects and smooth transitions

### Improved Visibility
- **Larger school name** with text shadow for better contrast
- **Enhanced manager name** display with improved typography
- **Clear role badge** showing "School Manager" status
- **Better contrast** for all text elements
- **Proper top padding** ensuring school logo and name are fully visible
- **Adequate spacing** from the top edge of the sidebar
- **Full-width content** with minimal side margins for maximum space utilization

## Notes

- The dashboard automatically detects Company Manager role
- Statistics are pulled from your IOMAD database
- All links point to existing admin pages in the `admin/` folder
- The sidebar is responsive and works on mobile devices
- **Automatic redirect** ensures school managers always land on their dedicated dashboard
- **Clean white design** with improved school branding visibility
- **Automatic logo fetching** from company database
- **Dynamic logo display** with proper fallback handling

## Add Teacher Functionality

### Features
- **Complete Teacher Form** - All necessary fields for teacher registration
- **Automatic Role Assignment** - Teachers are automatically assigned the 'teacher' role
- **Company Association** - New teachers are automatically linked to your school/company
- **Grade Level Assignment** - Teachers can be assigned to specific grade levels (1-10)
- **Form Validation** - Comprehensive validation for all required fields
- **Success Redirect** - Automatic redirect back to dashboard after successful submission

### Form Fields
- **Required**: Username, Password, First Name, Last Name, Email
- **Optional**: Phone, City, Country, Grade Level, Description/Notes
- **Auto-generated**: User ID, Creation timestamp, Company association

### Access
- **URL**: `http://localhost/kodeit/iomad/theme/remui_kids/admin/add_teacher.php`
- **Access**: School Manager dashboard → Quick Actions → "Add Teacher" button
- **Permissions**: Only company managers can add teachers

## Debugging Tools

If you're experiencing issues with the dashboard (like showing 0 counts), use these debug tools:

1. **Simple Debug**: `http://localhost/kodeit/iomad/theme/remui_kids/simple_debug.php`
   - Basic database connection test
   - User role verification
   - Company association check

2. **Full Debug**: `http://localhost/kodeit/iomad/theme/remui_kids/debug_database.php`
   - Complete database structure analysis
   - Detailed role and user information
   - Step-by-step query testing

3. **Auto-Associate Teachers**: `http://localhost/kodeit/iomad/theme/remui_kids/auto_associate_teachers.php`
   - Automatically associates all teachers with your company
   - Shows real-time processing results
   - Updates dashboard statistics immediately

4. **Manual Association Tool**: `http://localhost/kodeit/iomad/theme/remui_kids/associate_teachers.php`
   - Individual teacher association control
   - Bulk association options
   - Detailed company statistics

## Troubleshooting

If you can't access the dashboard:
1. Ensure you're logged in with Company Manager credentials
2. Check that WAMP services are running
3. Verify the database connection in `config.php`
4. Use the debug tools above to identify database issues
4. Check browser console for any JavaScript errors
