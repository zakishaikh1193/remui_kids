# Course Assignment Fix - Analysis and Solution

## Problem Summary

When assigning courses to schools using the custom `assign_to_school.php` page:
1. ✅ Assignment showed as "successful" initially
2. ✅ Reload of custom page showed course as assigned
3. ❌ IOMAD's official page (`company_courses_form.php`) showed NO courses assigned
4. ❌ After that, custom page also showed NO courses assigned

## Root Cause Analysis

### Issue #1: Direct Database Manipulation (CRITICAL)
The custom page was performing **direct database insertions** instead of using IOMAD's official methods:

**Old Code (WRONG):**
```php
// Direct DB insertion
$company_course = new stdClass();
$company_course->companyid = $school_id;
$company_course->courseid = $course_id;
$company_course->departmentid = $departmentid;
$DB->insert_record('company_course', $company_course);
```

**Problem:** This bypasses IOMAD's internal logic and doesn't trigger cache purges.

### Issue #2: Missing Cache Purge (CRITICAL)
IOMAD uses **extensive caching** for course lists. After ANY course assignment/removal, IOMAD calls:
```php
cache_helper::purge_by_event('changesincompanycourses');
```

The custom page was NOT purging this cache, so:
- Immediate reload showed stale cached data (appeared to work)
- IOMAD's page used different cache keys or no cache, showed actual DB state (no courses)
- Subsequent reloads of custom page used the purged/refreshed cache (no courses)

### Issue #3: Missing Manager/Educator Enrollment
IOMAD's `add_course()` method automatically enrolls company managers and educators in newly assigned courses based on configuration. The custom page was skipping this step.

## The Solution

### For Course Assignment (`assign_course`)

**BEFORE (Direct DB manipulation):**
```php
// Manual transaction
$transaction = $DB->start_delegated_transaction();
$DB->insert_record('company_course', $company_course);
$DB->insert_record('iomad_courses', $iomad_course);
$transaction->allow_commit();
// NO CACHE PURGE!
```

**AFTER (Using IOMAD's official method):**
```php
require_once($CFG->dirroot . '/local/iomad/lib/company.php');
$company = new company($school_id);
$parentlevel = company::get_company_parentnode($school_id);

// This ONE method does EVERYTHING:
// 1. Inserts into company_course
// 2. Ensures iomad_courses record exists
// 3. Auto-enrolls managers/educators
// 4. PURGES THE CACHE (critical!)
$result = $company->add_course($course, $parentlevel->id);
```

### For Course Removal (`unassign_course`)

**BEFORE (Direct DB deletion):**
```php
$transaction = $DB->start_delegated_transaction();
$DB->delete_records('company_course', [...]);
// Manual cleanup of iomad_courses
$transaction->allow_commit();
// NO CACHE PURGE!
```

**AFTER (Using IOMAD's official method):**
```php
require_once($CFG->dirroot . '/local/iomad/lib/company.php');

// This ONE method does EVERYTHING:
// 1. Removes from company_course
// 2. Handles shared courses properly
// 3. Removes from licenses
// 4. Unenrolls users if needed
// 5. PURGES THE CACHE (critical!)
$result = company::remove_course($course, $school_id, 0);
```

## What IOMAD's Official Methods Do

### `company::add_course($course, $departmentid)`
Located: `iomad/local/iomad/lib/company.php:706`

1. ✅ Validates department ID (uses parent level if 0)
2. ✅ Checks if course already assigned
3. ✅ Inserts into `company_course` table
4. ✅ Creates/updates `iomad_courses` record
5. ✅ Auto-enrolls company managers with proper roles
6. ✅ Auto-enrolls educators if configured
7. ✅ **Calls `cache_helper::purge_by_event('changesincompanycourses')`**

### `company::remove_course($course, $companyid, $departmentid)`
Located: `iomad/local/iomad/lib/company.php:887`

1. ✅ Starts transaction for data integrity
2. ✅ Removes from `company_course` table
3. ✅ Handles `company_created_courses` cleanup
4. ✅ Manages shared course logic properly
5. ✅ Removes from company licenses
6. ✅ Unenrolls all company users from course
7. ✅ **Calls `cache_helper::purge_by_event('changesincompanycourses')`**

## Testing Checklist

After this fix, verify:

1. ✅ Assign a course using custom page → Shows as assigned
2. ✅ Reload custom page → Course still shows as assigned
3. ✅ Go to IOMAD's official page → **Course now appears in assigned list**
4. ✅ Reload custom page again → Course still shows as assigned
5. ✅ Remove course using custom page → Shows as removed
6. ✅ Check IOMAD's official page → Course appears in available list
7. ✅ Check custom page → Course appears in potential courses list

## Key Lessons

1. **NEVER bypass framework methods** - Always use the official API methods
2. **Understand caching** - Modern systems use extensive caching; direct DB operations break this
3. **Follow the framework pattern** - IOMAD has established patterns; replicate them exactly
4. **Check official implementation** - When in doubt, look at how IOMAD's own pages do it

## Files Modified

- `iomad/theme/remui_kids/admin/assign_to_school.php`
  - Line 306-371: Replaced direct DB insertion with `$company->add_course()`
  - Line 373-425: Replaced direct DB deletion with `company::remove_course()`

## No Changes Needed In

- Course retrieval logic (lines 66-197) - Already correct
- Department list building - Already using correct IOMAD method
- SQL queries for fetching courses - Already matches IOMAD's approach

