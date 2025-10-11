<?php
/**
 * School Manager - Students Content (AJAX)
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

<div class="students-management-page">
    <div class="page-header">
        <div class="header-content">
            <h1 class="page-title">Students Management</h1>
            <p class="page-subtitle">Manage all students in <?php echo format_string($department->name); ?></p>
        </div>
    </div>

    <!-- Search and Filter Controls -->
    <div class="controls-section">
        <div class="search-box">
            <span class="search-icon"><i class="fa fa-search"></i></span>
            <input type="text" class="search-input" placeholder="Search students..." id="studentSearch">
        </div>
        <div class="filter-buttons">
            <button class="filter-btn active" data-filter="all">All Students</button>
            <button class="filter-btn" data-filter="active">Active</button>
            <button class="filter-btn" data-filter="inactive">Inactive</button>
        </div>
        <div class="view-controls">
            <select class="per-page-select" id="perPageSelect">
                <option value="20">20 per page</option>
                <option value="50">50 per page</option>
                <option value="100">100 per page</option>
            </select>
        </div>
    </div>

    <!-- Students Table -->
    <div class="students-table-wrapper">
        <table class="students-table" id="studentsTable">
            <thead>
                <tr>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Last Access</th>
                    <th>Enrolled Courses</th>
                    <th>Completed</th>
                </tr>
            </thead>
            <tbody id="studentsTableBody">
                <tr>
                    <td colspan="5" class="table-loading">
                        <i class="fa fa-spinner fa-spin"></i> Loading students...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="pagination-controls" id="paginationControls">
        <!-- Pagination will be inserted here -->
    </div>
</div>

<style>
    .students-management-page {
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
        margin-bottom: 1.5rem;
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

    .students-table-wrapper {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        overflow: hidden;
        margin-bottom: 1.5rem;
    }

    .students-table {
        width: 100%;
        border-collapse: collapse;
    }

    .students-table th {
        padding: 1rem;
        text-align: left;
        font-weight: 600;
        color: #495057;
        background: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
    }

    .students-table td {
        padding: 1rem;
        border-bottom: 1px solid #e9ecef;
    }

    .table-loading {
        text-align: center;
        padding: 3rem !important;
        color: #6c757d;
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
// Students page JavaScript would go here
document.addEventListener('DOMContentLoaded', function() {
    // Load students data
    loadStudents();
});

function loadStudents() {
    // Implementation for loading students data
    const tbody = document.getElementById('studentsTableBody');
    tbody.innerHTML = '<tr><td colspan="5" class="table-loading">Loading students...</td></tr>';
    
    // Simulate data loading
    setTimeout(() => {
        tbody.innerHTML = '<tr><td colspan="5" class="table-loading">No students found</td></tr>';
    }, 1000);
}
</script>

