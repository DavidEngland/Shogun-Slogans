<?php
/**
 * Shogun Slogans Dynamic Animation API
 * 
 * This file contains the core animation API implementation
 * for generating CSS on-demand and minimizing JavaScript.
 * 
 * @package ShogunSlogans
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

/**
 * Animation Registry - Central hub for all animations
 */
class ShogunAnimationRegistry {
    
    /**
     * Registered animations
     * 
     * @var array
     */
    private static $animations = array();
    
    /**
     * Animation categories
     * 
     * @var array
     */
    private static $categories = array(
        'text' => 'Text Effects',
        'visual' => 'Visual Effects',
        'interactive' => 'Interactive Effects',
        'advanced' => 'Advanced Effects'
    );
    
    /**
     * Register an animation
     * 
     * @param string $name Animation name
     * @param array $config Animation configuration
     */
    public static function register_animation($name, $config) {
        // Validate config
        $config = wp_parse_args($config, array(
            'name' => $name,
            'category' => 'text',
            'description' => '',
            'parameters' => array(),
            'css_template' => '',
            'js_init' => '',
            'dependencies' => array(),
            'version' => '1.0.0'
        ));
        
        self::$animations[$name] = $config;
        
        // Fire action for extensibility
        do_action('shogun_slogans_animation_registered', $name, $config);
    }
    
    /**
     * Get animation configuration
     * 
     * @param string $name Animation name
     * @return array|null
     */
    public static function get_animation($name) {
        return isset(self::$animations[$name]) ? self::$animations[$name] : null;
    }
    
    /**
     * Get all registered animations
     * 
     * @return array
     */
    public static function get_all_animations() {
        return self::$animations;
    }
    
    /**
     * Get animations by category
     * 
     * @param string $category Category name
     * @return array
     */
    public static function get_animations_by_category($category) {
        return array_filter(self::$animations, function($animation) use ($category) {
            return $animation['category'] === $category;
        });
    }
    
    /**
     * Get available categories
     * 
     * @return array
     */
    public static function get_categories() {
        return self::$categories;
    }
    
