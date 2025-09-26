-- SQL Queries to Verify Course Count Accuracy
-- Run these queries to check if your dashboard course count is correct

-- 1. BASIC COUNT - What your dashboard currently shows
SELECT COUNT(*) as dashboard_course_count
FROM mdl_course 
WHERE visible = 1 
AND id > 1;

-- 2. DETAILED BREAKDOWN - See exactly what's being counted
SELECT 
    id,
    fullname,
    shortname,
    visible,
    startdate,
    enddate,
    'COUNTED AS COURSE' as status
FROM mdl_course 
WHERE visible = 1 
AND id > 1
ORDER BY fullname;

-- 3. ALL COURSES - Complete overview
SELECT 
    id,
    fullname,
    shortname,
    visible,
    FROM_UNIXTIME(timecreated) as created_date,
    CASE 
        WHEN id = 1 THEN 'Site Course (Excluded)'
        WHEN visible = 0 THEN 'Hidden (Excluded)'
        ELSE 'Counted'
    END as counted_status
FROM mdl_course 
ORDER BY id;

-- 4. COUNT BY VISIBILITY
SELECT 
    visible,
    COUNT(*) as count,
    CASE 
        WHEN visible = 1 THEN 'Visible (included in count)'
        ELSE 'Hidden (excluded from count)'
    END as status
FROM mdl_course 
GROUP BY visible;

-- 5. COURSES BY CATEGORY
SELECT 
    cc.id as category_id,
    cc.name as category_name,
    COUNT(c.id) as total_courses,
    COUNT(CASE WHEN c.visible = 1 THEN 1 END) as visible_courses,
    COUNT(CASE WHEN c.visible = 1 AND c.id > 1 THEN 1 END) as dashboard_courses
FROM mdl_course_categories cc
LEFT JOIN mdl_course c ON cc.id = c.category
GROUP BY cc.id, cc.name
ORDER BY dashboard_courses DESC;

-- 6. COURSE ENROLLMENT STATISTICS
SELECT 
    c.id,
    c.fullname,
    c.visible,
    COUNT(DISTINCT ue.userid) as enrolled_users,
    COUNT(DISTINCT CASE WHEN ue.status = 0 THEN ue.userid END) as active_enrollments
FROM mdl_course c
LEFT JOIN mdl_enrol e ON c.id = e.courseid
LEFT JOIN mdl_user_enrolments ue ON e.id = ue.enrolid
WHERE c.visible = 1 AND c.id > 1
GROUP BY c.id, c.fullname, c.visible
ORDER BY enrolled_users DESC;

-- 7. RECENT COURSES (Last 10 created)
SELECT 
    c.id,
    c.fullname,
    c.visible,
    FROM_UNIXTIME(c.timecreated) as created_date,
    CASE 
        WHEN c.id = 1 THEN 'Site Course'
        WHEN c.visible = 0 THEN 'Hidden'
        ELSE 'Counted'
    END as status
FROM mdl_course c
ORDER BY c.timecreated DESC
LIMIT 10;

-- 8. SUMMARY STATISTICS
SELECT 
    'Total Courses' as metric,
    COUNT(*) as count
FROM mdl_course

UNION ALL

SELECT 
    'Visible Courses' as metric,
    COUNT(*) as count
FROM mdl_course 
WHERE visible = 1

UNION ALL

SELECT 
    'Hidden Courses' as metric,
    COUNT(*) as count
FROM mdl_course 
WHERE visible = 0

UNION ALL

SELECT 
    'Site Course (ID=1)' as metric,
    COUNT(*) as count
FROM mdl_course 
WHERE id = 1

UNION ALL

SELECT 
    'Dashboard Course Count' as metric,
    COUNT(*) as count
FROM mdl_course 
WHERE visible = 1 
AND id > 1;

-- 9. COURSES WITH NO ENROLLMENTS
SELECT 
    'Courses with no enrollments' as issue_type,
    COUNT(*) as count
FROM mdl_course c
LEFT JOIN mdl_enrol e ON c.id = e.courseid
LEFT JOIN mdl_user_enrolments ue ON e.id = ue.enrolid
WHERE c.visible = 1 
AND c.id > 1
AND ue.id IS NULL;

-- 10. COURSES BY CREATION DATE
SELECT 
    DATE(FROM_UNIXTIME(timecreated)) as creation_date,
    COUNT(*) as courses_created,
    COUNT(CASE WHEN visible = 1 AND id > 1 THEN 1 END) as dashboard_courses
FROM mdl_course 
WHERE timecreated > 0
GROUP BY DATE(FROM_UNIXTIME(timecreated))
ORDER BY creation_date DESC
LIMIT 30;

-- 11. VERIFICATION QUERY - This should match your dashboard
SELECT 
    'VERIFICATION: Dashboard should show this number' as message,
    COUNT(*) as course_count
FROM mdl_course 
WHERE visible = 1 
AND id > 1;



