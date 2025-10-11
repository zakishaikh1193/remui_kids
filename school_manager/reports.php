<?php
/**
 * School Manager - Reports and Analytics
 * View comprehensive reports and analytics for the school/department
 * 
 * @package   theme_remui_kids
 * @copyright 2024
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

// Require login
require_login();
$context = context_system::instance();

// Check if user is a department manager (school manager)
global $USER, $DB;

// Get user's company assignment
$company_user = $DB->get_record('company_users', ['userid' => $USER->id]);

if (!$company_user || $company_user->managertype != 2) {
    throw new moodle_exception('nopermissions', 'error', '', 'access school manager reports page - you must be a department/school manager');
}

// Get department/school information
$department = $DB->get_record('department', ['id' => $company_user->departmentid]);
$company = $DB->get_record('company', ['id' => $company_user->companyid]);

// Set up the page
$PAGE->set_context($context);
$PAGE->set_url('/theme/remui_kids/school_manager/reports.php');
$PAGE->set_pagelayout('admin');
$PAGE->set_title('Reports & Analytics');
$PAGE->set_heading('Reports & Analytics');
$PAGE->add_body_class('school-manager-reports-page');

// Handle AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['action']) {
        case 'get_enrollment_stats':
            try {
                // Get enrollment statistics over time
                $enrollment_data = $DB->get_records_sql(
                    "SELECT DATE_FORMAT(FROM_UNIXTIME(ue.timecreated), '%Y-%m') as month,
                            COUNT(DISTINCT ue.id) as enrollments
                     FROM {user_enrolments} ue
                     JOIN {enrol} e ON ue.enrolid = e.id
                     JOIN {company_course} cc ON e.courseid = cc.courseid
                     WHERE cc.departmentid = ?
                     AND ue.timecreated > UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 6 MONTH))
                     GROUP BY month
                     ORDER BY month ASC",
                    [$company_user->departmentid]
                );
                
                echo json_encode([
                    'status' => 'success',
                    'data' => array_values($enrollment_data)
                ]);
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
            exit;
            
        case 'get_completion_stats':
            try {
                // Get course completion statistics
                $completion_data = $DB->get_records_sql(
                    "SELECT c.id, c.fullname, c.shortname,
                            COUNT(DISTINCT ue.userid) as enrolled,
                            COUNT(DISTINCT cc.userid) as completed
                     FROM {company_course} ccourse
                     JOIN {course} c ON ccourse.courseid = c.id
                     LEFT JOIN {enrol} e ON e.courseid = c.id
                     LEFT JOIN {user_enrolments} ue ON ue.enrolid = e.id
                     LEFT JOIN {course_completions} cc ON cc.course = c.id AND cc.timecompleted IS NOT NULL
                     WHERE ccourse.departmentid = ?
                     GROUP BY c.id
                     ORDER BY enrolled DESC
                     LIMIT 10",
                    [$company_user->departmentid]
                );
                
                echo json_encode([
                    'status' => 'success',
                    'data' => array_values($completion_data)
                ]);
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
            exit;
            
        case 'get_student_performance':
            try {
                // Get top performing students
                $performance_data = $DB->get_records_sql(
                    "SELECT u.id, u.firstname, u.lastname,
                            COUNT(DISTINCT cc.course) as completed_courses,
                            AVG(gg.finalgrade) as average_grade
                     FROM {company_users} cu
                     JOIN {user} u ON cu.userid = u.id
                     LEFT JOIN {course_completions} cc ON cc.userid = u.id AND cc.timecompleted IS NOT NULL
                     LEFT JOIN {grade_grades} gg ON gg.userid = u.id
                     WHERE cu.departmentid = ? AND cu.managertype = 0
                     GROUP BY u.id
                     HAVING completed_courses > 0
                     ORDER BY completed_courses DESC, average_grade DESC
                     LIMIT 10",
                    [$company_user->departmentid]
                );
                
                echo json_encode([
                    'status' => 'success',
                    'data' => array_values($performance_data)
                ]);
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
            exit;
    }
}

echo $OUTPUT->header();

// Include sidebar
include('includes/sidebar.php');

echo '<div class="school-manager-main-content">';
?>

<div class="reports-page">
    <div class="page-header">
        <div class="header-content">
            <h1 class="page-title">Reports & Analytics</h1>
            <p class="page-subtitle">Comprehensive insights for <?php echo format_string($department->name); ?></p>
        </div>
        <div class="header-actions">
            <button class="btn-action" onclick="exportAllReports()">
                <i class="fa fa-download"></i> Export All Reports
            </button>
            <button class="btn-action btn-secondary" onclick="printReports()">
                <i class="fa fa-print"></i> Print
            </button>
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
            <a href="#" class="quick-report-card" onclick="alert('Student Progress Report will be implemented soon'); return false;">
                <div class="report-icon">
                    <i class="fa fa-user-graduate"></i>
                </div>
                <h3 class="report-name">Student Progress Report</h3>
                <p class="report-description">Detailed progress tracking for all students</p>
            </a>

            <a href="#" class="quick-report-card" onclick="alert('Teacher Activity Report will be implemented soon'); return false;">
                <div class="report-icon">
                    <i class="fa fa-chalkboard-teacher"></i>
                </div>
                <h3 class="report-name">Teacher Activity Report</h3>
                <p class="report-description">Teaching activity and engagement metrics</p>
            </a>

            <a href="#" class="quick-report-card" onclick="alert('Course Performance Report will be implemented soon'); return false;">
                <div class="report-icon">
                    <i class="fa fa-book"></i>
                </div>
                <h3 class="report-name">Course Performance</h3>
                <p class="report-description">Course-wise enrollment and completion data</p>
            </a>

            <a href="#" class="quick-report-card" onclick="alert('Custom Report Builder will be implemented soon'); return false;">
                <div class="report-icon">
                    <i class="fa fa-cogs"></i>
                </div>
                <h3 class="report-name">Custom Report Builder</h3>
                <p class="report-description">Create your own custom reports</p>
            </a>
        </div>
    </div>
</div>

<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

<style>
    .reports-page {
        max-width: 1400px;
        margin: 0 auto;
    }

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        padding-bottom: 1.5rem;
        border-bottom: 2px solid #e9ecef;
    }

    .page-title {
        font-size: 2rem;
        font-weight: 700;
        color: #2c3e50;
        margin: 0 0 0.5rem 0;
    }

    .page-subtitle {
        font-size: 1rem;
        color: #6c757d;
        margin: 0;
    }

    .header-actions {
        display: flex;
        gap: 1rem;
    }

    .btn-action {
        padding: 0.75rem 1.5rem;
        background: #667eea;
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-action:hover {
        background: #5568d3;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }

    .btn-secondary {
        background: #6c757d;
    }

    .btn-secondary:hover {
        background: #5a6268;
    }

    /* Report Cards Grid */
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

    /* Quick Reports Section */
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
        text-decoration: none;
        color: inherit;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        border: 2px solid transparent;
    }

    .quick-report-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.15);
        border-color: #667eea;
        text-decoration: none;
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

    /* Table styling */
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

    /* Responsive */
    @media (max-width: 1024px) {
        .report-cards-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 768px) {
        .page-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
        }

        .quick-reports-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<script>
