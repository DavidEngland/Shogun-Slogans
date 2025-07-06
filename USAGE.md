# Shogun Slogans Plugin - Usage Guide

## Overview
Shogun Slogans is a WordPress plugin that creates beautiful typewriter effects and animated text displays. Perfect for creating eye-catching slogans, quotes, and dynamic text content.

## Shortcodes

### 1. Basic Slogan Display
```
[shogun_slogan text="Your amazing slogan here" style="fade"]
```

**Parameters:**
- `text` - The text to display (required)
- `style` - Animation style: `fade`, `slide`, or `typewriter` (default: `fade`)
- `class` - Additional CSS class
- `id` - HTML ID attribute

### 2. Typewriter Effect
```
[typewriter_text text="This text types out character by character..." speed="100"]
```

**Parameters:**
- `text` - The text to type out (required)
- `speed` - Typing speed in milliseconds (default: `100`)
- `cursor` - Cursor character (default: `|`)
- `loop` - Loop the animation: `true` or `false` (default: `true`)
- `delay` - Delay before starting in milliseconds (default: `0`)
- `class` - Additional CSS class
- `id` - HTML ID attribute

## Examples

### Fast Typewriter
```
[typewriter_text text="I type really fast!" speed="30" cursor="▌"]
```

### Slow Dramatic Effect
```
[typewriter_text text="Slow... and... dramatic..." speed="200" cursor="_"]
```

### No Loop (Type Once)
```
[typewriter_text text="This only types once" loop="false"]
```

### With Delay
```
[typewriter_text text="I appear after 2 seconds" delay="2000"]
```

### Custom Cursor
```
[typewriter_text text="Custom cursor example" cursor="█"]
```

## PHP Functions

You can also use these helper functions in your theme files:

### Display Typewriter Text
```php
echo shogun_typewriter('Your text here', 100, '|');
```

### Display Slogan
```php
echo shogun_slogan('Your slogan', 'fade');
```

## CSS Classes

### Typewriter Variations
Add these classes to customize appearance:

- `.fast` - Faster typing speed
- `.slow` - Slower typing speed  
- `.dramatic` - Very slow with letter spacing

Example:
```
[typewriter_text text="Fast typing" class="fast"]
```

### Custom Styling
You can add custom CSS to style your slogans:

```css
.my-custom-slogan {
    font-size: 24px;
    color: #007cba;
    text-align: center;
}
```

Then use it:
```
[typewriter_text text="Custom styled text" class="my-custom-slogan"]
```

## Accessibility

The plugin includes accessibility features:
- ARIA live regions for screen readers
- Respects `prefers-reduced-motion` setting
- Proper semantic markup
- Keyboard accessible

## Performance

- Uses Intersection Observer for better performance
- Only animates when elements are visible
- Pauses animations when page is not visible
- Optimized for mobile devices

## Browser Support

- Modern browsers (Chrome, Firefox, Safari, Edge)
- IE11+ (with limited features)
- Mobile browsers (iOS Safari, Chrome Mobile)

## Troubleshooting

### Typewriter not working?
1. Check that JavaScript is enabled
2. Ensure no JavaScript errors in console
3. Verify the shortcode syntax is correct

### Animation too fast/slow?
- Adjust the `speed` parameter
- Use CSS classes like `.fast` or `.slow`
- Custom speed: any value from 10 (very fast) to 500 (very slow)

### Text not showing?
- Check that the `text` parameter is properly quoted
- Escape special characters if needed
- Ensure the element is visible on page

## Advanced Usage

### Multiple Typewriters
```html
<div class="typewriter-container">
    [typewriter_text text="First line..." speed="80"]
    <br>
    [typewriter_text text="Second line appears after..." delay="3000"]
</div>
```

### Combining with Other Elements
```html
<div class="hero-section">
    <h1>[shogun_slogan text="Welcome to Our Site" style="fade"]</h1>
    <p>[typewriter_text text="We create amazing experiences..." speed="100"]</p>
</div>
```

## Support

For support and feature requests, please contact the plugin developer or check the plugin documentation in your WordPress admin area.