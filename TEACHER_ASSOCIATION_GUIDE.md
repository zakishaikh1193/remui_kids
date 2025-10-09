# Teacher Association Guide

## Quick Solutions for Associating Teachers with Your School/Company

### 🚀 **Method 1: Super Admin Tool (Easiest)**

1. **Login as Super Admin**
2. **Access the tool**: `http://localhost/kodeit/iomad/theme/remui_kids/super_admin_associate_teachers.php`
3. **Select your company** from the list
4. **Click "Manage Teachers"**
5. **Choose one of these options**:
   - **Add individual teachers** (click "Add to Company" next to each teacher)
   - **Add ALL teachers** (click "Add ALL Teachers to This Company")
   - **Add by grade** (click "Add Teachers by Grade (1-10)")

### 🎯 **Method 2: IOMAD Company Management**

1. **Login as Super Admin**
2. **Navigate**: Site Administration → Plugins → Local plugins → IOMAD → Company management
3. **Find your company** and click "Manage" or the company name
4. **Look for "Users" tab** or "Company Users" section
5. **Click "Add user to company"** or "Assign users"
6. **Search for teachers** by username or email
7. **Select teachers** and assign them as "User" (not "Manager")
8. **Save changes**

### 🔧 **Method 3: User Profile Management**

1. **Login as Super Admin**
2. **Navigate**: Site Administration → Users → Accounts → Browse list of users
3. **Filter by role "teacher"**
4. **Click on each teacher's name** to edit their profile
5. **Look for "Company" or "IOMAD" section**
6. **Select your school/company** from dropdown
7. **Save changes**

### 📊 **Method 4: Bulk User Actions**

1. **Login as Super Admin**
2. **Navigate**: Site Administration → Users → Accounts → Bulk user actions
3. **Select "Company assignment"** or "IOMAD assignment"
4. **Filter users by role "teacher"**
5. **Select all 10 teachers** using checkboxes
6. **Choose "Add to company"** action
7. **Select your school/company**
8. **Execute the bulk action**

## 🎯 **For Your Specific Case (10 Teachers)**

Since you have 10 teachers for Grades 1-10, I recommend:

### **Quick Solution**:
1. Use the **Super Admin Tool** (Method 1)
2. Click **"Add ALL Teachers to This Company"**
3. This will associate all 10 teachers instantly

### **Grade-Specific Solution**:
1. Use the **Super Admin Tool** (Method 1)
2. Click **"Add Teachers by Grade (1-10)"**
3. Select specific grades you want to add

## ✅ **Verification Steps**

After associating teachers:

1. **Check Dashboard**: Go to your School Manager Dashboard
2. **Click "Refresh Stats"**: The teacher count should now show 10
3. **Verify in IOMAD**: Go to IOMAD → Company management → Your company → Users

## 🔍 **Troubleshooting**

**If teachers still don't show up**:
1. Check if the teachers have the "teacher" role assigned
2. Verify the company_users table has the associations
3. Use the debug tools to check database status

**Debug Tools**:
- `simple_debug.php` - Basic verification
- `debug_database.php` - Detailed analysis
- `auto_associate_teachers.php` - Automatic association

## 📋 **Expected Results**

After successful association:
- **Total Teachers**: Should show 10
- **Enrolled Teachers**: Will show teachers actively enrolled in courses
- **Dashboard Statistics**: Will display real data instead of 0s

## 🎉 **Benefits**

- **Real-time Statistics**: Dashboard shows actual teacher counts
- **Proper Organization**: Teachers are properly linked to your school
- **Grade Management**: Teachers can be managed by grade level
- **Course Assignment**: Teachers can be assigned to specific courses
- **Reporting**: Accurate reports and analytics for your school

