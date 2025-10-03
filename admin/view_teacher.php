<?php
/**
 * View Teacher Page - Beautiful animated page for viewing teacher profile
 */

require_once('../../../config.php');
global $DB, $CFG, $OUTPUT, $PAGE;

// Get teacher ID from URL
$teacher_id = optional_param('id', 0, PARAM_INT);

if (!$teacher_id) {
    header('Location: teachers_list.php');
    exit;
}

// Set up the page
$PAGE->set_url('/theme/remui_kids/admin/view_teacher.php', ['id' => $teacher_id]);
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Teacher Profile');
$PAGE->set_heading('Teacher Profile');
$PAGE->set_pagelayout('admin');

// Check if user has admin capabilities
require_capability('moodle/site:config', context_system::instance());

// Get teacher data
$teacher = $DB->get_record('user', ['id' => $teacher_id]);
if (!$teacher) {
    header('Location: teachers_list.php');
    exit;
}

// Get teacher role assignment info
$role_info = $DB->get_record_sql(
    "SELECT 
        r.name as role_name,
        r.shortname as role_shortname,
        FROM_UNIXTIME(ra.timemodified) as assigned_date,
        ra.timemodified as role_timestamp
     FROM {role_assignments} ra
     JOIN {role} r ON ra.roleid = r.id
     JOIN {context} ctx ON ra.contextid = ctx.id
     WHERE ra.userid = ? AND ctx.contextlevel = ? AND r.shortname = 'teachers'",
    [$teacher_id, CONTEXT_SYSTEM]
);


// Get recent activity
$recent_activity = $DB->get_records_sql(
    "SELECT 
        'login' as type,
        'Last Login' as description,
        FROM_UNIXTIME(lastaccess) as activity_date,
        lastaccess as timestamp
     FROM {user}
     WHERE id = ? AND lastaccess > 0
     UNION ALL
     SELECT 
        'role' as type,
        'Role Assigned' as description,
        FROM_UNIXTIME(ra.timemodified) as activity_date,
        ra.timemodified as timestamp
     FROM {role_assignments} ra
     WHERE ra.userid = ? AND ra.roleid = (
         SELECT id FROM {role} WHERE shortname = 'teachers'
     )
     ORDER BY timestamp DESC
     LIMIT 5",
    [$teacher_id, $teacher_id]
);

echo $OUTPUT->header();

