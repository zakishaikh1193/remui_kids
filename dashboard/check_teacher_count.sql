-- SQL Queries to Check Teacher Count in Database
-- Run these queries to verify teacher users count

-- 1. Check if teacher role exists
SELECT 
    'Teacher Role Check' as check_type,
    id,
    shortname,
    name,
    description
FROM mdl_role 
WHERE shortname = 'teacher';

-- 2. Show all available roles (to see what roles exist)
SELECT 
    'All Roles' as check_type,
    id,
    shortname,
    name,
    description
FROM mdl_role 
ORDER BY id;

-- 3. Look for teacher-like roles
SELECT 
    'Teacher-like Roles' as check_type,
    id,
    shortname,
    name,
    description
FROM mdl_role 
WHERE shortname LIKE '%teacher%' 
   OR shortname LIKE '%instructor%'
   OR shortname LIKE '%educator%'
ORDER BY shortname;

-- 4. Count total teacher role assignments
SELECT 
    'Total Teacher Assignments' as check_type,
    COUNT(*) as count
FROM mdl_role_assignments ra
JOIN mdl_role r ON ra.roleid = r.id
WHERE r.shortname = 'teacher';

-- 5. Count teacher assignments at system level
SELECT 
    'System Level Teacher Assignments' as check_type,
    COUNT(*) as count
FROM mdl_role_assignments ra
JOIN mdl_role r ON ra.roleid = r.id
JOIN mdl_context ctx ON ra.contextid = ctx.id
WHERE r.shortname = 'teacher' 
AND ctx.contextlevel = 10;

-- 6. Count active teacher users (dashboard method)
SELECT 
    'Active Teacher Users (Dashboard Count)' as check_type,
    COUNT(DISTINCT u.id) as count
FROM mdl_user u
JOIN mdl_role_assignments ra ON u.id = ra.userid
JOIN mdl_role r ON ra.roleid = r.id
JOIN mdl_context ctx ON ra.contextid = ctx.id
WHERE r.shortname = 'teacher'
AND ctx.contextlevel = 10
AND u.deleted = 0 
AND u.suspended = 0;

-- 7. Show actual teacher users
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
WHERE r.shortname = 'teacher' 
AND ctx.contextlevel = 10
ORDER BY u.firstname, u.lastname;

-- 8. Check teacher assignments by context level
SELECT 
    ctx.contextlevel,
    CASE 
        WHEN ctx.contextlevel = 10 THEN 'System Level'
        WHEN ctx.contextlevel = 40 THEN 'Course Level'
        WHEN ctx.contextlevel = 50 THEN 'Course Category Level'
        ELSE 'Other Level'
    END as context_description,
    COUNT(*) as teacher_assignments
FROM mdl_role_assignments ra
JOIN mdl_role r ON ra.roleid = r.id
JOIN mdl_context ctx ON ra.contextid = ctx.id
WHERE r.shortname = 'teacher'
GROUP BY ctx.contextlevel
ORDER BY ctx.contextlevel;

-- 9. Check if there are any users with teacher role at course level
SELECT 
    'Teacher Users at Course Level' as check_type,
    COUNT(DISTINCT u.id) as count
FROM mdl_user u
JOIN mdl_role_assignments ra ON u.id = ra.userid
JOIN mdl_role r ON ra.roleid = r.id
JOIN mdl_context ctx ON ra.contextid = ctx.id
WHERE r.shortname = 'teacher'
AND ctx.contextlevel = 40
AND u.deleted = 0 
AND u.suspended = 0;

-- 10. Summary statistics
SELECT 
    'Summary Statistics' as check_type,
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
    'Active Non-Suspended Users' as check_type,
    COUNT(*) as count
FROM mdl_user 
WHERE deleted = 0 AND suspended = 0

UNION ALL

SELECT 
    'Teacher Role Exists' as check_type,
    CASE 
        WHEN COUNT(*) > 0 THEN 1 
        ELSE 0 
    END as count
FROM mdl_role 
WHERE shortname = 'teacher'

UNION ALL

SELECT 
    'Total Teacher Assignments' as check_type,
    COUNT(*) as count
FROM mdl_role_assignments ra
JOIN mdl_role r ON ra.roleid = r.id
WHERE r.shortname = 'teacher'

UNION ALL

SELECT 
    'Active Teacher Users' as check_type,
    COUNT(DISTINCT u.id) as count
FROM mdl_user u
JOIN mdl_role_assignments ra ON u.id = ra.userid
JOIN mdl_role r ON ra.roleid = r.id
JOIN mdl_context ctx ON ra.contextid = ctx.id
WHERE r.shortname = 'teacher'
AND ctx.contextlevel = 10
AND u.deleted = 0 
AND u.suspended = 0;

-- 11. Quick verification query (this should match your dashboard)
SELECT 
    'VERIFICATION: Dashboard should show this number' as message,
    COUNT(DISTINCT u.id) as teacher_count
FROM mdl_user u
JOIN mdl_role_assignments ra ON u.id = ra.userid
JOIN mdl_role r ON ra.roleid = r.id
JOIN mdl_context ctx ON ra.contextid = ctx.id
WHERE r.shortname = 'teacher'
AND ctx.contextlevel = 10
AND u.deleted = 0 
AND u.suspended = 0;



