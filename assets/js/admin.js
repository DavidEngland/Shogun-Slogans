/**
 * Shogun Slogans - Admin JavaScript
 * Version: 3.1.0
 * 
 * Handles admin interface functionality:
 * - Live preview of shortcodes
 * - Settings validation
 * - Copy-to-clipboard functionality
 * - Real-time testing
 * 
 * @package ShogunSlogans
 * @since 3.1.0
 */

(function($) {
    'use strict';

    // Admin configuration from WordPress
    const adminConfig = window.shogunAdminConfig || {
        options: {},
        ajax: {
            url: '/wp-admin/admin-ajax.php',
            nonce: ''
        }
    };

    /**
     * Debug logging for admin
     * 
     * @since 3.1.0
     * @param {string} message Debug message
     * @param {*} data Optional data to log
     */
    function debugLog(message, data = null) {
        if (console && console.log) {
            console.log(`[Shogun Admin] ${message}`, data || '');
        }
    }

    /**
     * Show admin notice
     * 
     * @since 3.1.0
     * @param {string} message Notice message
     * @param {string} type Notice type (success, error, warning, info)
     */
    function showNotice(message, type = 'info') {
        const noticeClass = `notice notice-${type}`;
        const noticeHtml = `
            <div class="${noticeClass} is-dismissible">
                <p>${message}</p>
                <button type="button" class="notice-dismiss">
                    <span class="screen-reader-text">Dismiss this notice.</span>
                </button>
            </div>
        `;
        
        $('.wrap h1').after(noticeHtml);
        
        // Auto-dismiss after 5 seconds for non-error notices
        if (type !== 'error') {
            setTimeout(() => {
                $('.notice').fadeOut();
            }, 5000);
        }
    }

    /**
     * Copy text to clipboard
     * 
     * @since 3.1.0
     * @param {string} text Text to copy
     * @param {jQuery} $button Button element that triggered the copy
     */
    function copyToClipboard(text, $button) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text)
                .then(() => {
                    showCopySuccess($button);
                })
                .catch(() => {
                    fallbackCopyToClipboard(text, $button);
                });
        } else {
            fallbackCopyToClipboard(text, $button);
        }
    }

    /**
     * Fallback copy method for older browsers
     * 
     * @since 3.1.0
     * @param {string} text Text to copy
     * @param {jQuery} $button Button element that triggered the copy
     */
    function fallbackCopyToClipboard(text, $button) {
        const $textarea = $('<textarea>')
            .val(text)
            .css({
                position: 'fixed',
                top: 0,
                left: 0,
                width: '2em',
                height: '2em',
                padding: 0,
                border: 'none',
                outline: 'none',
                boxShadow: 'none',
                background: 'transparent'
            })
            .appendTo('body');
        
        $textarea[0].select();
        $textarea[0].setSelectionRange(0, 99999);
        
        try {
            const successful = document.execCommand('copy');
            if (successful) {
                showCopySuccess($button);
            } else {
                showCopyError($button);
            }
        } catch (err) {
            showCopyError($button);
        }
        
        $textarea.remove();
    }

    /**
     * Show copy success feedback
     * 
     * @since 3.1.0
     * @param {jQuery} $button Button element
     */
    function showCopySuccess($button) {
        const originalText = $button.text();
        $button.text('Copied!').addClass('copied');
        
        setTimeout(() => {
            $button.text(originalText).removeClass('copied');
        }, 2000);
    }

    /**
     * Show copy error feedback
     * 
     * @since 3.1.0
     * @param {jQuery} $button Button element
     */
    function showCopyError($button) {
        const originalText = $button.text();
        $button.text('Copy failed').addClass('copy-error');
        
        setTimeout(() => {
            $button.text(originalText).removeClass('copy-error');
        }, 2000);
    }

    /**
     * Initialize copy buttons
     * 
     * @since 3.1.0
     */
    function initCopyButtons() {
        // Add copy buttons to code blocks
        $('code, pre').each(function() {
            const $code = $(this);
            const text = $code.text().trim();
            
            if (text && text.length > 10) {
                const $button = $('<button type="button" class="copy-button">Copy</button>');
                $code.parent().css('position', 'relative').append($button);
                
                $button.on('click', function(e) {
                    e.preventDefault();
                    copyToClipboard(text, $button);
                });
            }
        });
        
        // Add copy buttons to test results
        $('.test-result').each(function() {
            const $result = $(this);
            const $code = $result.prev('code');
            
            if ($code.length) {
                const text = $code.text().trim();
                const $button = $('<button type="button" class="button button-secondary copy-shortcode">Copy Shortcode</button>');
                $result.append($button);
                
                $button.on('click', function(e) {
                    e.preventDefault();
                    copyToClipboard(text, $button);
                });
            }
        });
        
        debugLog('Copy buttons initialized');
    }

    /**
     * Initialize live preview functionality
     * 
     * @since 3.1.0
     */
    function initLivePreview() {
        // Create preview container
        const $previewContainer = $(`
            <div class="shogun-preview-container">
                <h3>Live Preview</h3>
                <div class="shogun-preview-content">
                    <p>Enter a shortcode below to see a live preview:</p>
                    <input type="text" id="preview-shortcode" class="regular-text" 
                           placeholder="[typewriter_text text='Hello World!' speed='100']" />
                    <button type="button" id="preview-button" class="button">Preview</button>
                    <div id="preview-output"></div>
                </div>
            </div>
        `);
        
        // Add to sidebar if it exists
        const $sidebar = $('.shogun-admin-sidebar');
        if ($sidebar.length) {
            $sidebar.append($previewContainer);
        }
        
        // Handle preview button
        $('#preview-button').on('click', function() {
            const shortcode = $('#preview-shortcode').val().trim();
            if (shortcode) {
                generatePreview(shortcode);
            }
        });
        
        // Handle enter key in input
        $('#preview-shortcode').on('keypress', function(e) {
            if (e.which === 13) { // Enter key
                $('#preview-button').click();
            }
        });
        
        debugLog('Live preview initialized');
    }

    /**
     * Generate shortcode preview
     * 
     * @since 3.1.0
     * @param {string} shortcode Shortcode to preview
     */
    function generatePreview(shortcode) {
        const $output = $('#preview-output');
        $output.html('<div class="spinner is-active"></div>');
        
        $.ajax({
            url: adminConfig.ajax.url,
            type: 'POST',
            data: {
                action: 'shogun_slogans_preview',
                shortcode: shortcode,
                nonce: adminConfig.ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $output.html(response.data.html);
                    
                    // Initialize any animations in the preview
                    if (window.ShogunSlogans && window.ShogunSlogans.init) {
                        setTimeout(() => {
                            window.ShogunSlogans.init();
                        }, 100);
                    }
                    
                    debugLog('Preview generated successfully');
                } else {
                    $output.html(`<div class="error">Error: ${response.data.message}</div>`);
                    debugLog('Preview generation failed:', response.data.message);
                }
            },
            error: function(xhr, status, error) {
                $output.html(`<div class="error">AJAX Error: ${error}</div>`);
                debugLog('Preview AJAX error:', error);
            }
        });
    }

    /**
     * Initialize settings validation
     * 
     * @since 3.1.0
     */
    function initSettingsValidation() {
        const $form = $('form[action="options.php"]');
        
        if (!$form.length) {
            return;
        }
        
        $form.on('submit', function(e) {
            const isValid = validateSettings();
            
            if (!isValid) {
                e.preventDefault();
                showNotice('Please fix the validation errors before saving.', 'error');
            }
        });
        
        // Real-time validation
        $form.find('input[type="number"]').on('change', function() {
            validateNumberField($(this));
        });
        
        $form.find('input[type="text"]').on('blur', function() {
            validateTextField($(this));
        });
        
        debugLog('Settings validation initialized');
    }

    /**
     * Validate all settings
     * 
     * @since 3.1.0
     * @returns {boolean} True if valid
     */
    function validateSettings() {
        let isValid = true;
        
        // Validate number fields
        $('input[type="number"]').each(function() {
            if (!validateNumberField($(this))) {
                isValid = false;
            }
        });
        
        // Validate text fields
        $('input[type="text"]').each(function() {
            if (!validateTextField($(this))) {
                isValid = false;
            }
        });
        
        return isValid;
    }

    /**
     * Validate number field
     * 
     * @since 3.1.0
     * @param {jQuery} $field Field to validate
     * @returns {boolean} True if valid
     */
    function validateNumberField($field) {
        const value = parseInt($field.val());
        const min = parseInt($field.attr('min')) || 1;
        const max = parseInt($field.attr('max')) || 10000;
        
        let isValid = true;
        let errorMessage = '';
        
        if (isNaN(value)) {
            isValid = false;
            errorMessage = 'Please enter a valid number.';
        } else if (value < min) {
            isValid = false;
            errorMessage = `Value must be at least ${min}.`;
        } else if (value > max) {
            isValid = false;
            errorMessage = `Value must be no more than ${max}.`;
        }
        
        showFieldValidation($field, isValid, errorMessage);
        return isValid;
    }

    /**
     * Validate text field
     * 
     * @since 3.1.0
     * @param {jQuery} $field Field to validate
     * @returns {boolean} True if valid
     */
    function validateTextField($field) {
        const value = $field.val().trim();
        let isValid = true;
        let errorMessage = '';
        
        // Check if field is required (has required attribute)
        if ($field.attr('required') && !value) {
            isValid = false;
            errorMessage = 'This field is required.';
        }
        
        // Special validation for cursor field
        if ($field.attr('name') && $field.attr('name').includes('cursor')) {
            if (value.length > 5) {
                isValid = false;
                errorMessage = 'Cursor should be 5 characters or less.';
            }
        }
        
        showFieldValidation($field, isValid, errorMessage);
        return isValid;
    }

    /**
     * Show field validation state
     * 
     * @since 3.1.0
     * @param {jQuery} $field Field element
     * @param {boolean} isValid Whether field is valid
     * @param {string} errorMessage Error message if invalid
     */
    function showFieldValidation($field, isValid, errorMessage = '') {
        // Remove existing validation
        $field.removeClass('valid invalid');
        $field.next('.validation-message').remove();
        
        if (isValid) {
            $field.addClass('valid');
        } else {
            $field.addClass('invalid');
            if (errorMessage) {
                $field.after(`<div class="validation-message error">${errorMessage}</div>`);
            }
        }
    }

    /**
     * Initialize diagnostic tools
     * 
     * @since 3.1.0
     */
    function initDiagnosticTools() {
        // Add diagnostic buttons
        const $diagnosticContainer = $(`
            <div class="shogun-diagnostic-tools">
                <h3>Diagnostic Tools</h3>
                <button type="button" id="test-ajax" class="button">Test AJAX</button>
                <button type="button" id="test-rest" class="button">Test REST API</button>
                <button type="button" id="clear-cache" class="button">Clear Cache</button>
                <div id="diagnostic-output"></div>
            </div>
        `);
        
        const $sidebar = $('.shogun-admin-sidebar');
        if ($sidebar.length) {
            $sidebar.append($diagnosticContainer);
        }
        
        // Handle diagnostic buttons
        $('#test-ajax').on('click', testAjax);
        $('#test-rest').on('click', testRestAPI);
        $('#clear-cache').on('click', clearCache);
        
        debugLog('Diagnostic tools initialized');
    }

    /**
     * Test AJAX functionality
     * 
     * @since 3.1.0
     */
    function testAjax() {
        const $output = $('#diagnostic-output');
        $output.html('<div class="spinner is-active"></div>');
        
        $.ajax({
            url: adminConfig.ajax.url,
            type: 'POST',
            data: {
                action: 'shogun_slogans_test',
                nonce: adminConfig.ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $output.html(`<div class="success">✓ AJAX working: ${response.data.message}</div>`);
                } else {
                    $output.html(`<div class="error">✗ AJAX error: ${response.data.message}</div>`);
                }
            },
            error: function(xhr, status, error) {
                $output.html(`<div class="error">✗ AJAX request failed: ${error}</div>`);
            }
        });
    }

    /**
     * Test REST API functionality
     * 
     * @since 3.1.0
     */
    function testRestAPI() {
        const $output = $('#diagnostic-output');
        $output.html('<div class="spinner is-active"></div>');
        
        fetch('/wp-json/shogun-slogans/v1/test')
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    $output.html(`<div class="success">✓ REST API working: ${data.message}</div>`);
                } else {
                    $output.html(`<div class="error">✗ REST API error: ${data.message}</div>`);
                }
            })
            .catch(error => {
                $output.html(`<div class="error">✗ REST API request failed: ${error.message}</div>`);
            });
    }

    /**
     * Clear plugin cache
     * 
     * @since 3.1.0
     */
    function clearCache() {
        const $output = $('#diagnostic-output');
        $output.html('<div class="spinner is-active"></div>');
        
        $.ajax({
            url: adminConfig.ajax.url,
            type: 'POST',
            data: {
                action: 'shogun_slogans_clear_cache',
                nonce: adminConfig.ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $output.html(`<div class="success">✓ Cache cleared successfully</div>`);
                } else {
                    $output.html(`<div class="error">✗ Cache clear failed: ${response.data.message}</div>`);
                }
            },
            error: function(xhr, status, error) {
                $output.html(`<div class="error">✗ Cache clear request failed: ${error}</div>`);
            }
        });
    }

    /**
     * Initialize tooltips
     * 
     * @since 3.1.0
     */
    function initTooltips() {
        // Add tooltips to form fields
        $('input, select, textarea').each(function() {
            const $field = $(this);
            const description = $field.next('.description').text();
            
            if (description) {
                $field.attr('title', description);
            }
        });
        
        debugLog('Tooltips initialized');
    }

    /**
     * Initialize collapsible sections
     * 
     * @since 3.1.0
     */
    function initCollapsibleSections() {
        $('.shogun-admin-container .card h2, .shogun-admin-container .card h3').each(function() {
            const $header = $(this);
            const $content = $header.nextAll();
            
            if ($content.length) {
                $header.css('cursor', 'pointer').append(' <span class="toggle-indicator">−</span>');
                
                $header.on('click', function() {
                    const $indicator = $header.find('.toggle-indicator');
                    
                    if ($content.is(':visible')) {
                        $content.slideUp();
                        $indicator.text('+');
                    } else {
                        $content.slideDown();
                        $indicator.text('−');
                    }
                });
            }
        });
        
        debugLog('Collapsible sections initialized');
    }

    /**
     * Initialize admin interface
     * 
     * @since 3.1.0
     */
    function initAdmin() {
        debugLog('Initializing Shogun Slogans admin interface');
        
        // Initialize all components
        initCopyButtons();
        initLivePreview();
        initSettingsValidation();
        initDiagnosticTools();
        initTooltips();
        initCollapsibleSections();
        
        // Show welcome message on first load
        if (localStorage.getItem('shogun_admin_welcome') !== 'shown') {
            showNotice('Welcome to Shogun Slogans! Check out the documentation and tools below.', 'info');
            localStorage.setItem('shogun_admin_welcome', 'shown');
        }
        
        debugLog('Admin interface initialized successfully');
    }

    // Initialize when DOM is ready
    $(document).ready(function() {
        // Only initialize on Shogun Slogans admin pages
        if ($('.shogun-admin-container').length) {
            initAdmin();
        }
    });

    // Public API for other scripts
    window.ShogunAdmin = {
        debugLog: debugLog,
        showNotice: showNotice,
        copyToClipboard: copyToClipboard,
        generatePreview: generatePreview,
        validateSettings: validateSettings
    };

})(jQuery);
