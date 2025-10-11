-- SQL Queries to Debug Student Count Issues
-- Run these queries to understand why student count is 0

-- 1. Check if student role exists
SELECT 
    'Student Role Check' as check_type,
    id,
    shortname,
    name,
    description
FROM mdl_role 
WHERE shortname = 'student';

-- 2. Show all available roles
SELECT 
    'All Roles' as check_type,
    id,
    shortname,
    name,
    description
FROM mdl_role 
ORDER BY id;

-- 3. User statistics
SELECT 
    'User Statistics' as check_type,
    COUNT(*) as total_users
FROM mdl_user

UNION ALL

SELECT 
    'Active Users (not deleted)' as check_type,
    COUNT(*) as count
FROM mdl_user 
WHERE deleted = 0

UNION ALL

SELECT 
    'Suspended Users' as check_type,
    COUNT(*) as count
FROM mdl_user 
WHERE deleted = 0 AND suspended = 1

UNION ALL

SELECT 
    'Active Non-Suspended' as check_type,
    COUNT(*) as count
FROM mdl_user 
WHERE deleted = 0 AND suspended = 0;

-- 4. Role assignment statistics
SELECT 
    'Total Role Assignments' as check_type,
    COUNT(*) as count
FROM mdl_role_assignments

UNION ALL

SELECT 
    'System Level Assignments' as check_type,
    COUNT(*) as count
FROM mdl_role_assignments ra
JOIN mdl_context ctx ON ra.contextid = ctx.id
WHERE ctx.contextlevel = 10;

-- 5. Student role assignments (if student role exists)
SELECT 
    'Total Student Assignments' as check_type,
    COUNT(*) as count
FROM mdl_role_assignments ra
JOIN mdl_role r ON ra.roleid = r.id
WHERE r.shortname = 'student'

UNION ALL

SELECT 
    'System Level Student Assignments' as check_type,
    COUNT(*) as count
FROM mdl_role_assignments ra
JOIN mdl_role r ON ra.roleid = r.id
JOIN mdl_context ctx ON ra.contextid = ctx.id
WHERE r.shortname = 'student' AND ctx.contextlevel = 10;

-- 6. Actual student users (if student role exists)
SELECT 
    u.id,
    u.username,
    u.firstname,
    u.lastname,
    u.email,
    u.suspended,
    u.deleted,
    FROM_UNIXTIME(ra.timemodified) as assigned_date,
    CASE 
        WHEN u.deleted = 1 THEN 'Deleted'
        WHEN u.suspended = 1 THEN 'Suspended'
        ELSE 'Active'
    END as status
FROM mdl_user u
JOIN mdl_role_assignments ra ON u.id = ra.userid
JOIN mdl_role r ON ra.roleid = r.id
JOIN mdl_context ctx ON ra.contextid = ctx.id
WHERE r.shortname = 'student' 
AND ctx.contextlevel = 10
ORDER BY u.firstname, u.lastname;

-- 7. Dashboard query test (what should be displayed)
SELECT 
    'Dashboard Student Count' as check_type,
    COUNT(DISTINCT u.id) as count
FROM mdl_user u
JOIN mdl_role_assignments ra ON u.id = ra.userid
JOIN mdl_role r ON ra.roleid = r.id
JOIN mdl_context ctx ON ra.contextid = ctx.id
WHERE r.shortname = 'student'
AND ctx.contextlevel = 10
AND u.deleted = 0 
AND u.suspended = 0;

-- 8. Alternative counting methods
SELECT 
    'All Student Assignments (any level)' as method,
    COUNT(DISTINCT u.id) as count
FROM mdl_user u
JOIN mdl_role_assignments ra ON u.id = ra.userid
JOIN mdl_role r ON ra.roleid = r.id
WHERE r.shortname = 'student'
AND u.deleted = 0 
AND u.suspended = 0

UNION ALL

SELECT 
    'Enrolled Users' as method,
    COUNT(DISTINCT ue.userid) as count
FROM mdl_user_enrolments ue
JOIN mdl_user u ON ue.userid = u.id
WHERE u.deleted = 0 
AND u.suspended = 0;

-- 9. Check course enrollments
SELECT 
    c.id as course_id,
    c.fullname as course_name,
    COUNT(DISTINCT ue.userid) as enrolled_students
FROM mdl_course c
LEFT JOIN mdl_enrol e ON c.id = e.courseid
LEFT JOIN mdl_user_enrolments ue ON e.id = ue.enrolid
LEFT JOIN mdl_user u ON ue.userid = u.id AND u.deleted = 0 AND u.suspended = 0
WHERE c.visible = 1 AND c.id > 1
GROUP BY c.id, c.fullname
ORDER BY enrolled_students DESC;

-- 10. Check if there are any users at all
SELECT 
    'Users with any role' as check_type,
    COUNT(DISTINCT u.id) as count
FROM mdl_user u
JOIN mdl_role_assignments ra ON u.id = ra.userid
WHERE u.deleted = 0 AND u.suspended = 0;



