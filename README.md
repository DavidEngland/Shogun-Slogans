# 🗾 Shogun Slogans

A modern WordPress plugin for creating stunning animated slogans with typewriter effects, handwritten styles, and dynamic text display. Perfect for hero sections, quotes, and call-to-action messages.

![WordPress](https://img.shields.io/badge/WordPress-5.0+-blue.svg)
![PHP](https://img.shields.io/badge/PHP-7.4+-purple.svg)
![Version](https://img.shields.io/badge/Version-3.1.0-green.svg)
![License](https://img.shields.io/badge/License-GPL%20v3-blue.svg)

## ✨ Features

- **🎬 Multiple Animation Styles**: Typewriter, handwritten, neon, gradient, rainbow, and more
- **⚡ Performance Optimized**: Intersection Observer, lazy loading, memory management
- **♿ Accessibility First**: Respects reduced motion preferences, screen reader friendly
- **📱 Responsive Design**: Works perfectly on all devices
- **🎨 Customizable**: Speed, cursors, colors, fonts, and timing
- **🔧 WordPress Native**: Shortcodes, REST API, admin interface
- **🌍 Internationalization Ready**: Translation-ready code

## 🚀 Quick Start

### Installation

1. Download the plugin files
2. Upload to your WordPress `/wp-content/plugins/` directory
3. Activate the plugin through the WordPress admin
4. Start using shortcodes in your posts and pages!

### Basic Usage

```html
<!-- Simple typewriter effect -->
[typewriter_text text="Hello World!" speed="100" cursor="|"]

<!-- Advanced slogan with styling -->
[shogun_slogan text="Your Amazing Slogan" style="gradient" animation="fade"]

<!-- Animated text effects -->
[animated_text text="Fade in smoothly" animation="fade" speed="1000"]
```

## 📖 Documentation

### Shortcodes Reference

#### `[typewriter_text]`
Creates a typewriter animation effect.

**Parameters:**
- `text` (required) - The text to display
- `speed` (default: 100) - Typing speed in milliseconds
- `cursor` (default: "|") - Cursor character
- `loop` (default: false) - Loop the animation
- `delay` (default: 0) - Delay before starting
- `class` - Additional CSS classes
- `id` - Element ID

**Examples:**

```html
[typewriter_text text="I will help you make The Smart Move - I guarantee it!" speed="80" cursor="|"]
[typewriter_text text="Fast typing!" speed="30" cursor="_"]
[typewriter_text text="Custom emoji cursor" speed="100" cursor="🔥"]
```

#### `[shogun_slogan]`
Creates animated slogans with various styles.

**Parameters:**
- `text` (required) - The slogan text
- `style` (default: typewriter) - Animation style
- `animation` (default: fade) - Entry animation
- `speed` (default: 1000) - Animation speed
- `class` - Additional CSS classes
- `id` - Element ID

**Available Styles:**
- `typewriter` - Classic typewriter effect
- `handwritten` - Handwritten script style
- `gradient` - Animated gradient colors
- `neon` - Glowing neon effect
- `rainbow` - Rainbow color shifting
- `fire` - Fire-like flickering
- `matrix` - Matrix-style effect
- `terminal` - Terminal/console style

**Examples:**

```html
[shogun_slogan text="Premium Services" style="gradient" animation="fade"]
[shogun_slogan text="Handwritten Note" style="handwritten" animation="slide"]
[shogun_slogan text="Future Tech" style="neon" animation="bounce"]
```

#### `[animated_text]`
Simple text animations without typewriter effect.

**Parameters:**
- `text` (required) - The text to animate
- `animation` (default: fade) - Animation type
- `speed` (default: 1000) - Animation duration
- `delay` (default: 0) - Delay before animation
- `loop` (default: false) - Loop the animation

**Examples:**

```html
[animated_text text="Smooth fade in" animation="fade" speed="1500"]
[animated_text text="Slide from bottom" animation="slide" speed="800"]
[animated_text text="Bouncy entrance" animation="bounce" speed="1200"]
```

### Custom Cursor Characters

Get creative with cursor characters:
- Standard: `|`, `_`, `▌`
- Symbols: `★`, `♦`, `●`, `♠`, `♣`, `♥`
- Arrows: `→`, `↗`, `↘`, `⟩`, `»`
- Emojis: `🔥`, `⚡`, `✨`, `💫`, `🌟`, `💎`
- Writing: `✍️`, `🖋️`, `🖊️`, `✏️`

## 🎨 Styling Examples

### REIA Real Estate Examples

Perfect for Real Estate Intelligence Agency (https://RealEstate-Huntsville.Com):

```html
<!-- Main Hero Slogan -->
[typewriter_text text="I will help you make The Smart Move - I guarantee it!" speed="80" cursor="|" loop="false"]

<!-- Attribution -->
[typewriter_text text="- Mikko Jetsu, REIA" speed="100" cursor="★" delay="3000"]

<!-- Call to Action -->
[shogun_slogan text="Join REIA today!" style="handwritten" animation="bounce"]
```

### Business & Marketing

```html
<!-- Professional Header -->
[shogun_slogan text="Innovation Meets Excellence" style="gradient" animation="fade"]

<!-- Product Launch -->
[typewriter_text text="Introducing the future of..." speed="60" cursor="⚡"]

<!-- Testimonial -->
[shogun_slogan text="Best service we've ever used!" style="handwritten" animation="slide"]
```

### Creative & Artistic

```html
<!-- Neon Style for Modern Sites -->
[shogun_slogan text="Digital Revolution" style="neon" animation="fade"]

<!-- Rainbow for Fun Brands -->
[shogun_slogan text="Colorful Creativity" style="rainbow" animation="bounce"]

<!-- Fire Effect for Energy -->
[shogun_slogan text="Blazing Fast Results" style="fire" animation="slide"]
```

## ⚙️ Configuration

### Plugin Settings

Access settings via **WordPress Admin → Shogun Slogans → Settings**

- **Default Speed**: Set default typing speed (milliseconds)
- **Default Cursor**: Set default cursor character
- **Default Loop**: Enable/disable looping by default
- **Accessibility**: Respect reduced motion preferences
- **Performance**: Enable Intersection Observer optimization
- **Debug Mode**: Enable for development/troubleshooting

### CSS Customization

Add custom styles via the plugin settings or your theme's CSS:

```css
/* Custom typewriter styling */
.shogun-typewriter {
    font-family: 'Your Custom Font', monospace;
    color: #your-color;
    font-size: 1.5rem;
}

/* Custom cursor styling */
.typewriter-cursor {
    color: #ff6b35;
    animation: customBlink 1s infinite;
}

@keyframes customBlink {
    0%, 50% { opacity: 1; }
    51%, 100% { opacity: 0; }
}
```

## 🔧 Technical Details

### Browser Support
- ✅ Chrome 60+
- ✅ Firefox 55+
- ✅ Safari 12+
- ✅ Edge 79+
- ✅ Mobile browsers

### Performance Features
- **Intersection Observer**: Animations start only when visible
- **Lazy Loading**: Assets load only when needed
- **Memory Management**: Automatic cleanup prevents leaks
- **Hardware Acceleration**: Smooth CSS animations
- **Reduced Motion**: Respects accessibility preferences

### WordPress Compatibility
- **WordPress**: 5.0+ (tested up to 6.7)
- **PHP**: 7.4+ (tested up to 8.3)
- **Multisite**: Fully supported
- **Block Editor**: Native integration
- **Classic Editor**: Full compatibility

## 🛠️ Development

### Local Development Setup

```bash
# Clone the repository
git clone https://github.com/DavidEngland/Shogun-Slogans.git

# Navigate to WordPress plugins directory
cd /path/to/wordpress/wp-content/plugins/

# Symlink or copy the plugin
ln -s /path/to/Shogun-Slogans shogun-slogans

# Activate in WordPress admin
```

### File Structure

```
shogun-slogans/
├── assets/
│   ├── css/
│   │   ├── slogans-final.css      # Main frontend styles
│   │   └── admin.css              # Admin interface styles
│   ├── js/
│   │   ├── slogans-final.js       # Main frontend script
│   │   └── admin.js               # Admin interface script
│   └── images/
│       └── shogun-slogans-logo.svg
├── languages/                     # Translation files
├── tests/                         # Test files
│   ├── comprehensive-test.html    # Complete test suite
│   └── wordpress-shortcodes-ready.html
├── shogun-slogans-final.php      # Main plugin file
├── README.md                      # This file
├── .gitignore                     # Git ignore rules
└── LICENSE                        # GPL v3 license
```

### API Reference

#### JavaScript API

```javascript
// Access the global API
window.ShogunSlogans

// Initialize all animations
ShogunSlogans.init();

// Control all animations
ShogunSlogans.pauseAll();
ShogunSlogans.resumeAll();
ShogunSlogans.stopAll();

// Event listeners
ShogunSlogans.on('typewriter:complete', function(event) {
    console.log('Animation completed:', event.detail);
});

// Create custom animations
const animation = ShogunSlogans.createAnimation(element);
```

#### PHP Hooks

```php
// Modify default options
add_filter('shogun_slogans_default_options', function($options) {
    $options['default_speed'] = 80;
    return $options;
});

// Force asset loading
add_filter('shogun_slogans_should_load_assets', '__return_true');

// After plugin initialization
add_action('shogun_slogans_init', function($plugin_instance) {
    // Your code here
});
```

## 🤝 Contributing

We welcome contributions! Please follow these steps:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Coding Standards
- Follow WordPress coding standards
- Use meaningful variable and function names
- Add PHPDoc comments for all functions
- Test across multiple WordPress versions
- Ensure accessibility compliance

## 📝 Changelog

### Version 3.1.0 (2025-07-06)
- ✨ Complete rewrite with modern architecture
- ⚡ Performance optimization with Intersection Observer
- ♿ Enhanced accessibility features
- 🎨 New animation styles (neon, rainbow, fire, matrix)
- 🔧 Improved WordPress integration
- 📱 Better responsive design
- 🐛 Multiple bug fixes and improvements

### Version 3.0.0 (2025-01-01)
- 🎉 Initial public release
- 🎬 Basic typewriter and slogan animations
- 🔧 WordPress shortcode support
- 📊 Admin interface

## 📄 License

This project is licensed under the GPL v3 License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- Inspired by classic typewriter effects
- Built with modern web standards
- Tested by the WordPress community
- Special thanks to REIA (Real Estate Intelligence Agency - https://RealEstate-Huntsville.Com) for the use case inspiration

## 📞 Support

- 📧 **Email**: support@yourwebsite.com
- 🐛 **Issues**: [GitHub Issues](https://github.com/DavidEngland/Shogun-Slogans/issues)
- 📖 **Documentation**: [Plugin Wiki](https://github.com/DavidEngland/Shogun-Slogans/wiki)
- 💬 **WordPress Forums**: [Support Forum](https://wordpress.org/support/plugin/shogun-slogans/)

---

**Made with ❤️ for the WordPress community**

> *"The right words at the right time can change everything."*
- Mobile-responsive design
- Added admin dashboard
- Improved shortcode system
- Added PHP helper functions

---

**Made with ❤️ for WordPress**