/**
 * Shogun Slogans - Final Frontend JavaScript
 * Version: 3.1.0
 * 
 * Handles typewriter effects and slogan animations with:
 * - Performance optimization with Intersection Observer
 * - Accessibility support for reduced motion
 * - Error handling and graceful degradation
 * - Modern ES6+ features with fallbacks
 * - Memory leak prevention
 * - Comprehensive animation styles
 * - WordPress integration
 * 
 * @package ShogunSlogans
 * @since 3.1.0
 */

(function($) {
    'use strict';

    // Global configuration from WordPress
    const config = window.shogunSlogansConfig || {
        options: {
            defaultSpeed: 100,
            defaultCursor: '|',
            defaultLoop: false,
            enableAccessibility: true,
            enablePerformanceOptimization: true,
            debugMode: false
        },
        strings: {
            loading: 'Loading...',
            error: 'Error loading content',
            pauseAnimation: 'Pause animation',
            resumeAnimation: 'Resume animation'
        },
        ajax: {
            url: '/wp-admin/admin-ajax.php',
            nonce: ''
        },
        rest: {
            url: '/wp-json/shogun-slogans/v1/',
            nonce: ''
        }
    };

    // Animation registry
    const animationRegistry = new Map();
    
    // Intersection Observer for performance optimization
    let intersectionObserver = null;
    
    // Global state
    const globalState = {
        isInitialized: false,
        animationsCount: 0,
        activeAnimations: new Set(),
        prefersReducedMotion: false
    };

    /**
     * Debug logging utility
     * 
     * @since 3.1.0
     * @param {string} message Debug message
     * @param {*} data Optional data to log
     */
    function debugLog(message, data = null) {
        if (config.options.debugMode && console && console.log) {
            console.log(`[Shogun Slogans] ${message}`, data || '');
        }
    }

    /**
     * Check if user prefers reduced motion
     * 
     * @since 3.1.0
     * @returns {boolean}
     */
    function checkReducedMotion() {
        if (!config.options.enableAccessibility) {
            return false;
        }
        
        const mediaQuery = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)');
        return mediaQuery && mediaQuery.matches;
    }

    /**
     * Initialize Intersection Observer for performance optimization
     * 
     * @since 3.1.0
     */
    function initIntersectionObserver() {
        if (!config.options.enablePerformanceOptimization || 
            !window.IntersectionObserver) {
            debugLog('Intersection Observer not available or disabled');
            return null;
        }

        const options = {
            root: null,
            rootMargin: '50px',
            threshold: 0.1
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                const element = entry.target;
                const animation = animationRegistry.get(element);
                
                if (entry.isIntersecting) {
                    if (animation && !animation.isStarted) {
                        animation.start();
                    }
                } else {
                    if (animation && animation.isRunning) {
                        animation.pause();
                    }
                }
            });
        }, options);

        debugLog('Intersection Observer initialized');
        return observer;
    }

    /**
     * Utility function to safely execute with error handling
     * 
     * @since 3.1.0
     * @param {Function} fn Function to execute
     * @param {string} context Error context
     * @param {*} defaultReturn Default return value on error
     */
    function safeExecute(fn, context = 'unknown', defaultReturn = null) {
        try {
            return fn();
        } catch (error) {
            debugLog(`Error in ${context}:`, error);
            return defaultReturn;
        }
    }

    /**
     * Base Animation Class
     * 
     * @since 3.1.0
     */
    class BaseAnimation {
        constructor(element, options = {}) {
            this.element = element;
            this.options = this.parseOptions(options);
            this.state = {
                isStarted: false,
                isRunning: false,
                isPaused: false,
                isComplete: false,
                currentPosition: 0,
                startTime: null,
                pauseTime: null
            };
            
            // Generate unique ID
            this.id = this.generateId();
            
            // Error handling
            this.errors = [];
            
            // Cleanup handlers
            this.cleanupHandlers = [];
            
            // Register animation
            animationRegistry.set(this.element, this);
            globalState.animationsCount++;
            
            this.init();
        }
        
        parseOptions(options) {
            return {
                speed: parseInt(options.speed) || config.options.defaultSpeed,
                delay: parseInt(options.delay) || 0,
                loop: options.loop === 'true' || options.loop === true,
                autoStart: options.autoStart !== 'false',
                ...options
            };
        }
        
        generateId() {
            return 'shogun-anim-' + Math.random().toString(36).substr(2, 9);
        }
        
        init() {
            // Override in subclasses
        }
        
        start() {
            if (this.state.isStarted && !this.state.isComplete) {
                return this;
            }
            
            this.state.isStarted = true;
            this.state.isRunning = true;
            this.state.startTime = performance.now();
            
            globalState.activeAnimations.add(this);
            
            debugLog(`Animation started: ${this.id}`);
            return this;
        }
        
        pause() {
            if (!this.state.isRunning) {
                return this;
            }
            
            this.state.isRunning = false;
            this.state.isPaused = true;
            this.state.pauseTime = performance.now();
            
            debugLog(`Animation paused: ${this.id}`);
            return this;
        }
        
        resume() {
            if (!this.state.isPaused) {
                return this;
            }
            
            this.state.isRunning = true;
            this.state.isPaused = false;
            
            // Adjust start time to account for pause duration
            const pauseDuration = performance.now() - this.state.pauseTime;
            this.state.startTime += pauseDuration;
            
            debugLog(`Animation resumed: ${this.id}`);
            return this;
        }
        
        stop() {
            this.state.isRunning = false;
            this.state.isComplete = true;
            
            globalState.activeAnimations.delete(this);
            
            debugLog(`Animation stopped: ${this.id}`);
            return this;
        }
        
        destroy() {
            this.stop();
            
            // Run cleanup handlers
            this.cleanupHandlers.forEach(handler => {
                safeExecute(handler, `cleanup for ${this.id}`);
            });
            
            // Remove from registry
            animationRegistry.delete(this.element);
            globalState.animationsCount--;
            
            debugLog(`Animation destroyed: ${this.id}`);
        }
        
        addCleanupHandler(handler) {
            this.cleanupHandlers.push(handler);
        }
        
        handleError(error, context = 'unknown') {
            this.errors.push({ error, context, timestamp: Date.now() });
            debugLog(`Animation error in ${context}:`, error);
        }
    }

    /**
     * Typewriter Animation Class
     * 
     * Enhanced with modern features:
     * - Better performance with requestAnimationFrame
     * - Accessibility support
     * - Error handling and graceful degradation
     * - Customizable timing and effects
     * - Memory leak prevention
     * 
     * @since 3.1.0
     */
    class TypewriterAnimation extends BaseAnimation {
        init() {
            // Find required elements
            this.textElement = this.element.querySelector('.typewriter-text');
            this.cursorElement = this.element.querySelector('.typewriter-cursor');
            
            // Validate required elements
            if (!this.textElement) {
                this.handleError('Missing .typewriter-text element', 'init');
                return;
            }
            
            if (!this.cursorElement) {
                this.handleError('Missing .typewriter-cursor element', 'init');
                return;
            }
            
            // Get text from data attribute or element content
            this.targetText = this.element.getAttribute('data-text') || this.textElement.textContent || '';
            this.cursor = this.element.getAttribute('data-cursor') || config.options.defaultCursor;
            
            // Clear initial content
            this.textElement.textContent = '';
            this.cursorElement.textContent = this.cursor;
            
            // Set up cursor blinking
            this.setupCursor();
            
            // Set up accessibility
            this.setupAccessibility();
            
            // Auto-start if enabled
            if (this.options.autoStart && !globalState.prefersReducedMotion) {
                if (this.options.delay > 0) {
                    setTimeout(() => this.start(), this.options.delay);
                } else {
                    this.start();
                }
            }
            
            debugLog('Typewriter animation initialized', {
                id: this.id,
                text: this.targetText,
                speed: this.options.speed
            });
        }
        
        setupCursor() {
            const cursorBlink = this.element.getAttribute('data-cursor-blink') !== 'false';
            const preserveCursor = this.element.getAttribute('data-preserve-cursor') === 'true';
            
            if (!cursorBlink) {
                this.cursorElement.style.animation = 'none';
            }
            
            this.options.preserveCursor = preserveCursor;
        }
        
        setupAccessibility() {
            // Set ARIA attributes
            this.textElement.setAttribute('role', 'text');
            this.textElement.setAttribute('aria-label', this.targetText);
            this.cursorElement.setAttribute('aria-hidden', 'true');
            
            // Add screen reader content for reduced motion users
            if (globalState.prefersReducedMotion) {
                this.textElement.textContent = this.targetText;
                this.cursorElement.style.display = 'none';
            }
        }
        
        start() {
            super.start();
            
            if (globalState.prefersReducedMotion) {
                // Show text immediately for reduced motion users
                this.textElement.textContent = this.targetText;
                this.complete();
                return this;
            }
            
            this.typeCharacter();
            return this;
        }
        
        typeCharacter() {
            if (!this.state.isRunning || this.state.currentPosition >= this.targetText.length) {
                this.complete();
                return;
            }
            
            // Add next character
            const nextChar = this.targetText.charAt(this.state.currentPosition);
            this.textElement.textContent += nextChar;
            this.state.currentPosition++;
            
            // Schedule next character
            const delay = this.calculateDelay(nextChar);
            setTimeout(() => {
                if (this.state.isRunning) {
                    this.typeCharacter();
                }
            }, delay);
        }
        
        calculateDelay(character) {
            let delay = this.options.speed;
            
            // Add natural pauses for punctuation
            if (character === '.' || character === '!' || character === '?') {
                delay *= 3;
            } else if (character === ',' || character === ';' || character === ':') {
                delay *= 2;
            } else if (character === ' ') {
                delay *= 0.5;
            }
            
            // Add slight randomness for more natural feel
            delay += (Math.random() - 0.5) * delay * 0.2;
            
            return Math.max(delay, 10); // Minimum delay
        }
        
        complete() {
            this.stop();
            
            // Handle cursor after completion
            if (!this.options.preserveCursor && !this.options.loop) {
                setTimeout(() => {
                    if (this.cursorElement) {
                        this.cursorElement.style.display = 'none';
                    }
                }, 1000);
            }
            
            // Handle looping
            if (this.options.loop && !globalState.prefersReducedMotion) {
                setTimeout(() => {
                    this.reset();
                    this.start();
                }, 2000);
            }
            
            // Trigger completion event
            this.element.dispatchEvent(new CustomEvent('shogun:typewriter:complete', {
                detail: { id: this.id, text: this.targetText }
            }));
        }
        
        reset() {
            this.state.currentPosition = 0;
            this.state.isComplete = false;
            this.textElement.textContent = '';
            
            if (this.cursorElement) {
                this.cursorElement.style.display = '';
                this.cursorElement.textContent = this.cursor;
            }
        }
    }

    /**
     * Slogan Animation Class
     * 
     * Handles various slogan animation styles
     * 
     * @since 3.1.0
     */
    class SloganAnimation extends BaseAnimation {
        init() {
            this.textElement = this.element.querySelector('.slogan-text') || 
                              this.element.querySelector('.typewriter-text');
            
            if (!this.textElement) {
                this.handleError('Missing text element', 'init');
                return;
            }
            
            this.targetText = this.element.getAttribute('data-text') || this.textElement.textContent || '';
            this.animationType = this.element.getAttribute('data-animation') || 'fade';
            
            // Set up based on animation type
            this.setupAnimation();
            
            // Auto-start if enabled
            if (this.options.autoStart && !globalState.prefersReducedMotion) {
                if (this.options.delay > 0) {
                    setTimeout(() => this.start(), this.options.delay);
                } else {
                    this.start();
                }
            }
            
            debugLog('Slogan animation initialized', {
                id: this.id,
                type: this.animationType,
                text: this.targetText
            });
        }
        
        setupAnimation() {
            switch (this.animationType) {
                case 'typewriter':
                    this.setupTypewriter();
                    break;
                case 'fade':
                    this.setupFade();
                    break;
                case 'slide':
                    this.setupSlide();
                    break;
                case 'bounce':
                    this.setupBounce();
                    break;
                default:
                    this.setupFade();
            }
        }
        
        setupTypewriter() {
            // If it's a typewriter style, delegate to TypewriterAnimation
            this.typewriterInstance = new TypewriterAnimation(this.element, this.options);
        }
        
        setupFade() {
            this.textElement.style.opacity = '0';
            this.textElement.style.transition = `opacity ${this.options.speed}ms ease`;
        }
        
        setupSlide() {
            this.textElement.style.transform = 'translateY(20px)';
            this.textElement.style.opacity = '0';
            this.textElement.style.transition = `all ${this.options.speed}ms ease`;
        }
        
        setupBounce() {
            this.textElement.style.transform = 'scale(0.8)';
            this.textElement.style.opacity = '0';
            this.textElement.style.transition = `all ${this.options.speed}ms cubic-bezier(0.68, -0.55, 0.265, 1.55)`;
        }
        
        start() {
            super.start();
            
            if (this.typewriterInstance) {
                return this.typewriterInstance.start();
            }
            
            if (globalState.prefersReducedMotion) {
                this.showImmediately();
                return this;
            }
            
            this.animate();
            return this;
        }
        
        animate() {
            switch (this.animationType) {
                case 'fade':
                    this.textElement.style.opacity = '1';
                    break;
                case 'slide':
                    this.textElement.style.transform = 'translateY(0)';
                    this.textElement.style.opacity = '1';
                    break;
                case 'bounce':
                    this.textElement.style.transform = 'scale(1)';
                    this.textElement.style.opacity = '1';
                    break;
            }
            
            setTimeout(() => this.complete(), this.options.speed);
        }
        
        showImmediately() {
            this.textElement.style.opacity = '1';
            this.textElement.style.transform = 'none';
            this.complete();
        }
        
        complete() {
            this.stop();
            
            // Trigger completion event
            this.element.dispatchEvent(new CustomEvent('shogun:slogan:complete', {
                detail: { id: this.id, type: this.animationType, text: this.targetText }
            }));
        }
    }

    /**
     * Animated Text Class
     * 
     * Generic animation handler for various text effects
     * 
     * @since 3.1.0
     */
    class AnimatedTextAnimation extends BaseAnimation {
        init() {
            this.textElement = this.element.querySelector('.animated-text');
            
            if (!this.textElement) {
                this.handleError('Missing .animated-text element', 'init');
                return;
            }
            
            this.targetText = this.element.getAttribute('data-text') || this.textElement.textContent || '';
            this.animationType = this.element.getAttribute('data-animation') || 'fade';
            this.direction = this.element.getAttribute('data-direction') || 'normal';
            
            this.setupAnimation();
            
            // Auto-start if enabled
            if (this.options.autoStart && !globalState.prefersReducedMotion) {
                if (this.options.delay > 0) {
                    setTimeout(() => this.start(), this.options.delay);
                } else {
                    this.start();
                }
            }
            
            debugLog('Animated text initialized', {
                id: this.id,
                type: this.animationType,
                text: this.targetText
            });
        }
        
        setupAnimation() {
            // Apply CSS class for animation
            this.element.classList.add(`shogun-animation-${this.animationType}`);
            
            // Set initial state
            this.textElement.style.animationDuration = `${this.options.speed}ms`;
            this.textElement.style.animationDirection = this.direction;
            this.textElement.style.animationFillMode = 'both';
            
            if (this.options.loop) {
                this.textElement.style.animationIterationCount = 'infinite';
            }
        }
        
        start() {
            super.start();
            
            if (globalState.prefersReducedMotion) {
                this.showImmediately();
                return this;
            }
            
            // Start CSS animation
            this.textElement.style.animationPlayState = 'running';
            
            if (!this.options.loop) {
                setTimeout(() => this.complete(), this.options.speed);
            }
            
            return this;
        }
        
        showImmediately() {
            this.textElement.style.animation = 'none';
            this.textElement.style.opacity = '1';
            this.textElement.style.transform = 'none';
            this.complete();
        }
        
        complete() {
            this.stop();
            
            // Trigger completion event
            this.element.dispatchEvent(new CustomEvent('shogun:animated:complete', {
                detail: { id: this.id, type: this.animationType, text: this.targetText }
            }));
        }
    }

    /**
     * Animation Factory
     * 
     * Creates appropriate animation instances based on element classes
     * 
     * @since 3.1.0
     */
    function createAnimation(element) {
        const classList = element.classList;
        
        if (classList.contains('shogun-typewriter')) {
            return new TypewriterAnimation(element);
        } else if (classList.contains('shogun-slogan')) {
            return new SloganAnimation(element);
        } else if (classList.contains('shogun-animated-text')) {
            return new AnimatedTextAnimation(element);
        }
        
        // Default to typewriter if no specific class found but has data-text
        if (element.hasAttribute('data-text')) {
            return new TypewriterAnimation(element);
        }
        
        return null;
    }

    /**
     * Initialize all animations on the page
     * 
     * @since 3.1.0
     */
    function initializeAnimations() {
        const selectors = [
            '.shogun-typewriter',
            '.shogun-slogan',
            '.shogun-animated-text',
            '[data-text]'
        ];
        
        const elements = document.querySelectorAll(selectors.join(', '));
        
        elements.forEach(element => {
            // Skip if already initialized
            if (animationRegistry.has(element)) {
                return;
            }
            
            const animation = createAnimation(element);
            if (animation) {
                // Use Intersection Observer if available
                if (intersectionObserver) {
                    intersectionObserver.observe(element);
                }
            }
        });
        
        debugLog(`Initialized ${elements.length} animations`);
    }

    /**
     * Handle dynamic content changes
     * 
     * @since 3.1.0
     */
    function handleDynamicContent() {
        // Use MutationObserver to watch for new content
        if (!window.MutationObserver) {
            return;
        }
        
        const observer = new MutationObserver((mutations) => {
            let hasNewAnimations = false;
            
            mutations.forEach(mutation => {
                if (mutation.type === 'childList') {
                    mutation.addedNodes.forEach(node => {
                        if (node.nodeType === Node.ELEMENT_NODE) {
                            const animationElements = node.querySelectorAll([
                                '.shogun-typewriter',
                                '.shogun-slogan',
                                '.shogun-animated-text',
                                '[data-text]'
                            ].join(', '));
                            
                            if (animationElements.length > 0) {
                                hasNewAnimations = true;
                            }
                        }
                    });
                }
            });
            
            if (hasNewAnimations) {
                setTimeout(initializeAnimations, 100);
            }
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
        
        debugLog('MutationObserver initialized for dynamic content');
    }

    /**
     * Public API
     * 
     * @since 3.1.0
     */
    window.ShogunSlogans = {
        // Core functions
        init: initializeAnimations,
        createAnimation: createAnimation,
        
        // Animation classes
        TypewriterAnimation: TypewriterAnimation,
        SloganAnimation: SloganAnimation,
        AnimatedTextAnimation: AnimatedTextAnimation,
        
        // Utility functions
        debugLog: debugLog,
        safeExecute: safeExecute,
        
        // State access
        getState: () => ({ ...globalState }),
        getConfig: () => ({ ...config }),
        getAnimations: () => Array.from(animationRegistry.values()),
        
        // Control functions
        pauseAll: () => {
            globalState.activeAnimations.forEach(animation => animation.pause());
        },
        resumeAll: () => {
            globalState.activeAnimations.forEach(animation => animation.resume());
        },
        stopAll: () => {
            globalState.activeAnimations.forEach(animation => animation.stop());
        },
        destroyAll: () => {
            Array.from(animationRegistry.values()).forEach(animation => animation.destroy());
        },
        
        // Event system
        on: (event, callback) => {
            document.addEventListener(`shogun:${event}`, callback);
        },
        off: (event, callback) => {
            document.removeEventListener(`shogun:${event}`, callback);
        }
    };

    /**
     * Initialize plugin when DOM is ready
     * 
     * @since 3.1.0
     */
    function initPlugin() {
        if (globalState.isInitialized) {
            return;
        }
        
        debugLog('Initializing Shogun Slogans');
        
        // Check for reduced motion preference
        globalState.prefersReducedMotion = checkReducedMotion();
        
        // Initialize Intersection Observer
        intersectionObserver = initIntersectionObserver();
        
        // Initialize animations
        initializeAnimations();
        
        // Handle dynamic content
        handleDynamicContent();
        
        // Listen for reduced motion changes
        if (window.matchMedia) {
            const mediaQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
            mediaQuery.addListener((e) => {
                globalState.prefersReducedMotion = e.matches;
                debugLog('Reduced motion preference changed:', e.matches);
            });
        }
        
        globalState.isInitialized = true;
        
        // Trigger initialization complete event
        document.dispatchEvent(new CustomEvent('shogun:initialized', {
            detail: { 
                version: '3.1.0',
                animationsCount: globalState.animationsCount,
                prefersReducedMotion: globalState.prefersReducedMotion
            }
        }));
        
        debugLog('Shogun Slogans initialized successfully', {
            animationsCount: globalState.animationsCount,
            prefersReducedMotion: globalState.prefersReducedMotion,
            intersectionObserver: !!intersectionObserver
        });
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPlugin);
    } else {
        initPlugin();
    }
    
    // Also initialize when jQuery is ready (for WordPress compatibility)
    $(document).ready(function() {
        // Small delay to ensure WordPress has processed everything
        setTimeout(initPlugin, 100);
    });

    // Cleanup on page unload
    window.addEventListener('beforeunload', function() {
        debugLog('Cleaning up animations before page unload');
        window.ShogunSlogans.destroyAll();
    });

})(window.jQuery || window.$);
