-- SQL Queries to Check School Count in Database
-- Run these queries in your MySQL database to verify school counts

-- 1. Basic count of all course categories (original method)
SELECT COUNT(*) as total_categories 
FROM mdl_course_categories 
WHERE visible = 1;

-- 2. Improved school count (excluding system categories)
SELECT COUNT(*) as meaningful_schools
FROM mdl_course_categories 
WHERE visible = 1 
AND id > 1 
AND (name NOT LIKE '%Miscellaneous%' 
     AND name NOT LIKE '%Default%' 
     AND name NOT LIKE '%System%'
     AND name NOT LIKE '%General%')
AND parent = 0;

-- 3. Detailed view of all course categories
SELECT 
    id,
    name,
    description,
    parent,
    visible,
    CASE 
        WHEN parent = 0 THEN 'Top Level'
        ELSE 'Sub Category'
    END as category_type
FROM mdl_course_categories 
WHERE visible = 1
ORDER BY parent, name;

-- 4. Count by category type
SELECT 
    CASE 
        WHEN parent = 0 THEN 'Top Level Categories'
        ELSE 'Sub Categories'
    END as category_type,
    COUNT(*) as count
FROM mdl_course_categories 
WHERE visible = 1 AND id > 1
GROUP BY 
    CASE 
        WHEN parent = 0 THEN 'Top Level Categories'
        ELSE 'Sub Categories'
    END;

-- 5. School-like categories (top level only)
SELECT 
    id,
    name,
    description,
    'School/Organization' as type
FROM mdl_course_categories 
WHERE visible = 1 
AND id > 1 
AND parent = 0
AND (name NOT LIKE '%Miscellaneous%' 
     AND name NOT LIKE '%Default%' 
     AND name NOT LIKE '%System%'
     AND name NOT LIKE '%General%')
ORDER BY name;

-- 6. Total courses in each school category
SELECT 
    cc.id,
    cc.name as school_name,
    COUNT(c.id) as total_courses
FROM mdl_course_categories cc
LEFT JOIN mdl_course c ON cc.id = c.category AND c.visible = 1 AND c.id > 1
WHERE cc.visible = 1 
AND cc.id > 1 
AND cc.parent = 0
AND (cc.name NOT LIKE '%Miscellaneous%' 
     AND cc.name NOT LIKE '%Default%' 
     AND cc.name NOT LIKE '%System%'
     AND cc.name NOT LIKE '%General%')
GROUP BY cc.id, cc.name
ORDER BY total_courses DESC;

-- 7. Summary statistics
SELECT 
    'Total Categories' as metric,
    COUNT(*) as count
FROM mdl_course_categories 
WHERE visible = 1

UNION ALL

SELECT 
    'Top Level Categories' as metric,
    COUNT(*) as count
FROM mdl_course_categories 
WHERE visible = 1 AND parent = 0

UNION ALL

SELECT 
    'Meaningful Schools' as metric,
    COUNT(*) as count
FROM mdl_course_categories 
WHERE visible = 1 
AND id > 1 
AND (name NOT LIKE '%Miscellaneous%' 
     AND name NOT LIKE '%Default%' 
     AND name NOT LIKE '%System%'
     AND name NOT LIKE '%General%')
AND parent = 0

UNION ALL

SELECT 
    'Total Courses' as metric,
    COUNT(*) as count
FROM mdl_course 
WHERE visible = 1 AND id > 1;

