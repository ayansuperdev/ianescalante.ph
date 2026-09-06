// assets/js/plugin-interceptor.js - ENHANCED VERSION
(function() {
    'use strict';
    
    // Define missing global variables that plugins expect
    if (typeof window.ajaxurl === 'undefined') {
        window.ajaxurl = '/admin-ajax.php';
        console.log('🔧 Defined missing global: ajaxurl =', window.ajaxurl);
    }
    
    if (typeof window.ajax_assign === 'undefined') {
        window.ajax_assign = window.ajaxurl;
        console.log('🔧 Defined missing global: ajax_assign =', window.ajax_assign);
    }
    
    if (typeof window.ajax_object === 'undefined') {
        window.ajax_object = {
            ajaxurl: window.ajaxurl,
            nonce: 'plugin-nonce-' + Date.now()
        };
        console.log('🔧 Defined missing global: ajax_object');
    }
    
    // Select2 loader for plugins that need it
    window.Select2Loader = {
        loaded: false,
        loading: false,
        callbacks: [],
        
        load: function(callback) {
            if (this.loaded) {
                if (callback) callback();
                return;
            }
            
            if (callback) {
                this.callbacks.push(callback);
            }
            
            if (this.loading) return;
            
            this.loading = true;
            console.log('🎯 Loading Select2 for plugins...');
            
            // Load Select2 CSS
            if (!document.querySelector('link[href*="select2"]')) {
                const css = document.createElement('link');
                css.rel = 'stylesheet';
                css.href = 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css';
                document.head.appendChild(css);
                console.log('🎯 Select2 CSS loaded');
            }
            
            // Load Select2 JS
            const script = document.createElement('script');
            script.src = 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js';
            
            script.onload = () => {
                console.log('✅ Select2 loaded successfully');
                this.loaded = true;
                this.loading = false;
                this.callbacks.forEach(cb => cb());
                this.callbacks = [];
            };
            
            script.onerror = () => {
                console.error('❌ Failed to load Select2');
                this.loading = false;
            };
            
            document.head.appendChild(script);
        },
        
        whenReady: function(callback) {
            if (this.loaded) {
                callback();
            } else {
                this.load(callback);
            }
        }
    };
    
    // Enhanced dependency checker
    function checkDependencies(callbackStr) {
        const dependencies = {
            datatables: /\.(DataTable|dataTable|isDataTable)\(/.test(callbackStr),
            select2: /\.select2\(/.test(callbackStr),
            jquery: /jQuery|\$\(|\$\./.test(callbackStr)
        };
        
        return dependencies;
    }
    
    // Safe execute with dependencies
    function safeExecuteWithDependencies(callback, dependencies) {
        const executeCallback = () => {
            try {
                callback.call(document, jQuery);
            } catch (e) {
                console.error('Error in intercepted callback:', e);
            }
        };
        
        let depsLoaded = 0;
        const totalDeps = Object.values(dependencies).filter(Boolean).length;
        
        if (totalDeps === 0) {
            executeCallback();
            return;
        }
        
        const checkDepsLoaded = () => {
            depsLoaded++;
            if (depsLoaded >= totalDeps) {
                executeCallback();
            }
        };
        
        if (dependencies.datatables && typeof DataTablesLoader !== 'undefined') {
            DataTablesLoader.whenReady(checkDepsLoaded);
        } else if (dependencies.datatables) {
            console.warn('DataTables needed but DataTablesLoader not available');
            checkDepsLoaded();
        }
        
        if (dependencies.select2 && typeof Select2Loader !== 'undefined') {
            Select2Loader.whenReady(checkDepsLoaded);
        } else if (dependencies.select2) {
            console.warn('Select2 needed but Select2Loader not available');
            checkDepsLoaded();
        }
        
        if (dependencies.jquery && typeof jQuery === 'undefined') {
            console.warn('jQuery needed but not available');
            checkDepsLoaded();
        }
    }
    
    // Intercept and fix common plugin patterns
    const originalReady = jQuery && jQuery.fn && jQuery.fn.ready;
    
    if (jQuery && originalReady) {
        // Wrap jQuery's ready function to ensure dependencies are loaded
        jQuery.fn.ready = function(callback) {
            return originalReady.call(this, function() {
                const callbackStr = callback.toString();
                const dependencies = checkDependencies(callbackStr);
                
                const hasDependencies = Object.values(dependencies).some(Boolean);
                
                if (hasDependencies) {
                    console.log('🔧 Intercepted jQuery.ready callback with dependencies:', dependencies);
                    safeExecuteWithDependencies(callback, dependencies);
                } else {
                    // Execute normally
                    try {
                        callback.call(document, jQuery);
                    } catch (e) {
                        console.error('Error in ready callback:', e);
                    }
                }
            });
        };
        
        console.log('🔧 jQuery ready interceptor installed');
    }
    
    // Fix common plugin initialization patterns
    function fixCommonPluginIssues() {
        const originalAddEventListener = window.addEventListener;
        window.addEventListener = function(type, listener, options) {
            if (type === 'DOMContentLoaded' && typeof listener === 'function') {
                const wrappedListener = function() {
                    const listenerStr = listener.toString();
                    const dependencies = checkDependencies(listenerStr);
                    const hasDependencies = Object.values(dependencies).some(Boolean);
                    
                    if (hasDependencies) {
                        console.log('🔧 Intercepted DOMContentLoaded listener with dependencies:', dependencies);
                        safeExecuteWithDependencies(listener, dependencies);
                    } else {
                        try {
                            listener.call(window, new Event('DOMContentLoaded'));
                        } catch (e) {
                            console.error('Error in DOMContentLoaded listener:', e);
                        }
                    }
                };
                
                return originalAddEventListener.call(this, type, wrappedListener, options);
            }
            return originalAddEventListener.call(this, type, listener, options);
        };
        
        console.log('🔧 DOMContentLoaded interceptor installed');
    }
    
    // Initialize when safe
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', fixCommonPluginIssues);
    } else {
        fixCommonPluginIssues();
    }
    
    // Global function for plugins to safely initialize
    window.safePluginInit = function(pluginName, initFunction) {
        console.log('🔧 Safe plugin init requested for:', pluginName);
        
        const initStr = initFunction.toString();
        const dependencies = checkDependencies(initStr);
        
        const hasDependencies = Object.values(dependencies).some(Boolean);
        
        if (hasDependencies) {
            console.log('🔧 Plugin needs dependencies:', dependencies);
            safeExecuteWithDependencies(initFunction, dependencies);
        } else {
            // No special dependencies needed
            try {
                initFunction();
                console.log('✅ Plugin initialized:', pluginName);
            } catch (e) {
                console.error('❌ Plugin initialization failed:', pluginName, e);
            }
        }
    };
    
    console.log('🔧 Plugin Interceptor installed - ENHANCED VERSION');
})();