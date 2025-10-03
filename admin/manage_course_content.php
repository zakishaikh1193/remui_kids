<?php
/**
 * Manage Course Content Page
 * Accordion-style interface for managing course content by categories
 */

require_once('../../../config.php');
require_login();

// Check admin capabilities
$context = context_system::instance();
require_capability('moodle/site:config', $context);

// Get current user
global $USER, $DB, $OUTPUT, $PAGE;

// Set page context and URL
$PAGE->set_context($context);
$PAGE->set_url('/theme/remui_kids/admin/manage_course_content.php');
$PAGE->set_title('Manage Course Content');
$PAGE->set_heading('Manage Course Content');


// Get all categories with error handling
try {
    $categories = $DB->get_records('course_categories', ['visible' => 1], 'name ASC');
    
    // Get category counts
    $category_counts = [];
    foreach ($categories as $category) {
        $count = $DB->count_records('course', ['category' => $category->id, 'visible' => 1]);
        $category_counts[$category->id] = $count;
    }
} catch (Exception $e) {
    // Handle database errors
    echo "Database error: " . $e->getMessage();
    exit;
}

echo $OUTPUT->header();

// Debug: Check if user is logged in
if (!isloggedin()) {
    echo "<div style='color: red; padding: 20px;'>User not logged in. Please log in first.</div>";
    echo $OUTPUT->footer();
    exit;
}

// Add session status indicator
echo "<div id='session-status' style='position: fixed; top: 10px; right: 10px; background: #28a745; color: white; padding: 5px 10px; border-radius: 4px; font-size: 12px; z-index: 9999;'>Session Active</div>";
?>

<style>
/* Manage Course Content Page Styles */
.manage-content-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    background: #f8f9fa;
    min-height: 100vh;
}

.page-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 30px;
    padding: 20px;
    background: white;
    border-radius: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.page-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
}

.page-title {
    font-size: 2rem;
    font-weight: 700;
    color: #333;
    margin: 0;
}

.categories-container {
    background: white;
    border-radius: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
}

.category-item {
    border-bottom: 1px solid #e9ecef;
    transition: all 0.3s ease;
}

.category-item:last-child {
    border-bottom: none;
}

.category-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 25px;
    cursor: pointer;
    background: white;
    transition: all 0.3s ease;
    position: relative;
}

.category-header:hover {
    background: #f8f9fa;
}

.category-header.active {
    background: #e3f2fd;
    border-left: 4px solid #2196f3;
}

.category-info {
    display: flex;
    align-items: center;
    gap: 15px;
}

.category-name {
    font-size: 1.1rem;
    font-weight: 600;
    color: #333;
    margin: 0;
}

.category-count {
    background: #e9ecef;
    color: #6c757d;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 500;
}

.category-arrow {
    font-size: 1.2rem;
    color: #6c757d;
    transition: transform 0.3s ease;
}

.category-header.active .category-arrow {
    transform: rotate(180deg);
    color: #2196f3;
}

.category-content {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease;
    background: #f8f9fa;
}

.category-content.active {
    max-height: 500px;
    padding: 20px 25px;
}

.courses-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.course-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 20px;
    transition: all 0.3s ease;
}

.course-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.15);
}

.course-thumbnail {
    width: 80px;
    height: 80px;
    border-radius: 10px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 2rem;
    flex-shrink: 0;
    overflow: hidden;
}

.course-thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 10px;
}

.course-info {
    flex: 1;
}

.course-title {
    font-size: 1.2rem;
    font-weight: 700;
    color: #333;
    margin: 0 0 5px 0;
}

.course-subtitle {
    font-size: 1rem;
    color: #667eea;
    margin: 0 0 10px 0;
    font-weight: 500;
}

.course-actions {
    display: flex;
    gap: 10px;
}

