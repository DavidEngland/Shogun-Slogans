# Shogun Slogans: Dynamic Animation API Design

## 🎯 Core Concept
Build animations on-demand using PHP, minimize JS, leverage WordPress capabilities.

## 🏗️ Architecture Overview

### 1. Animation Registry System
```php
class ShogunAnimationRegistry {
    private static $animations = [];
    
    public static function register_animation($name, $config) {
        self::$animations[$name] = $config;
    }
    
    public static function get_animation($name) {
        return self::$animations[$name] ?? null;
    }
}
```

### 2. CSS Generator Engine
```php
class ShogunCSSGenerator {
    public function generate_animation_css($animation_name, $params = []) {
        $animation = ShogunAnimationRegistry::get_animation($animation_name);
        if (!$animation) return '';
        
        return $this->compile_css_template($animation['template'], $params);
    }
}
```

### 3. REST API Endpoints

#### `/wp-json/shogun-slogans/v1/animations`
- GET: List available animations
- POST: Generate custom animation CSS

#### `/wp-json/shogun-slogans/v1/css/{animation_name}`
- GET: Get compiled CSS for specific animation
- Query params: speed, color, size, etc.

## 🎨 Animation Definition System

### Animation Config Structure
```php
$typewriter_config = [
    'name' => 'typewriter',
    'category' => 'text',
    'parameters' => [
        'speed' => ['type' => 'int', 'default' => 100, 'min' => 10, 'max' => 1000],
        'cursor' => ['type' => 'string', 'default' => '|', 'sanitize' => 'text'],
        'color' => ['type' => 'color', 'default' => '#000000'],
        'font_size' => ['type' => 'size', 'default' => '16px'],
    ],
    'css_template' => '
        .shogun-typewriter-{{id}} {
            --typing-speed: {{speed}}ms;
            --cursor-char: "{{cursor}}";
            --text-color: {{color}};
            font-size: {{font_size}};
        }
        .shogun-typewriter-{{id}} .typewriter-cursor {
            animation: blink {{cursor_blink_speed}}ms infinite;
        }
        @keyframes shogun-type-{{id}} {
            from { width: 0; }
            to { width: 100%; }
        }
    ',
    'js_minimal' => 'ShogunAPI.initTypewriter',
    'dependencies' => []
];
```

## 🔧 Implementation Strategy

### Phase 1: Core Animation Engine
1. **Animation Registry** - Store animation definitions
2. **CSS Compiler** - Template-based CSS generation
3. **Parameter Sanitization** - Security & validation
4. **Caching Layer** - Store generated CSS

### Phase 2: REST API Layer
1. **Animation Endpoints** - CRUD for animations
2. **CSS Generation API** - On-demand CSS compilation
3. **Preview System** - Live animation preview
4. **Performance Optimization** - Caching, minification

### Phase 3: WordPress Integration
1. **Shortcode Enhancement** - Dynamic parameter support
2. **Block Editor Integration** - Visual animation builder
3. **Theme Integration** - Hook system for themes
4. **Admin Interface** - Animation management

## 🚀 Usage Examples

### Shortcode with Dynamic CSS
```php
// User inputs:
[shogun_animation type="typewriter" speed="80" color="#ff6b6b" cursor="▌"]

// PHP generates unique CSS:
.shogun-anim-abc123 {
    --typing-speed: 80ms;
    --cursor-char: "▌";
    --text-color: #ff6b6b;
}

// Minimal JS initialization:
ShogunAPI.init('abc123', 'typewriter');
```

### REST API Usage
```javascript
// Get available animations
fetch('/wp-json/shogun-slogans/v1/animations')

// Generate custom CSS
fetch('/wp-json/shogun-slogans/v1/css/typewriter?speed=120&color=blue')
```

## 🎯 Benefits

### PHP-Heavy Approach
- ✅ Server-side rendering (better SEO)
- ✅ Reduced client-side processing
- ✅ Better caching strategies
- ✅ Security (server-side validation)
- ✅ WordPress ecosystem integration

### On-Demand CSS
- ✅ Smaller initial payload
- ✅ Only load what's needed
- ✅ Dynamic customization
- ✅ Better performance

