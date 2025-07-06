<?php
/**
 * Plugin Name: Shogun Slogans
 * Plugin URI: https://github.com/DavidEngland/Shogun-Slogans
 * Description: A modern WordPress plugin for creating stunning animated slogans with typewriter effects, handwritten styles, and dynamic text display. Perfect for hero sections, quotes, and call-to-action messages.
 * Version: 3.1.0
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
define('SHOGUN_SLOGANS_VERSION', '3.1.0');
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
 * @since 3.1.0
 */
class ShogunSlogansPlugin {
    
    /**
     * Plugin instance
     * 
     * @since 3.1.0
     * @var ShogunSlogansPlugin
     */
    private static $instance = null;
    
    /**
     * Plugin options
     * 
     * @since 3.1.0
     * @var array
     */
    private $options = array();
    
    /**
     * Assets loaded flag
     * 
     * @since 3.1.0
     * @var bool
     */
    private $assets_loaded = false;
    
    /**
     * Get plugin instance (Singleton pattern)
     * 
     * @since 3.1.0
     * @return ShogunSlogansPlugin
     */
    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor - Initialize plugin
     * 
     * @since 3.1.0
     */
    private function __construct() {
        $this->load_options();
        $this->init_hooks();
        $this->check_requirements();
    }
    
    /**
     * Initialize WordPress hooks
     * 
     * @since 3.1.0
     */
    private function init_hooks() {
        // Core hooks
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Shortcode hooks
        add_action('init', array($this, 'register_shortcodes'));
        
        // Admin hooks
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        
        // Plugin lifecycle hooks
        register_activation_hook(SHOGUN_SLOGANS_PLUGIN_FILE, array($this, 'activate'));
        register_deactivation_hook(SHOGUN_SLOGANS_PLUGIN_FILE, array($this, 'deactivate'));
        
        // AJAX hooks
        add_action('wp_ajax_shogun_slogans_test', array($this, 'ajax_test'));
        add_action('wp_ajax_nopriv_shogun_slogans_test', array($this, 'ajax_test'));
        
        // REST API hooks
        add_action('rest_api_init', array($this, 'register_rest_routes'));
    }
    
    /**
     * Check plugin requirements
     * 
     * @since 3.1.0
     */
    private function check_requirements() {
        global $wp_version;
        
        // Check PHP version
        if (version_compare(PHP_VERSION, SHOGUN_SLOGANS_MIN_PHP_VERSION, '<')) {
            add_action('admin_notices', array($this, 'php_version_notice'));
            return false;
        }
        
        // Check WordPress version
        if (version_compare($wp_version, SHOGUN_SLOGANS_MIN_WP_VERSION, '<')) {
            add_action('admin_notices', array($this, 'wp_version_notice'));
            return false;
        }
        
        return true;
    }
    
    /**
     * Load plugin options
     * 
     * @since 3.1.0
     */
    private function load_options() {
        $defaults = array(
            'default_speed' => 100,
            'default_cursor' => '|',
            'default_loop' => false,
            'enable_accessibility' => true,
            'enable_performance_optimization' => true,
            'debug_mode' => false,
            'load_assets_everywhere' => false,
            'cursor_styles' => array('|', '_', '▌', '●', '♦', '★', '⚡', '💫', '🔥', '✨'),
            'animation_styles' => array('typewriter', 'handwritten', 'quill', 'fountain', 'fade', 'slide', 'bounce')
        );
        
        $this->options = wp_parse_args(get_option('shogun_slogans_options', array()), $defaults);
    }
    