.manage-content-btn {
    background: linear-gradient(135deg, #2196f3 0%, #21cbf3 100%);
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
}

.manage-content-btn:hover {
    background: linear-gradient(135deg, #1976d2 0%, #1cb5e0 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(33, 150, 243, 0.3);
    color: white;
    text-decoration: none;
}

.manage-content-btn:disabled {
    opacity: 0.7;
    cursor: not-allowed;
    transform: none;
}

.manage-content-btn .fa-spinner {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.empty-category {
    text-align: center;
    padding: 40px 20px;
    color: #6c757d;
}

.empty-icon {
    font-size: 3rem;
    margin-bottom: 15px;
    opacity: 0.5;
}

.empty-text {
    font-size: 1.1rem;
    font-weight: 500;
    margin: 0;
}

/* Floating Action Button */
.fab {
    position: fixed;
    bottom: 30px;
    right: 30px;
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #9c27b0 0%, #e91e63 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
    cursor: pointer;
    box-shadow: 0 4px 20px rgba(156, 39, 176, 0.3);
    transition: all 0.3s ease;
    z-index: 1000;
}

.fab:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 25px rgba(156, 39, 176, 0.4);
}

/* Responsive Design */
@media (max-width: 768px) {
    .manage-content-container {
        padding: 15px;
    }
    
    .page-header {
        padding: 15px;
    }
    
    .page-title {
        font-size: 1.5rem;
    }
    
    .category-header {
        padding: 15px 20px;
    }
    
    .category-content.active {
        padding: 15px 20px;
    }
    
    .course-card {
        flex-direction: column;
        text-align: center;
        gap: 15px;
    }
    
    .course-thumbnail {
        width: 60px;
        height: 60px;
        font-size: 1.5rem;
    }
    
    .fab {
        bottom: 20px;
        right: 20px;
        width: 50px;
        height: 50px;
        font-size: 1.2rem;
    }
}
</style>

<div class="manage-content-container">
    <div class="page-header">
        <div class="page-icon">
            <i class="fa fa-book"></i>
        </div>
        <h1 class="page-title">Manage Course Content</h1>
    </div>

    <div class="categories-container">
        <?php foreach ($categories as $category): ?>
            <div class="category-item" data-category-id="<?php echo $category->id; ?>">
                <div class="category-header" onclick="toggleCategory(<?php echo $category->id; ?>)">
                    <div class="category-info">
                        <h3 class="category-name"><?php echo htmlspecialchars($category->name); ?></h3>
                        <span class="category-count"><?php echo $category_counts[$category->id]; ?></span>
                    </div>
                    <div class="category-arrow">
                        <i class="fa fa-chevron-down"></i>
                    </div>
                </div>
                <div class="category-content" id="content-<?php echo $category->id; ?>">
                    <div class="courses-list" id="courses-<?php echo $category->id; ?>">
                        <?php 
                        try {
                            $category_courses = $DB->get_records('course', ['category' => $category->id, 'visible' => 1], 'fullname ASC');
                            if (count($category_courses) > 0): 
                        ?>
                            <?php foreach ($category_courses as $course): ?>
                                <div class="course-card">
                                    <div class="course-thumbnail">
                                        <i class="fa fa-book"></i>
                                    </div>
                                    <div class="course-info">
                                        <h4 class="course-title"><?php echo htmlspecialchars($course->fullname); ?></h4>
                                        <p class="course-subtitle"><?php echo htmlspecialchars($course->shortname); ?></p>
                                    </div>
                                    <div class="course-actions">
                                    <button class="manage-content-btn" onclick="viewCourse(<?php echo $course->id; ?>)">
                                            Manage Content <i class="fa fa-chevron-right"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-category">
                                <div class="empty-icon">
                                    <i class="fa fa-book"></i>
                                </div>
                                <p class="empty-text">No courses in this category</p>
                            </div>
                        <?php endif; 
                        } catch (Exception $e) {
                            echo '<div class="empty-category"><p class="empty-text">Error loading courses: ' . htmlspecialchars($e->getMessage()) . '</p></div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Floating Action Button -->
<div class="fab" onclick="window.location.href='course_categories.php'">
    <i class="fa fa-plus"></i>
</div>

<script>
// Get base URL from PHP
const WWWROOT = '<?php echo $CFG->wwwroot; ?>';

// Ensure jQuery is available
if (typeof $ === 'undefined') {
    console.warn('jQuery not loaded, using vanilla JavaScript');
}

// Category toggle functionality
function toggleCategory(categoryId) {
    const categoryItem = document.querySelector(`[data-category-id="${categoryId}"]`);
    const categoryHeader = categoryItem.querySelector('.category-header');
    const categoryContent = document.getElementById(`content-${categoryId}`);
    
    // Toggle active state
    categoryHeader.classList.toggle('active');
    categoryContent.classList.toggle('active');
}

// AJAX functions removed - courses are now loaded directly in PHP

// Function to handle course selection for content management

    // Show loading state
    function selectCourseForContent(courseId) {
    // Call viewCourse function when a course is selected for content management
    viewCourse(courseId);
}


// View course function


function viewCourse(courseId) {
    // Redirect to course view page
    window.location.href = `${WWWROOT}/course/view.php?id=${courseId}`;
}

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    // Auto-expand first category with courses
    const categories = document.querySelectorAll('.category-item');
    for (let category of categories) {
        const count = category.querySelector('.category-count').textContent;
        if (count !== '0') {
            const categoryId = category.dataset.categoryId;
            toggleCategory(categoryId);
            break;
        }
    }
});


</script>

<?php
echo $OUTPUT->footer();
?>
