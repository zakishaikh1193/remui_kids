<?php
/**
 * Dropdown Test Page
 * Tests dropdown functionality in the admin dashboard
 */

require_once('../../config.php');
require_login();

// Check admin capabilities
$context = context_system::instance();
if (!has_capability('moodle/site:config', $context)) {
    die('This page requires admin access');
}

$PAGE->set_context($context);
$PAGE->set_url('/theme/remui_kids/test_dropdowns.php');
$PAGE->set_title('Dropdown Test Page');
$PAGE->set_heading('Dropdown Functionality Test');

echo $OUTPUT->header();

echo '
<div class="container-fluid">
    <h1>Dropdown Functionality Test</h1>
    <p>This page tests various dropdown components to ensure they work properly.</p>
    
    <div class="row">
        <div class="col-md-6">
            <h3>Basic Bootstrap Dropdown</h3>
            <div class="dropdown">
                <button class="btn btn-secondary dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    Basic Dropdown
                </button>
                <div class="dropdown-menu">
                    <a class="dropdown-item" href="#">Action</a>
                    <a class="dropdown-item" href="#">Another action</a>
                    <a class="dropdown-item" href="#">Something else here</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="#">Separated link</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <h3>Select Element</h3>
            <select class="form-control">
                <option>Choose an option</option>
                <option value="1">Option 1</option>
                <option value="2">Option 2</option>
                <option value="3">Option 3</option>
            </select>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-md-6">
            <h3>Sort Dropdown (MyOverview Style)</h3>
            <div class="dropdown">
                <button id="sortingdropdown" type="button" class="btn dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <span data-active-item-text>Sort by Title</span>
                </button>
                <ul class="dropdown-menu" role="menu" data-show-active-item data-skip-active-class="true">
                    <li>
                        <a class="dropdown-item" href="#" data-filter="sort" data-pref="title" data-value="fullname" aria-current="true">
                            Sort by Title
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="#" data-filter="sort" data-pref="shortname" data-value="shortname">
                            Sort by Short Name
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="#" data-filter="sort" data-pref="lastaccessed" data-value="ul.timeaccess desc">
                            Sort by Last Accessed
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        
        <div class="col-md-6">
            <h3>Button Group Dropdown</h3>
            <div class="btn-group">
                <button type="button" class="btn btn-primary">Primary</button>
                <button type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <span class="sr-only">Toggle Dropdown</span>
                </button>
                <div class="dropdown-menu">
                    <a class="dropdown-item" href="#">Action</a>
                    <a class="dropdown-item" href="#">Another action</a>
                    <a class="dropdown-item" href="#">Something else here</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="#">Separated link</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-md-12">
            <h3>Test Results</h3>
            <div id="test-results" class="alert alert-info">
                <strong>Testing...</strong> Please interact with the dropdowns above to test functionality.
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const resultsDiv = document.getElementById("test-results");
    let testsPassed = 0;
    let totalTests = 0;
    
    function updateResults() {
        if (totalTests > 0) {
            const percentage = Math.round((testsPassed / totalTests) * 100);
            resultsDiv.innerHTML = `
                <strong>Test Results:</strong> ${testsPassed}/${totalTests} tests passed (${percentage}%)
                <br><small>Click on dropdowns to test functionality</small>
            `;
            
            if (percentage === 100) {
                resultsDiv.className = "alert alert-success";
            } else if (percentage >= 50) {
                resultsDiv.className = "alert alert-warning";
            } else {
                resultsDiv.className = "alert alert-danger";
            }
        }
    }
    
    // Test dropdown functionality
    document.querySelectorAll(".dropdown-toggle").forEach(function(toggle) {
        totalTests++;
        const dropdown = toggle.closest(".dropdown");
        const menu = dropdown.querySelector(".dropdown-menu");
        
        toggle.addEventListener("click", function() {
            if (dropdown.classList.contains("show")) {
                testsPassed++;
                updateResults();
            }
        });
    });
    
    // Test select elements
    document.querySelectorAll("select.form-control").forEach(function(select) {
        totalTests++;
        select.addEventListener("change", function() {
            if (this.value !== "") {
                testsPassed++;
                updateResults();
            }
        });
    });
    
    // Initial update
    updateResults();
});
</script>
';

echo $OUTPUT->footer();
?>



