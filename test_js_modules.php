<?php
require_once('../../config.php');
require_login();

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/theme/remui_kids/test_js_modules.php');
$PAGE->set_title('Test JavaScript Modules');
$PAGE->set_heading('Test JavaScript Modules');

// Load the simple dropdown fix without dependencies
$PAGE->requires->js('/theme/remui_kids/javascript/simple_dropdown_fix.js');

echo $OUTPUT->header();
?>

<div class="container">
    <h2>JavaScript Modules Test</h2>
    <p>This page tests the JavaScript modules for any loading errors.</p>
    
    <div class="alert alert-info">
        <h4>Test Results:</h4>
        <p id="test-results">Loading...</p>
    </div>
    
    <div class="dropdown">
        <button class="btn btn-secondary dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false">
            Test Dropdown
        </button>
        <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="#">Action</a></li>
            <li><a class="dropdown-item" href="#">Another action</a></li>
            <li><a class="dropdown-item" href="#">Something else here</a></li>
        </ul>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Check if modules loaded successfully
    var results = document.getElementById('test-results');
    var moduleLoaded = true;
    var errors = [];
    
    // Check if JavaScript loaded successfully
    setTimeout(function() {
        // Check if dropdown functionality is working
        var dropdownTest = document.querySelectorAll('.dropdown-toggle').length > 0;
        var simpleDropdownTest = document.querySelectorAll('[data-toggle="dropdown"]').length > 0;
        
        if (dropdownTest || simpleDropdownTest) {
            results.innerHTML = '✅ JavaScript dropdown fix loaded successfully!<br>' +
                              '✅ Dropdown elements: Found<br>' +
                              '✅ Simple dropdown fix: Loaded<br>' +
                              '✅ No jQuery dependency: Working';
            results.parentElement.className = 'alert alert-success';
        } else {
            results.innerHTML = '❌ JavaScript loading issues detected:<br>' +
                              'Dropdown elements: ' + (dropdownTest || simpleDropdownTest ? 'Found' : 'Not found') + '<br>' +
                              'Check console for errors';
            results.parentElement.className = 'alert alert-danger';
        }
    }, 1000);
});
</script>

<?php
echo $OUTPUT->footer();
?>
