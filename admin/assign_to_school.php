<?php
/**
 * Assign Courses to School - Modern UI
 * Beautiful animated interface for managing school course assignments
 */

require_once('../../../config.php');
require_login();

// Check admin capabilities
$context = context_system::instance();
require_capability('moodle/site:config', $context);

// Get current user
global $USER, $DB, $OUTPUT;

// Handle AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['action']) {
        case 'get_schools':
            try {
                // Use course categories as schools with proper filtering (same as your working code)
                $schools = $DB->get_records_sql(
                    "SELECT id, name 
                     FROM {course_categories} 
                     WHERE visible = 1 
                     AND id > 1 
                     AND (name NOT LIKE '%Miscellaneous%' 
                          AND name NOT LIKE '%Default%' 
                          AND name NOT LIKE '%System%'
                          AND name NOT LIKE '%General%')
                     AND parent = 0
                     ORDER BY name ASC",
                    []
                );
                
                // If no schools found, return empty array
                if (empty($schools)) {
                    $schools = [];
                }
                
                echo json_encode(['status' => 'success', 'schools' => array_values($schools)]);
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => 'Failed to load schools: ' . $e->getMessage()]);
            }
            exit;
            
        case 'get_school_courses':
            $school_id = intval($_GET['school_id']);
            try {
                // Get courses that are already assigned to this school (category)
                $courses = $DB->get_records_sql(
                    "SELECT c.*, cc.name as category_name 
                     FROM {course} c 
                     LEFT JOIN {course_categories} cc ON c.category = cc.id 
                     WHERE c.visible = 1 
                     AND c.id > 1 
                     AND c.category = ?
                     ORDER BY c.fullname ASC",
                    [$school_id]
                );
                echo json_encode(['status' => 'success', 'courses' => array_values($courses)]);
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => 'Failed to load school courses: ' . $e->getMessage()]);
            }
            exit;
            
        case 'get_potential_courses':
            $school_id = intval($_GET['school_id']);
            try {
                // Get courses that are NOT assigned to this school (available to assign)
                $courses = $DB->get_records_sql(
                    "SELECT c.*, cc.name as category_name 
                     FROM {course} c 
                     LEFT JOIN {course_categories} cc ON c.category = cc.id 
                     WHERE c.visible = 1 
                     AND c.id > 1 
                     AND c.category != ?
                     AND c.category > 1
                     ORDER BY c.fullname ASC",
                    [$school_id]
                );
                echo json_encode(['status' => 'success', 'courses' => array_values($courses)]);
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => 'Failed to load potential courses: ' . $e->getMessage()]);
            }
            exit;
            
        case 'assign_course':
            $school_id = intval($_POST['school_id']);
            $course_id = intval($_POST['course_id']);
            
            try {
                // Move course to the selected school category
                $course = $DB->get_record('course', ['id' => $course_id]);
                if ($course) {
                    $course->category = $school_id;
                    $course->timemodified = time();
                    if ($DB->update_record('course', $course)) {
                        echo json_encode(['status' => 'success', 'message' => 'Course assigned successfully']);
                    } else {
                        echo json_encode(['status' => 'error', 'message' => 'Failed to assign course']);
                    }
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Course not found']);
                }
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => 'Failed to assign course: ' . $e->getMessage()]);
            }
            exit;
            
        case 'unassign_course':
            $school_id = intval($_POST['school_id']);
            $course_id = intval($_POST['course_id']);
            
            try {
                // Move course to a default category (category 1 or first available category)
                $course = $DB->get_record('course', ['id' => $course_id]);
                if ($course) {
                    // Find a default category to move the course to
                    $default_category = $DB->get_record('course_categories', ['id' => 1]);
                    if (!$default_category) {
                        // If category 1 doesn't exist, get the first available category
                        $default_category = $DB->get_record_sql(
                            "SELECT * FROM {course_categories} WHERE visible = 1 ORDER BY id ASC LIMIT 1"
                        );
                    }
                    
                    if ($default_category) {
                        $course->category = $default_category->id;
                        $course->timemodified = time();
                        if ($DB->update_record('course', $course)) {
                            echo json_encode(['status' => 'success', 'message' => 'Course unassigned successfully']);
                        } else {
                            echo json_encode(['status' => 'error', 'message' => 'Failed to unassign course']);
                        }
                    } else {
                        echo json_encode(['status' => 'error', 'message' => 'No default category found']);
                    }
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Course not found']);
                }
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => 'Failed to unassign course: ' . $e->getMessage()]);
            }
            exit;
    }
}

