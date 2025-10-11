<?php
/**
 * School Manager - Reports Content (AJAX)
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

<div class="reports-page">
    <div class="page-header">
        <div class="header-content">
            <h1 class="page-title">Reports & Analytics</h1>
            <p class="page-subtitle">Comprehensive insights for <?php echo format_string($department->name); ?></p>
        </div>
    </div>

    <!-- Report Cards Grid -->
    <div class="report-cards-grid">
        <div class="report-card">
            <div class="report-card-header">
                <h3 class="report-card-title">
                    <i class="fa fa-chart-line"></i> Enrollment Trends
                </h3>
            </div>
            <div class="report-card-body">
                <canvas id="enrollmentChart"></canvas>
            </div>
        </div>

        <div class="report-card">
            <div class="report-card-header">
                <h3 class="report-card-title">
                    <i class="fa fa-graduation-cap"></i> Course Completion
                </h3>
            </div>
            <div class="report-card-body">
                <canvas id="completionChart"></canvas>
            </div>
        </div>

        <div class="report-card full-width">
            <div class="report-card-header">
                <h3 class="report-card-title">
                    <i class="fa fa-award"></i> Top Performing Students
                </h3>
            </div>
            <div class="report-card-body">
                <div class="table-wrapper" id="topStudentsTable">
                    <p class="loading-message">
                        <i class="fa fa-spinner fa-spin"></i> Loading data...
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Report Links -->
    <div class="quick-reports-section">
        <h2 class="section-title">Quick Reports</h2>
        <div class="quick-reports-grid">
            <div class="quick-report-card">
                <div class="report-icon">
                    <i class="fa fa-user-graduate"></i>
                </div>
                <h3 class="report-name">Student Progress Report</h3>
                <p class="report-description">Detailed progress tracking for all students</p>
            </div>

            <div class="quick-report-card">
                <div class="report-icon">
                    <i class="fa fa-chalkboard-teacher"></i>
                </div>
                <h3 class="report-name">Teacher Activity Report</h3>
                <p class="report-description">Teaching activity and engagement metrics</p>
            </div>

            <div class="quick-report-card">
                <div class="report-icon">
                    <i class="fa fa-book"></i>
                </div>
                <h3 class="report-name">Course Performance</h3>
                <p class="report-description">Course-wise enrollment and completion data</p>
            </div>

            <div class="quick-report-card">
                <div class="report-icon">
                    <i class="fa fa-cogs"></i>
                </div>
                <h3 class="report-name">Custom Report Builder</h3>
                <p class="report-description">Create your own custom reports</p>
            </div>
        </div>
    </div>
</div>

<style>
    .reports-page {
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

    .report-cards-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1.5rem;
        margin-bottom: 3rem;
    }

    .report-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        overflow: hidden;
    }

    .report-card.full-width {
        grid-column: 1 / -1;
    }

    .report-card-header {
        padding: 1.5rem;
        background: #f8f9fa;
        border-bottom: 2px solid #e9ecef;
    }

    .report-card-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: #2c3e50;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .report-card-body {
        padding: 1.5rem;
    }

    .loading-message {
        text-align: center;
        padding: 2rem;
        color: #6c757d;
    }

    .section-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #2c3e50;
        margin: 0 0 1.5rem 0;
    }

    .quick-reports-section {
        margin-bottom: 2rem;
    }

    .quick-reports-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
    }

    .quick-report-card {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        border: 2px solid transparent;
    }

    .quick-report-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.15);
        border-color: #667eea;
    }

    .report-icon {
        width: 50px;
        height: 50px;
        border-radius: 10px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin-bottom: 1rem;
    }

    .report-name {
        font-size: 1rem;
        font-weight: 600;
        color: #2c3e50;
        margin: 0 0 0.5rem 0;
    }

    .report-description {
        font-size: 0.875rem;
        color: #6c757d;
        margin: 0;
    }

    .table-wrapper table {
        width: 100%;
        border-collapse: collapse;
    }

    .table-wrapper th {
        padding: 1rem;
        text-align: left;
        font-weight: 600;
        color: #495057;
        font-size: 0.875rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        background: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
    }

    .table-wrapper td {
        padding: 1rem;
        border-bottom: 1px solid #e9ecef;
    }

    @media (max-width: 1024px) {
        .report-cards-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 768px) {
        .quick-reports-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<script>
let enrollmentChart, completionChart;

document.addEventListener('DOMContentLoaded', function() {
    initializeCharts();
});

function initializeCharts() {
    // Initialize enrollment chart
    const enrollmentCtx = document.getElementById('enrollmentChart').getContext('2d');
    enrollmentChart = new Chart(enrollmentCtx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            datasets: [{
                label: 'Enrollments',
                data: [12, 19, 3, 5, 2, 3],
                borderColor: '#667eea',
                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Initialize completion chart
    const completionCtx = document.getElementById('completionChart').getContext('2d');
    completionChart = new Chart(completionCtx, {
        type: 'bar',
        data: {
            labels: ['Math', 'Science', 'English', 'History'],
            datasets: [
                {
                    label: 'Enrolled',
                    data: [25, 30, 20, 15],
                    backgroundColor: 'rgba(102, 126, 234, 0.5)',
                    borderColor: '#667eea',
                    borderWidth: 1
                },
                {
                    label: 'Completed',
                    data: [20, 25, 18, 12],
                    backgroundColor: 'rgba(67, 233, 123, 0.5)',
                    borderColor: '#43e97b',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Load top students data
    loadTopStudents();
}

function loadTopStudents() {
    const container = document.getElementById('topStudentsTable');
    
    setTimeout(() => {
        container.innerHTML = `
            <table>
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Student Name</th>
                        <th>Completed Courses</th>
                        <th>Average Grade</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>#1</strong></td>
                        <td>John Doe</td>
                        <td>5</td>
                        <td>95.5</td>
                    </tr>
                    <tr>
                        <td><strong>#2</strong></td>
                        <td>Jane Smith</td>
                        <td>4</td>
                        <td>92.3</td>
                    </tr>
                    <tr>
                        <td><strong>#3</strong></td>
                        <td>Mike Johnson</td>
                        <td>4</td>
                        <td>89.7</td>
                    </tr>
                </tbody>
            </table>
        `;
    }, 1000);
}
</script>