### Minimal JavaScript
- ✅ Faster page loads
- ✅ Better accessibility
- ✅ Reduced complexity
- ✅ Progressive enhancement

## 🔧 Technical Implementation

### 1. Animation Factory Pattern
```php
abstract class ShogunAnimation {
    abstract public function generate_css($params);
    abstract public function get_js_init();
    abstract public function validate_params($params);
}

class TypewriterAnimation extends ShogunAnimation {
    public function generate_css($params) {
        // Template compilation logic
    }
}
```

### 2. CSS Template Engine
```php
class ShogunTemplateEngine {
    public function compile($template, $variables) {
        return preg_replace_callback('/\{\{(\w+)\}\}/', function($matches) use ($variables) {
            return $variables[$matches[1]] ?? '';
        }, $template);
    }
}
```

### 3. Caching Strategy
```php
class ShogunCSSCache {
    public function get_cached_css($cache_key) {
        return get_transient("shogun_css_{$cache_key}");
    }
    
    public function cache_css($cache_key, $css, $expiry = 3600) {
        set_transient("shogun_css_{$cache_key}", $css, $expiry);
    }
}
```

## 🎨 Animation Types to Support

### Text Animations
- Typewriter (multiple variants)
- Fade in/out
- Slide effects
- Bounce typing
- Handwritten simulation

### Visual Effects
- Neon glow
- Rainbow text
- Shadow effects
- 3D transformations
- Particle effects (CSS-only)

### Interactive Animations
- Hover effects
- Click animations
- Scroll-triggered
- Time-based changes

## 🔧 API Endpoints Design

### GET `/wp-json/shogun-slogans/v1/animations`
```json
{
  "animations": [
    {
      "name": "typewriter",
      "category": "text",
      "description": "Classic typewriter effect",
      "parameters": {
        "speed": {"type": "int", "min": 10, "max": 1000, "default": 100},
        "cursor": {"type": "string", "default": "|"}
      }
    }
  ]
}
```

### POST `/wp-json/shogun-slogans/v1/generate-css`
```json
{
  "animation": "typewriter",
  "parameters": {
    "speed": 80,
    "cursor": "▌",
    "color": "#ff6b6b"
  },
  "selector": ".custom-selector"
}
```

Response:
```json
{
  "css": ".custom-selector { /* generated CSS */ }",
  "cache_key": "typewriter_abc123",
  "js_init": "ShogunAPI.initTypewriter"
}
```

## 🚀 Progressive Enhancement Strategy

### 1. Base Functionality (No JS)
- Static text with CSS animations
- Basic transitions and effects
- Fallback styling

### 2. Enhanced Experience (Minimal JS)
- Timing control
- Interactive features
- State management

### 3. Advanced Features (Full JS)
- Complex sequences
- User interactions
- Real-time customization

## 🔧 WordPress Hooks Integration

```php
// Allow themes/plugins to register animations
add_action('shogun_slogans_register_animations', function($registry) {
    $registry->register('custom_fade', $custom_fade_config);
});

// Filter generated CSS
add_filter('shogun_slogans_generated_css', function($css, $animation, $params) {
    // Modify CSS before output
    return $css;
}, 10, 3);

// Customize JS initialization
add_filter('shogun_slogans_js_init', function($js_config, $animation) {
    // Modify JS behavior
    return $js_config;
}, 10, 2);
```

## 📊 Performance Considerations

### Caching Strategy
1. **Transient Cache** - Generated CSS (1 hour expiry)
2. **Object Cache** - Animation configs (persistent)
3. **Browser Cache** - Static assets (long expiry)
4. **CDN Integration** - CSS delivery optimization

### Optimization Techniques
1. **CSS Minification** - Remove whitespace, optimize
2. **Critical CSS** - Inline above-fold animations
3. **Lazy Loading** - Load animations on demand
4. **Resource Hints** - Preload, prefetch strategies

## 🎯 Next Steps

1. **Prototype Core Engine** - Basic animation registry + CSS generation
2. **REST API Implementation** - Core endpoints
3. **Shortcode Integration** - Enhanced shortcode with dynamic CSS
4. **Performance Testing** - Benchmark against static CSS approach
5. **Documentation** - API docs and examples

This approach gives us maximum flexibility while leveraging WordPress's strengths!
