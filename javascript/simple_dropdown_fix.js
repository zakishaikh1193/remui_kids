/**
 * Simple Dropdown Fix - No jQuery dependency
 * Basic dropdown functionality without external dependencies
 */

(function() {
    'use strict';

    // Wait for DOM to be ready
    function domReady(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback);
        } else {
            callback();
        }
    }

    // Simple dropdown functionality
    function initializeDropdowns() {
        // Handle dropdown toggle clicks
        document.addEventListener('click', function(e) {
            var target = e.target;
            var dropdownToggle = target.closest('[data-toggle="dropdown"]');
            
            if (dropdownToggle) {
                e.preventDefault();
                e.stopPropagation();
                
                var dropdown = dropdownToggle.closest('.dropdown');
                var menu = dropdown ? dropdown.querySelector('.dropdown-menu') : null;
                
                if (dropdown && menu) {
                    // Close other dropdowns
                    var allDropdowns = document.querySelectorAll('.dropdown');
                    allDropdowns.forEach(function(dd) {
                        if (dd !== dropdown) {
                            dd.classList.remove('show');
                            var ddMenu = dd.querySelector('.dropdown-menu');
                            if (ddMenu) ddMenu.classList.remove('show');
                        }
                    });
                    
                    // Toggle current dropdown
                    dropdown.classList.toggle('show');
                    menu.classList.toggle('show');
                    
                    // Update aria-expanded
                    dropdownToggle.setAttribute('aria-expanded', dropdown.classList.contains('show'));
                }
            } else {
                // Close all dropdowns when clicking outside
                var openDropdowns = document.querySelectorAll('.dropdown.show');
                openDropdowns.forEach(function(dropdown) {
                    dropdown.classList.remove('show');
                    var menu = dropdown.querySelector('.dropdown-menu');
                    if (menu) menu.classList.remove('show');
                });
                
                var allToggles = document.querySelectorAll('[data-toggle="dropdown"]');
                allToggles.forEach(function(toggle) {
                    toggle.setAttribute('aria-expanded', 'false');
                });
            }
        });

        // Handle dropdown item clicks
        document.addEventListener('click', function(e) {
            var target = e.target;
            var dropdownItem = target.closest('.dropdown-item');
            
            if (dropdownItem) {
                var dropdown = dropdownItem.closest('.dropdown');
                var toggle = dropdown ? dropdown.querySelector('[data-toggle="dropdown"]') : null;
                
                if (dropdown && toggle) {
                    // Update button text if data-active-item-text is present
                    var activeItemText = toggle.querySelector('[data-active-item-text]');
                    if (activeItemText) {
                        activeItemText.textContent = dropdownItem.textContent.trim();
                    }
                    
                    // Close dropdown
                    dropdown.classList.remove('show');
                    var menu = dropdown.querySelector('.dropdown-menu');
                    if (menu) menu.classList.remove('show');
                    toggle.setAttribute('aria-expanded', 'false');
                    
                    // Prevent default if it's a placeholder link
                    if (dropdownItem.getAttribute('href') === '#') {
                        e.preventDefault();
                    }
                }
            }
        });

        // Initialize dropdown toggles
        var dropdownToggles = document.querySelectorAll('[data-toggle="dropdown"]');
        dropdownToggles.forEach(function(toggle) {
            // Ensure proper attributes
            if (!toggle.getAttribute('aria-haspopup')) {
                toggle.setAttribute('aria-haspopup', 'true');
            }
            if (!toggle.getAttribute('aria-expanded')) {
                toggle.setAttribute('aria-expanded', 'false');
            }
        });
    }

    // Initialize when DOM is ready
    domReady(function() {
        initializeDropdowns();
        console.log('Simple dropdown fix initialized');
    });

})();