// Get all schools from database using the same logic as quick_school_count.php
try {
    // Use course categories as schools with proper filtering (same as your working code)
    $schools = $DB->get_records_sql(
        "SELECT id, name 
         FROM {course_categories} 
         WHERE visible = 1 
         AND id > 1 
         AND (name NOT LIKE '%Miscellaneous%' 
              AND name NOT LIKE '%Default%' 
              AND name NOT LIKE '%System%'
              AND name NOT LIKE '%General%')
         AND parent = 0
         ORDER BY name ASC",
        []
    );
    
    // If no schools found, return empty array
    if (empty($schools)) {
        $schools = [];
    }
} catch (Exception $e) {
    // If all fails, return empty array
    $schools = [];
}

$PAGE->set_context($context);
$PAGE->set_url('/theme/remui_kids/admin/assign_to_school.php');
$PAGE->set_title('Assign Courses to School');
$PAGE->set_heading('Assign Courses to School');

echo $OUTPUT->header();
?>

<style>
/* Modern Assign to School Page Styles */
.assign-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
}

.assign-header {
    text-align: center;
    margin-bottom: 40px;
    color: white;
    position: relative;
}

.assign-header h1 {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 10px;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
    animation: titleGlow 2s ease-in-out infinite alternate;
}

.assign-header p {
    font-size: 1.1rem;
    opacity: 0.9;
    margin-bottom: 30px;
}

@keyframes titleGlow {
    from { text-shadow: 2px 2px 4px rgba(0,0,0,0.3), 0 0 20px rgba(255,255,255,0.3); }
    to { text-shadow: 2px 2px 4px rgba(0,0,0,0.3), 0 0 30px rgba(255,255,255,0.6); }
}

/* School Selection */
.school-selection {
    background: rgba(255, 255, 255, 0.95);
    border-radius: 20px;
    padding: 30px;
    margin-bottom: 30px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.3);
    animation: fadeInUp 0.6s ease-out;
}

.school-selection h3 {
    color: #333;
    margin-bottom: 20px;
    font-size: 1.5rem;
    font-weight: 600;
}

.school-dropdown {
    position: relative;
    width: 100%;
}

.school-select {
    width: 100%;
    padding: 15px 20px;
    border: 2px solid #e9ecef;
    border-radius: 15px;
    font-size: 1.1rem;
    background: white;
    cursor: pointer;
    transition: all 0.3s ease;
    appearance: none;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 12px center;
    background-repeat: no-repeat;
    background-size: 16px;
}

.school-select:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

/* Main Assignment Interface */
.assignment-interface {
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    gap: 30px;
    margin-bottom: 30px;
    animation: fadeInUp 0.8s ease-out;
}

.course-panel {
    background: rgba(255, 255, 255, 0.95);
    border-radius: 20px;
    padding: 25px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.3);
    transition: all 0.3s ease;
}

.course-panel:hover {
    transform: translateY(-2px);
    box-shadow: 0 15px 40px rgba(0,0,0,0.3);
}

.panel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f8f9fa;
}

.panel-title {
    font-size: 1.3rem;
    font-weight: 700;
    color: #333;
    margin: 0;
}

