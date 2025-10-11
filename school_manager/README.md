# School Manager Module - RemUI Kids Theme

## Overview

The School Manager module provides a comprehensive management system for department managers (school managers) within the IOMAD Learning Management System. This module allows school managers to oversee their department/school operations, including student management, teacher management, course oversight, and detailed reporting.

## Features

### 1. Dashboard (`dashboard.php`)
The main landing page for school managers, providing:
- **Quick Statistics**: 
  - Total students count
  - Active students (logged in within last 30 days)
  - Total teachers
  - Total courses
  - Course completion rate
  - Total enrollments

- **Quick Actions**: Direct links to:
  - Manage Students
  - Manage Teachers
  - View Reports
  - Manage Courses

- **Recent Activity**: 
  - Latest student enrollments
  - Recent course activities

### 2. Students Management (`students.php`)
Comprehensive student management interface:
- **Student List**: View all students in the department
- **Search & Filter**: 
  - Search by name, email, or username
  - Filter by activity status (all/active/inactive)
  - Pagination support (20/50/100 per page)

- **Student Information**:
  - Full name and email
  - Last access date and status
  - Enrolled courses count
  - Completed courses count

- **Actions**:
  - View individual student details
  - Export student data
  - Enroll new students

### 3. Teachers Management (`teachers.php`)
Manage teaching staff within the department:
- **Teacher Cards**: Visual card-based interface showing:
  - Teacher name and role (Editing Teacher/Teacher)
  - Number of courses teaching
  - Number of students
  - Email and last access information

- **Features**:
  - Search functionality
  - Pagination support
  - View teacher details
  - View teacher courses
  - Assign new teachers

### 4. Courses Management (`courses.php`)
Oversee all courses in the department:
- **Course Cards**: Visual representation showing:
  - Course name and short name
  - Visibility status (Visible/Hidden)
  - Enrolled students count
  - Assigned teachers count
  - Completed students count
  - Completion rate with progress bar

- **Filters**:
  - All courses
  - Visible courses only
  - Hidden courses only

- **Actions**:
  - View course details
  - Access course directly
  - Assign courses to department
  - Export course data

### 5. Reports & Analytics (`reports.php`)
Comprehensive reporting dashboard:
- **Enrollment Trends**: 
  - Line chart showing enrollment trends over last 6 months
  - Month-by-month breakdown

- **Course Completion**:
  - Bar chart comparing enrolled vs completed students per course
  - Top 10 courses by enrollment

- **Top Performing Students**:
  - Table showing top 10 students
  - Completed courses count
  - Average grades

- **Quick Reports**:
  - Student Progress Report (coming soon)
  - Teacher Activity Report (coming soon)
  - Course Performance (coming soon)
  - Custom Report Builder (coming soon)

## Technical Architecture

### Database Tables Used

1. **company_users**: Links users to companies/departments with manager type
   - `managertype = 0`: Regular user (student)
   - `managertype = 1`: Company manager
   - `managertype = 2`: Department manager (School Manager)

2. **department**: Stores department/school information
3. **company**: Company information
4. **company_course**: Links courses to departments
5. **user**: User information
6. **course**: Course data
7. **user_enrolments**: Student enrollments
8. **course_completions**: Course completion tracking
9. **role_assignments**: Role assignments for teachers

### Access Control

All school manager pages verify:
1. User is logged in
2. User has a `company_users` record
3. User's `managertype = 2` (Department Manager)
4. User belongs to a valid department

Access is denied with a Moodle exception if any condition is not met.

### File Structure

```
school_manager/
├── dashboard.php           # Main dashboard
├── students.php            # Students management
├── teachers.php            # Teachers management
├── courses.php             # Courses management
├── reports.php             # Reports and analytics
├── includes/
│   └── sidebar.php        # Shared navigation sidebar
└── README.md              # This file
```

### Templates

- `school_manager_dashboard.mustache`: Dashboard template with statistics cards

### Sidebar Navigation

The sidebar (`includes/sidebar.php`) provides consistent navigation across all school manager pages:

**Sections**:
- **Dashboard**: Overview, Reports, Analytics
- **Students**: All Students, Enroll Students, Student Progress
- **Teachers**: All Teachers, Assign Teachers, Performance
- **Courses**: All Courses, Course Assignments, Completion Rates
- **Settings**: School Settings, My Profile

## Styling

