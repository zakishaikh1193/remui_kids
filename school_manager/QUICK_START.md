# School Manager Module - Quick Start Guide

## 🚀 Getting Started

### For School Managers

1. **Access the Dashboard**
   - URL: `https://your-moodle-site.com/theme/remui_kids/school_manager/`
   - Or: `https://your-moodle-site.com/theme/remui_kids/school_manager/dashboard.php`

2. **Login Requirements**
   - You must be assigned as a **Department Manager** in IOMAD
   - Your account must have `managertype = 2` in the `company_users` table
   - You must be assigned to a valid department

### Quick Navigation

Once logged in, use the sidebar to navigate:

📊 **Dashboard** → Overview of your school  
👥 **Students** → Manage students  
👨‍🏫 **Teachers** → Manage teachers  
📚 **Courses** → View and manage courses  
📈 **Reports** → Analytics and insights  

---

## 📱 Main Features

### 1. Dashboard (`dashboard.php`)
View at a glance:
- Total students (with active count)
- Total teachers
- Total courses
- Completion rate
- Recent student enrollments

**Quick Actions**: Direct links to manage students, teachers, view reports, and courses.

---

### 2. Students Management (`students.php`)
- **Search**: Type to find students by name or email
- **Filter**: Show all, active, or inactive students
- **View**: See enrolled and completed courses for each student
- **Pagination**: Switch between 20, 50, or 100 students per page

---

### 3. Teachers Management (`teachers.php`)
- **Card View**: Visual cards showing each teacher
- **Search**: Find teachers quickly
- **Info**: See courses teaching and student count
- **Details**: View teacher email and last access

---

### 4. Courses Management (`courses.php`)
- **Filter**: Show all, visible, or hidden courses
- **Search**: Find courses by name
- **Progress**: See completion rates with visual progress bars
- **Stats**: View enrolled students, teachers, and completions

---

### 5. Reports & Analytics (`reports.php`)
- **Enrollment Trends**: 6-month chart
- **Course Completion**: Compare enrolled vs completed
- **Top Students**: See highest performers
- **Export**: Download reports (coming soon)

---

## 🎨 Interface Tips

### Sidebar
- **Desktop**: Always visible on the left
- **Mobile**: Click the menu icon (☰) to show/hide
- **Active Page**: Highlighted in purple

### Cards & Tables
- **Hover**: Cards lift up when you hover over them
- **Sorting**: Click column headers to sort tables
- **Loading**: See spinner while data loads

### Search & Filters
- **Search**: Type and results update automatically
- **Filters**: Click to activate (purple = active)
- **Reset**: Refresh page to clear filters

---

## 🔧 Troubleshooting

### "Access Denied" Error
**Problem**: You see "nopermissions" error  
**Solution**: Contact your administrator to:
- Verify you're assigned as a Department Manager
- Check your `managertype = 2` in `company_users` table
- Confirm your department assignment

### No Data Showing
**Problem**: Pages show "No students/teachers/courses found"  
**Solution**: 
- Verify your department has assigned courses in `company_course` table
- Check users are properly assigned to your department
- Ensure courses have enrollments

### Charts Not Loading
**Problem**: Charts show loading spinner indefinitely  
**Solution**:
- Check browser console for errors (F12 → Console)
- Verify Chart.js CDN is accessible
- Clear browser cache and refresh

### Styling Issues
**Problem**: Pages look broken or unstyled  
**Solution**:
- Clear Moodle cache: Site Administration → Development → Purge all caches
- Clear browser cache
- Verify RemUI parent theme is installed

---

## 📞 Getting Help

1. **Check Documentation**: See `README.md` for detailed technical info
2. **View Summary**: See `SCHOOL_MANAGER_SUMMARY.md` for implementation details
3. **Contact Support**: Reach out to your system administrator
4. **Browser Console**: Press F12 to check for JavaScript errors

---

## 🎯 Coming Soon

These features are planned for future releases:

✨ **Student Enrollment** - Bulk enroll students  
✨ **Teacher Assignment** - Assign teachers to courses  
✨ **Course Assignment** - Assign courses to your department  
✨ **Advanced Analytics** - Predictive insights  
✨ **Export Tools** - Download data to CSV/Excel  
✨ **Email Notifications** - Automated alerts  
✨ **Custom Reports** - Build your own reports  

---

## 🔐 Security Note

All pages are secured with:
- ✅ Login required
- ✅ Role-based access control
- ✅ Department-level isolation
- ✅ SQL injection prevention
- ✅ XSS protection

You can only see data for **your department** - no access to other schools' data.

---

## 📊 Quick Stats Reference

### Dashboard Statistics

| Stat | Description |
|------|-------------|
| **Total Students** | All students in your department |
| **Active Students** | Logged in within last 30 days |
| **Total Teachers** | Teachers assigned to your courses |
| **Total Courses** | Courses assigned to your department |
| **Completion Rate** | % of enrolled students who completed |

### Student Status Badges

- 🟢 **Active** = Logged in within last 30 days
- 🔴 **Inactive** = Not logged in for 30+ days

---

## 💡 Best Practices

1. **Check Dashboard Daily**: Stay updated on school statistics
2. **Monitor Active Students**: Follow up with inactive students
3. **Review Completion Rates**: Identify courses needing attention
4. **Use Search**: Faster than scrolling through lists
5. **Export Reports**: Document progress (when available)
6. **Track Trends**: Use the reports page to spot patterns

---

## 🚀 Your First Steps

1. ✅ **Login** to your account
2. ✅ **Visit Dashboard** to see overview
3. ✅ **Check Students** to see who's enrolled
4. ✅ **View Teachers** to see teaching staff
5. ✅ **Review Courses** to check course status
6. ✅ **Generate Reports** to analyze performance

---

**Need more help?** See the full README.md for detailed documentation!

**Version**: 1.0.0  
**Last Updated**: 2024


