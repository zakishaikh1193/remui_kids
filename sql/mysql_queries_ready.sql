-- MySQL-compatible queries for Dashboard Statistics
-- Replace 'mdl_' with your actual table prefix if different

-- 1. TOTAL COURSES ENROLLED
SELECT COUNT(DISTINCT c.id) as total_courses
FROM mdl_course c 
JOIN mdl_enrol e ON c.id = e.courseid 
JOIN mdl_user_enrolments ue ON e.id = ue.enrolid 
WHERE ue.userid = 2 AND c.visible = 1 AND c.id > 1;

-- 2. LESSONS COMPLETED (Course Modules Completed)
SELECT COUNT(DISTINCT cmc.coursemoduleid) as lessons_completed
FROM mdl_course_modules_completion cmc 
JOIN mdl_course_modules cm ON cmc.coursemoduleid = cm.id 
JOIN mdl_course c ON cm.course = c.id 
WHERE cmc.userid = 2 AND cmc.completionstate > 0 AND c.visible = 1 AND c.id > 1;

-- 3. ACTIVITIES COMPLETED (All Completion Records)
SELECT COUNT(*) as activities_completed
FROM mdl_course_modules_completion cmc 
JOIN mdl_course_modules cm ON cmc.coursemoduleid = cm.id 
JOIN mdl_course c ON cm.course = c.id 
WHERE cmc.userid = 2 AND cmc.completionstate > 0 AND c.visible = 1 AND c.id > 1;

-- 4. TOTAL ACTIVITIES AVAILABLE
SELECT COUNT(*) as total_activities
FROM mdl_course_modules cm 
JOIN mdl_course c ON cm.course = c.id 
JOIN mdl_enrol e ON c.id = e.courseid 
JOIN mdl_user_enrolments ue ON e.id = ue.enrolid 
WHERE ue.userid = 2 AND c.visible = 1 AND c.id > 1 AND cm.completion > 0;

-- 5. OVERALL PROGRESS CALCULATION (Single Query)
SELECT 
    COUNT(*) as total_activities,
    (SELECT COUNT(*) 
     FROM mdl_course_modules_completion cmc2 
     JOIN mdl_course_modules cm2 ON cmc2.coursemoduleid = cm2.id 
     JOIN mdl_course c2 ON cm2.course = c2.id 
     WHERE cmc2.userid = 2 AND cmc2.completionstate > 0 AND c2.visible = 1 AND c2.id > 1) as completed_activities,
    ROUND(
        (SELECT COUNT(*) 
         FROM mdl_course_modules_completion cmc3 
         JOIN mdl_course_modules cm3 ON cmc3.coursemoduleid = cm3.id 
         JOIN mdl_course c3 ON cm3.course = c3.id 
         WHERE cmc3.userid = 2 AND cmc3.completionstate > 0 AND c3.visible = 1 AND c3.id > 1) * 100.0 / 
        COUNT(*), 2
    ) as progress_percentage
FROM mdl_course_modules cm 
JOIN mdl_course c ON cm.course = c.id 
JOIN mdl_enrol e ON c.id = e.courseid 
JOIN mdl_user_enrolments ue ON e.id = ue.enrolid 
WHERE ue.userid = 2 AND c.visible = 1 AND c.id > 1 AND cm.completion > 0;

-- 6. DETAILED COURSE BREAKDOWN
SELECT 
    c.id,
    c.fullname,
    c.shortname,
    COUNT(DISTINCT cm.id) as total_activities,
    COUNT(DISTINCT CASE WHEN cmc.completionstate > 0 THEN cmc.coursemoduleid END) as completed_activities,
    ROUND(COUNT(DISTINCT CASE WHEN cmc.completionstate > 0 THEN cmc.coursemoduleid END) * 100.0 / COUNT(DISTINCT cm.id), 2) as progress_percentage
FROM mdl_course c 
JOIN mdl_enrol e ON c.id = e.courseid 
JOIN mdl_user_enrolments ue ON e.id = ue.enrolid 
LEFT JOIN mdl_course_modules cm ON c.id = cm.course AND cm.completion > 0
LEFT JOIN mdl_course_modules_completion cmc ON cm.id = cmc.coursemoduleid AND cmc.userid = 2
WHERE ue.userid = 2 AND c.visible = 1 AND c.id > 1
GROUP BY c.id, c.fullname, c.shortname
ORDER BY c.fullname;

-- 7. GET ALL USERS TO TEST WITH DIFFERENT USER IDs
SELECT id, firstname, lastname, username 
FROM mdl_user 
WHERE deleted = 0 AND id > 1 
ORDER BY firstname 
LIMIT 10;

-- 8. CHECK TABLE PREFIX (if you're not sure)
SHOW TABLES LIKE '%course%';

-- INSTRUCTIONS:
-- 1. Replace 'mdl_' with your actual table prefix (check with query #8)
-- 2. Replace '2' with the actual user ID you want to test
-- 3. Run these queries in phpMyAdmin or MySQL client
-- 4. The results will show the real data for your dashboard statistics cards
