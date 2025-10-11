<?php
/**
 * School Manager - Courses Content (AJAX)
 * Returns only the content portion for AJAX loading
 */

require_once('../../../../../config.php');
require_login();

global $USER, $DB;

// Check if user is a department manager
$company_user = $DB->get_record('company_users', ['userid' => $USER->id]);
if (!$company_user || $company_user->managertype != 2) {
    echo '<div class="error-message">Access denied. You must be a school manager.</div>';
    exit;
}

$department = $DB->get_record('department', ['id' => $company_user->departmentid]);
?>

<div class="courses-management-page">
    <div class="page-header">
        <div class="header-content">
            <h1 class="page-title">Courses Management</h1>
            <p class="page-subtitle">Manage all courses in <?php echo format_string($department->name); ?></p>
        </div>
    </div>

    <!-- Search and Filter Controls -->
    <div class="controls-section">
        <div class="search-box">
            <span class="search-icon"><i class="fa fa-search"></i></span>
            <input type="text" class="search-input" placeholder="Search courses..." id="courseSearch">
        </div>
        <div class="filter-buttons">
            <button class="filter-btn active" data-filter="all">All Courses</button>
            <button class="filter-btn" data-filter="visible">Visible</button>
            <button class="filter-btn" data-filter="hidden">Hidden</button>
        </div>
        <div class="view-controls">
            <select class="per-page-select" id="perPageSelect">
                <option value="20">20 per page</option>
                <option value="50">50 per page</option>
                <option value="100">100 per page</option>
            </select>
        </div>
    </div>

    <!-- Courses Grid -->
    <div class="courses-grid" id="coursesGrid">
        <div class="loading-state">
            <i class="fa fa-spinner fa-spin"></i> Loading courses...
        </div>
    </div>

    <!-- Pagination -->
    <div class="pagination-controls" id="paginationControls">
        <!-- Pagination will be inserted here -->
    </div>
</div>

<style>
    .courses-management-page {
        max-width: 100%;
        margin: 0;
    }

    .page-header {
        margin-bottom: 2rem;
        padding-bottom: 1.5rem;
        border-bottom: 2px solid #e9ecef;
    }

    .page-title {
        font-size: 1.8rem;
        font-weight: 700;
        color: #2c3e50;
        margin: 0 0 0.5rem 0;
    }

    .page-subtitle {
        font-size: 1rem;
        color: #6c757d;
        margin: 0;
    }

    .controls-section {
        display: flex;
        gap: 1rem;
        margin-bottom: 2rem;
        flex-wrap: wrap;
        align-items: center;
    }

    .search-box {
        position: relative;
        flex: 1;
        min-width: 250px;
    }

    .search-icon {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: #6c757d;
    }

    .search-input {
        width: 100%;
        padding: 0.75rem 1rem 0.75rem 2.5rem;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        font-size: 0.95rem;
    }

    .filter-buttons {
        display: flex;
        gap: 0.5rem;
    }

    .filter-btn {
        padding: 0.75rem 1.25rem;
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 500;
    }

    .filter-btn.active {
        background: #667eea;
        color: white;
        border-color: #667eea;
    }

    .per-page-select {
        padding: 0.75rem 1rem;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        font-size: 0.95rem;
    }

    .courses-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .loading-state {
        grid-column: 1 / -1;
        text-align: center;
        padding: 3rem;
        color: #6c757d;
    }

    .course-card {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
    }

    .course-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
    }

    .course-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 1.5rem;
        color: white;
        position: relative;
    }

    .course-visibility-badge {
        position: absolute;
        top: 1rem;
        right: 1rem;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        background: rgba(255, 255, 255, 0.2);
    }

    .course-name {
        font-size: 1.2rem;
        font-weight: 700;
        margin: 0 0 0.5rem 0;
        color: white;
    }

    .course-shortname {
        font-size: 0.85rem;
        margin: 0;
        opacity: 0.9;
    }

    .course-body {
        padding: 1.5rem;
    }

    .course-stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .course-stat {
        text-align: center;
    }

    .stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: #667eea;
        margin: 0 0 0.25rem 0;
    }

    .stat-label {
        font-size: 0.75rem;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .course-progress {
        margin-bottom: 1.5rem;
    }

    .progress-label {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.5rem;
        font-size: 0.875rem;
        color: #6c757d;
    }

    .progress-bar-container {
        width: 100%;
        height: 8px;
        background: #e9ecef;
        border-radius: 4px;
        overflow: hidden;
    }

    .progress-bar {
        height: 100%;
        background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
        border-radius: 4px;
        transition: width 0.3s ease;
    }

    .course-actions {
        display: flex;
        gap: 0.5rem;
    }

    .btn-course-action {
        flex: 1;
        padding: 0.75rem;
        background: #667eea;
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 0.875rem;
        font-weight: 600;
        transition: all 0.2s ease;
        text-decoration: none;
        text-align: center;
    }

    .btn-course-action:hover {
        background: #5568d3;
        text-decoration: none;
        color: white;
    }

    .pagination-controls {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }

    .pagination-info {
        color: #6c757d;
        font-size: 0.9rem;
    }

    .pagination-buttons {
        display: flex;
        gap: 0.5rem;
    }

    .pagination-btn {
        padding: 0.5rem 1rem;
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 6px;
        cursor: pointer;
    }

    .pagination-btn.active {
        background: #667eea;
        color: white;
        border-color: #667eea;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    loadCourses();
});

function loadCourses() {
    const grid = document.getElementById('coursesGrid');
    grid.innerHTML = '<div class="loading-state"><i class="fa fa-spinner fa-spin"></i> Loading courses...</div>';
    
    // Simulate data loading
    setTimeout(() => {
        grid.innerHTML = '<div class="loading-state">No courses found</div>';
    }, 1000);
}
</script>

