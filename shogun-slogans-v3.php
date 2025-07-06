<?php
/**
 * Plugin Name: Shogun Slogans
 * Plugin URI: https://github.com/your-username/shogun-slogans
 * Description: A modern WordPress plugin for creating stunning animated slogans with typewriter effects, handwritten styles, and dynamic text display. Perfect for hero sections, quotes, and call-to-action messages.
 * Version: 3.0.0
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * License: GPL v3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: shogun-slogans
 * Domain Path: /languages
 * Network: false
 * 
 * @package ShogunSlogans
 * @author Your Name
 * @copyright 2025 Your Name
 * @license GPL-3.0+
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
            add_action('admin_notices', array($this, 'display_admin_notices'));
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
                    'Shogun Slogans requires PHP %s or higher. You are running PHP %s.',
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
                    'Shogun Slogans requires WordPress %s or higher. You are running WordPress %s.',
                    SHOGUN_SLOGANS_MIN_WP_VERSION,
                    $wp_version
                )
            );
            return false;
        }
        
        return true;
    }
    
    /**
     * Add error message
     * 
     * @since 3.0.0
     * @param string $message Error message
     */
    private function add_error($message) {
        $this->errors[] = $message;
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
    }
    
    /**
     * Display admin notices
     * 
     * @since 3.0.0
     */
    public function display_admin_notices() {
        if (!empty($this->errors)) {
            foreach ($this->errors as $error) {
                echo '<div class="notice notice-error"><p>' . esc_html($error) . '</p></div>';
            }
        }
    }
    
    /**
     * Shogun Slogan Shortcode
     * 
     * @since 3.0.0
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function shogun_slogan_shortcode($atts) {
        $atts = shortcode_atts(array(
            'text' => 'Your slogan here',
            'style' => 'fade',
            'class' => '',
            'id' => '',
            'color' => '',
            'size' => '',
        ), $atts, 'shogun_slogan');
        
        $classes = array('shogun-slogan', 'shogun-' . esc_attr($atts['style']));
        if (!empty($atts['class'])) {
            $classes[] = esc_attr($atts['class']);
        }
        
        $id_attr = !empty($atts['id']) ? 'id="' . esc_attr($atts['id']) . '"' : '';
        
        $style_attr = '';
        if (!empty($atts['color']) || !empty($atts['size'])) {
            $styles = array();
            if (!empty($atts['color'])) {
                $styles[] = 'color: ' . esc_attr($atts['color']);
            }
            if (!empty($atts['size'])) {
                $styles[] = 'font-size: ' . esc_attr($atts['size']);
            }
            $style_attr = 'style="' . implode('; ', $styles) . '"';
        }
        
        return sprintf(
            '<div %s class="%s" %s><span class="slogan-text">%s</span></div>',
            $id_attr,
            implode(' ', $classes),
            $style_attr,
            esc_html($atts['text'])
        );
    }
    
    /**
     * Typewriter Text Shortcode
     * 
     * Enhanced with more options and better error handling
     * 
     * @since 3.0.0
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function typewriter_text_shortcode($atts) {
        $atts = shortcode_atts(array(
            'text' => 'Type your message here...',
            'speed' => $this->options['default_speed'],
            'cursor' => $this->options['default_cursor'],
            'loop' => $this->options['default_loop'] ? 'true' : 'false',
            'delay' => '0',
            'delete_speed' => '50',
            'pause_end' => '2000',
            'pause_start' => '1000',
            'class' => '',
            'id' => '',
            'style' => '',
        ), $atts, 'typewriter_text');
        
        // Sanitize attributes
        $text = esc_attr($atts['text']);
        $speed = absint($atts['speed']);
        $cursor = esc_attr($atts['cursor']);
        $loop = $atts['loop'] === 'true' ? 'true' : 'false';
        $delay = absint($atts['delay']);
        $delete_speed = absint($atts['delete_speed']);
        $pause_end = absint($atts['pause_end']);
        $pause_start = absint($atts['pause_start']);
        
        $classes = array('shogun-typewriter');
        if (!empty($atts['class'])) {
            $classes[] = esc_attr($atts['class']);
        }
        
        $id_attr = !empty($atts['id']) ? 'id="' . esc_attr($atts['id']) . '"' : '';
        $style_attr = !empty($atts['style']) ? 'style="' . esc_attr($atts['style']) . '"' : '';
        
        $data_attrs = array(
            'data-text="' . $text . '"',
            'data-speed="' . $speed . '"',
            'data-cursor="' . $cursor . '"',
            'data-loop="' . $loop . '"',
            'data-delay="' . $delay . '"',
            'data-delete-speed="' . $delete_speed . '"',
            'data-pause-end="' . $pause_end . '"',
            'data-pause-start="' . $pause_start . '"',
        );
        
        return sprintf(
            '<div %s class="%s" %s %s><span class="typewriter-text"></span><span class="typewriter-cursor">%s</span></div>',
            $id_attr,
            implode(' ', $classes),
            $style_attr,
            implode(' ', $data_attrs),
            esc_html($cursor)
        );
    }
    
    /**
     * Admin page callback
     * 
     * @since 3.0.0
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Shogun Slogans', 'shogun-slogans'); ?></h1>
            
            <div class="shogun-admin-container">
                <div class="shogun-admin-main">
                    <div class="card">
                        <h2><?php _e('Quick Start Guide', 'shogun-slogans'); ?></h2>
                        <p><?php _e('Get started with Shogun Slogans in minutes:', 'shogun-slogans'); ?></p>
                        
                        <h3><?php _e('1. Basic Typewriter Effect', 'shogun-slogans'); ?></h3>
                        <div class="code-example">
                            <code>[typewriter_text text="Hello World!" speed="100" cursor="|"]</code>
                            <button class="button button-small copy-code" data-clipboard-text='[typewriter_text text="Hello World!" speed="100" cursor="|"]'>
                                <?php _e('Copy', 'shogun-slogans'); ?>
                            </button>
                        </div>
                        
                        <h3><?php _e('2. REIA Style Example', 'shogun-slogans'); ?></h3>
                        <div class="code-example">
                            <code>[typewriter_text text="I will help you make The Smart Move - I guarantee it!" speed="80" cursor="|" loop="false"]</code>
                            <button class="button button-small copy-code" data-clipboard-text='[typewriter_text text="I will help you make The Smart Move - I guarantee it!" speed="80" cursor="|" loop="false"]'>
                                <?php _e('Copy', 'shogun-slogans'); ?>
                            </button>
                        </div>
                        
                        <h3><?php _e('3. Handwritten Style', 'shogun-slogans'); ?></h3>
                        <div class="code-example">
                            <code>[typewriter_text text="Elegant handwritten message" speed="150" cursor="✍️" class="handwritten-style"]</code>
                            <button class="button button-small copy-code" data-clipboard-text='[typewriter_text text="Elegant handwritten message" speed="150" cursor="✍️" class="handwritten-style"]'>
                                <?php _e('Copy', 'shogun-slogans'); ?>
                            </button>
                        </div>
                    </div>
                    
                    <div class="card">
                        <h2><?php _e('Live Preview', 'shogun-slogans'); ?></h2>
                        <div class="shogun-preview-area">
                            <div class="shogun-typewriter" 
                                 data-text="Welcome to Shogun Slogans! This plugin creates amazing typewriter effects..."
                                 data-speed="80"
                                 data-cursor="|"
                                 data-loop="true">
                                <span class="typewriter-text"></span>
                                <span class="typewriter-cursor">|</span>
                            </div>
                        </div>
                        
                        <div class="preview-controls">
                            <label>
                                <?php _e('Text:', 'shogun-slogans'); ?>
                                <input type="text" id="preview-text" value="Welcome to Shogun Slogans! This plugin creates amazing typewriter effects..." />
                            </label>
                            <label>
                                <?php _e('Speed:', 'shogun-slogans'); ?>
                                <input type="range" id="preview-speed" min="20" max="300" value="80" />
                                <span id="speed-value">80ms</span>
                            </label>
                            <label>
                                <?php _e('Cursor:', 'shogun-slogans'); ?>
                                <select id="preview-cursor">
                                    <option value="|">| (pipe)</option>
                                    <option value="▌">▌ (block)</option>
                                    <option value="_">_ (underscore)</option>
                                    <option value="❘">❘ (line)</option>
                                    <option value="✍️">✍️ (pen)</option>
                                    <option value="🖋️">🖋️ (fountain pen)</option>
                                </select>
                            </label>
                            <button type="button" class="button" id="update-preview">
                                <?php _e('Update Preview', 'shogun-slogans'); ?>
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="shogun-admin-sidebar">
                    <div class="card">
                        <h3><?php _e('Plugin Info', 'shogun-slogans'); ?></h3>
                        <p><strong><?php _e('Version:', 'shogun-slogans'); ?></strong> <?php echo SHOGUN_SLOGANS_VERSION; ?></p>
                        <p><strong><?php _e('Status:', 'shogun-slogans'); ?></strong> <span class="status-active"><?php _e('Active', 'shogun-slogans'); ?></span></p>
                        <p><strong><?php _e('PHP Version:', 'shogun-slogans'); ?></strong> <?php echo PHP_VERSION; ?></p>
                        <p><strong><?php _e('WordPress Version:', 'shogun-slogans'); ?></strong> <?php echo get_bloginfo('version'); ?></p>
                    </div>
                    
                    <div class="card">
                        <h3><?php _e('Available Parameters', 'shogun-slogans'); ?></h3>
                        <ul class="parameter-list">
                            <li><strong>text:</strong> <?php _e('The text to display', 'shogun-slogans'); ?></li>
                            <li><strong>speed:</strong> <?php _e('Typing speed (milliseconds)', 'shogun-slogans'); ?></li>
                            <li><strong>cursor:</strong> <?php _e('Cursor character', 'shogun-slogans'); ?></li>
                            <li><strong>loop:</strong> <?php _e('Loop animation (true/false)', 'shogun-slogans'); ?></li>
                            <li><strong>delay:</strong> <?php _e('Start delay (milliseconds)', 'shogun-slogans'); ?></li>
                            <li><strong>class:</strong> <?php _e('Additional CSS classes', 'shogun-slogans'); ?></li>
                            <li><strong>style:</strong> <?php _e('Inline CSS styles', 'shogun-slogans'); ?></li>
                        </ul>
                    </div>
                    
                    <div class="card">
                        <h3><?php _e('Quick Actions', 'shogun-slogans'); ?></h3>
                        <p>
                            <a href="<?php echo admin_url('admin.php?page=shogun-slogans-settings'); ?>" class="button">
                                <?php _e('Settings', 'shogun-slogans'); ?>
                            </a>
                        </p>
                        <p>
                            <a href="<?php echo admin_url('admin.php?page=shogun-slogans-help'); ?>" class="button">
                                <?php _e('Documentation', 'shogun-slogans'); ?>
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Settings page callback
     * 
     * @since 3.0.0
     */
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Shogun Slogans Settings', 'shogun-slogans'); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('shogun_slogans_settings');
                do_settings_sections('shogun_slogans_settings');
                ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Default Typing Speed', 'shogun-slogans'); ?></th>
                        <td>
                            <input type="number" name="shogun_slogans_options[default_speed]" value="<?php echo esc_attr($this->options['default_speed']); ?>" min="10" max="1000" />
                            <p class="description"><?php _e('Default speed in milliseconds (lower = faster)', 'shogun-slogans'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Default Cursor', 'shogun-slogans'); ?></th>
                        <td>
                            <input type="text" name="shogun_slogans_options[default_cursor]" value="<?php echo esc_attr($this->options['default_cursor']); ?>" maxlength="10" />
                            <p class="description"><?php _e('Default cursor character or emoji', 'shogun-slogans'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Default Loop Setting', 'shogun-slogans'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="shogun_slogans_options[default_loop]" value="1" <?php checked($this->options['default_loop'], true); ?> />
                                <?php _e('Enable looping by default', 'shogun-slogans'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Accessibility Features', 'shogun-slogans'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="shogun_slogans_options[enable_accessibility]" value="1" <?php checked($this->options['enable_accessibility'], true); ?> />
                                <?php _e('Respect reduced motion preferences', 'shogun-slogans'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Performance Optimization', 'shogun-slogans'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="shogun_slogans_options[enable_performance_optimization]" value="1" <?php checked($this->options['enable_performance_optimization'], true); ?> />
                                <?php _e('Use Intersection Observer API for better performance', 'shogun-slogans'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Custom CSS', 'shogun-slogans'); ?></th>
                        <td>
                            <textarea name="shogun_slogans_options[custom_css]" rows="10" cols="50" class="large-text code"><?php echo esc_textarea($this->options['custom_css']); ?></textarea>
                            <p class="description"><?php _e('Add custom CSS to style your slogans', 'shogun-slogans'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Debug Mode', 'shogun-slogans'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="shogun_slogans_options[debug_mode]" value="1" <?php checked($this->options['debug_mode'], true); ?> />
                                <?php _e('Enable debug logging', 'shogun-slogans'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Help page callback
     * 
     * @since 3.0.0
     */
    public function help_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Shogun Slogans Help & Documentation', 'shogun-slogans'); ?></h1>
            
            <div class="shogun-help-content">
                <div class="card">
                    <h2><?php _e('Getting Started', 'shogun-slogans'); ?></h2>
                    <p><?php _e('Shogun Slogans allows you to create beautiful animated text effects using simple shortcodes or HTML blocks.', 'shogun-slogans'); ?></p>
                    
                    <h3><?php _e('Basic Usage', 'shogun-slogans'); ?></h3>
                    <p><?php _e('Use the typewriter_text shortcode to create animated text:', 'shogun-slogans'); ?></p>
                    <pre><code>[typewriter_text text="Your message here" speed="100" cursor="|"]</code></pre>
                    
                    <h3><?php _e('HTML Usage', 'shogun-slogans'); ?></h3>
                    <p><?php _e('You can also use HTML directly in Custom HTML blocks:', 'shogun-slogans'); ?></p>
                    <pre><code>&lt;div class="shogun-typewriter" 
     data-text="Your message here"
     data-speed="100"
     data-cursor="|"&gt;
    &lt;span class="typewriter-text"&gt;&lt;/span&gt;
    &lt;span class="typewriter-cursor"&gt;|&lt;/span&gt;
&lt;/div&gt;</code></pre>
                </div>
                
                <div class="card">
                    <h2><?php _e('Advanced Features', 'shogun-slogans'); ?></h2>
                    
                    <h3><?php _e('Custom Styling', 'shogun-slogans'); ?></h3>
                    <p><?php _e('Add CSS classes or inline styles to customize appearance:', 'shogun-slogans'); ?></p>
                    <pre><code>[typewriter_text text="Styled text" class="my-custom-class" style="color: blue; font-size: 24px;"]</code></pre>
                    
                    <h3><?php _e('Speed Control', 'shogun-slogans'); ?></h3>
                    <ul>
                        <li><strong>20-50ms:</strong> <?php _e('Very fast typing', 'shogun-slogans'); ?></li>
                        <li><strong>80-120ms:</strong> <?php _e('Normal typing speed', 'shogun-slogans'); ?></li>
                        <li><strong>150-200ms:</strong> <?php _e('Slow, dramatic effect', 'shogun-slogans'); ?></li>
                        <li><strong>300ms+:</strong> <?php _e('Very slow, word-by-word', 'shogun-slogans'); ?></li>
                    </ul>
                    
                    <h3><?php _e('Popular Cursors', 'shogun-slogans'); ?></h3>
                    <ul>
                        <li><code>|</code> - <?php _e('Classic pipe cursor', 'shogun-slogans'); ?></li>
                        <li><code>▌</code> - <?php _e('Block cursor', 'shogun-slogans'); ?></li>
                        <li><code>_</code> - <?php _e('Underscore cursor', 'shogun-slogans'); ?></li>
                        <li><code>✍️</code> - <?php _e('Handwriting pen', 'shogun-slogans'); ?></li>
                        <li><code>🖋️</code> - <?php _e('Fountain pen', 'shogun-slogans'); ?></li>
                        <li><code>💎</code> - <?php _e('Diamond cursor', 'shogun-slogans'); ?></li>
                    </ul>
                </div>
                
                <div class="card">
                    <h2><?php _e('Troubleshooting', 'shogun-slogans'); ?></h2>
                    
                    <h3><?php _e('Animation Not Working', 'shogun-slogans'); ?></h3>
                    <ol>
                        <li><?php _e('Check that JavaScript is enabled in your browser', 'shogun-slogans'); ?></li>
                        <li><?php _e('Ensure there are no JavaScript errors in the browser console (F12)', 'shogun-slogans'); ?></li>
                        <li><?php _e('Try disabling other plugins to check for conflicts', 'shogun-slogans'); ?></li>
                        <li><?php _e('Make sure you\'re using a Custom HTML block, not a regular text block', 'shogun-slogans'); ?></li>
                    </ol>
                    
                    <h3><?php _e('Shortcode Displays as Text', 'shogun-slogans'); ?></h3>
                    <p><?php _e('If shortcodes appear as plain text instead of animated content:', 'shogun-slogans'); ?></p>
                    <ol>
                        <li><?php _e('Make sure the plugin is activated', 'shogun-slogans'); ?></li>
                        <li><?php _e('Check that you\'re using the correct shortcode syntax', 'shogun-slogans'); ?></li>
                        <li><?php _e('Try using the HTML version instead in a Custom HTML block', 'shogun-slogans'); ?></li>
                    </ol>
                    
                    <h3><?php _e('Performance Issues', 'shogun-slogans'); ?></h3>
                    <p><?php _e('If you experience slow page loading:', 'shogun-slogans'); ?></p>
                    <ol>
                        <li><?php _e('Enable Performance Optimization in Settings', 'shogun-slogans'); ?></li>
                        <li><?php _e('Use fewer animated elements per page', 'shogun-slogans'); ?></li>
                        <li><?php _e('Consider using loop="false" for elements that don\'t need to repeat', 'shogun-slogans'); ?></li>
                    </ol>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Sanitize settings
     * 
     * @since 3.0.0
     * @param array $input Raw input data
     * @return array Sanitized data
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        
        $sanitized['default_speed'] = absint($input['default_speed']);
        if ($sanitized['default_speed'] < 10) {
            $sanitized['default_speed'] = 10;
        }
        if ($sanitized['default_speed'] > 1000) {
            $sanitized['default_speed'] = 1000;
        }
        
        $sanitized['default_cursor'] = sanitize_text_field($input['default_cursor']);
        $sanitized['default_loop'] = !empty($input['default_loop']);
        $sanitized['enable_accessibility'] = !empty($input['enable_accessibility']);
        $sanitized['enable_performance_optimization'] = !empty($input['enable_performance_optimization']);
        $sanitized['custom_css'] = wp_strip_all_tags($input['custom_css']);
        $sanitized['debug_mode'] = !empty($input['debug_mode']);
        
        return $sanitized;
    }
    
    /**
     * Plugin activation
     * 
     * @since 3.0.0
     */
    public function activate() {
        // Create default options
        $default_options = array(
            'default_speed' => 100,
            'default_cursor' => '|',
            'default_loop' => true,
            'enable_accessibility' => true,
            'enable_performance_optimization' => true,
            'custom_css' => '',
            'debug_mode' => false,
        );
        
        add_option('shogun_slogans_options', $default_options);
        add_option('shogun_slogans_version', SHOGUN_SLOGANS_VERSION);
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Log activation
        if (function_exists('error_log')) {
            error_log('Shogun Slogans Plugin Activated - Version ' . SHOGUN_SLOGANS_VERSION);
        }
    }
    
    /**
     * Plugin deactivation
     * 
     * @since 3.0.0
     */
    public function deactivate() {
        // Clean up temporary data
        delete_transient('shogun_slogans_cache');
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Log deactivation
        if (function_exists('error_log')) {
            error_log('Shogun Slogans Plugin Deactivated');
        }
    }
    
    /**
     * Plugin uninstall
     * 
     * @since 3.0.0
     */
    public static function uninstall() {
        // Remove options
        delete_option('shogun_slogans_options');
        delete_option('shogun_slogans_version');
        
        // Remove transients
        delete_transient('shogun_slogans_cache');
        
        // Remove custom posts
        $posts = get_posts(array(
            'post_type' => 'shogun_slogan',
            'numberposts' => -1,
            'post_status' => 'any',
        ));
        
        foreach ($posts as $post) {
            wp_delete_post($post->ID, true);
        }
        
        // Log uninstall
        if (function_exists('error_log')) {
            error_log('Shogun Slogans Plugin Uninstalled');
        }
    }
    
    /**
     * AJAX Preview Handler
     * 
     * @since 3.0.0
     */
    public function ajax_preview() {
        check_ajax_referer('shogun_slogans_nonce', 'nonce');
        
        $text = sanitize_text_field($_POST['text'] ?? '');
        $speed = absint($_POST['speed'] ?? 100);
        $cursor = sanitize_text_field($_POST['cursor'] ?? '|');
        
        $output = $this->typewriter_text_shortcode(array(
            'text' => $text,
            'speed' => $speed,
            'cursor' => $cursor,
            'loop' => 'true',
        ));
        
        wp_send_json_success($output);
    }
    
    /**
     * REST API Preview Handler
     * 
     * @since 3.0.0
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function rest_preview($request) {
        $text = $request->get_param('text');
        $speed = $request->get_param('speed');
        $cursor = $request->get_param('cursor');
        
        $output = $this->typewriter_text_shortcode(array(
            'text' => $text,
            'speed' => $speed,
            'cursor' => $cursor,
            'loop' => 'true',
        ));
        
        return new WP_REST_Response(array(
            'success' => true,
            'data' => $output,
        ), 200);
    }
}

// Initialize the plugin
ShogunSlogansPlugin::get_instance();

/**
 * Helper function to display typewriter text
 * 
 * @since 3.0.0
 * @param string $text Text to display
 * @param int $speed Typing speed
 * @param string $cursor Cursor character
 * @return string
 */
function shogun_typewriter($text, $speed = 100, $cursor = '|') {
    return do_shortcode("[typewriter_text text=\"{$text}\" speed=\"{$speed}\" cursor=\"{$cursor}\"]");
}

/**
 * Helper function to display slogan
 * 
 * @since 3.0.0
 * @param string $text Slogan text
 * @param string $style Animation style
 * @return string
 */
function shogun_slogan($text, $style = 'fade') {
    return do_shortcode("[shogun_slogan text=\"{$text}\" style=\"{$style}\"]");
}

/**
 * Get plugin options
 * 
 * @since 3.0.0
 * @return array
 */
function shogun_slogans_get_options() {
    return get_option('shogun_slogans_options', array());
}

/**
 * Check if plugin is debug mode
 * 
 * @since 3.0.0
 * @return bool
 */
function shogun_slogans_is_debug() {
    $options = shogun_slogans_get_options();
    return !empty($options['debug_mode']);
}
