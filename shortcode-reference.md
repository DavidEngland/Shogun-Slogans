# Shogun Slogans - Shortcode Quick Reference

## Basic Shortcodes

### Typewriter Effect
```
[typewriter_text text="Your text here"]
```

### Slogan Display  
```
[shogun_slogan text="Your slogan here"]
```

## Common Examples

### Fast Typing
```
[typewriter_text text="Fast typing!" speed="50"]
```

### Slow Typing
```
[typewriter_text text="Slow typing..." speed="200"]
```

### Custom Cursor
```
[typewriter_text text="Custom cursor" cursor="▌"]
```

### No Loop
```
[typewriter_text text="Type once only" loop="false"]
```

### With Delay
```
[typewriter_text text="Delayed start" delay="2000"]
```

## All Parameters

### typewriter_text
- `text` - The text to type (required)
- `speed` - Typing speed in ms (default: 100)
- `cursor` - Cursor character (default: |)
- `loop` - Loop animation true/false (default: true)
- `delay` - Start delay in ms (default: 0)
- `class` - CSS class
- `id` - HTML ID

### shogun_slogan  
- `text` - The slogan text (required)
- `style` - Animation: fade, slide, typewriter (default: fade)
- `class` - CSS class
- `id` - HTML ID