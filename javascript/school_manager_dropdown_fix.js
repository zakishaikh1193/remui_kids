/**
 * School Manager Dashboard Dropdown Fix
 * Fixes dropdown functionality issues in the school manager dashboard
 */

define(['jquery'], function($) {
    'use strict';

    return {
        init: function() {
            $(document).ready(function() {
                this.initializeSchoolManagerDropdowns();
                this.fixSidebarDropdowns();
                this.initializeSchoolManagerComponents();
            }.bind(this));
        },

        initializeSchoolManagerDropdowns: function() {
            // Handle dropdown toggle clicks in school manager dashboard
            $(document).on('click', '[data-toggle="dropdown"]', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                var $dropdown = $(this).closest('.dropdown');
                var $menu = $dropdown.find('.dropdown-menu');
                
                // Close other dropdowns
                $('.dropdown').not($dropdown).removeClass('show');
                $('.dropdown-menu').not($menu).removeClass('show');
                
                // Toggle current dropdown
                $dropdown.toggleClass('show');
                $menu.toggleClass('show');
                
                // Update aria-expanded
                $(this).attr('aria-expanded', $dropdown.hasClass('show'));
            });

            // Close dropdowns when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.dropdown').length) {
                    $('.dropdown').removeClass('show');
                    $('.dropdown-menu').removeClass('show');
                    $('[data-toggle="dropdown"]').attr('aria-expanded', 'false');
                }
            });

            // Handle dropdown item clicks
            $(document).on('click', '.dropdown-item', function(e) {
                var $item = $(this);
                var $dropdown = $item.closest('.dropdown');
                var $toggle = $dropdown.find('[data-toggle="dropdown"]');
                
                // Update button text if data-active-item-text is present
                var $activeItemText = $toggle.find('[data-active-item-text]');
                if ($activeItemText.length) {
                    $activeItemText.text($item.text());
                }
                
                // Close dropdown
                $dropdown.removeClass('show');
                $dropdown.find('.dropdown-menu').removeClass('show');
                $toggle.attr('aria-expanded', 'false');
            });
        },

        fixSidebarDropdowns: function() {
            // Fix sidebar navigation dropdowns
            $('.school-manager-sidebar .dropdown-toggle').each(function() {
                var $this = $(this);
                
                // Ensure proper attributes
                if (!$this.attr('data-toggle')) {
                    $this.attr('data-toggle', 'dropdown');
                }
                
                // Ensure proper classes
                if (!$this.hasClass('dropdown-toggle')) {
                    $this.addClass('dropdown-toggle');
                }
                
                // Fix aria attributes
                if (!$this.attr('aria-haspopup')) {
                    $this.attr('aria-haspopup', 'true');
                }
                if (!$this.attr('aria-expanded')) {
                    $this.attr('aria-expanded', 'false');
                }
            });
        },

        initializeSchoolManagerComponents: function() {
            // Initialize school manager specific components
            this.initializeFloatingActionButtons();
            this.initializeSidebarNavigation();
            this.initializeStatsCards();
        },

        initializeFloatingActionButtons: function() {
            // Handle floating action button clicks
            $('.fab-button').on('click', function(e) {
                e.preventDefault();
                var $button = $(this);
                var action = $button.attr('title') || $button.find('i').attr('class');
                
                // Add click animation
                $button.addClass('fab-clicked');
                setTimeout(function() {
                    $button.removeClass('fab-clicked');
                }, 200);
                
                // Handle specific actions
                if (action.includes('Accessibility')) {
                    this.handleAccessibilityAction();
                } else if (action.includes('Help')) {
                    this.handleHelpAction();
                }
            }.bind(this));
        },

        initializeSidebarNavigation: function() {
            // Handle sidebar navigation clicks
            $('.school-manager-sidebar .sidebar-link').on('click', function(e) {
                var $link = $(this);
                var href = $link.attr('href');
                
                // Remove active class from all sidebar items
                $('.school-manager-sidebar .sidebar-item').removeClass('active');
                
                // Add active class to current item
                $link.closest('.sidebar-item').addClass('active');
                
                // Handle placeholder links
                if (href === '#' || !href) {
                    e.preventDefault();
                    console.log('Navigation to:', $link.find('.sidebar-text').text());
                }
            });
        },

        initializeStatsCards: function() {
            // Add hover effects to stats cards
            $('.school-manager-stat-card').hover(
                function() {
                    $(this).addClass('stat-card-hover');
                },
                function() {
                    $(this).removeClass('stat-card-hover');
                }
            );
        },

        handleAccessibilityAction: function() {
            // Handle accessibility button click
            console.log('Accessibility features activated');
            // You can add accessibility features here
        },

        handleHelpAction: function() {
            // Handle help button click
            console.log('Help requested');
            // You can add help functionality here
        }
    };
});