All pages use a consistent design system:
- **Color Scheme**: Purple gradient (#667eea to #764ba2)
- **Typography**: Inter font family
- **Components**: Cards, buttons, tables with modern styling
- **Responsive**: Mobile-friendly with collapsible sidebar
- **Animations**: Smooth transitions and hover effects

## AJAX Endpoints

Each page provides AJAX endpoints for dynamic data loading:

### Dashboard
- `?action=get_dashboard_stats`: Get overview statistics
- `?action=get_recent_activity`: Get recent enrollments

### Students
- `?action=get_students`: Get paginated student list
- `?action=get_student_details`: Get individual student details

### Teachers
- `?action=get_teachers`: Get paginated teacher list
- `?action=get_teacher_details`: Get individual teacher details

### Courses
- `?action=get_courses`: Get paginated course list
- `?action=get_course_details`: Get individual course details

### Reports
- `?action=get_enrollment_stats`: Get enrollment trends
- `?action=get_completion_stats`: Get completion statistics
- `?action=get_student_performance`: Get top performers

## Future Enhancements

### Planned Features
1. **Student Enrollment Module**: Bulk enroll students
2. **Teacher Assignment**: Assign teachers to courses
3. **Course Assignments**: Assign courses to department
4. **Student Progress Tracking**: Detailed progress reports
5. **Teacher Performance Metrics**: Track teaching effectiveness
6. **Custom Report Builder**: Create custom reports
7. **Export Functionality**: Export data to CSV/Excel
8. **Email Notifications**: Notify stakeholders of important events
9. **Advanced Analytics**: Predictive analytics and insights
10. **Mobile App Support**: Native mobile application

### Placeholder Pages
The following pages are referenced in the sidebar but not yet implemented:
- `analytics.php` - Advanced analytics dashboard
- `student_enrollment.php` - Bulk student enrollment
- `student_progress.php` - Detailed progress tracking
- `assign_teachers.php` - Teacher assignment interface
- `teacher_performance.php` - Teacher performance metrics
- `course_assignments.php` - Course assignment to department
- `course_completion.php` - Detailed completion tracking
- `school_settings.php` - School/department settings

## Usage

### For School Managers

1. **Login** to your IOMAD instance
2. Your account must be assigned as a **Department Manager** (managertype = 2)
3. Navigate to: `/theme/remui_kids/school_manager/dashboard.php`
4. Use the sidebar to navigate between different sections

### For Administrators

To assign a user as a School Manager:
1. Ensure the user exists in the `user` table
2. Create a record in `company_users`:
   - `userid`: User's ID
   - `companyid`: Company ID
   - `departmentid`: Department ID they should manage
   - `managertype`: Set to `2` for Department Manager

## Security

- All pages require authentication
- Role-based access control enforced
- SQL injection prevention through parameterized queries
- XSS protection via Moodle's output functions
- CSRF protection via Moodle's session management

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Mobile browsers (iOS Safari, Chrome Mobile)

## Dependencies

- **Moodle Core**: 4.0+
- **IOMAD**: Latest version
- **RemUI Kids Theme**: Parent theme
- **Chart.js**: 3.9.1+ (for reports)
- **Font Awesome**: 5.x+ (for icons)
- **Google Fonts**: Inter font family

## Troubleshooting

### Common Issues

1. **Access Denied Error**
   - Verify user has `managertype = 2` in `company_users` table
   - Check department assignment is valid

2. **No Data Showing**
   - Verify department has assigned courses in `company_course`
   - Check users are properly assigned to department in `company_users`
   - Ensure courses have enrollments

3. **Charts Not Loading**
   - Check browser console for JavaScript errors
   - Verify Chart.js CDN is accessible
   - Check AJAX endpoints are returning data

4. **Styling Issues**
   - Clear Moodle cache: Site Administration > Development > Purge all caches
   - Check browser cache
   - Verify parent theme (RemUI) is properly installed

## Support

For issues, questions, or feature requests:
1. Check this documentation
2. Review Moodle error logs
3. Contact your system administrator
4. Refer to IOMAD documentation

## License

GPL v3 or later

## Authors

RemUI Kids Theme Development Team
Copyright © 2024

## Version History

- **v1.0.0** (2024): Initial release
  - Dashboard with statistics
  - Students management
  - Teachers management
  - Courses management
  - Reports and analytics
  - Responsive sidebar navigation


