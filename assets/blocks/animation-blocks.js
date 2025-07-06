/**
 * Shogun Slogans Gutenberg Block
 * 
 * Dynamic animation block using the Shogun Slogans API
 * 
 * @package ShogunSlogans
 * @since 3.2.0
 */

const { registerBlockType } = wp.blocks;
const { InspectorControls, useBlockProps } = wp.blockEditor;
const { PanelBody, TextControl, SelectControl, RangeControl, ColorPicker, ToggleControl } = wp.components;
const { useState, useEffect } = wp.element;
const { __ } = wp.i18n;

// Register the main animation block
registerBlockType('shogun-slogans/animation-block', {
    title: __('Shogun Animation', 'shogun-slogans'),
    description: __('Add animated text effects with customizable parameters', 'shogun-slogans'),
    icon: 'format-quote',
    category: 'text',
    keywords: [
        __('animation', 'shogun-slogans'),
        __('typewriter', 'shogun-slogans'),
        __('text effect', 'shogun-slogans'),
        __('slogan', 'shogun-slogans')
    ],
    
    attributes: {
        text: {
            type: 'string',
            default: 'Your animated text here...'
        },
        animationType: {
            type: 'string',
            default: 'typewriter'
        },
        speed: {
            type: 'number',
            default: 100
        },
        cursor: {
            type: 'string',
            default: '|'
        },
        color: {
            type: 'string',
            default: '#000000'
        },
        fontSize: {
            type: 'string',
            default: '16px'
        },
        fontFamily: {
            type: 'string',
            default: 'inherit'
        },
        glowColor: {
            type: 'string',
            default: '#00ffff'
        },
        intensity: {
            type: 'number',
            default: 20
        },
        flicker: {
            type: 'boolean',
            default: false
        },
        wobble: {
            type: 'boolean',
            default: true
        },
        className: {
            type: 'string',
            default: ''
        }
    },
    
    edit: function(props) {
        const { attributes, setAttributes } = props;
        const {
            text,
            animationType,
            speed,
            cursor,
            color,
            fontSize,
            fontFamily,
            glowColor,
            intensity,
            flicker,
            wobble,
            className
        } = attributes;
        
        const [availableAnimations, setAvailableAnimations] = useState([]);
        const [previewCSS, setPreviewCSS] = useState('');
        const [previewHTML, setPreviewHTML] = useState('');
        const [isLoading, setIsLoading] = useState(false);
        
        // Fetch available animations on component mount
        useEffect(() => {
            if (window.shogunSlogansConfig && window.shogunSlogansConfig.restUrl) {
                fetch(window.shogunSlogansConfig.restUrl + 'animations')
                    .then(response => response.json())
                    .then(data => {
                        if (data.animations) {
                            const animationOptions = data.animations.map(animation => ({
                                label: animation.description || animation.name,
                                value: animation.name
                            }));
                            setAvailableAnimations(animationOptions);
                        }
                    })
                    .catch(error => {
                        console.error('Failed to fetch animations:', error);
                        // Fallback to default animations
                        setAvailableAnimations([
                            { label: 'Typewriter Effect', value: 'typewriter' },
                            { label: 'Handwritten Style', value: 'handwritten' },
                            { label: 'Neon Glow', value: 'neon' }
                        ]);
                    });
            }
        }, []);
        
        // Generate preview when attributes change
        useEffect(() => {
            if (text && animationType && window.shogunSlogansConfig) {
                generatePreview();
            }
        }, [text, animationType, speed, cursor, color, fontSize, fontFamily, glowColor, intensity, flicker, wobble]);
        
        const generatePreview = () => {
            setIsLoading(true);
            
            const parameters = buildParameters();
            
            const requestData = {
                animation: animationType,
                text: text,
                parameters: parameters
            };
            
            fetch(window.shogunSlogansConfig.restUrl + 'preview', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': window.shogunSlogansConfig.restNonce
                },
                body: JSON.stringify(requestData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.css && data.html) {
                    setPreviewCSS(data.css);
                    setPreviewHTML(data.html);
                }
                setIsLoading(false);
            })
            .catch(error => {
                console.error('Preview generation failed:', error);
                setIsLoading(false);
            });
        };
        
        const buildParameters = () => {
            const parameters = {};
            
            // Common parameters
            if (speed !== 100) parameters.speed = speed;
            if (cursor !== '|') parameters.cursor = cursor;
            if (color !== '#000000') parameters.color = color;
            if (fontSize !== '16px') parameters.font_size = fontSize;
            if (fontFamily !== 'inherit') parameters.font_family = fontFamily;
            
            // Animation-specific parameters
            switch (animationType) {
                case 'neon':
                    if (glowColor !== '#00ffff') parameters.glow_color = glowColor;
                    if (intensity !== 20) parameters.intensity = intensity;
                    if (flicker) parameters.flicker = true;
                    break;
                case 'handwritten':
                    if (!wobble) parameters.wobble = false;
                    break;
            }
            
            return parameters;
        };
        
        const blockProps = useBlockProps({
            className: `shogun-animation-block ${className}`
        });
        
        return (
            <div {...blockProps}>
                <InspectorControls>
                    <PanelBody title={__('Animation Settings', 'shogun-slogans')} initialOpen={true}>
                        <TextControl
                            label={__('Text to Animate', 'shogun-slogans')}
                            value={text}
                            onChange={(value) => setAttributes({ text: value })}
                            help={__('Enter the text you want to animate', 'shogun-slogans')}
                        />
                        
                        <SelectControl
                            label={__('Animation Type', 'shogun-slogans')}
                            value={animationType}
                            options={availableAnimations}
                            onChange={(value) => setAttributes({ animationType: value })}
                        />
                        
                        {animationType === 'typewriter' && (
                            <>
                                <RangeControl
                                    label={__('Typing Speed (ms)', 'shogun-slogans')}
                                    value={speed}
                                    onChange={(value) => setAttributes({ speed: value })}
                                    min={10}
                                    max={1000}
                                    step={10}
                                />
                                
                                <TextControl
                                    label={__('Cursor Character', 'shogun-slogans')}
                                    value={cursor}
                                    onChange={(value) => setAttributes({ cursor: value })}
                                    maxLength={2}
                                />
                            </>
                        )}
                        
                        {animationType === 'neon' && (
                            <>
                                <div style={{ marginBottom: '16px' }}>
                                    <label>{__('Glow Color', 'shogun-slogans')}</label>
                                    <ColorPicker
                                        color={glowColor}
                                        onChange={(value) => setAttributes({ glowColor: value })}
                                    />
                                </div>
                                
                                <RangeControl
                                    label={__('Glow Intensity', 'shogun-slogans')}
                                    value={intensity}
                                    onChange={(value) => setAttributes({ intensity: value })}
                                    min={5}
                                    max={50}
                                    step={1}
                                />
                                
                                <ToggleControl
                                    label={__('Flicker Effect', 'shogun-slogans')}
                                    checked={flicker}
                                    onChange={(value) => setAttributes({ flicker: value })}
                                />
                            </>
                        )}
                        
                        {animationType === 'handwritten' && (
                            <ToggleControl
                                label={__('Natural Wobble', 'shogun-slogans')}
                                checked={wobble}
                                onChange={(value) => setAttributes({ wobble: value })}
                                help={__('Add natural handwriting variations', 'shogun-slogans')}
                            />
                        )}
                    </PanelBody>
                    
                    <PanelBody title={__('Text Styling', 'shogun-slogans')} initialOpen={false}>
                        <div style={{ marginBottom: '16px' }}>
                            <label>{__('Text Color', 'shogun-slogans')}</label>
                            <ColorPicker
                                color={color}
                                onChange={(value) => setAttributes({ color: value })}
                            />
                        </div>
                        
                        <TextControl
                            label={__('Font Size', 'shogun-slogans')}
                            value={fontSize}
                            onChange={(value) => setAttributes({ fontSize: value })}
                            help={__('Use CSS units (px, em, rem, etc.)', 'shogun-slogans')}
                        />
                        
                        <SelectControl
                            label={__('Font Family', 'shogun-slogans')}
                            value={fontFamily}
                            options={[
                                { label: 'Inherit from theme', value: 'inherit' },
                                { label: 'Sans-serif', value: 'sans-serif' },
                                { label: 'Serif', value: 'serif' },
                                { label: 'Monospace', value: 'monospace' },
                                { label: 'Cursive', value: 'cursive' },
                                { label: 'Fantasy', value: 'fantasy' }
                            ]}
                            onChange={(value) => setAttributes({ fontFamily: value })}
                        />
                        
                        <TextControl
                            label={__('Additional CSS Classes', 'shogun-slogans')}
                            value={className}
                            onChange={(value) => setAttributes({ className: value })}
                            help={__('Add custom CSS classes for styling', 'shogun-slogans')}
                        />
                    </PanelBody>
                </InspectorControls>
                
                <div className="shogun-block-preview">
                    <div className="shogun-block-label">
                        <strong>{__('Shogun Animation Preview', 'shogun-slogans')}</strong>
                        {isLoading && <span className="shogun-loading"> (Loading...)</span>}
                    </div>
                    
                    <div 
                        className="shogun-preview-container"
                        style={{
                            border: '2px dashed #ccc',
                            padding: '20px',
                            minHeight: '60px',
                            backgroundColor: '#f9f9f9',
                            borderRadius: '4px',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center'
                        }}
                    >
                        {previewHTML ? (
                            <div dangerouslySetInnerHTML={{ __html: previewHTML }} />
                        ) : (
                            <div style={{ color: '#666', fontStyle: 'italic' }}>
                                {text || __('Enter text to see preview', 'shogun-slogans')}
                            </div>
                        )}
                    </div>
                    
                    {previewCSS && (
                        <style dangerouslySetInnerHTML={{ __html: previewCSS }} />
                    )}
                </div>
            </div>
        );
    },
    
    save: function(props) {
        const { attributes } = props;
        const {
            text,
            animationType,
            speed,
            cursor,
            color,
            fontSize,
            fontFamily,
            glowColor,
            intensity,
            flicker,
            wobble,
            className
        } = attributes;
        
        // Build shortcode attributes
        const shortcodeAttrs = [`type="${animationType}"`, `text="${text}"`];
        
        if (speed !== 100) shortcodeAttrs.push(`speed="${speed}"`);
        if (cursor !== '|') shortcodeAttrs.push(`cursor="${cursor}"`);
        if (color !== '#000000') shortcodeAttrs.push(`color="${color}"`);
        if (fontSize !== '16px') shortcodeAttrs.push(`font_size="${fontSize}"`);
        if (fontFamily !== 'inherit') shortcodeAttrs.push(`font_family="${fontFamily}"`);
        if (className) shortcodeAttrs.push(`class="${className}"`);
        
        // Animation-specific attributes
        if (animationType === 'neon') {
            if (glowColor !== '#00ffff') shortcodeAttrs.push(`glow_color="${glowColor}"`);
            if (intensity !== 20) shortcodeAttrs.push(`intensity="${intensity}"`);
            if (flicker) shortcodeAttrs.push(`flicker="true"`);
        }
        
        if (animationType === 'handwritten' && !wobble) {
            shortcodeAttrs.push(`wobble="false"`);
        }
        
        const shortcode = `[shogun_animation ${shortcodeAttrs.join(' ')}]`;
        
        const blockProps = useBlockProps.save({
            className: `shogun-animation-block ${className}`
        });
        
        return (
            <div {...blockProps}>
                {shortcode}
            </div>
        );
    }
});

