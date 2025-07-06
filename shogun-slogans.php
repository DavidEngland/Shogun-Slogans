<?php
/**
 * Plugin Name: Shogun Slogans
 * Plugin URI: https://github.com/DavidEngland/Shogun-Slogans
 * Description: A modern WordPress plugin for creating stunning animated slogans with typewriter effects, handwritten styles, and dynamic text display. Perfect for hero sections, quotes, and call-to-action messages.
 * Version: 3.0.0
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * Author: David England
 * Author URI: https://github.com/DavidEngland
 * License: MIT License
 * License URI: https://opensource.org/licenses/MIT
 * Text Domain: shogun-slogans
 * Domain Path: /languages
 * Network: false
 * 
 * @package ShogunSlogans
 * @author David England
 * @copyright 2024 David England
 * @license MIT
 */

// Prevent direct access - Security first
if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

// Plugin constants for consistency and maintainability
define('SHOGUN_SLOGANS_VERSION', '3.0.0');
define('SHOGUN_SLOGANS_PLUGIN_FILE', __FILE__);
define('SHOGUN_SLOGANS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SHOGUN_SLOGANS_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('SHOGUN_SLOGANS_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('SHOGUN_SLOGANS_MIN_PHP_VERSION', '7.4');
define('SHOGUN_SLOGANS_MIN_WP_VERSION', '5.0');

/**
 * Main Shogun Slogans Plugin Class
 * 
 * This class handles all plugin functionality including:
 * - Asset loading and management
 * - Shortcode registration and processing
 * - Admin interface
 * - Plugin lifecycle management
 * - Error handling and logging
 * 
 * @since 3.0.0
 */
class ShogunSlogansPlugin {
    
    /**
     * Plugin instance
     * 
     * @since 3.0.0
     * @var ShogunSlogansPlugin|null
     */
    private static $instance = null;
    
    /**
     * Plugin options
     * 
     * @since 3.0.0
     * @var array
     */
    private $options = array();
    
    /**
     * Error messages
     * 
     * @since 3.0.0
     * @var array
     */
    private $errors = array();
    
    /**
     * Get singleton instance
     * 
     * @since 3.0.0
     * @return ShogunSlogansPlugin
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor - Initialize plugin
     * 
     * @since 3.0.0
     */
    private function __construct() {
        // Check system requirements
        if (!$this->check_requirements()) {
            return;
        }
        
        // Initialize plugin
        $this->init_hooks();
        $this->load_options();
        
        // Register activation and deactivation hooks
        register_activation_hook(SHOGUN_SLOGANS_PLUGIN_FILE, array($this, 'activate'));
        register_deactivation_hook(SHOGUN_SLOGANS_PLUGIN_FILE, array($this, 'deactivate'));
        register_uninstall_hook(SHOGUN_SLOGANS_PLUGIN_FILE, array(__CLASS__, 'uninstall'));
    }
    
    /**
     * Check system requirements
     * 
     * @since 3.0.0
     * @return bool
     */
    private function check_requirements() {
        // Check PHP version
        if (version_compare(PHP_VERSION, SHOGUN_SLOGANS_MIN_PHP_VERSION, '<')) {
            $this->add_error(
                sprintf(
                    __('Shogun Slogans requires PHP %s or higher. You are running PHP %s.', 'shogun-slogans'),
                    SHOGUN_SLOGANS_MIN_PHP_VERSION,
                    PHP_VERSION
                )
            );
            return false;
        }
        
        // Check WordPress version
        global $wp_version;
        if (version_compare($wp_version, SHOGUN_SLOGANS_MIN_WP_VERSION, '<')) {
            $this->add_error(
                sprintf(
                    __('Shogun Slogans requires WordPress %s or higher. You are running WordPress %s.', 'shogun-slogans'),
                    SHOGUN_SLOGANS_MIN_WP_VERSION,
                    $wp_version
                )
            );
            return false;
        }
        
        return true;
    }
    
    /**
     * Initialize hooks
     * 
     * @since 3.0.0
     */
    private function init_hooks() {
        // Core hooks
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Shortcode hooks
        add_action('init', array($this, 'register_shortcodes'));
        
        // Admin hooks
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_notices', array($this, 'display_admin_notices'));
        
        // AJAX hooks
        add_action('wp_ajax_shogun_slogans_preview', array($this, 'ajax_preview'));
        add_action('wp_ajax_nopriv_shogun_slogans_preview', array($this, 'ajax_preview'));
        
        // Block editor support
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_block_editor_assets'));
    }
    
    /**
     * Load plugin options
     * 
     * @since 3.0.0
     */
    private function load_options() {
        $defaults = array(
            'default_speed' => 100,
            'default_cursor' => '|',
            'default_loop' => true,
            'enable_accessibility' => true,
            'enable_performance_optimization' => true,
            'custom_css' => '',
            'debug_mode' => false,
        );
        
        $this->options = wp_parse_args(get_option('shogun_slogans_options', array()), $defaults);
    }
    
    /**
     * Plugin initialization
     * 
     * @since 3.0.0
     */
    public function init() {
        // Load text domain for internationalization
        load_plugin_textdomain(
            'shogun-slogans',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
        
        // Initialize components
        $this->init_custom_post_types();
        $this->init_rest_api();
        
        // Add support for excerpt in custom post types
        add_post_type_support('shogun_slogan', 'excerpt');
        
        // Fire init action for extensibility
        do_action('shogun_slogans_init', $this);
    }
    
    /**
     * Initialize custom post types
     * 
     * @since 3.0.0
     */
    private function init_custom_post_types() {
        register_post_type('shogun_slogan', array(
            'labels' => array(
                'name' => __('Slogans', 'shogun-slogans'),
                'singular_name' => __('Slogan', 'shogun-slogans'),
                'add_new' => __('Add New Slogan', 'shogun-slogans'),
                'add_new_item' => __('Add New Slogan', 'shogun-slogans'),
                'edit_item' => __('Edit Slogan', 'shogun-slogans'),
                'new_item' => __('New Slogan', 'shogun-slogans'),
                'view_item' => __('View Slogan', 'shogun-slogans'),
                'search_items' => __('Search Slogans', 'shogun-slogans'),
                'not_found' => __('No slogans found', 'shogun-slogans'),
                'not_found_in_trash' => __('No slogans found in trash', 'shogun-slogans'),
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'shogun-slogans',
            'capability_type' => 'post',
            'hierarchical' => false,
            'supports' => array('title', 'editor', 'excerpt', 'custom-fields'),
            'show_in_rest' => true,
            'rest_base' => 'shogun-slogans',
        ));
    }
    
    /**
     * Initialize REST API endpoints
     * 
     * @since 3.0.0
     */
    private function init_rest_api() {
        add_action('rest_api_init', function() {
            register_rest_route('shogun-slogans/v1', '/preview', array(
                'methods' => 'POST',
                'callback' => array($this, 'rest_preview'),
                'permission_callback' => function() {
                    return current_user_can('edit_posts');
                },
                'args' => array(
                    'text' => array(
                        'required' => true,
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'speed' => array(
                        'default' => 100,
                        'sanitize_callback' => 'absint',
                    ),
                    'cursor' => array(
                        'default' => '|',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                ),
            ));
        });
    }
    
    /**
     * Enqueue frontend assets
     * 
     * @since 3.0.0
     */
    public function enqueue_frontend_assets() {
        // Only load if needed (has shortcode or typewriter elements)
        if ($this->should_load_assets()) {
            // Main CSS
            wp_enqueue_style(
                'shogun-slogans-style',
                SHOGUN_SLOGANS_PLUGIN_URL . 'assets/css/slogans.css',
                array(),
                SHOGUN_SLOGANS_VERSION
            );
            
            // Add custom CSS if provided
            if (!empty($this->options['custom_css'])) {
                wp_add_inline_style('shogun-slogans-style', $this->options['custom_css']);
            }
            
            // Main JavaScript
            wp_enqueue_script(
                'shogun-slogans-script',
                SHOGUN_SLOGANS_PLUGIN_URL . 'assets/js/slogans.js',
                array('jquery'),
                SHOGUN_SLOGANS_VERSION,
                true
            );
            
            // Localize script with options and strings
            wp_localize_script('shogun-slogans-script', 'shogunSlogansConfig', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('shogun_slogans_nonce'),
                'restUrl' => rest_url('shogun-slogans/v1/'),
                'restNonce' => wp_create_nonce('wp_rest'),
                'options' => array(
                    'defaultSpeed' => $this->options['default_speed'],
                    'defaultCursor' => $this->options['default_cursor'],
                    'defaultLoop' => $this->options['default_loop'],
                    'enableAccessibility' => $this->options['enable_accessibility'],
                    'enablePerformanceOptimization' => $this->options['enable_performance_optimization'],
                    'debugMode' => $this->options['debug_mode'],
                ),
                'strings' => array(
                    'loading' => __('Loading...', 'shogun-slogans'),
                    'error' => __('Error loading content', 'shogun-slogans'),
                    'pauseAnimation' => __('Pause animation', 'shogun-slogans'),
                    'resumeAnimation' => __('Resume animation', 'shogun-slogans'),
                ),
            ));
        }
    }
    
    /**
     * Enqueue admin assets
     * 
     * @since 3.0.0
     * @param string $hook Current admin page hook
     */
    public function enqueue_admin_assets($hook) {
        // Only load on plugin admin pages
        if (!$this->is_plugin_admin_page($hook)) {
            return;
        }
        
        // Admin CSS
        wp_enqueue_style(
            'shogun-slogans-admin-style',
            SHOGUN_SLOGANS_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            SHOGUN_SLOGANS_VERSION
        );
        
        // Admin JavaScript
        wp_enqueue_script(
            'shogun-slogans-admin-script',
            SHOGUN_SLOGANS_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'wp-color-picker'),
            SHOGUN_SLOGANS_VERSION,
            true
        );
        
        // Color picker for admin
        wp_enqueue_style('wp-color-picker');
        
        // Localize admin script
        wp_localize_script('shogun-slogans-admin-script', 'shogunSlogansAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('shogun_slogans_admin_nonce'),
            'strings' => array(
                'confirmDelete' => __('Are you sure you want to delete this slogan?', 'shogun-slogans'),
                'saved' => __('Settings saved successfully!', 'shogun-slogans'),
                'error' => __('Error saving settings. Please try again.', 'shogun-slogans'),
                'preview' => __('Preview', 'shogun-slogans'),
                'copy' => __('Copy to clipboard', 'shogun-slogans'),
                'copied' => __('Copied!', 'shogun-slogans'),
            ),
        ));
    }
    
    /**
     * Enqueue block editor assets
     * 
     * @since 3.0.0
     */
    public function enqueue_block_editor_assets() {
        wp_enqueue_script(
            'shogun-slogans-block-editor',
            SHOGUN_SLOGANS_PLUGIN_URL . 'assets/js/block-editor.js',
            array('wp-blocks', 'wp-element', 'wp-editor'),
            SHOGUN_SLOGANS_VERSION,
            true
        );
        
        wp_enqueue_style(
            'shogun-slogans-block-editor-style',
            SHOGUN_SLOGANS_PLUGIN_URL . 'assets/css/block-editor.css',
            array(),
            SHOGUN_SLOGANS_VERSION
        );
    }
    
    /**
     * Register shortcodes
     * 
     * @since 3.0.0
     */
    public function register_shortcodes() {
        add_shortcode('shogun_slogan', array($this, 'shogun_slogan_shortcode'));
        add_shortcode('typewriter_text', array($this, 'typewriter_text_shortcode'));
        add_shortcode('shogun_typewriter', array($this, 'typewriter_text_shortcode')); // Alias
    }
    
    /**
     * Check if assets should be loaded
     * 
     * @since 3.0.0
     * @return bool
     */
    private function should_load_assets() {
        global $post;
        
        // Always load in admin
        if (is_admin()) {
            return true;
        }
        
        // Load if post/page contains shortcodes
        if (is_singular() && $post && has_shortcode($post->post_content, 'typewriter_text')) {
            return true;
        }
        
        if (is_singular() && $post && has_shortcode($post->post_content, 'shogun_slogan')) {
            return true;
        }
        
        if (is_singular() && $post && has_shortcode($post->post_content, 'shogun_typewriter')) {
            return true;
        }
        
        // Load if widgets contain shortcodes
        if (is_active_widget(false, false, 'text')) {
            return true;
        }
        
        // Load if page contains typewriter class
        if (is_singular() && $post && strpos($post->post_content, 'shogun-typewriter') !== false) {
            return true;
        }
        
        // Allow themes/plugins to force loading
        return apply_filters('shogun_slogans_should_load_assets', false);
    }
    
    /**
     * Check if current page is plugin admin page
     * 
     * @since 3.0.0
     * @param string $hook Current admin page hook
     * @return bool
     */
    private function is_plugin_admin_page($hook) {
        $plugin_pages = array(
            'toplevel_page_shogun-slogans',
            'shogun-slogans_page_shogun-slogans-settings',
            'shogun-slogans_page_shogun-slogans-help',
        );
        
        return in_array($hook, $plugin_pages, true);
    }
    
    /**
     * Add admin menu
     * 
     * @since 3.0.0
     */
    public function add_admin_menu() {
        // Main menu page
        add_menu_page(
            __('Shogun Slogans', 'shogun-slogans'),
            __('Shogun Slogans', 'shogun-slogans'),
            'manage_options',
            'shogun-slogans',
            array($this, 'admin_page'),
            'dashicons-format-quote',
            30
        );
        
        // Settings submenu
        add_submenu_page(
            'shogun-slogans',
            __('Settings', 'shogun-slogans'),
            __('Settings', 'shogun-slogans'),
            'manage_options',
            'shogun-slogans-settings',
            array($this, 'settings_page')
        );
        
        // Help submenu
        add_submenu_page(
            'shogun-slogans',
            __('Help & Documentation', 'shogun-slogans'),
            __('Help', 'shogun-slogans'),
            'manage_options',
            'shogun-slogans-help',
            array($this, 'help_page')
        );
    }
    
    /**
     * Register settings
     * 
     * @since 3.0.0
     */
    public function register_settings() {
        register_setting(
            'shogun_slogans_settings',
            'shogun_slogans_options',
            array(
                'sanitize_callback' => array($this, 'sanitize_settings'),
                'default' => array(),
            )
        );
        
        // General settings section
        add_settings_section(
            'shogun_slogans_general',
            __('General Settings', 'shogun-slogans'),
            array($this, 'general_settings_section_callback'),
            'shogun_slogans_settings'
        );
        
        // Individual settings fields
        add_settings_field(
            'default_speed',
            __('Default Typing Speed', 'shogun-slogans'),
            array($this, 'default_speed_field_callback'),
            'shogun_slogans_settings',
            'shogun_slogans_general'
        );
        
        add_settings_field(
            'default_cursor',
            __('Default Cursor', 'shogun-slogans'),
            array($this, 'default_cursor_field_callback'),
            'shogun_slogans_settings',
            'shogun_slogans_general'
        );
        
        add_settings_field(
            'default_loop',
            __('Default Loop Setting', 'shogun-slogans'),
            array($this, 'default_loop_field_callback'),
            'shogun_slogans_settings',
            'shogun_slogans_general'
        );
        
        add_settings_field(
            'enable_accessibility',
            __('Enable Accessibility Features', 'shogun-slogans'),
            array($this, 'enable_accessibility_field_callback'),
            'shogun_slogans_settings',
            'shogun_slogans_general'
        );
        
        add_settings_field(
            'enable_performance_optimization',
            __('Enable Performance Optimization', 'shogun-slogans'),
            array($this, 'enable_performance_optimization_field_callback'),
            'shogun_slogans_settings',
            'shogun_slogans_general'
        );
        
        add_settings_field(
            'custom_css',
            __('Custom CSS', 'shogun-slogans'),
            array($this, 'custom_css_field_callback'),
            'shogun_slogans_settings',
            'shogun_slogans_general'
        );
        
        add_settings_field(
            'debug_mode',
            __('Debug Mode', 'shogun-slogans'),
            array($this, 'debug_mode_field_callback'),
            'shogun_slogans_settings',
            'shogun_slogans_general'
        );
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Shogun Slogans', 'shogun-slogans'),
            __('Shogun Slogans', 'shogun-slogans'),
            'manage_options',
            'shogun-slogans',
            array($this, 'admin_page'),
            'dashicons-format-quote',
            30
        );
    }
    
    /**
     * Admin page callback
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Shogun Slogans', 'shogun-slogans'); ?></h1>
            
            <div class="shogun-admin-container">
                <div class="shogun-admin-main">
                    <div class="card">
                        <h2><?php _e('Shortcode Usage', 'shogun-slogans'); ?></h2>
                        <p><?php _e('Use these shortcodes to display slogans and typewriter effects:', 'shogun-slogans'); ?></p>
                        
                        <h3><?php _e('Basic Slogan', 'shogun-slogans'); ?></h3>
                        <code>[shogun_slogan text="Your slogan here" style="fade"]</code>
                        
                        <h3><?php _e('Typewriter Effect', 'shogun-slogans'); ?></h3>
                        <code>[typewriter_text text="Type this text..." speed="100" cursor="|"]</code>
                        
                        <h3><?php _e('Available Parameters', 'shogun-slogans'); ?></h3>
                        <ul>
                            <li><strong>text:</strong> <?php _e('The text to display', 'shogun-slogans'); ?></li>
                            <li><strong>style:</strong> <?php _e('Animation style (fade, slide, typewriter)', 'shogun-slogans'); ?></li>
                            <li><strong>speed:</strong> <?php _e('Typing speed for typewriter effect (in milliseconds)', 'shogun-slogans'); ?></li>
                            <li><strong>cursor:</strong> <?php _e('Cursor character for typewriter effect', 'shogun-slogans'); ?></li>
                            <li><strong>loop:</strong> <?php _e('Loop the animation (true/false)', 'shogun-slogans'); ?></li>
                        </ul>
                    </div>
                    
                    <div class="card">
                        <h2><?php _e('Live Preview', 'shogun-slogans'); ?></h2>
                        <div class="shogun-preview-area">
                            <div class="typewriter-demo" 
                                 data-text="Welcome to Shogun Slogans! This plugin creates amazing typewriter effects..."
                                 data-speed="80"
                                 data-cursor="|">
                                <span class="typewriter-text"></span>
                                <span class="typewriter-cursor">|</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="shogun-admin-sidebar">
                    <div class="card">
                        <h3><?php _e('Plugin Info', 'shogun-slogans'); ?></h3>
                        <p><strong><?php _e('Version:', 'shogun-slogans'); ?></strong> <?php echo SHOGUN_SLOGANS_VERSION; ?></p>
                        <p><strong><?php _e('Status:', 'shogun-slogans'); ?></strong> <span class="status-active"><?php _e('Active', 'shogun-slogans'); ?></span></p>
                    </div>
                    
                    <div class="card">
                        <h3><?php _e('Quick Examples', 'shogun-slogans'); ?></h3>
                        <p><?php _e('Copy and paste these examples:', 'shogun-slogans'); ?></p>
                        
                        <div class="example-code">
                            <strong><?php _e('Fast Typewriter:', 'shogun-slogans'); ?></strong><br>
                            <code>[typewriter_text text="Fast typing!" speed="50"]</code>
                        </div>
                        
                        <div class="example-code">
                            <strong><?php _e('Slow Dramatic:', 'shogun-slogans'); ?></strong><br>
                            <code>[typewriter_text text="Slow... and... dramatic..." speed="200"]</code>
                        </div>
                        
                        <div class="example-code">
                            <strong><?php _e('Custom Cursor:', 'shogun-slogans'); ?></strong><br>
                            <code>[typewriter_text text="Custom cursor" cursor="▌"]</code>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Initialize demo typewriter
            var demo = $('.typewriter-demo');
            if (demo.length) {
                new ShogunTypewriter(demo[0]);
            }
        });
        </script>
        <?php
    }
    
    /**
     * Shogun Slogan Shortcode
     */
    public function shogun_slogan_shortcode($atts) {
        $atts = shortcode_atts(array(
            'text' => 'Your slogan here',
            'style' => 'fade',
            'class' => '',
            'id' => ''
        ), $atts, 'shogun_slogan');
        
        $classes = array('shogun-slogan', 'shogun-' . esc_attr($atts['style']));
        if (!empty($atts['class'])) {
            $classes[] = esc_attr($atts['class']);
        }
        
        $id_attr = !empty($atts['id']) ? 'id="' . esc_attr($atts['id']) . '"' : '';
        
        return sprintf(
            '<div %s class="%s"><span class="slogan-text">%s</span></div>',
            $id_attr,
            implode(' ', $classes),
            esc_html($atts['text'])
        );
    }
    
    /**
     * Typewriter Text Shortcode
     */
    public function typewriter_text_shortcode($atts) {
        $atts = shortcode_atts(array(
            'text' => 'Type your message here...',
            'speed' => '100',
            'cursor' => '|',
            'loop' => 'true',
            'delay' => '0',
            'class' => '',
            'id' => ''
        ), $atts, 'typewriter_text');
        
        $classes = array('shogun-typewriter');
        if (!empty($atts['class'])) {
            $classes[] = esc_attr($atts['class']);
        }
        
        $id_attr = !empty($atts['id']) ? 'id="' . esc_attr($atts['id']) . '"' : '';
        
        $data_attrs = array(
            'data-text="' . esc_attr($atts['text']) . '"',
            'data-speed="' . esc_attr($atts['speed']) . '"',
            'data-cursor="' . esc_attr($atts['cursor']) . '"',
            'data-loop="' . esc_attr($atts['loop']) . '"',
            'data-delay="' . esc_attr($atts['delay']) . '"'
        );
        
        return sprintf(
            '<div %s class="%s" %s><span class="typewriter-text"></span><span class="typewriter-cursor">%s</span></div>',
            $id_attr,
            implode(' ', $classes),
            implode(' ', $data_attrs),
            esc_html($atts['cursor'])
        );
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create options with default values
        add_option('shogun_slogans_version', SHOGUN_SLOGANS_VERSION);
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clean up if needed
        flush_rewrite_rules();
    }
}

// Initialize the plugin
ShogunSlogans::get_instance();

/**
 * Helper function to display typewriter text
 */
function shogun_typewriter($text, $speed = 100, $cursor = '|') {
    return do_shortcode("[typewriter_text text=\"{$text}\" speed=\"{$speed}\" cursor=\"{$cursor}\"]");
}

/**
 * Helper function to display slogan
 */
function shogun_slogan($text, $style = 'fade') {
    return do_shortcode("[shogun_slogan text=\"{$text}\" style=\"{$style}\"]");
}