.course-count {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 8px 15px;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 600;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

.search-container {
    margin-bottom: 20px;
    position: relative;
}

.search-input {
    width: 100%;
    padding: 12px 15px 12px 45px;
    border: 2px solid #e9ecef;
    border-radius: 15px;
    font-size: 1rem;
    transition: all 0.3s ease;
    background: #f8f9fa;
}

.search-input:focus {
    outline: none;
    border-color: #667eea;
    background: white;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.search-icon {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
    font-size: 1.1rem;
}

.course-list {
    max-height: 500px;
    overflow-y: auto;
    border-radius: 15px;
    background: #f8f9fa;
    padding: 10px;
}

.course-item {
    background: white;
    border-radius: 12px;
    padding: 15px;
    margin-bottom: 10px;
    border: 2px solid transparent;
    transition: all 0.3s ease;
    cursor: pointer;
    position: relative;
    overflow: hidden;
}

.course-item::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    transform: scaleX(0);
    transition: transform 0.3s ease;
}

.course-item:hover::before {
    transform: scaleX(1);
}

.course-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    border-color: #667eea;
}

.course-item.selected {
    border-color: #28a745;
    background: linear-gradient(135deg, rgba(40, 167, 69, 0.1) 0%, rgba(40, 167, 69, 0.05) 100%);
}

.course-item.selected::before {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    transform: scaleX(1);
}

.course-name {
    font-size: 1.1rem;
    font-weight: 600;
    color: #333;
    margin-bottom: 5px;
    line-height: 1.3;
}

.course-category {
    font-size: 0.9rem;
    color: #6c757d;
    margin-bottom: 8px;
}

.course-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.85rem;
    color: #6c757d;
}

.enrollment-badge {
    background: #e3f2fd;
    color: #1976d2;
    padding: 4px 8px;
    border-radius: 12px;
    font-weight: 500;
}

.warning-badge {
    background: #fff3cd;
    color: #856404;
    padding: 4px 8px;
    border-radius: 12px;
    font-weight: 500;
}

/* Action Buttons */
.action-buttons {
    display: flex;
    flex-direction: column;
    gap: 20px;
    align-items: center;
    justify-content: center;
}

.action-btn {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 15px 25px;
    border-radius: 50px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 120px;
    justify-content: center;
}

.action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
}

.action-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
}

.action-btn.add {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
}

.action-btn.add:hover {
    box-shadow: 0 8px 25px rgba(40, 167, 69, 0.4);
}

.action-btn.remove {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
}

.action-btn.remove:hover {
    box-shadow: 0 8px 25px rgba(220, 53, 69, 0.4);
}

/* Warning Section */
.warning-section {
    background: rgba(255, 255, 255, 0.95);
    border-radius: 20px;
    padding: 25px;
    margin-bottom: 30px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.3);
    animation: fadeInUp 1s ease-out;
}

.warning-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 15px;
}

.warning-icon {
    background: linear-gradient(135deg, #ffc107 0%, #ff8c00 100%);
    color: white;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    animation: pulse 2s infinite;
}

.warning-title {
    font-size: 1.3rem;
    font-weight: 700;
    color: #333;
    margin: 0;
}

.warning-content {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 20px;
}

.warning-text {
    color: #856404;
    font-size: 1rem;
    line-height: 1.6;
    margin: 0;
}

.confirmation-section {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 15px;
    border: 2px solid #e9ecef;
}

.confirmation-checkbox {
    width: 20px;
    height: 20px;
    accent-color: #dc3545;
    cursor: pointer;
}

.confirmation-label {
    font-size: 1rem;
    font-weight: 600;
    color: #333;
    cursor: pointer;
    margin: 0;
}

/* Loading States */
.loading {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px;
    color: #6c757d;
}

.loading-spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid #667eea;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-right: 15px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Animations */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.course-item {
    animation: slideInLeft 0.3s ease-out;
}

