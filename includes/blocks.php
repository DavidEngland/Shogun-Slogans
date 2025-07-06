<?php
/**
 * Gutenberg Blocks Registration
 * 
 * Register and manage Gutenberg blocks for Shogun Slogans
 * 
 * @package ShogunSlogans
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

/**
 * Gutenberg Blocks Manager
 */
class ShogunSlogansBlocks {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'register_blocks'));
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_block_editor_assets'));
        add_action('enqueue_block_assets', array($this, 'enqueue_block_assets'));
        add_filter('block_categories_all', array($this, 'add_block_category'), 10, 2);
    }
    
    /**
     * Register Gutenberg blocks
     */
    public function register_blocks() {
        // Check if Gutenberg is available
        if (!function_exists('register_block_type')) {
            return;
        }
        
        // Register main animation block
        register_block_type('shogun-slogans/animation-block', array(
            'editor_script' => 'shogun-slogans-blocks',
            'editor_style' => 'shogun-slogans-block-editor',
            'render_callback' => array($this, 'render_animation_block'),
            'attributes' => array(
                'text' => array(
                    'type' => 'string',
                    'default' => 'Your animated text here...'
                ),
                'animationType' => array(
                    'type' => 'string',
                    'default' => 'typewriter'
                ),
                'speed' => array(
                    'type' => 'number',
                    'default' => 100
                ),
                'cursor' => array(
                    'type' => 'string',
                    'default' => '|'
                ),
                'color' => array(
                    'type' => 'string',
                    'default' => '#000000'
                ),
                'fontSize' => array(
                    'type' => 'string',
                    'default' => '16px'
                ),
                'fontFamily' => array(
                    'type' => 'string',
                    'default' => 'inherit'
                ),
                'glowColor' => array(
                    'type' => 'string',
                    'default' => '#00ffff'
                ),
                'intensity' => array(
                    'type' => 'number',
                    'default' => 20
                ),
                'flicker' => array(
                    'type' => 'boolean',
                    'default' => false
                ),
                'wobble' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'className' => array(
                    'type' => 'string',
                    'default' => ''
                )
            )
        ));
        
        // Register simplified typewriter block
        register_block_type('shogun-slogans/typewriter-block', array(
            'editor_script' => 'shogun-slogans-blocks',
            'editor_style' => 'shogun-slogans-block-editor',
            'render_callback' => array($this, 'render_typewriter_block'),
            'attributes' => array(
                'text' => array(
                    'type' => 'string',
                    'default' => 'Type your message here...'
                ),
                'speed' => array(
                    'type' => 'number',
                    'default' => 100
                ),
                'cursor' => array(
                    'type' => 'string',
                    'default' => '|'
                ),
                'className' => array(
                    'type' => 'string',
                    'default' => ''
                )
            )
        ));
    }
    
    /**
     * Enqueue block editor assets
     */
    public function enqueue_block_editor_assets() {
        // Enqueue block JavaScript
        wp_enqueue_script(
            'shogun-slogans-blocks',
            SHOGUN_SLOGANS_PLUGIN_URL . 'assets/blocks/animation-blocks.js',
            array('wp-blocks', 'wp-block-editor', 'wp-components', 'wp-element', 'wp-i18n'),
            SHOGUN_SLOGANS_VERSION,
            true
        );
        
        // Enqueue block editor styles
        wp_enqueue_style(
            'shogun-slogans-block-editor',
            SHOGUN_SLOGANS_PLUGIN_URL . 'assets/css/block-editor.css',
            array(),
            SHOGUN_SLOGANS_VERSION
        );
        
        // Localize script with config
        wp_localize_script('shogun-slogans-blocks', 'shogunSlogansConfig', array(
            'restUrl' => rest_url('shogun-slogans/v1/'),
            'restNonce' => wp_create_nonce('wp_rest'),
            'pluginUrl' => SHOGUN_SLOGANS_PLUGIN_URL,
            'version' => SHOGUN_SLOGANS_VERSION,
            'strings' => array(
                'blockCategory' => __('Shogun Slogans', 'shogun-slogans'),
                'animationBlock' => __('Animation Block', 'shogun-slogans'),
                'typewriterBlock' => __('Typewriter Block', 'shogun-slogans'),
                'preview' => __('Preview', 'shogun-slogans'),
                'loading' => __('Loading...', 'shogun-slogans'),
                'error' => __('Error loading preview', 'shogun-slogans'),
            )
        ));
    }
    
    /**
     * Enqueue block assets (frontend and editor)
     */
    public function enqueue_block_assets() {
        // This runs on both frontend and editor
        // Frontend styles are handled by the main plugin
    }
    
    /**
     * Add custom block category
     * 
     * @param array $categories Existing categories
     * @param WP_Post $post Current post
     * @return array Modified categories
     */
    public function add_block_category($categories, $post) {
        return array_merge(
            $categories,
            array(
                array(
                    'slug' => 'shogun-slogans',
                    'title' => __('Shogun Slogans', 'shogun-slogans'),
                    'icon' => 'format-quote',
                ),
            )
        );
    }
    
    /**
     * Render animation block on frontend
     * 
     * @param array $attributes Block attributes
     * @return string Rendered HTML
     */
    public function render_animation_block($attributes) {
        // Extract attributes with defaults
        $text = $attributes['text'] ?? 'Your animated text here...';
        $animation_type = $attributes['animationType'] ?? 'typewriter';
        $speed = $attributes['speed'] ?? 100;
        $cursor = $attributes['cursor'] ?? '|';
        $color = $attributes['color'] ?? '#000000';
        $font_size = $attributes['fontSize'] ?? '16px';
        $font_family = $attributes['fontFamily'] ?? 'inherit';
        $glow_color = $attributes['glowColor'] ?? '#00ffff';
        $intensity = $attributes['intensity'] ?? 20;
        $flicker = $attributes['flicker'] ?? false;
        $wobble = $attributes['wobble'] ?? true;
        $class_name = $attributes['className'] ?? '';
        
        // Build shortcode attributes
        $shortcode_attrs = array(
            'type' => $animation_type,
            'text' => $text
        );
        
        // Add non-default attributes
        if ($speed !== 100) $shortcode_attrs['speed'] = $speed;
        if ($cursor !== '|') $shortcode_attrs['cursor'] = $cursor;
        if ($color !== '#000000') $shortcode_attrs['color'] = $color;
        if ($font_size !== '16px') $shortcode_attrs['font_size'] = $font_size;
        if ($font_family !== 'inherit') $shortcode_attrs['font_family'] = $font_family;
        if ($class_name) $shortcode_attrs['class'] = $class_name;
        
        // Animation-specific attributes
        if ($animation_type === 'neon') {
            if ($glow_color !== '#00ffff') $shortcode_attrs['glow_color'] = $glow_color;
            if ($intensity !== 20) $shortcode_attrs['intensity'] = $intensity;
            if ($flicker) $shortcode_attrs['flicker'] = 'true';
        }
        
        if ($animation_type === 'handwritten' && !$wobble) {
            $shortcode_attrs['wobble'] = 'false';
        }
        
        // Use the enhanced shortcode handler if available
        if (class_exists('ShogunSlogansEnhancedShortcode')) {
            $shortcode_handler = new ShogunSlogansEnhancedShortcode();
            return $shortcode_handler->animation_shortcode($shortcode_attrs);
        }
        
        // Fallback to basic shortcode
        $shortcode_string = '';
        foreach ($shortcode_attrs as $key => $value) {
            $shortcode_string .= " {$key}=\"{$value}\"";
        }
        
        return do_shortcode("[shogun_animation{$shortcode_string}]");
    }
    
    /**
     * Render typewriter block on frontend
     * 
     * @param array $attributes Block attributes
     * @return string Rendered HTML
     */
    public function render_typewriter_block($attributes) {
        // Convert to animation block attributes
        $animation_attributes = array(
            'text' => $attributes['text'] ?? 'Type your message here...',
            'animationType' => 'typewriter',
            'speed' => $attributes['speed'] ?? 100,
            'cursor' => $attributes['cursor'] ?? '|',
            'className' => $attributes['className'] ?? ''
        );
        
        return $this->render_animation_block($animation_attributes);
    }
    
    /**
     * Get available animations for blocks
     * 
     * @return array Available animations
     */
    public function get_available_animations() {
        if (class_exists('ShogunAnimationRegistry')) {
            $animations = ShogunAnimationRegistry::get_all_animations();
            
            $formatted = array();
            foreach ($animations as $name => $config) {
                $formatted[] = array(
                    'label' => $config['description'] ?: ucfirst($name),
                    'value' => $name,
                    'category' => $config['category'],
                    'parameters' => $config['parameters']
                );
            }
            
            return $formatted;
        }
        
        // Fallback animations
        return array(
            array(
                'label' => 'Typewriter Effect',
                'value' => 'typewriter',
                'category' => 'text'
            ),
            array(
                'label' => 'Handwritten Style',
                'value' => 'handwritten',
                'category' => 'text'
            ),
            array(
                'label' => 'Neon Glow',
                'value' => 'neon',
                'category' => 'visual'
            )
        );
    }
}

/**
 * Initialize blocks
 */
add_action('init', function() {
    new ShogunSlogansBlocks();
});

/**
 * REST endpoint for getting available animations for blocks
 */
add_action('rest_api_init', function() {
    register_rest_route('shogun-slogans/v1', '/block-animations', array(
        'methods' => 'GET',
        'callback' => function() {
            $blocks = new ShogunSlogansBlocks();
            return rest_ensure_response($blocks->get_available_animations());
        },
        'permission_callback' => '__return_true'
    ));
});
