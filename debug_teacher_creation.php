<?php
require_once('../../../config.php');

// Check if user is logged in and has proper permissions
require_login();
$context = context_system::instance();
require_capability('moodle/user:create', $context);

echo "<h2>Teacher Creation Debug Tool</h2>";

// Test 1: Database Connection
echo "<h3>1. Database Connection Test</h3>";
try {
    $test_query = $DB->get_record_sql("SELECT COUNT(*) as count FROM {user}");
    echo "✅ Database connection successful. Total users: " . $test_query->count . "<br>";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
    exit;
}

// Test 2: User Table Structure
echo "<h3>2. User Table Structure Analysis</h3>";
try {
    $columns = $DB->get_columns('user');
    echo "✅ User table accessible. Total columns: " . count($columns) . "<br>";
    
    // Check specific fields and their properties
    $critical_fields = [
        'username' => ['type' => 'varchar', 'null' => false],
        'password' => ['type' => 'varchar', 'null' => false],
        'firstname' => ['type' => 'varchar', 'null' => false],
        'lastname' => ['type' => 'varchar', 'null' => false],
        'email' => ['type' => 'varchar', 'null' => false],
        'auth' => ['type' => 'varchar', 'null' => false],
        'confirmed' => ['type' => 'tinyint', 'null' => false],
        'mnethostid' => ['type' => 'bigint', 'null' => false],
        'timecreated' => ['type' => 'bigint', 'null' => false],
        'timemodified' => ['type' => 'bigint', 'null' => false]
    ];
    
    foreach ($critical_fields as $field => $expected) {
        if (isset($columns[$field])) {
            $col = $columns[$field];
            echo "✅ Field '$field': Type={$col->type}, Null=" . ($col->not_null ? 'No' : 'Yes') . "<br>";
            
            // Check if field allows null but we're not providing value
            if (!$col->not_null && $expected['null'] === false) {
                echo "⚠️  Warning: Field '$field' allows NULL but is expected to be NOT NULL<br>";
            }
        } else {
            echo "❌ Critical field '$field' missing from user table<br>";
        }
    }
    
} catch (Exception $e) {
    echo "❌ User table analysis failed: " . $e->getMessage() . "<br>";
}