// Register a simplified typewriter block for quick access
registerBlockType('shogun-slogans/typewriter-block', {
    title: __('Typewriter Effect', 'shogun-slogans'),
    description: __('Quick typewriter animation block', 'shogun-slogans'),
    icon: 'edit',
    category: 'text',
    keywords: [
        __('typewriter', 'shogun-slogans'),
        __('typing', 'shogun-slogans'),
        __('text', 'shogun-slogans')
    ],
    
    attributes: {
        text: {
            type: 'string',
            default: 'Type your message here...'
        },
        speed: {
            type: 'number',
            default: 100
        },
        cursor: {
            type: 'string',
            default: '|'
        },
        className: {
            type: 'string',
            default: ''
        }
    },
    
    edit: function(props) {
        const { attributes, setAttributes } = props;
        const { text, speed, cursor, className } = attributes;
        
        const blockProps = useBlockProps({
            className: `shogun-typewriter-block ${className}`
        });
        
        return (
            <div {...blockProps}>
                <InspectorControls>
                    <PanelBody title={__('Typewriter Settings', 'shogun-slogans')}>
                        <TextControl
                            label={__('Text to Type', 'shogun-slogans')}
                            value={text}
                            onChange={(value) => setAttributes({ text: value })}
                        />
                        
                        <RangeControl
                            label={__('Typing Speed (ms)', 'shogun-slogans')}
                            value={speed}
                            onChange={(value) => setAttributes({ speed: value })}
                            min={10}
                            max={500}
                            step={10}
                        />
                        
                        <TextControl
                            label={__('Cursor', 'shogun-slogans')}
                            value={cursor}
                            onChange={(value) => setAttributes({ cursor: value })}
                            maxLength={2}
                        />
                    </PanelBody>
                </InspectorControls>
                
                <div className="shogun-typewriter-preview">
                    <div className="shogun-block-label">
                        <strong>{__('Typewriter Preview', 'shogun-slogans')}</strong>
                    </div>
                    <div 
                        style={{
                            border: '1px dashed #999',
                            padding: '15px',
                            backgroundColor: '#f5f5f5',
                            fontFamily: 'monospace'
                        }}
                    >
                        {text}{cursor}
                    </div>
                </div>
            </div>
        );
    },
    
    save: function(props) {
        const { attributes } = props;
        const { text, speed, cursor, className } = attributes;
        
        const shortcode = `[shogun_animation type="typewriter" text="${text}" speed="${speed}" cursor="${cursor}"${className ? ` class="${className}"` : ''}]`;
        
        const blockProps = useBlockProps.save({
            className: `shogun-typewriter-block ${className}`
        });
        
        return (
            <div {...blockProps}>
                {shortcode}
            </div>
        );
    }
});