@keyframes slideInLeft {
    from {
        opacity: 0;
        transform: translateX(-20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

/* Responsive Design */
@media (max-width: 768px) {
    .assignment-interface {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .action-buttons {
        flex-direction: row;
        justify-content: center;
    }
    
    .assign-header h1 {
        font-size: 2rem;
    }
    
    .course-panel {
        padding: 20px;
    }
}

/* Custom Scrollbar */
.course-list::-webkit-scrollbar {
    width: 8px;
}

.course-list::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.course-list::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 10px;
}

.course-list::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
}
</style>

<div class="assign-container">
    <div class="assign-header">
        <h1>Assign Courses to School</h1>
        <p>Manage course assignments for educational institutions</p>
    </div>

    <!-- School Selection -->
    <div class="school-selection">
        <h3>Select School</h3>
        <div class="school-dropdown">
            <select class="school-select" id="schoolSelect">
                <option value="">Choose a school...</option>
                <?php foreach ($schools as $school): ?>
                    <option value="<?php echo $school->id; ?>"><?php echo htmlspecialchars($school->name); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- Assignment Interface -->
    <div class="assignment-interface" id="assignmentInterface" style="display: none;">
        <!-- School Courses Panel -->
        <div class="course-panel">
            <div class="panel-header">
                <h3 class="panel-title">School Courses</h3>
                <div class="course-count" id="schoolCourseCount">0</div>
            </div>
            <div class="search-container">
                <i class="fa fa-search search-icon"></i>
                <input type="text" class="search-input" id="schoolSearch" placeholder="Search school courses...">
            </div>
            <div class="course-list" id="schoolCourseList">
                <div class="loading">
                    <div class="loading-spinner"></div>
                    Loading courses...
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <button class="action-btn add" id="addBtn" disabled>
                <i class="fa fa-arrow-left"></i>
                Add
            </button>
            <button class="action-btn remove" id="removeBtn" disabled>
                Remove
                <i class="fa fa-arrow-right"></i>
            </button>
        </div>

        <!-- Potential Courses Panel -->
        <div class="course-panel">
            <div class="panel-header">
                <h3 class="panel-title">Potential Courses</h3>
                <div class="course-count" id="potentialCourseCount">0</div>
            </div>
            <div class="search-container">
                <i class="fa fa-search search-icon"></i>
                <input type="text" class="search-input" id="potentialSearch" placeholder="Search potential courses...">
            </div>
            <div class="course-list" id="potentialCourseList">
                <div class="loading">
                    <div class="loading-spinner"></div>
                    Loading courses...
                </div>
            </div>
        </div>
    </div>

    <!-- Warning Section -->
    <div class="warning-section" id="warningSection" style="display: none;">
        <div class="warning-header">
            <div class="warning-icon">
                <i class="fa fa-exclamation-triangle"></i>
            </div>
            <h3 class="warning-title">Important Warning</h3>
        </div>
        <div class="warning-content">
            <p class="warning-text">
                <strong>WARNING:</strong> If "(existing enrollments)" is shown you must tick the box beneath to allow add or remove. 
                If you do this, all users will be unenrolled and ALL THEIR DATA (for that course) IS LOST. This cannot be undone.
            </p>
        </div>
        <div class="confirmation-section">
            <input type="checkbox" class="confirmation-checkbox" id="confirmUnenroll">
            <label for="confirmUnenroll" class="confirmation-label">OK to unenroll users</label>
        </div>
    </div>
</div>

<script>
// Global variables
let selectedSchool = null;
let schoolCourses = [];
let potentialCourses = [];
let selectedSchoolCourses = [];
let selectedPotentialCourses = [];

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    initializeEventListeners();
});

function initializeEventListeners() {
    // School selection
    document.getElementById('schoolSelect').addEventListener('change', handleSchoolChange);
    
    // Search functionality
    document.getElementById('schoolSearch').addEventListener('input', (e) => filterCourses('school', e.target.value));
    document.getElementById('potentialSearch').addEventListener('input', (e) => filterCourses('potential', e.target.value));
    
    // Action buttons
    document.getElementById('addBtn').addEventListener('click', addSelectedCourses);
    document.getElementById('removeBtn').addEventListener('click', removeSelectedCourses);
    
    // Confirmation checkbox
    document.getElementById('confirmUnenroll').addEventListener('change', updateActionButtons);
}

