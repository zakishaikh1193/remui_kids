-- SQL Queries to Verify School Count Accuracy
-- Run these queries to check if your dashboard count is correct

-- 1. BASIC COUNT - What your dashboard currently shows
SELECT COUNT(*) as dashboard_school_count
FROM mdl_course_categories 
WHERE visible = 1 
AND id > 1 
AND parent = 0;

-- 2. DETAILED BREAKDOWN - See exactly what's being counted
SELECT 
    id,
    name,
    description,
    parent,
    visible,
    'COUNTED AS SCHOOL' as status
FROM mdl_course_categories 
WHERE visible = 1 
AND id > 1 
AND parent = 0
ORDER BY name;

-- 3. ALL CATEGORIES - Complete overview
SELECT 
    id,
    name,
    parent,
    visible,
    CASE 
        WHEN parent = 0 THEN 'TOP LEVEL (School)'
        ELSE 'SUB CATEGORY'
    END as category_type,
    CASE 
        WHEN visible = 1 AND id > 1 AND parent = 0 THEN 'COUNTED'
        ELSE 'NOT COUNTED'
    END as counted_status
FROM mdl_course_categories 
ORDER BY parent, name;

-- 4. COUNT BY TYPE
SELECT 
    CASE 
        WHEN parent = 0 THEN 'Top Level Categories (Schools)'
        ELSE 'Sub Categories'
    END as category_type,
    COUNT(*) as count,
    CASE 
        WHEN parent = 0 AND visible = 1 AND id > 1 THEN 'These are counted as schools'
        ELSE 'These are NOT counted as schools'
    END as explanation
FROM mdl_course_categories 
GROUP BY 
    CASE 
        WHEN parent = 0 THEN 'Top Level Categories (Schools)'
        ELSE 'Sub Categories'
    END;

-- 5. VISIBILITY BREAKDOWN
SELECT 
    visible,
    COUNT(*) as count,
    CASE 
        WHEN visible = 1 THEN 'Visible (included in count)'
        ELSE 'Hidden (excluded from count)'
    END as status
FROM mdl_course_categories 
WHERE id > 1
GROUP BY visible;

-- 6. PARENT-CHILD RELATIONSHIPS
SELECT 
    p.id as parent_id,
    p.name as parent_name,
    COUNT(c.id) as child_count
FROM mdl_course_categories p
LEFT JOIN mdl_course_categories c ON p.id = c.parent
WHERE p.visible = 1 AND p.id > 1
GROUP BY p.id, p.name
ORDER BY child_count DESC;

-- 7. COURSES PER SCHOOL CATEGORY
SELECT 
    cc.id,
    cc.name as school_name,
    COUNT(c.id) as total_courses,
    COUNT(CASE WHEN c.visible = 1 THEN 1 END) as visible_courses
FROM mdl_course_categories cc
LEFT JOIN mdl_course c ON cc.id = c.category
WHERE cc.visible = 1 
AND cc.id > 1 
AND cc.parent = 0
GROUP BY cc.id, cc.name
ORDER BY total_courses DESC;

-- 8. SUMMARY STATISTICS
SELECT 
    'Total Categories' as metric,
    COUNT(*) as count
FROM mdl_course_categories

UNION ALL

SELECT 
    'Visible Categories' as metric,
    COUNT(*) as count
FROM mdl_course_categories 
WHERE visible = 1

UNION ALL

SELECT 
    'Top Level Categories' as metric,
    COUNT(*) as count
FROM mdl_course_categories 
WHERE parent = 0

UNION ALL

SELECT 
    'Dashboard School Count' as metric,
    COUNT(*) as count
FROM mdl_course_categories 
WHERE visible = 1 
AND id > 1 
AND parent = 0

UNION ALL

SELECT 
    'Total Courses' as metric,
    COUNT(*) as count
FROM mdl_course 
WHERE visible = 1 AND id > 1;

-- 9. POTENTIAL ISSUES CHECK
SELECT 
    'Categories with no courses' as issue_type,
    COUNT(*) as count
FROM mdl_course_categories cc
LEFT JOIN mdl_course c ON cc.id = c.category
WHERE cc.visible = 1 
AND cc.id > 1 
AND cc.parent = 0
AND c.id IS NULL

UNION ALL

SELECT 
    'Empty categories' as issue_type,
    COUNT(*) as count
FROM mdl_course_categories 
WHERE visible = 1 
AND id > 1 
AND parent = 0
AND (name = '' OR name IS NULL);

-- 10. VERIFICATION QUERIES - These should match your dashboard

-- Schools count
SELECT 
    'VERIFICATION: Dashboard Schools' as metric,
    COUNT(*) as count
FROM mdl_course_categories 
WHERE visible = 1 
AND id > 1 
AND parent = 0;

-- Courses count
SELECT 
    'VERIFICATION: Dashboard Courses' as metric,
    COUNT(*) as count
FROM mdl_course 
WHERE visible = 1 
AND id > 1;

-- Students count
SELECT 
    'VERIFICATION: Dashboard Students' as metric,
    COUNT(DISTINCT u.id) as count
FROM mdl_user u 
JOIN mdl_role_assignments ra ON u.id = ra.userid 
JOIN mdl_context ctx ON ra.contextid = ctx.id 
JOIN mdl_role r ON ra.roleid = r.id
WHERE ctx.contextlevel = 10 
AND r.shortname = 'student'
AND u.deleted = 0 
AND u.suspended = 0;
