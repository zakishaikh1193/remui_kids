/**
 * Course View Dropdown Fix
 * Specifically fixes dropdown issues on course view pages
 */

define(['jquery'], function($) {
    'use strict';

    return {
        init: function() {
            $(document).ready(function() {
                this.fixCourseDropdowns();
                this.handleActivityDropdowns();
                this.preventDropdownFlickering();
            }.bind(this));
        },

        fixCourseDropdowns: function() {
            // Fix course activity dropdowns
            $('.course-content .dropdown').each(function() {
                var $dropdown = $(this);
                var $toggle = $dropdown.find('[data-toggle="dropdown"], [data-bs-toggle="dropdown"]');
                var $menu = $dropdown.find('.dropdown-menu');
                
                // Ensure proper attributes
                if (!$toggle.attr('aria-haspopup')) {
                    $toggle.attr('aria-haspopup', 'true');
                }
                if (!$toggle.attr('aria-expanded')) {
                    $toggle.attr('aria-expanded', 'false');
                }
                
                // Fix menu positioning
                if ($menu.length) {
                    $menu.css({
                        'position': 'absolute',
                        'z-index': '1000',
                        'display': 'none'
                    });
                }
            });
        },

        handleActivityDropdowns: function() {
            // Handle activity dropdown toggles
            $(document).on('click', '.course-content [data-toggle="dropdown"], .course-content [data-bs-toggle="dropdown"]', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                var $toggle = $(this);
                var $dropdown = $toggle.closest('.dropdown');
                var $menu = $dropdown.find('.dropdown-menu');
                
                // Close other dropdowns
                $('.course-content .dropdown').not($dropdown).removeClass('show');
                $('.course-content .dropdown-menu').not($menu).removeClass('show');
                
                // Toggle current dropdown
                $dropdown.toggleClass('show');
                $menu.toggleClass('show');
                
                // Update aria-expanded
                $toggle.attr('aria-expanded', $dropdown.hasClass('show'));
                
                // Position menu correctly
                if ($dropdown.hasClass('show')) {
                    this.positionDropdownMenu($dropdown, $menu);
                }
            }.bind(this));
            
            // Handle hover events for activity dropdowns
            $(document).on('mouseenter', '.course-content .dropdown', function() {
                var $dropdown = $(this);
                var $menu = $dropdown.find('.dropdown-menu');
                
                // Close other dropdowns
                $('.course-content .dropdown').not($dropdown).removeClass('show');
                $('.course-content .dropdown-menu').not($menu).removeClass('show');
                
                // Show current dropdown
                $dropdown.addClass('show');
                $menu.addClass('show');
                
                // Position menu correctly
                this.positionDropdownMenu($dropdown, $menu);
            }.bind(this));
            
            // Handle mouse leave with delay
            $(document).on('mouseleave', '.course-content .dropdown', function() {
                var $dropdown = $(this);
                var $menu = $dropdown.find('.dropdown-menu');
                
                setTimeout(function() {
                    if (!$dropdown.is(':hover') && !$menu.is(':hover')) {
                        $dropdown.removeClass('show');
                        $menu.removeClass('show');
                    }
                }, 200);
            });
        },

        positionDropdownMenu: function($dropdown, $menu) {
            // Get dropdown position
            var dropdownOffset = $dropdown.offset();
            var dropdownWidth = $dropdown.outerWidth();
            var menuWidth = $menu.outerWidth();
            var windowWidth = $(window).width();
            
            // Check if menu would overflow to the right
            if (dropdownOffset.left + menuWidth > windowWidth) {
                $menu.addClass('dropdown-menu-right');
            } else {
                $menu.removeClass('dropdown-menu-right');
            }
            
            // Ensure menu is visible
            $menu.css({
                'display': 'block',
                'opacity': '1',
                'visibility': 'visible'
            });
        },

        preventDropdownFlickering: function() {
            // Add CSS to prevent flickering
            $('<style>')
                .prop('type', 'text/css')
                .html(`
                    .course-content .dropdown-menu {
                        transition: opacity 0.2s ease-in-out, visibility 0.2s ease-in-out;
                        will-change: opacity, visibility;
                    }
                    
                    .course-content .dropdown:hover .dropdown-menu {
                        display: block !important;
                        opacity: 1 !important;
                        visibility: visible !important;
                    }
                    
                    .course-content .dropdown-menu:hover {
                        display: block !important;
                        opacity: 1 !important;
                        visibility: visible !important;
                    }
                `)
                .appendTo('head');
        }
    };
});
