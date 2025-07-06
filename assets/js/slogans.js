/**
 * Shogun Slogans - Frontend JavaScript
 * Handles typewriter effects and slogan animations
 */
(function($) {
    'use strict';

    /**
     * Typewriter Text Animation Class
     */
    class ShogunTypewriter {
        constructor(element, options = {}) {
            this.element = element;
            this.textElement = element.querySelector('.typewriter-text');
            this.cursorElement = element.querySelector('.typewriter-cursor');
            
            // Get settings from data attributes or options
            this.settings = {
                text: options.text || element.dataset.text || 'Sample text...',
                speed: parseInt(options.speed || element.dataset.speed) || 100,
                cursor: options.cursor || element.dataset.cursor || '|',
                loop: options.loop !== false && element.dataset.loop !== 'false',
                delay: parseInt(options.delay || element.dataset.delay) || 0,
                deleteSpeed: parseInt(options.deleteSpeed || element.dataset.deleteSpeed) || 50,
                pauseEnd: parseInt(options.pauseEnd || element.dataset.pauseEnd) || 2000,
                pauseStart: parseInt(options.pauseStart || element.dataset.pauseStart) || 1000,
                ...options
            };
            
            this.currentIndex = 0;
            this.isTyping = false;
            this.isDeleting = false;
            this.timeoutId = null;
            this.isVisible = false;
            
            this.init();
        }
        
        init() {
            // Set up initial state
            if (this.textElement) {
                this.textElement.textContent = '';
            }
            
            if (this.cursorElement) {
                this.cursorElement.textContent = this.settings.cursor;
            }
            
            // Check if element is in viewport before starting
            this.checkVisibility();
            
            // Set up intersection observer for performance
            if ('IntersectionObserver' in window) {
                this.setupIntersectionObserver();
            } else {
                // Fallback for older browsers
                this.startAnimation();
            }
        }
        
        setupIntersectionObserver() {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting && !this.isVisible) {
                        this.isVisible = true;
                        this.startAnimation();
                    }
                });
            }, {
                threshold: 0.1,
                rootMargin: '50px'
            });
            
            observer.observe(this.element);
        }
        
        checkVisibility() {
            const rect = this.element.getBoundingClientRect();
            const windowHeight = window.innerHeight || document.documentElement.clientHeight;
            
            if (rect.top < windowHeight && rect.bottom > 0) {
                this.isVisible = true;
                return true;
            }
            return false;
        }
        
        startAnimation() {
            if (this.settings.delay > 0) {
                this.timeoutId = setTimeout(() => {
                    this.startTyping();
                }, this.settings.delay);
            } else {
                this.startTyping();
            }
        }
        
        startTyping() {
            if (this.isTyping || this.isDeleting) return;
            
            this.isTyping = true;
            this.typeCharacter();
        }
        
        typeCharacter() {
            if (!this.isTyping) return;
            
            const currentText = this.settings.text;
            const displayText = currentText.substring(0, this.currentIndex + 1);
            
            if (this.textElement) {
                this.textElement.textContent = displayText;
            }
            
            this.currentIndex++;
            
            if (this.currentIndex < currentText.length) {
                this.timeoutId = setTimeout(() => this.typeCharacter(), this.settings.speed);
            } else {
                this.isTyping = false;
                
                if (this.settings.loop) {
                    this.timeoutId = setTimeout(() => this.startDeleting(), this.settings.pauseEnd);
                }
            }
        }
        
        startDeleting() {
            if (this.isTyping || this.isDeleting) return;
            
            this.isDeleting = true;
            this.deleteCharacter();
        }
        
        deleteCharacter() {
            if (!this.isDeleting) return;
            
            const currentText = this.textElement ? this.textElement.textContent : '';
            
            if (currentText.length > 0) {
                if (this.textElement) {
                    this.textElement.textContent = currentText.substring(0, currentText.length - 1);
                }
                this.currentIndex--;
                
                this.timeoutId = setTimeout(() => this.deleteCharacter(), this.settings.deleteSpeed);
            } else {
                this.isDeleting = false;
                this.currentIndex = 0;
                
                this.timeoutId = setTimeout(() => this.startTyping(), this.settings.pauseStart);
            }
        }
        
        destroy() {
            if (this.timeoutId) {
                clearTimeout(this.timeoutId);
            }
            this.isTyping = false;
            this.isDeleting = false;
        }
        
        pause() {
            this.destroy();
        }
        
        resume() {
            if (!this.isTyping && !this.isDeleting) {
                this.startAnimation();
            }
        }
        
        updateText(newText) {
            this.destroy();
            this.settings.text = newText;
            this.currentIndex = 0;
            if (this.textElement) {
                this.textElement.textContent = '';
            }
            this.startAnimation();
        }
    }
    
    /**
     * Slogan Animation Handler
     */
    class ShogunSlogan {
        constructor(element) {
            this.element = element;
            this.init();
        }
        
        init() {
            // Add loaded class for CSS animations
            setTimeout(() => {
                this.element.classList.add('loaded');
            }, 100);
        }
    }
    
    /**
     * Plugin Initialization
     */
    const ShogunSlogansPlugin = {
        typewriters: [],
        slogans: [],
        
        init() {
            this.initializeTypewriters();
            this.initializeSlogans();
            this.bindEvents();
        },
        
        initializeTypewriters() {
            const typewriterElements = document.querySelectorAll('.shogun-typewriter');
            
            typewriterElements.forEach(element => {
                if (!element.shogunTypewriter) {
                    const typewriter = new ShogunTypewriter(element);
                    element.shogunTypewriter = typewriter;
                    this.typewriters.push(typewriter);
                }
            });
        },
        
        initializeSlogans() {
            const sloganElements = document.querySelectorAll('.shogun-slogan');
            
            sloganElements.forEach(element => {
                if (!element.shogunSlogan) {
                    const slogan = new ShogunSlogan(element);
                    element.shogunSlogan = slogan;
                    this.slogans.push(slogan);
                }
            });
        },
        
        bindEvents() {
            // Handle page visibility changes
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    this.pauseAll();
                } else {
                    this.resumeAll();
                }
            });
            
            // Handle window focus/blur
            window.addEventListener('blur', () => this.pauseAll());
            window.addEventListener('focus', () => this.resumeAll());
            
            // Handle AJAX content loading
            $(document).on('ready', () => {
                this.reinitialize();
            });
            
            // Handle dynamic content (for themes that load content via AJAX)
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
        },
        
        pauseAll() {
            this.typewriters.forEach(typewriter => {
                if (typewriter.pause) typewriter.pause();
            });
        },
        
        resumeAll() {
            this.typewriters.forEach(typewriter => {
                if (typewriter.resume) typewriter.resume();
            });
        },
        
        reinitialize() {
            setTimeout(() => {
                this.initializeTypewriters();
                this.initializeSlogans();
            }, 100);
        },
        
        destroy() {
            this.typewriters.forEach(typewriter => {
                if (typewriter.destroy) typewriter.destroy();
            });
            this.typewriters = [];
            this.slogans = [];
        }
    };
    
    // Make classes globally available
    window.ShogunTypewriter = ShogunTypewriter;
    window.ShogunSlogan = ShogunSlogan;
    window.ShogunSlogansPlugin = ShogunSlogansPlugin;
    
    // Auto-initialize when DOM is ready
    $(document).ready(function() {
        ShogunSlogansPlugin.init();
    });
    
    // Handle jQuery AJAX complete events
    $(document).ajaxComplete(function() {
        ShogunSlogansPlugin.reinitialize();
    });
    
    // Accessibility: Respect reduced motion preference
    if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        // Disable animations for users who prefer reduced motion
        const style = document.createElement('style');
        style.textContent = `
            .shogun-typewriter .typewriter-cursor {
                animation: none !important;
                opacity: 0.7;
            }
        `;
        document.head.appendChild(style);
    }
    
})(jQuery);