    /**
     * Plugin initialization
     * 
     * @since 3.1.0
     */
    public function init() {
        // Load text domain for internationalization
        load_plugin_textdomain('shogun-slogans', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        
        // Initialize components
        $this->log('Plugin initialized', 'info');
    }
    
    /**
     * Enqueue frontend assets
     * 
     * @since 3.1.0
     */
    public function enqueue_assets() {
        // Don't load on admin pages unless needed
        if (is_admin() && !wp_doing_ajax()) {
            return;
        }
        
        // Check if we should load assets everywhere or only when needed
        if (!$this->options['load_assets_everywhere'] && !$this->page_needs_assets()) {
            return;
        }
        
        $this->load_frontend_assets();
    }
    
    /**
     * Check if current page needs plugin assets
     * 
     * @since 3.1.0
     * @return bool
     */
    private function page_needs_assets() {
        global $post;
        
        // Always load if we can't determine
        if (!$post) {
            return true;
        }
        
        // Check for shortcodes in content
        if (has_shortcode($post->post_content, 'typewriter_text') ||
            has_shortcode($post->post_content, 'shogun_slogan') ||
            has_shortcode($post->post_content, 'animated_text')) {
            return true;
        }
        
        // Check for CSS classes in content
        if (strpos($post->post_content, 'shogun-typewriter') !== false ||
            strpos($post->post_content, 'shogun-slogan') !== false ||
            strpos($post->post_content, 'typewriter-text') !== false) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Load frontend assets
     * 
     * @since 3.1.0
     */
    private function load_frontend_assets() {
        if ($this->assets_loaded) {
            return;
        }
        
        $version = $this->options['debug_mode'] ? time() : SHOGUN_SLOGANS_VERSION;
        
        // Enqueue CSS
        wp_enqueue_style(
            'shogun-slogans-styles',
            SHOGUN_SLOGANS_PLUGIN_URL . 'assets/css/slogans.css',
            array(),
            $version
        );
        
        // Enqueue JavaScript
        wp_enqueue_script(
            'shogun-slogans-script',
            SHOGUN_SLOGANS_PLUGIN_URL . 'assets/js/slogans-final.js',
            array('jquery'),
            $version,
            true
        );
        
        // Localize script with configuration
        wp_localize_script('shogun-slogans-script', 'shogunSlogansConfig', array(
            'options' => $this->options,
            'strings' => array(
                'loading' => __('Loading...', 'shogun-slogans'),
                'error' => __('Error loading content', 'shogun-slogans'),
                'pauseAnimation' => __('Pause animation', 'shogun-slogans'),
                'resumeAnimation' => __('Resume animation', 'shogun-slogans')
            ),
            'ajax' => array(
                'url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('shogun_slogans_nonce')
            ),
            'rest' => array(
                'url' => rest_url('shogun-slogans/v1/'),
                'nonce' => wp_create_nonce('wp_rest')
            )
        ));
        
        $this->assets_loaded = true;
        $this->log('Frontend assets loaded', 'info');
    }
    
    /**
     * Enqueue admin assets
     * 
     * @since 3.1.0
     */
    public function enqueue_admin_assets($hook) {
        // Only load on our admin pages
        if ($hook !== 'settings_page_shogun-slogans' && $hook !== 'tools_page_shogun-slogans-tools') {
            return;
        }
        
        $version = $this->options['debug_mode'] ? time() : SHOGUN_SLOGANS_VERSION;
        
        // Enqueue admin CSS
        wp_enqueue_style(
            'shogun-slogans-admin-styles',
            SHOGUN_SLOGANS_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            $version
        );
        
        // Enqueue admin JavaScript if needed
        wp_enqueue_script(
            'shogun-slogans-admin-script',
            SHOGUN_SLOGANS_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            $version,
            true
        );
        
        // Localize admin script
        wp_localize_script('shogun-slogans-admin-script', 'shogunAdminConfig', array(
            'options' => $this->options,
            'ajax' => array(
                'url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('shogun_slogans_admin_nonce')
            )
        ));
        
        $this->log('Admin assets loaded', 'info');
    }
    
    /**
     * Register all shortcodes
     * 
     * @since 3.1.0
     */
    public function register_shortcodes() {
        add_shortcode('typewriter_text', array($this, 'typewriter_shortcode'));
        add_shortcode('shogun_slogan', array($this, 'slogan_shortcode'));
        add_shortcode('animated_text', array($this, 'animated_text_shortcode'));
        
        $this->log('Shortcodes registered', 'info');
    }
    
    /**
     * Main typewriter shortcode
     * 
     * @since 3.1.0
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function typewriter_shortcode($atts) {
        // Ensure assets are loaded
        $this->load_frontend_assets();
        
        $atts = shortcode_atts(array(
            'text' => 'Sample typewriter text',
            'speed' => $this->options['default_speed'],
            'cursor' => $this->options['default_cursor'],
            'loop' => $this->options['default_loop'] ? 'true' : 'false',
            'delay' => 0,
            'class' => '',
            'style' => 'typewriter',
            'id' => '',
            'auto_start' => 'true',
            'cursor_blink' => 'true',
            'preserve_cursor' => 'false'
        ), $atts, 'typewriter_text');
        
        // Sanitize attributes
        $text = sanitize_text_field($atts['text']);
        $speed = absint($atts['speed']);
        $cursor = sanitize_text_field($atts['cursor']);
        $loop = $atts['loop'] === 'true' ? 'true' : 'false';
        $delay = absint($atts['delay']);
        $class = sanitize_html_class($atts['class']);
        $style = sanitize_html_class($atts['style']);
        $id = sanitize_html_class($atts['id']);
        $auto_start = $atts['auto_start'] === 'true' ? 'true' : 'false';
        $cursor_blink = $atts['cursor_blink'] === 'true' ? 'true' : 'false';
        $preserve_cursor = $atts['preserve_cursor'] === 'true' ? 'true' : 'false';
        
        // Generate unique ID if not provided
        if (empty($id)) {
            $id = 'shogun-typewriter-' . wp_generate_uuid4();
        }
        
        // Build CSS classes
        $css_classes = array('shogun-typewriter', 'shogun-' . $style);
        if (!empty($class)) {
            $css_classes[] = $class;
        }
        
        // Build data attributes
        $data_attrs = array(
            'data-text' => esc_attr($text),
            'data-speed' => esc_attr($speed),
            'data-cursor' => esc_attr($cursor),
            'data-loop' => esc_attr($loop),
            'data-delay' => esc_attr($delay),
            'data-auto-start' => esc_attr($auto_start),
            'data-cursor-blink' => esc_attr($cursor_blink),
            'data-preserve-cursor' => esc_attr($preserve_cursor)
        );
        
        // Build HTML
        $html = sprintf(
            '<div id="%s" class="%s" %s>',
            esc_attr($id),
            esc_attr(implode(' ', $css_classes)),
            implode(' ', array_map(function($key, $value) {
                return $key . '="' . $value . '"';
            }, array_keys($data_attrs), $data_attrs))
        );
        
        $html .= '<span class="typewriter-text" role="text" aria-label="' . esc_attr($text) . '"></span>';
        $html .= '<span class="typewriter-cursor" aria-hidden="true">' . esc_html($cursor) . '</span>';
        $html .= '</div>';
        
        $this->log('Typewriter shortcode rendered', 'info', array('id' => $id, 'text' => $text));
        
        return $html;
    }
    
    /**
     * Slogan shortcode
     * 
     * @since 3.1.0
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function slogan_shortcode($atts) {
        // Ensure assets are loaded
        $this->load_frontend_assets();
        
        $atts = shortcode_atts(array(
            'text' => 'Your amazing slogan here',
            'style' => 'typewriter',
            'speed' => $this->options['default_speed'],
            'cursor' => $this->options['default_cursor'],
            'loop' => $this->options['default_loop'] ? 'true' : 'false',
            'class' => '',
            'id' => '',
            'animation' => 'fade',
            'delay' => 0,
            'color' => '',
            'size' => ''
        ), $atts, 'shogun_slogan');
        
        // Sanitize attributes
        $text = sanitize_text_field($atts['text']);
        $style = sanitize_html_class($atts['style']);
        $speed = absint($atts['speed']);
        $cursor = sanitize_text_field($atts['cursor']);
        $loop = $atts['loop'] === 'true' ? 'true' : 'false';
        $class = sanitize_html_class($atts['class']);
        $id = sanitize_html_class($atts['id']);
        $animation = sanitize_html_class($atts['animation']);
        $delay = absint($atts['delay']);
        $color = sanitize_hex_color($atts['color']);
        $size = sanitize_text_field($atts['size']);
        
        // Generate unique ID if not provided
        if (empty($id)) {
            $id = 'shogun-slogan-' . wp_generate_uuid4();
        }
        
        // Build CSS classes
        $css_classes = array('shogun-slogan', 'shogun-' . $style, 'shogun-' . $animation);
        if (!empty($class)) {
            $css_classes[] = $class;
        }
        
        // Build inline styles
        $inline_styles = array();
        if (!empty($color)) {
            $inline_styles[] = 'color: ' . $color;
        }
        if (!empty($size)) {
            $inline_styles[] = 'font-size: ' . $size;
        }
        
        // Build data attributes
        $data_attrs = array(
            'data-text' => esc_attr($text),
            'data-speed' => esc_attr($speed),
            'data-cursor' => esc_attr($cursor),
            'data-loop' => esc_attr($loop),
            'data-delay' => esc_attr($delay),
            'data-animation' => esc_attr($animation)
        );
        
        // Build HTML
        $html = sprintf(
            '<div id="%s" class="%s" %s%s>',
            esc_attr($id),
            esc_attr(implode(' ', $css_classes)),
            implode(' ', array_map(function($key, $value) {
                return $key . '="' . $value . '"';
            }, array_keys($data_attrs), $data_attrs)),
            !empty($inline_styles) ? ' style="' . esc_attr(implode('; ', $inline_styles)) . '"' : ''
        );
        
        if ($style === 'typewriter') {
            $html .= '<span class="typewriter-text" role="text" aria-label="' . esc_attr($text) . '"></span>';
            $html .= '<span class="typewriter-cursor" aria-hidden="true">' . esc_html($cursor) . '</span>';
        } else {
            $html .= '<span class="slogan-text" role="text">' . esc_html($text) . '</span>';
        }
        
        $html .= '</div>';
        
        $this->log('Slogan shortcode rendered', 'info', array('id' => $id, 'style' => $style));
        
        return $html;
    }
    
    /**
     * Animated text shortcode
     * 
     * @since 3.1.0
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function animated_text_shortcode($atts) {
        // Ensure assets are loaded
        $this->load_frontend_assets();
        
        $atts = shortcode_atts(array(
            'text' => 'Animated text',
            'animation' => 'fade',
            'speed' => 1000,
            'delay' => 0,
            'class' => '',
            'id' => '',
            'loop' => 'false',
            'direction' => 'normal'
        ), $atts, 'animated_text');
        
        // Sanitize attributes
        $text = sanitize_text_field($atts['text']);
        $animation = sanitize_html_class($atts['animation']);
        $speed = absint($atts['speed']);
        $delay = absint($atts['delay']);
        $class = sanitize_html_class($atts['class']);
        $id = sanitize_html_class($atts['id']);
        $loop = $atts['loop'] === 'true' ? 'true' : 'false';
        $direction = sanitize_html_class($atts['direction']);
        
        // Generate unique ID if not provided
        if (empty($id)) {
            $id = 'shogun-animated-' . wp_generate_uuid4();
        }
        
        // Build CSS classes
        $css_classes = array('shogun-animated-text', 'shogun-' . $animation);
        if (!empty($class)) {
            $css_classes[] = $class;
        }
        
        // Build data attributes
        $data_attrs = array(
            'data-text' => esc_attr($text),
            'data-animation' => esc_attr($animation),
            'data-speed' => esc_attr($speed),
            'data-delay' => esc_attr($delay),
            'data-loop' => esc_attr($loop),
            'data-direction' => esc_attr($direction)
        );
        
        // Build HTML
        $html = sprintf(
            '<div id="%s" class="%s" %s>',
            esc_attr($id),
            esc_attr(implode(' ', $css_classes)),
            implode(' ', array_map(function($key, $value) {
                return $key . '="' . $value . '"';
            }, array_keys($data_attrs), $data_attrs))
        );
        
        $html .= '<span class="animated-text" role="text">' . esc_html($text) . '</span>';
        $html .= '</div>';
        
        $this->log('Animated text shortcode rendered', 'info', array('id' => $id, 'animation' => $animation));
        
        return $html;
    }
    
    /**
     * Add admin menu
     * 
     * @since 3.1.0
     */
    public function add_admin_menu() {
        add_options_page(
            __('Shogun Slogans Settings', 'shogun-slogans'),
            __('Shogun Slogans', 'shogun-slogans'),
            'manage_options',
            'shogun-slogans',
            array($this, 'admin_page')
        );
        
        add_management_page(
            __('Shogun Slogans Tools', 'shogun-slogans'),
            __('Shogun Slogans Tools', 'shogun-slogans'),
            'manage_options',
            'shogun-slogans-tools',
            array($this, 'tools_page')
        );
    }
    
    /**
     * Register plugin settings
     * 
     * @since 3.1.0
     */
    public function register_settings() {
        register_setting(
            'shogun_slogans_settings',
            'shogun_slogans_options',
            array($this, 'sanitize_options')
        );
        
        add_settings_section(
            'shogun_slogans_general',
            __('General Settings', 'shogun-slogans'),
            array($this, 'general_section_callback'),
            'shogun-slogans'
        );
        
        add_settings_field(
            'default_speed',
            __('Default Speed (ms)', 'shogun-slogans'),
            array($this, 'number_field_callback'),
            'shogun-slogans',
            'shogun_slogans_general',
            array('field' => 'default_speed', 'description' => __('Default typing speed in milliseconds', 'shogun-slogans'))
        );
        
        add_settings_field(
            'default_cursor',
            __('Default Cursor', 'shogun-slogans'),
            array($this, 'text_field_callback'),
            'shogun-slogans',
            'shogun_slogans_general',
            array('field' => 'default_cursor', 'description' => __('Default cursor character', 'shogun-slogans'))
        );
        
        add_settings_field(
            'default_loop',
            __('Default Loop', 'shogun-slogans'),
            array($this, 'checkbox_field_callback'),
            'shogun-slogans',
            'shogun_slogans_general',
            array('field' => 'default_loop', 'description' => __('Enable looping by default', 'shogun-slogans'))
        );
        
        add_settings_field(
            'enable_accessibility',
            __('Enable Accessibility', 'shogun-slogans'),
            array($this, 'checkbox_field_callback'),
            'shogun-slogans',
            'shogun_slogans_general',
            array('field' => 'enable_accessibility', 'description' => __('Respect user preferences for reduced motion', 'shogun-slogans'))
        );
        
        add_settings_field(
            'enable_performance_optimization',
            __('Enable Performance Optimization', 'shogun-slogans'),
            array($this, 'checkbox_field_callback'),
            'shogun-slogans',
            'shogun_slogans_general',
            array('field' => 'enable_performance_optimization', 'description' => __('Use Intersection Observer for better performance', 'shogun-slogans'))
        );
        
        add_settings_field(
            'debug_mode',
            __('Debug Mode', 'shogun-slogans'),
            array($this, 'checkbox_field_callback'),
            'shogun-slogans',
            'shogun_slogans_general',
            array('field' => 'debug_mode', 'description' => __('Enable debug logging (for development)', 'shogun-slogans'))
        );
        
        add_settings_field(
            'load_assets_everywhere',
            __('Load Assets Everywhere', 'shogun-slogans'),
            array($this, 'checkbox_field_callback'),
            'shogun-slogans',
            'shogun_slogans_general',
            array('field' => 'load_assets_everywhere', 'description' => __('Load plugin assets on all pages (disable for better performance)', 'shogun-slogans'))
        );
    }
    
    /**
     * Sanitize plugin options
     * 
     * @since 3.1.0
     * @param array $input Input options
     * @return array Sanitized options
     */
    public function sanitize_options($input) {
        $sanitized = array();
        
        $sanitized['default_speed'] = absint($input['default_speed']);
        $sanitized['default_cursor'] = sanitize_text_field($input['default_cursor']);
        $sanitized['default_loop'] = !empty($input['default_loop']);
        $sanitized['enable_accessibility'] = !empty($input['enable_accessibility']);
        $sanitized['enable_performance_optimization'] = !empty($input['enable_performance_optimization']);
        $sanitized['debug_mode'] = !empty($input['debug_mode']);
        $sanitized['load_assets_everywhere'] = !empty($input['load_assets_everywhere']);
        
        // Preserve arrays that might not be in the form
        $sanitized['cursor_styles'] = $this->options['cursor_styles'];
        $sanitized['animation_styles'] = $this->options['animation_styles'];
        
        return $sanitized;
    }
    
    /**
     * Admin page callback
     * 
     * @since 3.1.0
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Shogun Slogans Settings', 'shogun-slogans'); ?></h1>
            
            <div class="shogun-admin-container">
                <div class="shogun-admin-main">
                    <form method="post" action="options.php">
                        <?php
                        settings_fields('shogun_slogans_settings');
                        do_settings_sections('shogun-slogans');
                        submit_button();
                        ?>
                    </form>
                </div>
                
                <div class="shogun-admin-sidebar">
                    <div class="card">
                        <h2><?php _e('Quick Start', 'shogun-slogans'); ?></h2>
                        <p><?php _e('Use these shortcodes to add animated text to your posts and pages:', 'shogun-slogans'); ?></p>
                        
                        <h3><?php _e('Basic Typewriter', 'shogun-slogans'); ?></h3>
                        <code>[typewriter_text text="Your text here"]</code>
                        
                        <h3><?php _e('Advanced Slogan', 'shogun-slogans'); ?></h3>
                        <code>[shogun_slogan text="Your slogan" style="typewriter" speed="100" cursor="|"]</code>
                        
                        <h3><?php _e('Animated Text', 'shogun-slogans'); ?></h3>
                        <code>[animated_text text="Your text" animation="fade" speed="1000"]</code>
                    </div>
                    
                    <div class="card">
                        <h2><?php _e('Documentation', 'shogun-slogans'); ?></h2>
                        <p><?php _e('For detailed documentation and examples, visit:', 'shogun-slogans'); ?></p>
                        <p><a href="<?php echo admin_url('tools.php?page=shogun-slogans-tools'); ?>"><?php _e('Tools & Testing', 'shogun-slogans'); ?></a></p>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Tools page callback
     * 
     * @since 3.1.0
     */
    public function tools_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Shogun Slogans Tools', 'shogun-slogans'); ?></h1>
            
            <div class="shogun-admin-container">
                <div class="shogun-admin-main">
                    <div class="card">
                        <h2><?php _e('Test Shortcodes', 'shogun-slogans'); ?></h2>
                        <p><?php _e('Use these test shortcodes to verify your plugin is working:', 'shogun-slogans'); ?></p>
                        
                        <div class="test-shortcode">
                            <h3><?php _e('Test 1: Basic Typewriter', 'shogun-slogans'); ?></h3>
                            <code>[typewriter_text text="Hello from Shogun Slogans!" speed="100"]</code>
                            <div class="test-result">
                                <?php echo do_shortcode('[typewriter_text text="Hello from Shogun Slogans!" speed="100"]'); ?>
                            </div>
                        </div>
                        
                        <div class="test-shortcode">
                            <h3><?php _e('Test 2: Custom Cursor', 'shogun-slogans'); ?></h3>
                            <code>[typewriter_text text="Custom cursor test" cursor="★" speed="150"]</code>
                            <div class="test-result">
                                <?php echo do_shortcode('[typewriter_text text="Custom cursor test" cursor="★" speed="150"]'); ?>
                            </div>
                        </div>
                        
                        <div class="test-shortcode">
                            <h3><?php _e('Test 3: Slogan Style', 'shogun-slogans'); ?></h3>
                            <code>[shogun_slogan text="I will help you make The Smart Move - I guarantee it!" style="typewriter" speed="80" cursor="|"]</code>
                            <div class="test-result">
                                <?php echo do_shortcode('[shogun_slogan text="I will help you make The Smart Move - I guarantee it!" style="typewriter" speed="80" cursor="|"]'); ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <h2><?php _e('Debug Information', 'shogun-slogans'); ?></h2>
                        <ul>
                            <li><strong><?php _e('Plugin Version:', 'shogun-slogans'); ?></strong> <?php echo SHOGUN_SLOGANS_VERSION; ?></li>
                            <li><strong><?php _e('WordPress Version:', 'shogun-slogans'); ?></strong> <?php echo get_bloginfo('version'); ?></li>
                            <li><strong><?php _e('PHP Version:', 'shogun-slogans'); ?></strong> <?php echo PHP_VERSION; ?></li>
                            <li><strong><?php _e('jQuery Loaded:', 'shogun-slogans'); ?></strong> <?php echo wp_script_is('jquery') ? __('Yes', 'shogun-slogans') : __('No', 'shogun-slogans'); ?></li>
                            <li><strong><?php _e('Assets Loaded:', 'shogun-slogans'); ?></strong> <?php echo $this->assets_loaded ? __('Yes', 'shogun-slogans') : __('No', 'shogun-slogans'); ?></li>
                            <li><strong><?php _e('Debug Mode:', 'shogun-slogans'); ?></strong> <?php echo $this->options['debug_mode'] ? __('Enabled', 'shogun-slogans') : __('Disabled', 'shogun-slogans'); ?></li>
                        </ul>
                    </div>
                </div>
                
                <div class="shogun-admin-sidebar">
                    <div class="card">
                        <h2><?php _e('Troubleshooting', 'shogun-slogans'); ?></h2>
                        <h3><?php _e('Common Issues', 'shogun-slogans'); ?></h3>
                        <ul>
                            <li><?php _e('Animation not working: Check if jQuery is loaded', 'shogun-slogans'); ?></li>
                            <li><?php _e('Shortcode visible: Check if shortcodes are enabled', 'shogun-slogans'); ?></li>
                            <li><?php _e('Styles not applied: Check if CSS is loaded', 'shogun-slogans'); ?></li>
                        </ul>
                        
                        <h3><?php _e('Support', 'shogun-slogans'); ?></h3>
                        <p><?php _e('If you need help, please provide the debug information above.', 'shogun-slogans'); ?></p>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * General settings section callback
     * 
     * @since 3.1.0
     */
    public function general_section_callback() {
        echo '<p>' . __('Configure the default settings for your animated slogans and typewriter effects.', 'shogun-slogans') . '</p>';
    }
    
    /**
     * Number field callback
     * 
     * @since 3.1.0
     * @param array $args Field arguments
     */
    public function number_field_callback($args) {
        $field = $args['field'];
        $value = isset($this->options[$field]) ? $this->options[$field] : '';
        $description = isset($args['description']) ? $args['description'] : '';
        
        echo '<input type="number" name="shogun_slogans_options[' . esc_attr($field) . ']" value="' . esc_attr($value) . '" min="1" max="10000" />';
        if ($description) {
            echo '<p class="description">' . esc_html($description) . '</p>';
        }
    }
    
    /**
     * Text field callback
     * 
     * @since 3.1.0
     * @param array $args Field arguments
     */
    public function text_field_callback($args) {
        $field = $args['field'];
        $value = isset($this->options[$field]) ? $this->options[$field] : '';
        $description = isset($args['description']) ? $args['description'] : '';
        
        echo '<input type="text" name="shogun_slogans_options[' . esc_attr($field) . ']" value="' . esc_attr($value) . '" />';
        if ($description) {
            echo '<p class="description">' . esc_html($description) . '</p>';
        }
    }
    
    /**
     * Checkbox field callback
     * 
     * @since 3.1.0
     * @param array $args Field arguments
     */
    public function checkbox_field_callback($args) {
        $field = $args['field'];
        $value = isset($this->options[$field]) ? $this->options[$field] : false;
        $description = isset($args['description']) ? $args['description'] : '';
        
        echo '<input type="checkbox" name="shogun_slogans_options[' . esc_attr($field) . ']" value="1" ' . checked($value, true, false) . ' />';
        if ($description) {
            echo '<p class="description">' . esc_html($description) . '</p>';
        }
    }
    
    /**
     * Register REST API routes
     * 
     * @since 3.1.0
     */
    public function register_rest_routes() {
        register_rest_route('shogun-slogans/v1', '/test', array(
            'methods' => 'GET',
            'callback' => array($this, 'rest_test_callback'),
            'permission_callback' => '__return_true'
        ));
        
        register_rest_route('shogun-slogans/v1', '/render', array(
            'methods' => 'POST',
            'callback' => array($this, 'rest_render_callback'),
            'permission_callback' => '__return_true',
            'args' => array(
                'shortcode' => array(
                    'required' => true,
                    'type' => 'string'
                )
            )
        ));
    }
    
    /**
     * REST API test callback
     * 
     * @since 3.1.0
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function rest_test_callback($request) {
        return rest_ensure_response(array(
            'status' => 'success',
            'message' => 'Shogun Slogans REST API is working',
            'version' => SHOGUN_SLOGANS_VERSION,
            'timestamp' => current_time('timestamp')
        ));
    }
    
    /**
     * REST API render callback
     * 
     * @since 3.1.0
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function rest_render_callback($request) {
        $shortcode = $request->get_param('shortcode');
        
        if (empty($shortcode)) {
            return rest_ensure_response(array(
                'status' => 'error',
                'message' => 'Shortcode parameter is required'
            ));
        }
        
        $rendered = do_shortcode($shortcode);
        
        return rest_ensure_response(array(
            'status' => 'success',
            'shortcode' => $shortcode,
            'rendered' => $rendered
        ));
    }
    
    /**
     * AJAX test callback
     * 
     * @since 3.1.0
     */
    public function ajax_test() {
        check_ajax_referer('shogun_slogans_nonce', 'nonce');
        
        wp_send_json_success(array(
            'message' => 'AJAX is working',
            'version' => SHOGUN_SLOGANS_VERSION,
            'timestamp' => current_time('timestamp')
        ));
    }
    
    /**
     * Plugin activation
     * 
     * @since 3.1.0
     */
    public function activate() {
        // Set default options
        $this->load_options();
        update_option('shogun_slogans_options', $this->options);
        
        // Log activation
        $this->log('Plugin activated', 'info');
        
        // Clear any caches
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
    }
    
    /**
     * Plugin deactivation
     * 
     * @since 3.1.0
     */
    public function deactivate() {
        // Log deactivation
        $this->log('Plugin deactivated', 'info');
        
        // Clear any caches
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
    }
    
    /**
     * PHP version notice
     * 
     * @since 3.1.0
     */
    public function php_version_notice() {
        $message = sprintf(
            __('Shogun Slogans requires PHP version %s or higher. You are running version %s.', 'shogun-slogans'),
            SHOGUN_SLOGANS_MIN_PHP_VERSION,
            PHP_VERSION
        );
        
        echo '<div class="notice notice-error"><p>' . $message . '</p></div>';
    }
    
    /**
     * WordPress version notice
     * 
     * @since 3.1.0
     */
    public function wp_version_notice() {
        global $wp_version;
        
        $message = sprintf(
            __('Shogun Slogans requires WordPress version %s or higher. You are running version %s.', 'shogun-slogans'),
            SHOGUN_SLOGANS_MIN_WP_VERSION,
            $wp_version
        );
        
        echo '<div class="notice notice-error"><p>' . $message . '</p></div>';
    }
    
    /**
     * Log messages
     * 
     * @since 3.1.0
     * @param string $message Log message
     * @param string $level Log level
     * @param mixed $context Additional context
     */
    private function log($message, $level = 'info', $context = null) {
        if (!$this->options['debug_mode']) {
            return;
        }
        
        $log_entry = array(
            'timestamp' => current_time('c'),
            'level' => $level,
            'message' => $message,
            'context' => $context
        );
        
        if (function_exists('error_log')) {
            error_log('[Shogun Slogans] ' . json_encode($log_entry));
        }
    }
}

// Initialize the plugin
function shogun_slogans_init() {
    return ShogunSlogansPlugin::instance();
}

// Start the plugin
add_action('plugins_loaded', 'shogun_slogans_init');

// Plugin loaded hook for other plugins/themes
do_action('shogun_slogans_loaded');
