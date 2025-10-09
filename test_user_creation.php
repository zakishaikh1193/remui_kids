<?php
require_once('../../../config.php');

// Check if user is logged in and has proper permissions
require_login();
$context = context_system::instance();
require_capability('moodle/user:create', $context);

echo "<h2>User Creation Test</h2>";

// Test database connection
echo "<h3>Database Connection Test</h3>";
try {
    $test_query = $DB->get_record_sql("SELECT COUNT(*) as count FROM {user}");
    echo "✅ Database connection successful. Total users: " . $test_query->count . "<br>";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
    exit;
}

// Test user table structure
echo "<h3>User Table Structure Test</h3>";
try {
    $columns = $DB->get_columns('user');
    echo "✅ User table accessible. Columns found: " . count($columns) . "<br>";
    
    // Check for required fields
    $required_fields = ['username', 'password', 'firstname', 'lastname', 'email', 'auth', 'confirmed', 'mnethostid'];
    foreach ($required_fields as $field) {
        if (isset($columns[$field])) {
            echo "✅ Required field '$field' exists<br>";
        } else {
            echo "❌ Required field '$field' missing<br>";
        }
    }
} catch (Exception $e) {
    echo "❌ User table access failed: " . $e->getMessage() . "<br>";
}

// Test role table
echo "<h3>Role Table Test</h3>";
try {
    $teacher_role = $DB->get_record('role', ['shortname' => 'teacher']);
    if ($teacher_role) {
        echo "✅ Teacher role found (ID: " . $teacher_role->id . ")<br>";
    } else {
        echo "❌ Teacher role not found<br>";
    }
} catch (Exception $e) {
    echo "❌ Role table access failed: " . $e->getMessage() . "<br>";
}

// Test company_users table
echo "<h3>Company Users Table Test</h3>";
try {
    if ($DB->get_manager()->table_exists('company_users')) {
        $company_count = $DB->count_records('company_users');
        echo "✅ Company users table exists. Records: " . $company_count . "<br>";
    } else {
        echo "❌ Company users table does not exist<br>";
    }
} catch (Exception $e) {
    echo "❌ Company users table test failed: " . $e->getMessage() . "<br>";
}

// Test user creation with minimal data
echo "<h3>Minimal User Creation Test</h3>";
try {
    $test_user = new stdClass();
    $test_user->username = 'test_user_' . time();
    $test_user->password = hash_internal_user_password('testpass123');
    $test_user->firstname = 'Test';
    $test_user->lastname = 'User';
    $test_user->email = 'test' . time() . '@example.com';
    $test_user->auth = 'manual';
    $test_user->confirmed = 1;
    $test_user->mnethostid = $CFG->mnet_localhost_id;
    $test_user->timecreated = time();
    $test_user->timemodified = time();
    $test_user->firstaccess = 0;
    $test_user->lastaccess = 0;
    $test_user->suspended = 0;
    $test_user->deleted = 0;
    
    echo "Attempting to create test user...<br>";
    $user_id = $DB->insert_record('user', $test_user);
    
    if ($user_id) {
        echo "✅ Test user created successfully with ID: " . $user_id . "<br>";
        
        // Clean up - delete the test user
        $DB->delete_records('user', ['id' => $user_id]);
        echo "✅ Test user cleaned up<br>";
    } else {
        echo "❌ Test user creation failed<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Test user creation failed: " . $e->getMessage() . "<br>";
    echo "Error details: " . $e->getTraceAsString() . "<br>";
}

echo "<h3>Test Complete</h3>";
echo "<a href='admin/add_teacher.php'>← Back to Add Teacher</a>";
?>