function handleSchoolChange(event) {
    const schoolId = event.target.value;
    if (schoolId) {
        selectedSchool = schoolId;
        document.getElementById('assignmentInterface').style.display = 'grid';
        loadSchoolCourses();
        loadPotentialCourses();
    } else {
        selectedSchool = null;
        document.getElementById('assignmentInterface').style.display = 'none';
        document.getElementById('warningSection').style.display = 'none';
    }
}

async function loadSchoolCourses() {
    try {
        showLoading('schoolCourseList');
        const response = await fetch(`?action=get_school_courses&school_id=${selectedSchool}`);
        const data = await response.json();
        
        if (data.status === 'success' && data.courses) {
            schoolCourses = data.courses;
            renderCourses('schoolCourseList', schoolCourses, 'school');
            updateCourseCount('schoolCourseCount', schoolCourses.length);
        } else {
            schoolCourses = [];
            renderCourses('schoolCourseList', [], 'school');
            updateCourseCount('schoolCourseCount', 0);
        }
    } catch (error) {
        console.error('Error loading school courses:', error);
        showError('schoolCourseList', 'Failed to load school courses');
    }
}

async function loadPotentialCourses() {
    try {
        showLoading('potentialCourseList');
        const response = await fetch(`?action=get_potential_courses&school_id=${selectedSchool}`);
        const data = await response.json();
        
        if (data.status === 'success' && data.courses) {
            potentialCourses = data.courses;
            renderCourses('potentialCourseList', potentialCourses, 'potential');
            updateCourseCount('potentialCourseCount', potentialCourses.length);
        } else {
            potentialCourses = [];
            renderCourses('potentialCourseList', [], 'potential');
            updateCourseCount('potentialCourseCount', 0);
        }
    } catch (error) {
        console.error('Error loading potential courses:', error);
        showError('potentialCourseList', 'Failed to load potential courses');
    }
}

function renderCourses(containerId, courses, type) {
    const container = document.getElementById(containerId);
    
    if (!container) {
        console.error('Container not found:', containerId);
        return;
    }
    
    if (!courses || courses.length === 0) {
        container.innerHTML = '<div class="loading">No courses found</div>';
        return;
    }
    
    container.innerHTML = courses.map(course => {
        if (!course) return '';
        return `
            <div class="course-item" data-course-id="${course.id || ''}" data-type="${type}">
                <div class="course-name">${escapeHtml(course.fullname || 'Unknown Course')}</div>
                <div class="course-category">${escapeHtml(course.category_name || 'Uncategorized')}</div>
                <div class="course-meta">
                    <span class="enrollment-badge">${course.idnumber || 'No ID'}</span>
                    ${course.id > 1 ? '<span class="warning-badge">Existing enrollments</span>' : ''}
                </div>
            </div>
        `;
    }).join('');
    
    // Add click listeners
    container.querySelectorAll('.course-item').forEach(item => {
        item.addEventListener('click', () => toggleCourseSelection(item, type));
    });
}

function toggleCourseSelection(item, type) {
    const courseId = item.dataset.courseId;
    
    if (type === 'school') {
        if (selectedSchoolCourses.includes(courseId)) {
            selectedSchoolCourses = selectedSchoolCourses.filter(id => id !== courseId);
            item.classList.remove('selected');
        } else {
            selectedSchoolCourses.push(courseId);
            item.classList.add('selected');
        }
    } else {
        if (selectedPotentialCourses.includes(courseId)) {
            selectedPotentialCourses = selectedPotentialCourses.filter(id => id !== courseId);
            item.classList.remove('selected');
        } else {
            selectedPotentialCourses.push(courseId);
            item.classList.add('selected');
        }
    }
    
    updateActionButtons();
}

function updateActionButtons() {
    const addBtn = document.getElementById('addBtn');
    const removeBtn = document.getElementById('removeBtn');
    const confirmCheckbox = document.getElementById('confirmUnenroll');
    
    // Update add button
    addBtn.disabled = selectedPotentialCourses.length === 0;
    
    // Update remove button
    removeBtn.disabled = selectedSchoolCourses.length === 0;
    
    // Show warning if needed
    const hasEnrollments = selectedSchoolCourses.some(courseId => {
        const course = schoolCourses.find(c => c.id == courseId);
        return course && course.id > 1;
    });
    
    if (hasEnrollments) {
        document.getElementById('warningSection').style.display = 'block';
        removeBtn.disabled = !confirmCheckbox.checked;
    } else {
        document.getElementById('warningSection').style.display = 'none';
    }
}