    /**
     * Initialize default animations
     */
    public static function init_default_animations() {
        
        // Typewriter Animation
        self::register_animation('typewriter', array(
            'name' => 'typewriter',
            'category' => 'text',
            'description' => 'Classic typewriter effect with customizable cursor',
            'parameters' => array(
                'speed' => array(
                    'type' => 'int',
                    'default' => 100,
                    'min' => 10,
                    'max' => 1000,
                    'label' => 'Typing Speed (ms)',
                    'description' => 'Speed of typing animation in milliseconds'
                ),
                'cursor' => array(
                    'type' => 'string',
                    'default' => '|',
                    'label' => 'Cursor Character',
                    'description' => 'Character to use as cursor'
                ),
                'cursor_speed' => array(
                    'type' => 'int',
                    'default' => 500,
                    'min' => 100,
                    'max' => 2000,
                    'label' => 'Cursor Blink Speed (ms)',
                    'description' => 'Speed of cursor blinking'
                ),
                'color' => array(
                    'type' => 'color',
                    'default' => 'inherit',
                    'label' => 'Text Color',
                    'description' => 'Color of the text'
                ),
                'font_size' => array(
                    'type' => 'size',
                    'default' => 'inherit',
                    'label' => 'Font Size',
                    'description' => 'Size of the text'
                ),
                'font_family' => array(
                    'type' => 'string',
                    'default' => 'inherit',
                    'label' => 'Font Family',
                    'description' => 'Font family for the text'
                )
            ),
            'css_template' => '
                .shogun-typewriter-{{id}} {
                    --typing-speed: {{speed}}ms;
                    --cursor-char: "{{cursor}}";
                    --cursor-speed: {{cursor_speed}}ms;
                    --text-color: {{color}};
                    font-size: {{font_size}};
                    font-family: {{font_family}};
                    color: var(--text-color);
                    overflow: hidden;
                    white-space: nowrap;
                    display: inline-block;
                    position: relative;
                }
                
                .shogun-typewriter-{{id}} .typewriter-text {
                    display: inline-block;
                    overflow: hidden;
                    white-space: nowrap;
                    animation: shogun-type-{{id}} calc(var(--typing-speed) * {{text_length}}) steps({{text_length}}, end) forwards;
                }
                
                .shogun-typewriter-{{id}} .typewriter-cursor {
                    display: inline-block;
                    animation: shogun-blink-{{id}} var(--cursor-speed) infinite;
                    margin-left: 1px;
                }
                
                @keyframes shogun-type-{{id}} {
                    from { width: 0; }
                    to { width: 100%; }
                }
                
                @keyframes shogun-blink-{{id}} {
                    0%, 50% { opacity: 1; }
                    51%, 100% { opacity: 0; }
                }
            ',
            'js_init' => 'ShogunAPI.initTypewriter'
        ));
        
        // Handwritten Animation
        self::register_animation('handwritten', array(
            'name' => 'handwritten',
            'category' => 'text',
            'description' => 'Handwritten effect with natural variations',
            'parameters' => array(
                'speed' => array(
                    'type' => 'int',
                    'default' => 150,
                    'min' => 50,
                    'max' => 500,
                    'label' => 'Writing Speed (ms)',
                    'description' => 'Speed of handwriting animation'
                ),
                'color' => array(
                    'type' => 'color',
                    'default' => '#2c3e50',
                    'label' => 'Ink Color',
                    'description' => 'Color of the handwritten text'
                ),
                'font_family' => array(
                    'type' => 'string',
                    'default' => 'cursive',
                    'label' => 'Font Family',
                    'description' => 'Handwriting font family'
                ),
                'wobble' => array(
                    'type' => 'boolean',
                    'default' => true,
                    'label' => 'Natural Wobble',
                    'description' => 'Add natural handwriting variations'
                )
            ),
            'css_template' => '
                .shogun-handwritten-{{id}} {
                    --writing-speed: {{speed}}ms;
                    --ink-color: {{color}};
                    color: var(--ink-color);
                    font-family: {{font_family}};
                    position: relative;
                    overflow: hidden;
                    display: inline-block;
                }
                
                .shogun-handwritten-{{id}} .handwritten-text {
                    display: inline-block;
                    opacity: 0;
                    animation: shogun-handwrite-{{id}} calc(var(--writing-speed) * {{text_length}}) ease-in-out forwards;
                    {{#if wobble}}
                    transform: rotate(0.5deg);
                    {{/if}}
                }
                
                @keyframes shogun-handwrite-{{id}} {
                    0% { 
                        opacity: 0;
                        transform: translateY(10px) {{#if wobble}}rotate(0.5deg){{/if}};
                    }
                    20% { 
                        opacity: 1;
                        transform: translateY(0) {{#if wobble}}rotate(-0.2deg){{/if}};
                    }
                    100% { 
                        opacity: 1;
                        transform: translateY(0) {{#if wobble}}rotate(0.1deg){{/if}};
                    }
                }
            ',
            'js_init' => 'ShogunAPI.initHandwritten'
        ));
        
        // Neon Glow Animation
        self::register_animation('neon', array(
            'name' => 'neon',
            'category' => 'visual',
            'description' => 'Neon glow effect with customizable colors',
            'parameters' => array(
                'glow_color' => array(
                    'type' => 'color',
                    'default' => '#00ffff',
                    'label' => 'Glow Color',
                    'description' => 'Color of the neon glow'
                ),
                'text_color' => array(
                    'type' => 'color',
                    'default' => '#ffffff',
                    'label' => 'Text Color',
                    'description' => 'Color of the text'
                ),
                'intensity' => array(
                    'type' => 'int',
                    'default' => 20,
                    'min' => 5,
                    'max' => 50,
                    'label' => 'Glow Intensity',
                    'description' => 'Intensity of the glow effect'
                ),
                'flicker' => array(
                    'type' => 'boolean',
                    'default' => false,
                    'label' => 'Flicker Effect',
                    'description' => 'Add flickering neon effect'
                ),
                'speed' => array(
                    'type' => 'int',
                    'default' => 2000,
                    'min' => 500,
                    'max' => 5000,
                    'label' => 'Animation Speed (ms)',
                    'description' => 'Speed of the neon animation'
                )
            ),
            'css_template' => '
                .shogun-neon-{{id}} {
                    --glow-color: {{glow_color}};
                    --text-color: {{text_color}};
                    --glow-intensity: {{intensity}}px;
                    --animation-speed: {{speed}}ms;
                    
                    color: var(--text-color);
                    text-shadow: 
                        0 0 5px var(--glow-color),
                        0 0 10px var(--glow-color),
                        0 0 15px var(--glow-color),
                        0 0 var(--glow-intensity) var(--glow-color);
                    
                    {{#if flicker}}
                    animation: shogun-neon-flicker-{{id}} var(--animation-speed) infinite alternate;
                    {{/if}}
                }
                
                {{#if flicker}}
                @keyframes shogun-neon-flicker-{{id}} {
                    0%, 19%, 21%, 23%, 25%, 54%, 56%, 100% {
                        text-shadow: 
                            0 0 5px var(--glow-color),
                            0 0 10px var(--glow-color),
                            0 0 15px var(--glow-color),
                            0 0 var(--glow-intensity) var(--glow-color);
                    }
                    20%, 24%, 55% {
                        text-shadow: none;
                    }
                }
                {{/if}}
            ',
            'js_init' => 'ShogunAPI.initNeon'
        ));
        
        // Apply filters to allow customization
        self::$animations = apply_filters('shogun_slogans_default_animations', self::$animations);
    }
}

/**
 * CSS Generator - Compiles animation templates into CSS
 */
class ShogunCSSGenerator {
    
    /**
     * Template engine instance
     * 
     * @var ShogunTemplateEngine
     */
    private $template_engine;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->template_engine = new ShogunTemplateEngine();
    }
    
    /**
     * Generate CSS for an animation
     * 
     * @param string $animation_name Animation name
     * @param array $params Animation parameters
     * @param string $unique_id Unique identifier for the animation
     * @return string Generated CSS
     */
    public function generate_animation_css($animation_name, $params = array(), $unique_id = '') {
        $animation = ShogunAnimationRegistry::get_animation($animation_name);
        
        if (!$animation) {
            return '';
        }
        
        // Generate unique ID if not provided
        if (empty($unique_id)) {
            $unique_id = $this->generate_unique_id($animation_name, $params);
        }
        
        // Merge with defaults
        $params = $this->merge_with_defaults($animation['parameters'], $params);
        
        // Add meta parameters
        $params['id'] = $unique_id;
        $params['animation_name'] = $animation_name;
        
        // Calculate text length if text is provided
        if (isset($params['text'])) {
            $params['text_length'] = strlen($params['text']);
        }
        
        // Compile template
        $css = $this->template_engine->compile($animation['css_template'], $params);
        
        // Minify CSS
        $css = $this->minify_css($css);
        
        // Apply filters
        $css = apply_filters('shogun_slogans_generated_css', $css, $animation_name, $params);
        
        return $css;
    }
    
    /**
     * Generate a unique ID for the animation
     * 
     * @param string $animation_name Animation name
     * @param array $params Parameters
     * @return string Unique ID
     */
    private function generate_unique_id($animation_name, $params) {
        $hash_data = $animation_name . serialize($params);
        return substr(md5($hash_data), 0, 8);
    }
    
    /**
     * Merge parameters with defaults
     * 
     * @param array $defaults Default parameters
     * @param array $params User parameters
     * @return array Merged parameters
     */
    private function merge_with_defaults($defaults, $params) {
        $merged = array();
        
        foreach ($defaults as $key => $config) {
            if (isset($params[$key])) {
                $merged[$key] = $this->sanitize_parameter($params[$key], $config);
            } else {
                $merged[$key] = $config['default'];
            }
        }
        
        return $merged;
    }
    
    /**
     * Sanitize parameter based on type
     * 
     * @param mixed $value Parameter value
     * @param array $config Parameter configuration
     * @return mixed Sanitized value
     */
    private function sanitize_parameter($value, $config) {
        switch ($config['type']) {
            case 'int':
                $value = intval($value);
                if (isset($config['min']) && $value < $config['min']) {
                    $value = $config['min'];
                }
                if (isset($config['max']) && $value > $config['max']) {
                    $value = $config['max'];
                }
                break;
                
            case 'string':
                $value = sanitize_text_field($value);
                break;
                
            case 'color':
                $value = sanitize_hex_color($value) ?: $config['default'];
                break;
                
            case 'size':
                $value = sanitize_text_field($value);
                // Basic size validation
                if (!preg_match('/^(\d+(?:\.\d+)?)(px|em|rem|%|vh|vw)$/', $value)) {
                    $value = $config['default'];
                }
                break;
                
            case 'boolean':
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                break;
                
            default:
                $value = $config['default'];
        }
        
        return $value;
    }
    
    /**
     * Minify CSS
     * 
     * @param string $css CSS code
     * @return string Minified CSS
     */
    private function minify_css($css) {
        // Remove comments
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        
        // Remove unnecessary whitespace
        $css = preg_replace('/\s+/', ' ', $css);
        $css = str_replace(array(' {', '{ ', ' }', '} ', '; ', ' ;', ', ', ' ,'), 
                          array('{', '{', '}', '}', ';', ';', ',', ','), $css);
        
        return trim($css);
    }
}

/**
 * Simple Template Engine
 */
class ShogunTemplateEngine {
    
    /**
     * Compile template with variables
     * 
     * @param string $template Template string
     * @param array $variables Template variables
     * @return string Compiled template
     */
    public function compile($template, $variables) {
        // Handle simple variable replacement
        $template = preg_replace_callback('/\{\{(\w+)\}\}/', function($matches) use ($variables) {
            return isset($variables[$matches[1]]) ? $variables[$matches[1]] : '';
        }, $template);
        
        // Handle conditional blocks
        $template = $this->handle_conditionals($template, $variables);
        
        return $template;
    }
    
    /**
     * Handle conditional blocks in templates
     * 
     * @param string $template Template string
     * @param array $variables Template variables
     * @return string Processed template
     */
    private function handle_conditionals($template, $variables) {
        // Handle {{#if condition}} blocks
        $template = preg_replace_callback('/\{\{#if (\w+)\}\}(.*?)\{\{\/if\}\}/s', function($matches) use ($variables) {
            $condition = $matches[1];
            $content = $matches[2];
            
            if (isset($variables[$condition]) && $variables[$condition]) {
                return $content;
            }
            
            return '';
        }, $template);
        
        return $template;
    }
}

/**
 * CSS Cache Manager
 */
class ShogunCSSCache {
    
    /**
     * Cache prefix
     */
    const CACHE_PREFIX = 'shogun_css_';
    
    /**
     * Default cache expiry (1 hour)
     */
    const DEFAULT_EXPIRY = 3600;
    
    /**
     * Get cached CSS
     * 
     * @param string $cache_key Cache key
     * @return string|false Cached CSS or false if not found
     */
    public function get_cached_css($cache_key) {
        return get_transient(self::CACHE_PREFIX . $cache_key);
    }
    
    /**
     * Cache CSS
     * 
     * @param string $cache_key Cache key
     * @param string $css CSS content
     * @param int $expiry Cache expiry in seconds
     * @return bool Success
     */
    public function cache_css($cache_key, $css, $expiry = self::DEFAULT_EXPIRY) {
        return set_transient(self::CACHE_PREFIX . $cache_key, $css, $expiry);
    }
    
    /**
     * Clear CSS cache
     * 
     * @param string $cache_key Optional specific cache key to clear
     */
    public function clear_cache($cache_key = null) {
        if ($cache_key) {
            delete_transient(self::CACHE_PREFIX . $cache_key);
        } else {
            // Clear all CSS caches
            global $wpdb;
            $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '_transient_' . self::CACHE_PREFIX . '%'));
        }
    }
    
    /**
     * Generate cache key
     * 
     * @param string $animation_name Animation name
     * @param array $params Parameters
     * @return string Cache key
     */
    public function generate_cache_key($animation_name, $params) {
        return md5($animation_name . serialize($params));
    }
}

/**
 * Initialize the animation system
 */
add_action('init', function() {
    ShogunAnimationRegistry::init_default_animations();
});