// Test 3: Sample User Creation with Minimal Data
echo "<h3>3. Minimal User Creation Test</h3>";
try {
    $test_user = new stdClass();
    $test_user->username = 'debug_test_' . time();
    $test_user->password = hash_internal_user_password('testpass123');
    $test_user->firstname = 'Debug';
    $test_user->lastname = 'Test';
    $test_user->email = 'debug' . time() . '@test.com';
    $test_user->auth = 'manual';
    $test_user->confirmed = 1;
    $test_user->mnethostid = $CFG->mnet_localhost_id;
    $test_user->timecreated = time();
    $test_user->timemodified = time();
    $test_user->firstaccess = 0;
    $test_user->lastaccess = 0;
    $test_user->suspended = 0;
    $new_user->deleted = 0;
    
    echo "Attempting to create user with minimal data...<br>";
    echo "Username: " . $test_user->username . "<br>";
    echo "Email: " . $test_user->email . "<br>";
    
    $user_id = $DB->insert_record('user', $test_user);
    
    if ($user_id) {
        echo "✅ Minimal user created successfully with ID: " . $user_id . "<br>";
        
        // Clean up
        $DB->delete_records('user', ['id' => $user_id]);
        echo "✅ Test user cleaned up<br>";
    } else {
        echo "❌ Minimal user creation failed - no ID returned<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Minimal user creation failed: " . $e->getMessage() . "<br>";
    echo "Error code: " . $e->getCode() . "<br>";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "<br>";
}

// Test 4: Test with Full User Data (like in add_teacher.php)
echo "<h3>4. Full User Data Creation Test</h3>";
try {
    $full_user = new stdClass();
    $full_user->username = 'full_test_' . time();
    $full_user->password = hash_internal_user_password('testpass123');
    $full_user->firstname = 'Full';
    $full_user->lastname = 'Test';
    $full_user->email = 'full' . time() . '@test.com';
    $full_user->city = 'Test City';
    $full_user->country = 'US';
    $full_user->phone1 = '1234567890';
    $full_user->phone2 = '';
    $full_user->institution = '';
    $full_user->department = '';
    $full_user->address = '';
    $full_user->description = 'Test description';
    $full_user->descriptionformat = 1;
    $full_user->mailformat = 1;
    $full_user->maildigest = 0;
    $full_user->maildisplay = 2;
    $full_user->autosubscribe = 1;
    $full_user->trackforums = 0;
    $full_user->timezone = '';
    $full_user->theme = '';
    $full_user->lang = '';
    $full_user->calendartype = '';
    $full_user->auth = 'manual';
    $full_user->confirmed = 1;
    $full_user->mnethostid = $CFG->mnet_localhost_id;
    $full_user->timecreated = time();
    $full_user->timemodified = time();
    $full_user->firstaccess = 0;
    $full_user->lastaccess = 0;
    $full_user->lastlogin = 0;
    $full_user->currentlogin = 0;
    $full_user->lastip = '';
    $full_user->secret = '';
    $full_user->picture = 0;
    $full_user->url = '';
    $full_user->suspended = 0;
    $full_user->deleted = 0;
    $full_user->idnumber = '';
    $full_user->skype = '';
    $full_user->msn = '';
    $full_user->aim = '';
    $full_user->yahoo = '';
    $full_user->icq = '';
    $full_user->alternatename = '';
    $full_user->middlename = '';
    $full_user->lastnamephonetic = '';
    $full_user->firstnamephonetic = '';
    
    echo "Attempting to create user with full data...<br>";
    echo "Username: " . $full_user->username . "<br>";
    
    $user_id = $DB->insert_record('user', $full_user);
    
    if ($user_id) {
        echo "✅ Full user created successfully with ID: " . $user_id . "<br>";
        
        // Clean up
        $DB->delete_records('user', ['id' => $user_id]);
        echo "✅ Full test user cleaned up<br>";
    } else {
        echo "❌ Full user creation failed - no ID returned<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Full user creation failed: " . $e->getMessage() . "<br>";
    echo "Error code: " . $e->getCode() . "<br>";
    echo "SQL State: " . (isset($e->errorInfo) ? $e->errorInfo[0] : 'Unknown') . "<br>";
    
    // Try to get more specific error information
    if (method_exists($e, 'getSqlState')) {
        echo "SQL State: " . $e->getSqlState() . "<br>";
    }
}

// Test 5: Check MySQL Error Log
echo "<h3>5. Database Engine Check</h3>";
try {
    $engine_info = $DB->get_record_sql("SHOW TABLE STATUS LIKE 'mdl_user'");
    if ($engine_info) {
        echo "✅ User table engine: " . $engine_info->engine . "<br>";
        echo "✅ Table collation: " . $engine_info->collation . "<br>";
        echo "✅ Row format: " . $engine_info->row_format . "<br>";
    }
} catch (Exception $e) {
    echo "❌ Could not get table status: " . $e->getMessage() . "<br>";
}

// Test 6: Check if there are any constraints
echo "<h3>6. Database Constraints Check</h3>";
try {
    $constraints = $DB->get_records_sql("SHOW CREATE TABLE mdl_user");
    if (!empty($constraints)) {
        echo "✅ Table creation info retrieved<br>";
        // Note: The actual constraint info would be in the Create Table field
    }
} catch (Exception $e) {
    echo "❌ Could not get table constraints: " . $e->getMessage() . "<br>";
}

echo "<h3>Debug Complete</h3>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ul>";
echo "<li>If all tests pass, the issue might be in the form data or validation</li>";
echo "<li>If tests fail, check the specific error messages above</li>";
echo "<li>Try the simple version: <a href='admin/add_teacher_simple.php'>add_teacher_simple.php</a></li>";
echo "<li>Check Moodle error logs in moodledata folder</li>";
echo "</ul>";

echo "<a href='admin/add_teacher.php'>← Back to Add Teacher</a>";
?>

