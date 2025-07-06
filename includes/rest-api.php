<?php
/**
 * Shogun Slogans REST API Endpoints
 * 
 * Dynamic animation API endpoints for CSS generation
 * and animation management.
 * 
 * @package ShogunSlogans
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

/**
 * REST API Controller for Shogun Slogans
 */
class ShogunSlogansRestController {
    
    /**
     * API namespace
     */
    const NAMESPACE = 'shogun-slogans/v1';
    
    /**
     * CSS Generator instance
     * 
     * @var ShogunCSSGenerator
     */
    private $css_generator;
    
    /**
     * CSS Cache instance
     * 
     * @var ShogunCSSCache
     */
    private $css_cache;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->css_generator = new ShogunCSSGenerator();
        $this->css_cache = new ShogunCSSCache();
    }
    
    /**
     * Register REST API routes
     */
    public function register_routes() {
        // Get all animations
        register_rest_route(self::NAMESPACE, '/animations', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_animations'),
            'permission_callback' => '__return_true', // Public endpoint
        ));
        
        // Get specific animation
        register_rest_route(self::NAMESPACE, '/animations/(?P<name>[a-zA-Z0-9_-]+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_animation'),
            'permission_callback' => '__return_true',
            'args' => array(
                'name' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));
        
        // Generate CSS
        register_rest_route(self::NAMESPACE, '/generate-css', array(
            'methods' => 'POST',
            'callback' => array($this, 'generate_css'),
            'permission_callback' => array($this, 'check_edit_permissions'),
            'args' => array(
                'animation' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'parameters' => array(
                    'default' => array(),
                    'sanitize_callback' => array($this, 'sanitize_parameters'),
                ),
                'selector' => array(
                    'default' => '',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'use_cache' => array(
                    'default' => true,
                    'sanitize_callback' => 'rest_sanitize_boolean',
                ),
            ),
        ));
        
        // Get compiled CSS (cached endpoint)
        register_rest_route(self::NAMESPACE, '/css/(?P<animation>[a-zA-Z0-9_-]+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_compiled_css'),
            'permission_callback' => '__return_true',
            'args' => array(
                'animation' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));
        
        // Preview animation
        register_rest_route(self::NAMESPACE, '/preview', array(
            'methods' => 'POST',
            'callback' => array($this, 'preview_animation'),
            'permission_callback' => array($this, 'check_edit_permissions'),
            'args' => array(
                'animation' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'text' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'parameters' => array(
                    'default' => array(),
                    'sanitize_callback' => array($this, 'sanitize_parameters'),
                ),
            ),
        ));
        
        // Clear cache (admin only)
        register_rest_route(self::NAMESPACE, '/cache/clear', array(
            'methods' => 'POST',
            'callback' => array($this, 'clear_cache'),
            'permission_callback' => array($this, 'check_admin_permissions'),
            'args' => array(
                'cache_key' => array(
                    'default' => '',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));
    }
    
    /**
     * Get all animations
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function get_animations($request) {
        $animations = ShogunAnimationRegistry::get_all_animations();
        $categories = ShogunAnimationRegistry::get_categories();
        
        // Format response
        $formatted_animations = array();
        foreach ($animations as $name => $animation) {
            $formatted_animations[] = array(
                'name' => $name,
                'category' => $animation['category'],
                'description' => $animation['description'],
                'parameters' => $animation['parameters'],
                'version' => $animation['version'],
            );
        }
        
        return rest_ensure_response(array(
            'animations' => $formatted_animations,
            'categories' => $categories,
            'total' => count($formatted_animations),
        ));
    }
    
    /**
     * Get specific animation
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function get_animation($request) {
        $name = $request->get_param('name');
        $animation = ShogunAnimationRegistry::get_animation($name);
        
        if (!$animation) {
            return new WP_Error('animation_not_found', 'Animation not found', array('status' => 404));
        }
        
        return rest_ensure_response(array(
            'name' => $name,
            'category' => $animation['category'],
            'description' => $animation['description'],
            'parameters' => $animation['parameters'],
            'version' => $animation['version'],
            'js_init' => $animation['js_init'],
        ));
    }
    
    /**
     * Generate CSS for animation
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function generate_css($request) {
        $animation_name = $request->get_param('animation');
        $parameters = $request->get_param('parameters');
        $selector = $request->get_param('selector');
        $use_cache = $request->get_param('use_cache');
        
        // Check if animation exists
        $animation = ShogunAnimationRegistry::get_animation($animation_name);
        if (!$animation) {
            return new WP_Error('animation_not_found', 'Animation not found', array('status' => 404));
        }
        
        // Generate cache key
        $cache_key = $this->css_cache->generate_cache_key($animation_name, $parameters);
        
        // Check cache first
        if ($use_cache) {
            $cached_css = $this->css_cache->get_cached_css($cache_key);
            if ($cached_css !== false) {
                return rest_ensure_response(array(
                    'css' => $cached_css,
                    'cache_key' => $cache_key,
                    'cached' => true,
                    'js_init' => $animation['js_init'],
                    'selector' => $selector,
                ));
            }
        }
        
        // Generate CSS
        $unique_id = substr($cache_key, 0, 8);
        $css = $this->css_generator->generate_animation_css($animation_name, $parameters, $unique_id);
        
        if (empty($css)) {
            return new WP_Error('css_generation_failed', 'Failed to generate CSS', array('status' => 500));
        }
        
        // Apply custom selector if provided
        if (!empty($selector)) {
            $css = str_replace(".shogun-{$animation_name}-{$unique_id}", $selector, $css);
        }
        
        // Cache the CSS
        if ($use_cache) {
            $this->css_cache->cache_css($cache_key, $css);
        }
        
        return rest_ensure_response(array(
            'css' => $css,
            'cache_key' => $cache_key,
            'cached' => false,
            'js_init' => $animation['js_init'],
            'selector' => $selector ?: ".shogun-{$animation_name}-{$unique_id}",
            'unique_id' => $unique_id,
        ));
    }
    
    /**
     * Get compiled CSS (with query parameters)
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function get_compiled_css($request) {
        $animation_name = $request->get_param('animation');
        
        // Get all query parameters as animation parameters
        $parameters = array();
        $query_params = $request->get_query_params();
        
        // Remove system parameters
        unset($query_params['animation']);
        
        $parameters = $query_params;
        
        // Forward to generate_css
        $generate_request = new WP_REST_Request('POST');
        $generate_request->set_param('animation', $animation_name);
        $generate_request->set_param('parameters', $parameters);
        $generate_request->set_param('use_cache', true);
        
        $response = $this->generate_css($generate_request);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $data = $response->get_data();
        
        // Return just the CSS with proper headers
        $css_response = new WP_REST_Response($data['css']);
        $css_response->header('Content-Type', 'text/css');
        $css_response->header('Cache-Control', 'public, max-age=3600');
        
        return $css_response;
    }
    
    /**
     * Preview animation
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function preview_animation($request) {
        $animation_name = $request->get_param('animation');
        $text = $request->get_param('text');
        $parameters = $request->get_param('parameters');
        
        // Add text to parameters
        $parameters['text'] = $text;
        
        // Generate CSS
        $generate_request = new WP_REST_Request('POST');
        $generate_request->set_param('animation', $animation_name);
        $generate_request->set_param('parameters', $parameters);
        $generate_request->set_param('use_cache', false); // Don't cache previews
        
        $css_response = $this->generate_css($generate_request);
        
        if (is_wp_error($css_response)) {
            return $css_response;
        }
        
        $css_data = $css_response->get_data();
        
        // Generate HTML for preview
        $unique_id = $css_data['unique_id'];
        $selector_class = "shogun-{$animation_name}-{$unique_id}";
        
        $html = $this->generate_preview_html($animation_name, $text, $selector_class);
        
        return rest_ensure_response(array(
            'html' => $html,
            'css' => $css_data['css'],
            'js_init' => $css_data['js_init'],
            'selector' => $css_data['selector'],
            'parameters' => $parameters,
        ));
    }
    
    /**
     * Clear cache
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function clear_cache($request) {
        $cache_key = $request->get_param('cache_key');
        
        if (!empty($cache_key)) {
            $this->css_cache->clear_cache($cache_key);
            $message = 'Specific cache cleared';
        } else {
            $this->css_cache->clear_cache();
            $message = 'All cache cleared';
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'message' => $message,
        ));
    }
    
    /**
     * Generate preview HTML
     * 
     * @param string $animation_name Animation name
     * @param string $text Text to display
     * @param string $selector_class CSS class
     * @return string HTML
     */
    private function generate_preview_html($animation_name, $text, $selector_class) {
        switch ($animation_name) {
            case 'typewriter':
                return sprintf(
                    '<div class="%s"><span class="typewriter-text">%s</span><span class="typewriter-cursor">|</span></div>',
                    esc_attr($selector_class),
                    esc_html($text)
                );
                
            case 'handwritten':
                return sprintf(
                    '<div class="%s"><span class="handwritten-text">%s</span></div>',
                    esc_attr($selector_class),
                    esc_html($text)
                );
                
            case 'neon':
                return sprintf(
                    '<div class="%s">%s</div>',
                    esc_attr($selector_class),
                    esc_html($text)
                );
                
            default:
                return sprintf(
                    '<div class="%s">%s</div>',
                    esc_attr($selector_class),
                    esc_html($text)
                );
        }
    }
    
    /**
     * Sanitize parameters array
     * 
     * @param array $parameters Parameters to sanitize
     * @return array Sanitized parameters
     */
    public function sanitize_parameters($parameters) {
        if (!is_array($parameters)) {
            return array();
        }
        
        $sanitized = array();
        foreach ($parameters as $key => $value) {
            $sanitized[sanitize_key($key)] = sanitize_text_field($value);
        }
        
        return $sanitized;
    }
    
    /**
     * Check edit permissions
     * 
     * @return bool
     */
    public function check_edit_permissions() {
        return current_user_can('edit_posts');
    }
    
    /**
     * Check admin permissions
     * 
     * @return bool
     */
    public function check_admin_permissions() {
        return current_user_can('manage_options');
    }
}

/**
 * Initialize REST API
 */
add_action('rest_api_init', function() {
    $controller = new ShogunSlogansRestController();
    $controller->register_routes();
});
