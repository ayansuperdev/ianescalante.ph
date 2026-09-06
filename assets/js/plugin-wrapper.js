// assets/js/plugin-wrapper.js - Safe wrapper for plugin JavaScript
(function() {
    'use strict';
    
    window.pluginLoader = {
        loadedPlugins: {},
        
        // Safe way to load plugin scripts
        loadPluginScript: function(pluginName, scriptUrl, dependencies) {
            console.log('Loading plugin script:', pluginName, scriptUrl);
            
            return new Promise(function(resolve, reject) {
                // Check dependencies
                var depsReady = true;
                if (dependencies && dependencies.includes('jquery') && typeof jQuery === 'undefined') {
                    console.warn('Waiting for jQuery before loading', pluginName);
                    depsReady = false;
                    
                    window.addEventListener('jqueryReady', function() {
                        loadScript();
                    });
                }
                
                if (depsReady) {
                    loadScript();
                }
                
                function loadScript() {
                    var script = document.createElement('script');
                    script.src = scriptUrl;
                    script.onload = function() {
                        console.log('Plugin script loaded successfully:', pluginName);
                        window.pluginLoader.loadedPlugins[pluginName] = true;
                        resolve(script);
                    };
                    script.onerror = function() {
                        console.error('Failed to load plugin script:', pluginName, scriptUrl);
                        reject(new Error('Failed to load ' + scriptUrl));
                    };
                    document.head.appendChild(script);
                }
            });
        },
        
        // Safe initialization for plugins
        initPlugin: function(pluginName, initFunction) {
            try {
                // Check if jQuery is needed
                if (initFunction.toString().includes('jQuery') || initFunction.toString().includes('$')) {
                    if (typeof jQuery === 'undefined') {
                        window.addEventListener('jqueryReady', function() {
                            try {
                                initFunction(jQuery);
                                console.log('Plugin initialized after jQuery ready:', pluginName);
                            } catch (e) {
                                console.error('Plugin initialization failed:', pluginName, e);
                            }
                        });
                    } else {
                        initFunction(jQuery);
                        console.log('Plugin initialized immediately:', pluginName);
                    }
                } else {
                    initFunction();
                    console.log('Plugin initialized (no jQuery):', pluginName);
                }
            } catch (e) {
                console.error('Plugin initialization error:', pluginName, e);
            }
        }
    };
    
    console.log('Plugin wrapper loaded');
})();