let enrollmentChart, completionChart;

document.addEventListener('DOMContentLoaded', function() {
    initializeEnrollmentChart();
    initializeCompletionChart();
    loadTopStudents();
});

function initializeEnrollmentChart() {
    const ctx = document.getElementById('enrollmentChart').getContext('2d');
    
    fetch('?action=get_enrollment_stats')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const labels = data.data.map(item => item.month);
                const values = data.data.map(item => item.enrollments);
                
                enrollmentChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Enrollments',
                            data: values,
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
            }
        })
        .catch(error => console.error('Error loading enrollment stats:', error));
}

function initializeCompletionChart() {
    const ctx = document.getElementById('completionChart').getContext('2d');
    
    fetch('?action=get_completion_stats')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const labels = data.data.map(item => item.shortname);
                const enrolled = data.data.map(item => item.enrolled);
                const completed = data.data.map(item => item.completed);
                
                completionChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: 'Enrolled',
                                data: enrolled,
                                backgroundColor: 'rgba(102, 126, 234, 0.5)',
                                borderColor: '#667eea',
                                borderWidth: 1
                            },
                            {
                                label: 'Completed',
                                data: completed,
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
            }
        })
        .catch(error => console.error('Error loading completion stats:', error));
}

function loadTopStudents() {
    fetch('?action=get_student_performance')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                renderTopStudentsTable(data.data);
            }
        })
        .catch(error => console.error('Error loading student performance:', error));
}

function renderTopStudentsTable(students) {
    const container = document.getElementById('topStudentsTable');
    
    if (students.length === 0) {
        container.innerHTML = '<p class="loading-message">No student performance data available</p>';
        return;
    }
    
    let html = `
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
    `;
    
    students.forEach((student, index) => {
        const avgGrade = student.average_grade ? parseFloat(student.average_grade).toFixed(2) : 'N/A';
        html += `
            <tr>
                <td><strong>#${index + 1}</strong></td>
                <td>${student.firstname} ${student.lastname}</td>
                <td>${student.completed_courses}</td>
                <td>${avgGrade}</td>
            </tr>
        `;
    });
    
    html += `
            </tbody>
        </table>
    `;
    
    container.innerHTML = html;
}

function exportAllReports() {
    alert('Export all reports functionality will be implemented soon.');
}

function printReports() {
    window.print();
}
</script>

<?php
echo '</div>'; // Close school-manager-main-content
echo $OUTPUT->footer();
?>