async function addSelectedCourses() {
    if (selectedPotentialCourses.length === 0) return;
    
    try {
        for (const courseId of selectedPotentialCourses) {
            const formData = new FormData();
            formData.append('school_id', selectedSchool);
            formData.append('course_id', courseId);
            
            const response = await fetch('?action=assign_course', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            if (data.status !== 'success') {
                throw new Error(data.message);
            }
        }
        
        // Refresh both lists
        await loadSchoolCourses();
        await loadPotentialCourses();
        
        // Clear selections
        selectedPotentialCourses = [];
        clearSelections('potential');
        
        showMessage('Courses assigned successfully!', 'success');
    } catch (error) {
        console.error('Error assigning courses:', error);
        showMessage('Failed to assign courses: ' + error.message, 'error');
    }
}

async function removeSelectedCourses() {
    if (selectedSchoolCourses.length === 0) return;
    
    try {
        for (const courseId of selectedSchoolCourses) {
            const formData = new FormData();
            formData.append('school_id', selectedSchool);
            formData.append('course_id', courseId);
            
            const response = await fetch('?action=unassign_course', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            if (data.status !== 'success') {
                throw new Error(data.message);
            }
        }
        
        // Refresh both lists
        await loadSchoolCourses();
        await loadPotentialCourses();
        
        // Clear selections
        selectedSchoolCourses = [];
        clearSelections('school');
        
        showMessage('Courses unassigned successfully!', 'success');
    } catch (error) {
        console.error('Error unassigning courses:', error);
        showMessage('Failed to unassign courses: ' + error.message, 'error');
    }
}

function clearSelections(type) {
    const container = type === 'school' ? 'schoolCourseList' : 'potentialCourseList';
    document.getElementById(container).querySelectorAll('.course-item.selected').forEach(item => {
        item.classList.remove('selected');
    });
}

function filterCourses(type, searchTerm) {
    const container = type === 'school' ? 'schoolCourseList' : 'potentialCourseList';
    const courses = type === 'school' ? schoolCourses : potentialCourses;
    
    const filteredCourses = courses.filter(course => 
        course.fullname.toLowerCase().includes(searchTerm.toLowerCase()) ||
        (course.category_name && course.category_name.toLowerCase().includes(searchTerm.toLowerCase()))
    );
    
    renderCourses(container, filteredCourses, type);
}

function updateCourseCount(elementId, count) {
    document.getElementById(elementId).textContent = count;
}

function showLoading(containerId) {
    document.getElementById(containerId).innerHTML = `
        <div class="loading">
            <div class="loading-spinner"></div>
            Loading courses...
        </div>
    `;
}

function showError(containerId, message) {
    document.getElementById(containerId).innerHTML = `
        <div class="loading">
            <i class="fa fa-exclamation-triangle" style="color: #dc3545; margin-right: 10px;"></i>
            ${message}
        </div>
    `;
}

function showMessage(message, type) {
    const messageDiv = document.createElement('div');
    messageDiv.className = `message message-${type}`;
    messageDiv.textContent = message;
    messageDiv.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 10px;
        color: white;
        font-weight: 600;
        z-index: 1000;
        animation: slideInRight 0.3s ease-out;
        ${type === 'success' ? 'background: #28a745;' : 'background: #dc3545;'}
    `;
    
    document.body.appendChild(messageDiv);
    
    setTimeout(() => {
        messageDiv.style.animation = 'slideOutRight 0.3s ease-out';
        setTimeout(() => messageDiv.remove(), 300);
    }, 3000);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Add CSS for animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes slideOutRight {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
`;
document.head.appendChild(style);
</script>

<?php
echo $OUTPUT->footer();
?>
