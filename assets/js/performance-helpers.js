/**
 * BloxAuth Frontend Performance Helpers
 * 
 * This file contains helper functions for lazy loading, asset optimization,
 * and performance monitoring.
 */

// ============================================================================
// Lazy Loading for Charts
// ============================================================================

/**
 * Lazy load Chart.js library and initialize charts
 */
const ChartLoader = {
    loaded: false,
    charts: [],

    /**
     * Load Chart.js from CDN
     */
    loadLibrary() {
        return new Promise((resolve, reject) => {
            if (this.loaded || typeof Chart !== 'undefined') {
                this.loaded = true;
                resolve();
                return;
            }

            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js';
            script.async = true;
            script.onload = () => {
                this.loaded = true;
                resolve();
            };
            script.onerror = () => reject(new Error('Failed to load Chart.js'));
            document.head.appendChild(script);
        });
    },

    /**
     * Initialize a chart when it becomes visible
     * @param {HTMLElement} canvas - Canvas element for the chart
     * @param {Object} config - Chart.js configuration
     */
    async initChart(canvas, config) {
        if (!canvas) return;

        try {
            await this.loadLibrary();
            const chart = new Chart(canvas, config);
            this.charts.push(chart);
            return chart;
        } catch (error) {
            console.error('Error initializing chart:', error);
        }
    },

    /**
     * Setup intersection observer for lazy chart loading
     * @param {string} selector - CSS selector for chart containers
     */
    setupLazyLoading(selector = '.chart-container') {
        const containers = document.querySelectorAll(selector);
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const canvas = entry.target.querySelector('canvas');
                    const config = JSON.parse(entry.target.dataset.chartConfig || '{}');
                    
                    if (canvas && config) {
                        this.initChart(canvas, config);
                        observer.unobserve(entry.target);
                    }
                }
            });
        }, {
            rootMargin: '50px'
        });

        containers.forEach(container => observer.observe(container));
    },

    /**
     * Destroy all charts (cleanup)
     */
    destroyAll() {
        this.charts.forEach(chart => chart.destroy());
        this.charts = [];
    }
};

// ============================================================================
// Image Lazy Loading
// ============================================================================

/**
 * Lazy load images using Intersection Observer
 */
const ImageLazyLoader = {
    /**
     * Setup lazy loading for images
     * @param {string} selector - CSS selector for images with data-src attribute
     */
    setup(selector = 'img[data-src]') {
        const images = document.querySelectorAll(selector);
        
        const imageObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    const src = img.dataset.src;
                    const srcset = img.dataset.srcset;

                    if (src) {
                        img.src = src;
                        img.removeAttribute('data-src');
                    }

                    if (srcset) {
                        img.srcset = srcset;
                        img.removeAttribute('data-srcset');
                    }

                    img.classList.add('loaded');
                    imageObserver.unobserve(img);
                }
            });
        }, {
            rootMargin: '50px'
        });

        images.forEach(img => imageObserver.observe(img));
    }
};

// ============================================================================
// Performance Monitoring
// ============================================================================

/**
 * Performance monitoring utilities
 */
const PerformanceMonitor = {
    /**
     * Log page load time
     */
    logPageLoad() {
        if (window.performance && window.performance.timing) {
            const timing = window.performance.timing;
            const loadTime = timing.loadEventEnd - timing.navigationStart;
            const domReadyTime = timing.domContentLoadedEventEnd - timing.navigationStart;

            console.log(`Page Load Time: ${loadTime}ms`);
            console.log(`DOM Ready Time: ${domReadyTime}ms`);

            // Send to analytics if available
            if (typeof gtag !== 'undefined') {
                gtag('event', 'timing_complete', {
                    name: 'page_load',
                    value: loadTime,
                    event_category: 'Performance'
                });
            }
        }
    },

    /**
     * Measure function execution time
     * @param {Function} fn - Function to measure
     * @param {string} label - Label for the measurement
     */
    async measureAsync(fn, label = 'Operation') {
        const start = performance.now();
        const result = await fn();
        const duration = performance.now() - start;
        console.log(`${label} took ${duration.toFixed(2)}ms`);
        return result;
    },

    /**
     * Measure sync function execution time
     * @param {Function} fn - Function to measure
     * @param {string} label - Label for the measurement
     */
    measure(fn, label = 'Operation') {
        const start = performance.now();
        const result = fn();
        const duration = performance.now() - start;
        console.log(`${label} took ${duration.toFixed(2)}ms`);
        return result;
    },

    /**
     * Check if cache is being used (from API responses)
     */
    checkCacheUsage() {
        if (window.performance && window.performance.getEntriesByType) {
            const resources = window.performance.getEntriesByType('resource');
            const cachedResources = resources.filter(r => 
                r.transferSize === 0 && r.decodedBodySize > 0
            );
            
            console.log(`Cached resources: ${cachedResources.length}/${resources.length}`);
        }
    }
};

// ============================================================================
// Asset Preloading
// ============================================================================

/**
 * Preload critical assets
 */
const AssetPreloader = {
    /**
     * Preload an image
     * @param {string} src - Image source URL
     */
    preloadImage(src) {
        const link = document.createElement('link');
        link.rel = 'preload';
        link.as = 'image';
        link.href = src;
        document.head.appendChild(link);
    },

    /**
     * Preload multiple images
     * @param {string[]} sources - Array of image URLs
     */
    preloadImages(sources) {
        sources.forEach(src => this.preloadImage(src));
    },

    /**
     * Preload a script
     * @param {string} src - Script source URL
     */
    preloadScript(src) {
        const link = document.createElement('link');
        link.rel = 'preload';
        link.as = 'script';
        link.href = src;
        document.head.appendChild(link);
    },

    /**
     * Preload a stylesheet
     * @param {string} href - Stylesheet URL
     */
    preloadStylesheet(href) {
        const link = document.createElement('link');
        link.rel = 'preload';
        link.as = 'style';
        link.href = href;
        document.head.appendChild(link);
    }
};

// ============================================================================
// Debounce and Throttle Utilities
// ============================================================================

/**
 * Debounce function calls
 * @param {Function} func - Function to debounce
 * @param {number} wait - Wait time in milliseconds
 */
function debounce(func, wait = 300) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Throttle function calls
 * @param {Function} func - Function to throttle
 * @param {number} limit - Limit in milliseconds
 */
function throttle(func, limit = 300) {
    let inThrottle;
    return function executedFunction(...args) {
        if (!inThrottle) {
            func(...args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// ============================================================================
// Initialize on DOM Ready
// ============================================================================

document.addEventListener('DOMContentLoaded', () => {
    // Setup lazy loading for images
    ImageLazyLoader.setup();

    // Setup lazy loading for charts
    ChartLoader.setupLazyLoading();

    // Log page load performance after everything is loaded
    window.addEventListener('load', () => {
        setTimeout(() => {
            PerformanceMonitor.logPageLoad();
            PerformanceMonitor.checkCacheUsage();
        }, 0);
    });
});

// ============================================================================
// Export utilities
// ============================================================================

if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        ChartLoader,
        ImageLazyLoader,
        PerformanceMonitor,
        AssetPreloader,
        debounce,
        throttle
    };
}
