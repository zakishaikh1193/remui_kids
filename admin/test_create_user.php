<?php
/**
 * Test Create User Functionality
 */

require_once('../../../config.php');
require_login();

// Check admin capabilities
$context = context_system::instance();
require_capability('moodle/site:config', $context);

global $USER, $DB, $OUTPUT;

// Handle test request
if (isset($_GET['test'])) {
    header('Content-Type: application/json');
    
    try {
        // Test data
        $test_data = [
            'username' => 'testuser_' . time(),
            'email' => 'test_' . time() . '@example.com',
            'firstname' => 'Test',
            'lastname' => 'User',
            'password' => 'TestPassword123!',
            'role' => '',
            'lang' => 'en',
            'timezone' => '99',
            'maildisplay' => '2',
            'mailformat' => '1',
            'maildigest' => '0',
            'autosubscribe' => '1',
            'trackforums' => '0'
        ];
        
        // Check if username already exists
        if ($DB->record_exists('user', ['username' => $test_data['username']])) {
            throw new Exception("Test username already exists");
        }
        
        // Check if email already exists
        if ($DB->record_exists('user', ['email' => $test_data['email']])) {
            throw new Exception("Test email already exists");
        }
        
        // Create user object
        $user = new stdClass();
        $user->username = $test_data['username'];
        $user->email = $test_data['email'];
        $user->firstname = $test_data['firstname'];
        $user->lastname = $test_data['lastname'];
        $user->password = hash_internal_user_password($test_data['password']);
        $user->confirmed = 1;
        $user->mnethostid = 1;
        $user->timecreated = time();
        $user->timemodified = time();
        $user->lang = $test_data['lang'];
        $user->timezone = $test_data['timezone'];
        $user->maildisplay = $test_data['maildisplay'];
        $user->mailformat = $test_data['mailformat'];
        $user->maildigest = $test_data['maildigest'];
        $user->autosubscribe = $test_data['autosubscribe'];
        $user->trackforums = $test_data['trackforums'];
        $user->deleted = 0;
        $user->suspended = 0;
        
        // Insert user
        $userid = $DB->insert_record('user', $user);
        
        if (!$userid) {
            throw new Exception("Failed to create test user");
        }
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Test user created successfully',
            'userid' => $userid,
            'test_data' => $test_data
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

// Set page context
$PAGE->set_context($context);
$PAGE->set_url('/theme/remui_kids/admin/test_create_user.php');
$PAGE->set_title('Test Create User');
$PAGE->set_heading('Test Create User');

echo $OUTPUT->header();
?>

<div style="padding: 20px; max-width: 800px; margin: 0 auto;">
    <h1>Test Create User Functionality</h1>
    
    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
        <h3>Test Information</h3>
        <p>This page tests the user creation functionality directly.</p>
        <button onclick="testCreateUser()" style="background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;">
            Test Create User
        </button>
    </div>
    
    <div id="testResult" style="margin-top: 20px;"></div>
    
    <div style="background: #e9ecef; padding: 20px; border-radius: 8px; margin: 20px 0;">
        <h3>Debug Information</h3>
        <p><strong>Current User:</strong> <?php echo fullname($USER); ?> (ID: <?php echo $USER->id; ?>)</p>
        <p><strong>Admin Capability:</strong> <?php echo has_capability('moodle/site:config', $context) ? 'Yes' : 'No'; ?></p>
        <p><strong>Database Status:</strong> <?php echo $DB->get_dbfamily(); ?></p>
    </div>
</div>

<script>
function testCreateUser() {
    const resultDiv = document.getElementById('testResult');
    resultDiv.innerHTML = '<p>Testing user creation...</p>';
    
    fetch('?test=1')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                resultDiv.innerHTML = `
                    <div style="background: #d4edda; padding: 15px; border-radius: 5px; color: #155724;">
                        <h4>Success!</h4>
                        <p>${data.message}</p>
                        <p><strong>User ID:</strong> ${data.userid}</p>
                        <p><strong>Username:</strong> ${data.test_data.username}</p>
                        <p><strong>Email:</strong> ${data.test_data.email}</p>
                    </div>
                `;
            } else {
                resultDiv.innerHTML = `
                    <div style="background: #f8d7da; padding: 15px; border-radius: 5px; color: #721c24;">
                        <h4>Error!</h4>
                        <p>${data.message}</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            resultDiv.innerHTML = `
                <div style="background: #f8d7da; padding: 15px; border-radius: 5px; color: #721c24;">
                    <h4>Error!</h4>
                    <p>Network error: ${error.message}</p>
                </div>
            `;
        });
}
</script>

<?php
echo $OUTPUT->footer();
?>
