/**
 * Shogun Slogans - Modern Frontend JavaScript
 * Version: 3.0.0
 * 
 * Handles typewriter effects and slogan animations with:
 * - Performance optimization
 * - Accessibility support
 * - Error handling
 * - Modern ES6+ features
 * - Intersection Observer API
 * - Reduced motion support
 * 
 * @package ShogunSlogans
 * @since 3.0.0
 */

(function($) {
    'use strict';

    // Global configuration from WordPress
    const config = window.shogunSlogansConfig || {
        options: {
            defaultSpeed: 100,
            defaultCursor: '|',
            defaultLoop: true,
            enableAccessibility: true,
            enablePerformanceOptimization: true,
            debugMode: false
        },
        strings: {
            loading: 'Loading...',
            error: 'Error loading content',
            pauseAnimation: 'Pause animation',
            resumeAnimation: 'Resume animation'
        }
    };

    /**
     * Debug logging utility
     * 
     * @since 3.0.0
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
     * @since 3.0.0
     * @returns {boolean}
     */
    function prefersReducedMotion() {
        if (!config.options.enableAccessibility) {
            return false;
        }
        
        return window.matchMedia && 
               window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    }

    /**
     * Typewriter Text Animation Class
     * 
     * Enhanced with modern features:
     * - Better performance
     * - Accessibility support
     * - Error handling
     * - Customizable timing
     * - Memory leak prevention
     * 
     * @since 3.0.0
     */
    class ShogunTypewriter {
        /**
         * Constructor
         * 
         * @param {HTMLElement} element Target element
         * @param {Object} options Configuration options
         */
        constructor(element, options = {}) {
            this.element = element;
            this.textElement = element.querySelector('.typewriter-text');
            this.cursorElement = element.querySelector('.typewriter-cursor');
            
            // Validate required elements
            if (!this.textElement || !this.cursorElement) {
                debugLog('Error: Missing required typewriter elements', element);
                return;
            }
            
            // Get settings from data attributes or options
            this.settings = this.parseSettings(options);
            
            // State management
            this.state = {
                currentIndex: 0,
                isTyping: false,
                isDeleting: false,
                isPaused: false,
                isVisible: false,
                isDestroyed: false
            };
            
            // Timers for cleanup
            this.timers = new Set();
            
            // Intersection observer for performance
            this.observer = null;
            
            debugLog('Initializing typewriter', this.settings);
            this.init();
        }
        
        /**
         * Parse settings from element and options
         * 
         * @param {Object} options User options
         * @returns {Object} Parsed settings
         */
        parseSettings(options) {
            const dataset = this.element.dataset;
            
            return {
                text: options.text || dataset.text || 'Sample text...',
                speed: this.parseNumber(options.speed || dataset.speed, config.options.defaultSpeed, 10, 1000),
                cursor: options.cursor || dataset.cursor || config.options.defaultCursor,
                loop: this.parseBoolean(options.loop || dataset.loop, config.options.defaultLoop),
                delay: this.parseNumber(options.delay || dataset.delay, 0, 0, 10000),
                deleteSpeed: this.parseNumber(options.deleteSpeed || dataset.deleteSpeed, 50, 10, 500),
                pauseEnd: this.parseNumber(options.pauseEnd || dataset.pauseEnd, 2000, 500, 10000),
                pauseStart: this.parseNumber(options.pauseStart || dataset.pauseStart, 1000, 100, 5000),
                enableSound: this.parseBoolean(options.enableSound || dataset.enableSound, false),
                ...options
            };
        }
        
        /**
         * Parse number with validation
         * 
         * @param {*} value Input value
         * @param {number} defaultValue Default value
         * @param {number} min Minimum value
         * @param {number} max Maximum value
         * @returns {number}
         */
        parseNumber(value, defaultValue, min = 0, max = Infinity) {
            const parsed = parseInt(value, 10);
            if (isNaN(parsed)) return defaultValue;
            return Math.max(min, Math.min(max, parsed));
        }
        
        /**
         * Parse boolean value
         * 
         * @param {*} value Input value
         * @param {boolean} defaultValue Default value
         * @returns {boolean}
         */
        parseBoolean(value, defaultValue) {
            if (typeof value === 'boolean') return value;
            if (typeof value === 'string') {
                return value.toLowerCase() === 'true';
            }
            return defaultValue;
        }
        
        /**
         * Initialize typewriter
         */
        init() {
            if (this.state.isDestroyed) return;
            
            // Set up initial state
            this.resetText();
            this.updateCursor();
            
            // Handle reduced motion preference
            if (prefersReducedMotion()) {
                this.showStaticText();
                return;
            }
            
            // Set up performance optimization
            if (config.options.enablePerformanceOptimization && 'IntersectionObserver' in window) {
                this.setupIntersectionObserver();
            } else {
                this.startAnimation();
            }
            
            // Add accessibility attributes
            this.setupAccessibility();
            
            debugLog('Typewriter initialized');
        }
        
        /**
         * Set up accessibility features
         */
        setupAccessibility() {
            if (!config.options.enableAccessibility) return;
            
            // Add ARIA attributes
            this.element.setAttribute('aria-live', 'polite');
            this.element.setAttribute('aria-label', `Animated text: ${this.settings.text}`);
            
            // Add pause/resume functionality for screen readers
            this.element.setAttribute('tabindex', '0');
            this.element.addEventListener('keydown', (e) => {
                if (e.key === ' ' || e.key === 'Enter') {
                    e.preventDefault();
                    this.togglePause();
                }
            });
            
            // Add mouse pause functionality
            this.element.addEventListener('mouseenter', () => this.pause());
            this.element.addEventListener('mouseleave', () => this.resume());
        }
        
        /**
         * Show static text for reduced motion
         */
        showStaticText() {
            if (this.textElement) {
                this.textElement.textContent = this.settings.text;
            }
            if (this.cursorElement) {
                this.cursorElement.style.animation = 'none';
                this.cursorElement.style.opacity = '0.7';
            }
            debugLog('Showing static text due to reduced motion preference');
        }
        
        /**
         * Set up intersection observer for performance
         */
        setupIntersectionObserver() {
            const options = {
                threshold: 0.1,
                rootMargin: '50px'
            };
            
            this.observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting && !this.state.isVisible) {
                        this.state.isVisible = true;
                        this.startAnimation();
                        debugLog('Element entered viewport, starting animation');
                    } else if (!entry.isIntersecting && this.state.isVisible) {
                        this.pause();
                        debugLog('Element left viewport, pausing animation');
                    }
                });
            }, options);
            
            this.observer.observe(this.element);
        }
        
        /**
         * Start animation sequence
         */
        startAnimation() {
            if (this.state.isDestroyed || this.state.isPaused) return;
            
            if (this.settings.delay > 0) {
                this.setTimeout(() => {
                    this.startTyping();
                }, this.settings.delay);
            } else {
                this.startTyping();
            }
        }
        
        /**
         * Start typing animation
         */
        startTyping() {
            if (this.state.isDestroyed || this.state.isPaused || 
                this.state.isTyping || this.state.isDeleting) {
                return;
            }
            
            this.state.isTyping = true;
            this.typeCharacter();
            debugLog('Started typing animation');
        }
        
        /**
         * Type single character
         */
        typeCharacter() {
            if (!this.state.isTyping || this.state.isDestroyed || this.state.isPaused) {
                return;
            }
            
            const currentText = this.settings.text;
            const displayText = currentText.substring(0, this.state.currentIndex + 1);
            
            if (this.textElement) {
                this.textElement.textContent = displayText;
                
                // Update ARIA live region
                if (config.options.enableAccessibility) {
                    this.element.setAttribute('aria-label', `Typing: ${displayText}`);
                }
            }
            
            // Play typing sound if enabled
            if (this.settings.enableSound) {
                this.playTypingSound();
            }
            
            this.state.currentIndex++;
            
            if (this.state.currentIndex < currentText.length) {
                this.setTimeout(() => this.typeCharacter(), this.settings.speed);
            } else {
                this.state.isTyping = false;
                debugLog('Finished typing');
                
                if (this.settings.loop) {
                    this.setTimeout(() => this.startDeleting(), this.settings.pauseEnd);
                } else {
                    // Hide cursor when done if not looping
                    this.hideCursor();
                }
            }
        }
        
        /**
         * Start deleting animation
         */
        startDeleting() {
            if (this.state.isDestroyed || this.state.isPaused || 
                this.state.isTyping || this.state.isDeleting) {
                return;
            }
            
            this.state.isDeleting = true;
            this.deleteCharacter();
            debugLog('Started deleting animation');
        }
        
        /**
         * Delete single character
         */
        deleteCharacter() {
            if (!this.state.isDeleting || this.state.isDestroyed || this.state.isPaused) {
                return;
            }
            
            const currentText = this.textElement ? this.textElement.textContent : '';
            
            if (currentText.length > 0) {
                const newText = currentText.substring(0, currentText.length - 1);
                if (this.textElement) {
                    this.textElement.textContent = newText;
                }
                this.state.currentIndex--;
                
                this.setTimeout(() => this.deleteCharacter(), this.settings.deleteSpeed);
            } else {
                this.state.isDeleting = false;
                this.state.currentIndex = 0;
                debugLog('Finished deleting');
                
                this.setTimeout(() => this.startTyping(), this.settings.pauseStart);
            }
        }
        
        /**
         * Set timeout with cleanup tracking
         * 
         * @param {Function} callback Callback function
         * @param {number} delay Delay in milliseconds
         */
        setTimeout(callback, delay) {
            const timeoutId = setTimeout(() => {
                this.timers.delete(timeoutId);
                if (!this.state.isDestroyed) {
                    callback();
                }
            }, delay);
            
            this.timers.add(timeoutId);
            return timeoutId;
        }
        
        /**
         * Reset text content
         */
        resetText() {
            if (this.textElement) {
                this.textElement.textContent = '';
            }
        }
        
        /**
         * Update cursor display
         */
        updateCursor() {
            if (this.cursorElement) {
                this.cursorElement.textContent = this.settings.cursor;
                this.cursorElement.style.opacity = '1';
            }
        }
        
        /**
         * Hide cursor
         */
        hideCursor() {
            if (this.cursorElement) {
                this.cursorElement.style.opacity = '0';
            }
        }
        
        /**
         * Play typing sound effect
         */
        playTypingSound() {
            // Simple click sound using Web Audio API
            if ('AudioContext' in window || 'webkitAudioContext' in window) {
                try {
                    const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                    const oscillator = audioContext.createOscillator();
                    const gainNode = audioContext.createGain();
                    
                    oscillator.connect(gainNode);
                    gainNode.connect(audioContext.destination);
                    
                    oscillator.frequency.setValueAtTime(800, audioContext.currentTime);
                    gainNode.gain.setValueAtTime(0.1, audioContext.currentTime);
                    gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.1);
                    
                    oscillator.start();
                    oscillator.stop(audioContext.currentTime + 0.1);
                } catch (e) {
                    debugLog('Error playing typing sound:', e.message);
                }
            }
        }
        
        /**
         * Pause animation
         */
        pause() {
            if (this.state.isDestroyed) return;
            
            this.state.isPaused = true;
            debugLog('Animation paused');
        }
        
        /**
         * Resume animation
         */
        resume() {
            if (this.state.isDestroyed || !this.state.isPaused) return;
            
            this.state.isPaused = false;
            
            if (!this.state.isTyping && !this.state.isDeleting) {
                this.startAnimation();
            }
            
            debugLog('Animation resumed');
        }
        
        /**
         * Toggle pause state
         */
        togglePause() {
            if (this.state.isPaused) {
                this.resume();
            } else {
                this.pause();
            }
        }
        
        /**
         * Update text content
         * 
         * @param {string} newText New text to display
         */
        updateText(newText) {
            if (this.state.isDestroyed) return;
            
            this.destroy();
            this.settings.text = newText;
            this.state.currentIndex = 0;
            this.resetText();
            this.startAnimation();
            
            debugLog('Text updated:', newText);
        }
        
        /**
         * Update settings
         * 
         * @param {Object} newSettings New settings
         */
        updateSettings(newSettings) {
            if (this.state.isDestroyed) return;
            
            this.settings = { ...this.settings, ...newSettings };
            this.updateCursor();
            
            debugLog('Settings updated:', newSettings);
        }
        
        /**
         * Destroy typewriter instance
         */
        destroy() {
            if (this.state.isDestroyed) return;
            
            // Clear all timers
            this.timers.forEach(timerId => clearTimeout(timerId));
            this.timers.clear();
            
            // Disconnect observer
            if (this.observer) {
                this.observer.disconnect();
                this.observer = null;
            }
            
            // Reset state
            this.state.isDestroyed = true;
            this.state.isTyping = false;
            this.state.isDeleting = false;
            this.state.isPaused = false;
            
            debugLog('Typewriter destroyed');
        }
        
        /**
         * Get current state
         * 
         * @returns {Object} Current state
         */
        getState() {
            return { ...this.state };
        }
        
        /**
         * Get current settings
         * 
         * @returns {Object} Current settings
         */
        getSettings() {
            return { ...this.settings };
        }
    }

    /**
     * Slogan Animation Handler
     * 
     * Handles fade, slide, and other slogan animations
     * 
     * @since 3.0.0
     */
    class ShogunSlogan {
        /**
         * Constructor
         * 
         * @param {HTMLElement} element Target element
         */
        constructor(element) {
            this.element = element;
            this.observer = null;
            
            debugLog('Initializing slogan animation');
            this.init();
        }
        
        /**
         * Initialize slogan animation
         */
        init() {
            // Handle reduced motion
            if (prefersReducedMotion()) {
                this.element.classList.add('loaded', 'no-animation');
                return;
            }
            
            // Set up intersection observer for better performance
            if (config.options.enablePerformanceOptimization && 'IntersectionObserver' in window) {
                this.setupIntersectionObserver();
            } else {
                this.startAnimation();
            }
        }
        
        /**
         * Set up intersection observer
         */
        setupIntersectionObserver() {
            const options = {
                threshold: 0.1,
                rootMargin: '50px'
            };
            
            this.observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        this.startAnimation();
                        this.observer.disconnect(); // Only animate once
                    }
                });
            }, options);
            
            this.observer.observe(this.element);
        }
        
        /**
         * Start animation
         */
        startAnimation() {
            // Add loaded class for CSS animations
            setTimeout(() => {
                this.element.classList.add('loaded');
                debugLog('Slogan animation started');
            }, 100);
        }
        
        /**
         * Destroy slogan instance
         */
        destroy() {
            if (this.observer) {
                this.observer.disconnect();
                this.observer = null;
            }
        }
    }

    /**
     * Plugin Manager
     * 
     * Manages all typewriter and slogan instances
     * 
     * @since 3.0.0
     */
    class ShogunSlogansManager {
        constructor() {
            this.typewriters = new Map();
            this.slogans = new Map();
            this.isInitialized = false;
            
            debugLog('Manager initialized');
        }
        
        /**
         * Initialize all instances
         */
        init() {
            if (this.isInitialized) return;
            
            this.initializeTypewriters();
            this.initializeSlogans();
            this.bindEvents();
            
            this.isInitialized = true;
            debugLog('Manager fully initialized');
        }
        
        /**
         * Initialize typewriter instances
         */
        initializeTypewriters() {
            const elements = document.querySelectorAll('.shogun-typewriter');
            
            elements.forEach((element, index) => {
                const id = element.id || `shogun-typewriter-${index}`;
                
                if (!this.typewriters.has(id)) {
                    const typewriter = new ShogunTypewriter(element);
                    
                    if (typewriter.textElement && typewriter.cursorElement) {
                        this.typewriters.set(id, typewriter);
                        element.shogunTypewriter = typewriter;
                        element.setAttribute('data-shogun-id', id);
                        
                        debugLog(`Typewriter initialized: ${id}`);
                    }
                }
            });
        }
        
        /**
         * Initialize slogan instances
         */
        initializeSlogans() {
            const elements = document.querySelectorAll('.shogun-slogan');
            
            elements.forEach((element, index) => {
                const id = element.id || `shogun-slogan-${index}`;
                
                if (!this.slogans.has(id)) {
                    const slogan = new ShogunSlogan(element);
                    this.slogans.set(id, slogan);
                    element.shogunSlogan = slogan;
                    element.setAttribute('data-shogun-id', id);
                    
                    debugLog(`Slogan initialized: ${id}`);
                }
            });
        }
        
        /**
         * Bind global events
         */
        bindEvents() {
            // Page visibility changes
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    this.pauseAll();
                } else {
                    this.resumeAll();
                }
            });
            
            // Window focus/blur
            window.addEventListener('blur', () => this.pauseAll());
            window.addEventListener('focus', () => this.resumeAll());
            
            // Handle dynamic content
            this.setupMutationObserver();
            
            // Handle reduced motion changes
            if (window.matchMedia) {
                const mediaQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
                mediaQuery.addListener(() => {
                    if (mediaQuery.matches) {
                        this.pauseAll();
                    }
                });
            }
        }
        
        /**
         * Set up mutation observer for dynamic content
         */
        setupMutationObserver() {
            if (!('MutationObserver' in window)) return;
            
            const observer = new MutationObserver((mutations) => {
                let shouldReinit = false;
                
                mutations.forEach((mutation) => {
                    mutation.addedNodes.forEach((node) => {
                        if (node.nodeType === 1) { // Element node
                            if (node.classList?.contains('shogun-typewriter') || 
                                node.classList?.contains('shogun-slogan') ||
                                node.querySelector?.('.shogun-typewriter, .shogun-slogan')) {
                                shouldReinit = true;
                            }
                        }
                    });
                });
                
                if (shouldReinit) {
                    this.reinitialize();
                }
            });
            
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        }
        
        /**
         * Pause all animations
         */
        pauseAll() {
            this.typewriters.forEach(typewriter => {
                if (typewriter.pause) typewriter.pause();
            });
            
            debugLog('All animations paused');
        }
        
        /**
         * Resume all animations
         */
        resumeAll() {
            this.typewriters.forEach(typewriter => {
                if (typewriter.resume) typewriter.resume();
            });
            
            debugLog('All animations resumed');
        }
        
        /**
         * Reinitialize new elements
         */
        reinitialize() {
            setTimeout(() => {
                this.initializeTypewriters();
                this.initializeSlogans();
                debugLog('Reinitialized for dynamic content');
            }, 100);
        }
        
        /**
         * Get typewriter instance by ID or element
         * 
         * @param {string|HTMLElement} identifier ID or element
         * @returns {ShogunTypewriter|null}
         */
        getTypewriter(identifier) {
            if (typeof identifier === 'string') {
                return this.typewriters.get(identifier) || null;
            }
            
            if (identifier instanceof HTMLElement) {
                const id = identifier.getAttribute('data-shogun-id');
                return id ? this.typewriters.get(id) : null;
            }
            
            return null;
        }
        
        /**
         * Get slogan instance by ID or element
         * 
         * @param {string|HTMLElement} identifier ID or element
         * @returns {ShogunSlogan|null}
         */
        getSlogan(identifier) {
            if (typeof identifier === 'string') {
                return this.slogans.get(identifier) || null;
            }
            
            if (identifier instanceof HTMLElement) {
                const id = identifier.getAttribute('data-shogun-id');
                return id ? this.slogans.get(id) : null;
            }
            
            return null;
        }
        
        /**
         * Destroy all instances
         */
        destroy() {
            this.typewriters.forEach(typewriter => {
                if (typewriter.destroy) typewriter.destroy();
            });
            
            this.slogans.forEach(slogan => {
                if (slogan.destroy) slogan.destroy();
            });
            
            this.typewriters.clear();
            this.slogans.clear();
            this.isInitialized = false;
            
            debugLog('Manager destroyed');
        }
        
        /**
         * Get statistics
         * 
         * @returns {Object} Statistics
         */
        getStats() {
            return {
                typewriters: this.typewriters.size,
                slogans: this.slogans.size,
                isInitialized: this.isInitialized
            };
        }
    }

    // Create global manager instance
    const manager = new ShogunSlogansManager();

    // Make classes globally available
    window.ShogunTypewriter = ShogunTypewriter;
    window.ShogunSlogan = ShogunSlogan;
    window.ShogunSlogansManager = manager;

    // Auto-initialize when DOM is ready
    $(document).ready(function() {
        manager.init();
        debugLog('Plugin loaded and initialized');
    });

    // Handle jQuery AJAX complete events
    $(document).ajaxComplete(function() {
        manager.reinitialize();
    });

    // Expose public API
    window.shogunSlogansAPI = {
        /**
         * Create a new typewriter instance
         * 
         * @param {HTMLElement} element Target element
         * @param {Object} options Configuration options
         * @returns {ShogunTypewriter}
         */
        createTypewriter(element, options = {}) {
            return new ShogunTypewriter(element, options);
        },
        
        /**
         * Get typewriter instance
         * 
         * @param {string|HTMLElement} identifier ID or element
         * @returns {ShogunTypewriter|null}
         */
        getTypewriter(identifier) {
            return manager.getTypewriter(identifier);
        },
        
        /**
         * Pause all animations
         */
        pauseAll() {
            manager.pauseAll();
        },
        
        /**
         * Resume all animations
         */
        resumeAll() {
            manager.resumeAll();
        },
        
        /**
         * Get plugin statistics
         * 
         * @returns {Object}
         */
        getStats() {
            return manager.getStats();
        },
        
        /**
         * Reinitialize plugin
         */
        reinit() {
            manager.reinitialize();
        }
    };

})(jQuery);