// Add custom CSS with animations
echo "<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
    
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    body {
        font-family: 'Inter', sans-serif;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        padding: 20px;
    }
    
    .view-container {
        max-width: 1200px;
        margin: 0 auto;
        animation: slideInUp 0.8s ease-out;
    }
    
    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(50px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .profile-header {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 20px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        backdrop-filter: blur(10px);
        overflow: hidden;
        margin-bottom: 30px;
        position: relative;
    }
    
    .header-background {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        height: 200px;
        position: relative;
        overflow: hidden;
    }
    
    .header-background::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        animation: rotate 20s linear infinite;
    }
    
    @keyframes rotate {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    
    .profile-content {
        padding: 40px;
        position: relative;
    }
    
    .breadcrumb {
        background: rgba(255, 255, 255, 0.1);
        padding: 15px 30px;
        border-radius: 12px;
        margin-bottom: 20px;
        backdrop-filter: blur(10px);
    }
    
    .breadcrumb a {
        color: rgba(255, 255, 255, 0.8);
        text-decoration: none;
        transition: color 0.3s ease;
    }
    
    .breadcrumb a:hover {
        color: white;
    }
    
    .breadcrumb-item {
        color: rgba(255, 255, 255, 0.9);
    }
    
    .profile-info {
        display: grid;
        grid-template-columns: 200px 1fr;
        gap: 40px;
        align-items: start;
        animation: fadeInUp 1s ease-out 0.3s both;
    }
    
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
    
    .profile-avatar {
        text-align: center;
        position: relative;
    }
    
    .avatar-container {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 4rem;
        font-weight: bold;
        color: white;
        margin: 0 auto 20px;
        border: 5px solid white;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        animation: pulse 2s infinite;
        position: relative;
        overflow: hidden;
    }
    
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }
    
    .avatar-container::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 70%);
        animation: rotate 15s linear infinite;
    }
    
    .status-badge {
        display: inline-block;
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 0.9rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        animation: slideInDown 0.8s ease-out 0.5s both;
    }
    
    @keyframes slideInDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .status-active {
        background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
        color: white;
    }
    
    .status-suspended {
        background: linear-gradient(135deg, #f56565 0%, #e53e3e 100%);
        color: white;
    }
    
    .profile-details {
        animation: fadeInRight 1s ease-out 0.4s both;
    }
    
    @keyframes fadeInRight {
        from {
            opacity: 0;
            transform: translateX(30px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    .profile-name {
        font-size: 2.5rem;
        font-weight: 800;
        color: #2d3748;
        margin-bottom: 10px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    .profile-title {
        font-size: 1.2rem;
        color: #4a5568;
        margin-bottom: 20px;
        font-weight: 500;
    }
    
    .profile-meta {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .meta-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 15px;
        background: #f8fafc;
        border-radius: 12px;
        border-left: 4px solid #667eea;
        transition: all 0.3s ease;
    }
    
    .meta-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    .meta-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.2rem;
    }
    
    .meta-content {
        flex: 1;
    }
    
    .meta-label {
        font-size: 0.85rem;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
    }
    
    .meta-value {
        font-size: 1rem;
        color: #2d3748;
        font-weight: 600;
        margin-top: 2px;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 30px;
        margin-bottom: 30px;
    }
    
    .stat-card {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 20px;
        padding: 30px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        backdrop-filter: blur(10px);
        text-align: center;
        position: relative;
        overflow: hidden;
        animation: slideInUp 0.8s ease-out;
    }
    
    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    
    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.5rem;
        margin: 0 auto 20px;
        animation: bounce 2s infinite;
    }
    
    @keyframes bounce {
        0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
        40% { transform: translateY(-10px); }
        60% { transform: translateY(-5px); }
    }
    
    .stat-number {
        font-size: 2.5rem;
        font-weight: 800;
        color: #2d3748;
        margin-bottom: 10px;
    }
    
    .stat-label {
        font-size: 1rem;
        color: #6b7280;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .activity-section {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 20px;
        padding: 30px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        backdrop-filter: blur(10px);
        animation: fadeIn 1s ease-out 0.6s both;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    .section-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #2d3748;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .activity-list {
        list-style: none;
    }
    
    .activity-item {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 15px 0;
        border-bottom: 1px solid #e2e8f0;
        animation: slideInLeft 0.6s ease-out;
    }
    
    .activity-item:last-child {
        border-bottom: none;
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
    
    .activity-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1rem;
    }
    
    .activity-content {
        flex: 1;
    }
    
    .activity-title {
        font-weight: 600;
        color: #2d3748;
        margin-bottom: 2px;
    }
    
    .activity-date {
        font-size: 0.9rem;
        color: #6b7280;
    }
    
    .action-buttons {
        display: flex;
        gap: 15px;
        justify-content: center;
        margin-top: 30px;
        animation: fadeInUp 1s ease-out 0.8s both;
    }
    
    .btn {
        padding: 15px 30px;
        border: none;
        border-radius: 12px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        min-width: 150px;
        justify-content: center;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
    }
    
    .btn-secondary {
        background: #f7fafc;
        color: #4a5568;
        border: 2px solid #e2e8f0;
    }
    
    .btn-secondary:hover {
        background: #edf2f7;
        transform: translateY(-2px);
    }
    
    .btn-success {
        background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
        color: white;
        box-shadow: 0 4px 15px rgba(72, 187, 120, 0.3);
    }
    
    .btn-success:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(72, 187, 120, 0.4);
    }
    
    .floating-elements {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        z-index: -1;
    }
    
    .floating-circle {
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.1);
        animation: float 6s ease-in-out infinite;
    }
    
    .floating-circle:nth-child(1) {
        width: 100px;
        height: 100px;
        top: 10%;
        left: 10%;
        animation-delay: 0s;
    }
    
    .floating-circle:nth-child(2) {
        width: 80px;
        height: 80px;
        top: 60%;
        right: 10%;
        animation-delay: 2s;
    }
    
    .floating-circle:nth-child(3) {
        width: 60px;
        height: 60px;
        bottom: 20%;
        left: 20%;
        animation-delay: 4s;
    }
    
    .floating-circle:nth-child(4) {
        width: 120px;
        height: 120px;
        top: 30%;
        right: 30%;
        animation-delay: 1s;
    }
    
    @keyframes float {
        0%, 100% { transform: translateY(0px) rotate(0deg); }
        50% { transform: translateY(-20px) rotate(180deg); }
    }
    @keyframes modalFadeIn {
        0% { 
            opacity: 0;
            backdrop-filter: blur(0px);
        }
        100% { 
            opacity: 1;
            backdrop-filter: blur(10px);
        }
    }
    @keyframes modalSlideIn {
        0% {
            opacity: 0;
            transform: translateY(-100px) scale(0.8) rotateX(20deg);
        }
        50% {
            opacity: 0.8;
            transform: translateY(10px) scale(1.02) rotateX(-5deg);
        }
        100% {
            opacity: 1;
            transform: translateY(0) scale(1) rotateX(0deg);
        }
    }
    @keyframes shimmer {
        0% { background-position: -200% 0; }
        100% { background-position: 200% 0; }
    }
    @keyframes bodySlideIn {
        0% {
            opacity: 0;
            transform: translateY(30px);
        }
        100% {
            opacity: 1;
            transform: translateY(0);
        }
    }
    @keyframes messageFadeIn {
        0% {
            opacity: 0;
            transform: scale(0.8);
        }
        100% {
            opacity: 1;
            transform: scale(1);
        }
    }
    @keyframes footerSlideUp {
        0% {
            opacity: 0;
            transform: translateY(20px);
        }
        100% {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .confirmation-modal {
        display: none;
        position: fixed;
        z-index: 10000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.8) 0%, rgba(118, 75, 162, 0.8) 100%);
        backdrop-filter: blur(10px);
        animation: modalFadeIn 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    }
    .modal-content {
        background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
        margin: 5% auto;
        padding: 0;
        border: none;
        border-radius: 24px;
        width: 90%;
        max-width: 500px;
        box-shadow: 
            0 30px 100px rgba(0, 0, 0, 0.3),
            0 0 0 1px rgba(255, 255, 255, 0.1),
            inset 0 1px 0 rgba(255, 255, 255, 0.2);
        animation: modalSlideIn 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
        overflow: hidden;
        position: relative;
        transform-style: preserve-3d;
    }
    .modal-content::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #667eea 0%, #764ba2 50%, #667eea 100%);
        background-size: 200% 100%;
        animation: shimmer 2s ease-in-out infinite;
    }
    .modal-body {
        padding: 40px 45px 35px;
        text-align: center;
        position: relative;
        animation: bodySlideIn 0.8s ease-out 0.2s both;
    }
    .modal-message {
        font-size: 1.2rem;
        color: #2d3748;
        margin-bottom: 0;
        line-height: 1.7;
        font-weight: 500;
        position: relative;
        animation: messageFadeIn 1s ease-out 0.4s both;
    }
    .modal-message::before {
        content: '⚠️';
        display: block;
        font-size: 3rem;
        margin-bottom: 20px;
        animation: bounce 2s infinite;
    }
    .modal-footer {
        padding: 0 45px 40px;
        display: flex;
        gap: 20px;
        justify-content: center;
        animation: footerSlideUp 0.8s ease-out 0.6s both;
    }
    .modal-btn {
        padding: 16px 32px;
        border: none;
        border-radius: 16px;
        font-size: 1.1rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        min-width: 140px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        letter-spacing: 0.5px;
        position: relative;
        overflow: hidden;
        text-transform: uppercase;
        font-size: 0.9rem;
    }
    .modal-btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
        transition: left 0.6s;
    }
    .modal-btn:hover::before {
        left: 100%;
    }
    .modal-btn-secondary {
        background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
        color: #4a5568;
        border: 2px solid #e2e8f0;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }
    .modal-btn-secondary:hover {
        background: linear-gradient(135deg, #edf2f7 0%, #e2e8f0 100%);
        transform: translateY(-4px) scale(1.05);
        box-shadow: 0 12px 35px rgba(0, 0, 0, 0.2);
    }
    .modal-btn-danger {
        background: linear-gradient(135deg, #e53e3e 0%, #c53030 100%);
        color: white;
        box-shadow: 0 6px 20px rgba(229, 62, 62, 0.4);
        border: 2px solid #e53e3e;
    }
    .modal-btn-danger:hover {
        transform: translateY(-4px) scale(1.05);
        box-shadow: 0 15px 40px rgba(229, 62, 62, 0.6);
        background: linear-gradient(135deg, #c53030 0%, #9c2626 100%);
    }
    .modal-btn-success {
        background: linear-gradient(135deg, #38a169 0%, #2f855a 100%);
        color: white;
        box-shadow: 0 6px 20px rgba(56, 161, 105, 0.4);
        border: 2px solid #38a169;
    }
    .modal-btn-success:hover {
        transform: translateY(-4px) scale(1.05);
        box-shadow: 0 15px 40px rgba(56, 161, 105, 0.6);
        background: linear-gradient(135deg, #2f855a 0%, #276749 100%);
    }
    
    @media (max-width: 768px) {
        .profile-info {
            grid-template-columns: 1fr;
            text-align: center;
        }
        
        .profile-meta {
            grid-template-columns: 1fr;
        }
        
        .stats-grid {
            grid-template-columns: 1fr;
        }
        
        .action-buttons {
            flex-direction: column;
            align-items: center;
        }
        
        .btn {
            width: 100%;
            max-width: 300px;
        }
    }
</style>";

// Floating background elements
echo "<div class='floating-elements'>";
echo "<div class='floating-circle'></div>";
echo "<div class='floating-circle'></div>";
echo "<div class='floating-circle'></div>";
echo "<div class='floating-circle'></div>";
echo "</div>";

echo "<div class='view-container'>";

// Profile Header
echo "<div class='profile-header'>";
echo "<div class='header-background'>";
echo "<div class='breadcrumb'>";
echo "<a href='{$CFG->wwwroot}/my/'>Dashboard</a> / ";
echo "<a href='teachers_list.php'>Teachers</a> / ";
echo "<span class='breadcrumb-item'>Teacher Profile</span>";
echo "</div>";
echo "</div>";

echo "<div class='profile-content'>";
echo "<div class='profile-info'>";

// Avatar and Status
echo "<div class='profile-avatar'>";
echo "<div class='avatar-container'>";
echo strtoupper(substr($teacher->firstname, 0, 1));
echo "</div>";
$status_class = $teacher->suspended ? 'status-suspended' : 'status-active';
$status_text = $teacher->suspended ? 'Suspended' : 'Active';
echo "<span class='status-badge $status_class'>$status_text</span>";
echo "</div>";

// Profile Details
echo "<div class='profile-details'>";
echo "<h1 class='profile-name'>{$teacher->firstname} {$teacher->lastname}</h1>";
echo "<p class='profile-title'>Teacher • ID: {$teacher->id}</p>";

echo "<div class='profile-meta'>";
echo "<div class='meta-item'>";
echo "<div class='meta-icon'><i class='fa fa-user'></i></div>";
echo "<div class='meta-content'>";
echo "<div class='meta-label'>Username</div>";
echo "<div class='meta-value'>{$teacher->username}</div>";
echo "</div>";
echo "</div>";

echo "<div class='meta-item'>";
echo "<div class='meta-icon'><i class='fa fa-envelope'></i></div>";
echo "<div class='meta-content'>";
echo "<div class='meta-label'>Email</div>";
echo "<div class='meta-value'>{$teacher->email}</div>";
echo "</div>";
echo "</div>";

if ($role_info) {
    echo "<div class='meta-item'>";
    echo "<div class='meta-icon'><i class='fa fa-graduation-cap'></i></div>";
    echo "<div class='meta-content'>";
    echo "<div class='meta-label'>Role Assigned</div>";
    echo "<div class='meta-value'>{$role_info->assigned_date}</div>";
    echo "</div>";
    echo "</div>";
}

$last_access = $teacher->lastaccess ? date('M j, Y g:i A', $teacher->lastaccess) : 'Never';
echo "<div class='meta-item'>";
echo "<div class='meta-icon'><i class='fa fa-clock'></i></div>";
echo "<div class='meta-content'>";
echo "<div class='meta-label'>Last Access</div>";
echo "<div class='meta-value'>$last_access</div>";
echo "</div>";
echo "</div>";
echo "</div>";
echo "</div>";
echo "</div>";
echo "</div>";
echo "</div>";

// Statistics Grid
echo "<div class='stats-grid'>";
echo "<div class='stat-card'>";
echo "<div class='stat-icon'><i class='fa fa-calendar'></i></div>";
echo "<div class='stat-number'>" . date('Y', $teacher->timecreated) . "</div>";
echo "<div class='stat-label'>Member Since</div>";
echo "</div>";
echo "</div>";

// Recent Activity
echo "<div class='activity-section'>";
echo "<h2 class='section-title'>";
echo "<i class='fa fa-history'></i> Recent Activity";
echo "</h2>";

if ($recent_activity) {
    echo "<ul class='activity-list'>";
    foreach ($recent_activity as $activity) {
        $icon = $activity->type === 'login' ? 'fa-sign-in-alt' : 'fa-user-plus';
        echo "<li class='activity-item'>";
        echo "<div class='activity-icon'><i class='fa $icon'></i></div>";
        echo "<div class='activity-content'>";
        echo "<div class='activity-title'>{$activity->description}</div>";
        echo "<div class='activity-date'>{$activity->activity_date}</div>";
        echo "</div>";
        echo "</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='text-align: center; color: #6b7280; padding: 20px;'>No recent activity to display</p>";
}
echo "</div>";

// Action Buttons
echo "<div class='action-buttons'>";
echo "<a href='edit_teacher.php?id={$teacher->id}' class='btn btn-primary'>";
echo "<i class='fa fa-edit'></i> Edit Teacher";
echo "</a>";
echo "<a href='teachers_list.php' class='btn btn-secondary'>";
echo "<i class='fa fa-arrow-left'></i> Back to Teachers";
echo "</a>";
if (!$teacher->suspended) {
    echo "<button onclick='toggleTeacherStatus({$teacher->id}, true)' class='btn btn-danger'>";
    echo "<i class='fa fa-ban'></i> Suspend Teacher";
    echo "</button>";
} else {
    echo "<button onclick='toggleTeacherStatus({$teacher->id}, false)' class='btn btn-primary'>";
    echo "<i class='fa fa-check'></i> Activate Teacher";
    echo "</button>";
}
echo "</div>";

echo "</div>"; // End view-container

// Confirmation Modal
echo "<div id='confirmationModal' class='confirmation-modal'>";
echo "<div class='modal-content'>";
echo "<div class='modal-body'>";
echo "<p class='modal-message' id='modalMessage'>Are you sure you want to perform this action?</p>";
echo "</div>";
echo "<div class='modal-footer'>";
echo "<button class='modal-btn modal-btn-secondary' onclick='closeConfirmationModal()'>";
echo "<i class='fa fa-times'></i> Cancel";
echo "</button>";
echo "<button class='modal-btn modal-btn-danger' id='confirmBtn' onclick='confirmAction()'>";
echo "<i class='fa fa-check'></i> Confirm";
echo "</button>";
echo "</div>";
echo "</div>";
echo "</div>";

// JavaScript for status toggle
echo "<script>
// Global variables for modal
let currentUserId = null;
let currentAction = null;

function toggleTeacherStatus(userid, suspend) {
    currentUserId = userid;
    currentAction = suspend ? 'suspend' : 'activate';
    
    // Update modal content based on action
    const modalMessage = document.getElementById('modalMessage');
    const confirmBtn = document.getElementById('confirmBtn');
    
    if (suspend) {
        modalMessage.innerHTML = 'Are you sure you want to suspend this teacher?<br>They will not be able to access the system until reactivated.';
        confirmBtn.className = 'modal-btn modal-btn-danger';
        confirmBtn.innerHTML = '<i class=\"fa fa-ban\"></i> Suspend';
    } else {
        modalMessage.innerHTML = 'Are you sure you want to activate this teacher?<br>They will regain access to the system.';
        confirmBtn.className = 'modal-btn modal-btn-success';
        confirmBtn.innerHTML = '<i class=\"fa fa-check\"></i> Activate';
    }
    
    // Show modal
    document.getElementById('confirmationModal').style.display = 'block';
}

function closeConfirmationModal() {
    document.getElementById('confirmationModal').style.display = 'none';
    currentUserId = null;
    currentAction = null;
}

function confirmAction() {
    if (!currentUserId) return;
    
    const formData = new FormData();
    formData.append('action', 'toggle_status');
    formData.append('userid', currentUserId);
    
    // Show loading state
    const confirmBtn = document.getElementById('confirmBtn');
    const originalText = confirmBtn.innerHTML;
    confirmBtn.innerHTML = '<i class=\"fa fa-spinner fa-spin\"></i> Processing...';
    confirmBtn.disabled = true;
    
    fetch('teachers_list.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // Show success message briefly
            confirmBtn.innerHTML = '<i class=\"fa fa-check\"></i> Success!';
            confirmBtn.className = 'modal-btn modal-btn-success';
            
            setTimeout(() => {
                closeConfirmationModal();
                location.reload();
            }, 1500);
        } else {
            // Show error
            confirmBtn.innerHTML = '<i class=\"fa fa-exclamation\"></i> Error';
            confirmBtn.className = 'modal-btn modal-btn-danger';
            setTimeout(() => {
                confirmBtn.innerHTML = originalText;
                confirmBtn.disabled = false;
            }, 2000);
        }
    })
    .catch(error => {
        confirmBtn.innerHTML = '<i class=\"fa fa-exclamation\"></i> Error';
        confirmBtn.className = 'modal-btn modal-btn-danger';
        setTimeout(() => {
            confirmBtn.innerHTML = originalText;
            confirmBtn.disabled = false;
        }, 2000);
    });
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('confirmationModal');
    if (event.target === modal) {
        closeConfirmationModal();
    }
}
</script>";

echo $OUTPUT->footer();
?>
