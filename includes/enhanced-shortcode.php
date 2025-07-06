<?php
/**
 * Enhanced Shortcode with Dynamic Animation API
 * 
 * This file demonstrates how to use the animation API
 * with shortcodes for on-demand CSS generation.
 * 
 * @package ShogunSlogans
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

/**
 * Enhanced Shogun Slogans Shortcode Handler
 */
class ShogunSlogansEnhancedShortcode {
    
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
     * Generated CSS for current page
     * 
     * @var array
     */
    private static $page_css = array();
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->css_generator = new ShogunCSSGenerator();
        $this->css_cache = new ShogunCSSCache();
        
        // Register shortcodes
        add_shortcode('shogun_animation', array($this, 'animation_shortcode'));
        add_shortcode('shogun_typewriter_v2', array($this, 'typewriter_shortcode'));
        
        // Hook to output CSS in footer
        add_action('wp_footer', array($this, 'output_page_css'));
    }
    
    /**
     * Main animation shortcode
     * 
     * Usage: [shogun_animation type="typewriter" text="Hello World" speed="100" color="#ff0000"]
     * 
     * @param array $atts Shortcode attributes
     * @param string $content Shortcode content
     * @return string Generated HTML
     */
    public function animation_shortcode($atts, $content = '') {
        // Default attributes
        $atts = shortcode_atts(array(
            'type' => 'typewriter',
            'text' => $content ?: 'Your text here',
            'class' => '',
            'id' => '',
            'speed' => null,
            'color' => null,
            'cursor' => null,
            'glow_color' => null,
            'intensity' => null,
            'font_size' => null,
            'font_family' => null,
            'flicker' => null,
            'wobble' => null,
            'cache' => 'true'
        ), $atts, 'shogun_animation');
        
        // Check if animation type exists
        $animation = ShogunAnimationRegistry::get_animation($atts['type']);
        if (!$animation) {
            return '<div class="shogun-error">Animation type "' . esc_html($atts['type']) . '" not found.</div>';
        }
        
        // Build parameters array
        $parameters = $this->build_parameters($atts, $animation['parameters']);
        
        // Generate unique ID
        $unique_id = $this->generate_shortcode_id($atts['type'], $parameters);
        
        // Check cache first
        $use_cache = filter_var($atts['cache'], FILTER_VALIDATE_BOOLEAN);
        $cache_key = $this->css_cache->generate_cache_key($atts['type'], $parameters);
        
        $css = null;
        if ($use_cache) {
            $css = $this->css_cache->get_cached_css($cache_key);
        }
        
        // Generate CSS if not cached
        if ($css === null || $css === false) {
            $css = $this->css_generator->generate_animation_css($atts['type'], $parameters, $unique_id);
            
            if ($use_cache && !empty($css)) {
                $this->css_cache->cache_css($cache_key, $css);
            }
        }
        
        // Add CSS to page CSS collection
        if (!empty($css)) {
            self::$page_css[$unique_id] = $css;
        }
        
        // Generate HTML
        $html = $this->generate_html($atts['type'], $atts['text'], $unique_id, $atts);
        
        return $html;
    }
    
    /**
     * Typewriter shortcode (v2 with API)
     * 
     * Usage: [shogun_typewriter_v2 text="Hello" speed="100" cursor="|"]
     * 
     * @param array $atts Shortcode attributes
     * @return string Generated HTML
     */
    public function typewriter_shortcode($atts) {
        // Convert to animation shortcode format
        $atts['type'] = 'typewriter';
        return $this->animation_shortcode($atts);
    }
    
    /**
     * Build parameters array from shortcode attributes
     * 
     * @param array $atts Shortcode attributes
     * @param array $animation_params Animation parameter definitions
     * @return array Parameters
     */
    private function build_parameters($atts, $animation_params) {
        $parameters = array();
        
        // Add text parameter
        $parameters['text'] = $atts['text'];
        
        // Map shortcode attributes to animation parameters
        foreach ($animation_params as $param_name => $param_config) {
            if (isset($atts[$param_name]) && $atts[$param_name] !== null) {
                $parameters[$param_name] = $atts[$param_name];
            }
        }
        
        return $parameters;
    }
    
    /**
     * Generate unique ID for shortcode instance
     * 
     * @param string $animation_type Animation type
     * @param array $parameters Parameters
     * @return string Unique ID
     */
    private function generate_shortcode_id($animation_type, $parameters) {
        $hash_data = $animation_type . serialize($parameters) . uniqid();
        return substr(md5($hash_data), 0, 8);
    }
    
    /**
     * Generate HTML for animation
     * 
     * @param string $animation_type Animation type
     * @param string $text Text to display
     * @param string $unique_id Unique ID
     * @param array $atts Shortcode attributes
     * @return string HTML
     */
    private function generate_html($animation_type, $text, $unique_id, $atts) {
        $classes = array("shogun-{$animation_type}-{$unique_id}");
        
        // Add custom class if provided
        if (!empty($atts['class'])) {
            $classes[] = esc_attr($atts['class']);
        }
        
        // Build attributes
        $attributes = array();
        if (!empty($atts['id'])) {
            $attributes[] = 'id="' . esc_attr($atts['id']) . '"';
        }
        $attributes[] = 'class="' . implode(' ', $classes) . '"';
        $attributes[] = 'data-animation="' . esc_attr($animation_type) . '"';
        $attributes[] = 'data-unique-id="' . esc_attr($unique_id) . '"';
        
        // Generate type-specific HTML
        switch ($animation_type) {
            case 'typewriter':
                return sprintf(
                    '<div %s><span class="typewriter-text">%s</span><span class="typewriter-cursor">%s</span></div>',
                    implode(' ', $attributes),
                    esc_html($text),
                    esc_html($atts['cursor'] ?: '|')
                );
                
            case 'handwritten':
                return sprintf(
                    '<div %s><span class="handwritten-text">%s</span></div>',
                    implode(' ', $attributes),
                    esc_html($text)
                );
                
            case 'neon':
                return sprintf(
                    '<div %s>%s</div>',
                    implode(' ', $attributes),
                    esc_html($text)
                );
                
            default:
                return sprintf(
                    '<div %s>%s</div>',
                    implode(' ', $attributes),
                    esc_html($text)
                );
        }
    }
    
    /**
     * Output accumulated CSS in footer
     */
    public function output_page_css() {
        if (empty(self::$page_css)) {
            return;
        }
        
        echo '<style id="shogun-slogans-dynamic-css">' . "\n";
        echo '/* Shogun Slogans Dynamic CSS */' . "\n";
        
        foreach (self::$page_css as $unique_id => $css) {
            echo "/* Animation ID: {$unique_id} */\n";
            echo $css . "\n";
        }
        
        echo '</style>' . "\n";
        
        // Clear the CSS array to prevent duplicate output
        self::$page_css = array();
    }
    
    /**
     * Get API endpoint for AJAX usage
     * 
     * @return string API endpoint URL
     */
    public static function get_api_endpoint() {
        return rest_url('shogun-slogans/v1/');
    }
    
    /**
     * Generate shortcode examples for admin
     * 
     * @return array Examples
     */
    public static function get_shortcode_examples() {
        return array(
            'Basic Typewriter' => array(
                'shortcode' => '[shogun_animation type="typewriter" text="Hello World!"]',
                'description' => 'Basic typewriter effect with default settings'
            ),
            'Fast Typewriter' => array(
                'shortcode' => '[shogun_animation type="typewriter" text="Fast typing!" speed="50"]',
                'description' => 'Fast typewriter effect'
            ),
            'Custom Cursor' => array(
                'shortcode' => '[shogun_animation type="typewriter" text="Custom cursor" cursor="▌"]',
                'description' => 'Typewriter with custom cursor character'
            ),
            'Colored Text' => array(
                'shortcode' => '[shogun_animation type="typewriter" text="Colored text" color="#ff6b6b"]',
                'description' => 'Typewriter with custom text color'
            ),
            'Handwritten Effect' => array(
                'shortcode' => '[shogun_animation type="handwritten" text="Handwritten style"]',
                'description' => 'Handwritten animation effect'
            ),
            'Neon Glow' => array(
                'shortcode' => '[shogun_animation type="neon" text="Neon Glow" glow_color="#00ffff"]',
                'description' => 'Neon glow effect with cyan color'
            ),
            'Flickering Neon' => array(
                'shortcode' => '[shogun_animation type="neon" text="Flickering" flicker="true"]',
                'description' => 'Neon effect with flickering animation'
            ),
            'REIA Slogan' => array(
                'shortcode' => '[shogun_animation type="typewriter" text="I will help you make The Smart Move - I guarantee it!" speed="80" color="#2c3e50"]',
                'description' => 'Professional real estate slogan'
            ),
        );
    }
}

/**
 * Initialize enhanced shortcodes
 */
add_action('init', function() {
    // Make sure animation API is loaded
    if (class_exists('ShogunAnimationRegistry')) {
        new ShogunSlogansEnhancedShortcode();
    }
});

/**
 * Helper function for theme developers
 * 
 * @param string $type Animation type
 * @param string $text Text to animate
 * @param array $params Animation parameters
 * @return string Generated HTML
 */
function shogun_dynamic_animation($type, $text, $params = array()) {
    if (!class_exists('ShogunSlogansEnhancedShortcode')) {
        return esc_html($text);
    }
    
    // Build shortcode attributes
    $atts = array_merge(array(
        'type' => $type,
        'text' => $text
    ), $params);
    
    $shortcode = new ShogunSlogansEnhancedShortcode();
    return $shortcode->animation_shortcode($atts);
}

/**
 * Helper function for typewriter effect
 * 
 * @param string $text Text to animate
 * @param int $speed Typing speed
 * @param string $cursor Cursor character
 * @return string Generated HTML
 */
function shogun_dynamic_typewriter($text, $speed = 100, $cursor = '|') {
    return shogun_dynamic_animation('typewriter', $text, array(
        'speed' => $speed,
        'cursor' => $cursor
    ));
}
