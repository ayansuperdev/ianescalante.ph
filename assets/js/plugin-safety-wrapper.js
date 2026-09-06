// assets/js/plugin-safety-wrapper.js - UPDATED VERSION
(function() {
    'use strict';
    
    window.pluginSafetySystem = {
        plugins: {},
        dependencies: {
            jquery: { loaded: false, checking: false },
            datatables: { loaded: false, checking: false },
            select2: { loaded: false, checking: false }
        },
        
        // Register a plugin
        register: function(pluginName, scriptUrl, dependencies = []) {
            const pluginId = pluginName + '-' + Math.random().toString(36).substr(2, 9);
            
            this.plugins[pluginId] = {
                name: pluginName,
                url: scriptUrl,
                loaded: false,
                dependencies: dependencies,
                element: null,
                registeredAt: Date.now()
            };
            
            console.log('🔧 Plugin registered:', pluginName, 'Deps:', dependencies, 'URL:', scriptUrl);
            
            // Enhanced dependency checking
            this.enhancedDependencyCheck(pluginId);
        },
        
        // Enhanced dependency checking
        enhancedDependencyCheck: function(pluginId) {
            const plugin = this.plugins[pluginId];
            if (!plugin || plugin.loaded) return;
            
            // Check if plugin needs AJAX variables
            const needsAjaxVars = this.doesPluginNeedAjax(plugin.url, plugin.dependencies);
            if (needsAjaxVars) {
                this.ensureAjaxVariables();
            }
            
            const allDepsMet = this.areDependenciesMet(plugin.dependencies);
            
            if (allDepsMet) {
                console.log('✅ All dependencies met for:', plugin.name);
                this.loadPlugin(pluginId);
            } else {
                console.log('⏳ Waiting for dependencies for:', plugin.name, 
                           'Missing:', this.getMissingDependencies(plugin.dependencies));
                this.waitForDependencies(pluginId);
            }
        },
        
        // Check if plugin needs AJAX variables by URL pattern
        doesPluginNeedAjax: function(scriptUrl, dependencies) {
            const ajaxPatterns = [
                'ajaxurl', 'ajax_assign', 'ajax_object',
                'generate-revenue', 'live-search', 'status-polling'
            ];
            
            return ajaxPatterns.some(pattern => 
                scriptUrl.includes(pattern) || dependencies.includes('ajax')
            );
        },
        
        // Ensure AJAX variables are defined
        ensureAjaxVariables: function() {
            if (typeof window.ajaxurl === 'undefined') {
                window.ajaxurl = '/admin-ajax.php';
                console.log('🔧 Safety System defined ajaxurl:', window.ajaxurl);
            }
            if (typeof window.ajax_assign === 'undefined') {
                window.ajax_assign = window.ajaxurl;
                console.log('🔧 Safety System defined ajax_assign');
            }
        },
        
        getMissingDependencies: function(dependencies) {
            const missing = [];
            for (const dep of dependencies) {
                if (!this.isDependencyReady(dep)) {
                    missing.push(dep);
                }
            }
            return missing;
        },
        
        areDependenciesMet: function(dependencies) {
            for (const dep of dependencies) {
                if (!this.isDependencyReady(dep)) {
                    return false;
                }
            }
            return true;
        },
        
        isDependencyReady: function(dep) {
            switch (dep) {
                case 'jquery':
                    return typeof jQuery !== 'undefined';
                case 'datatables':
                    return typeof DataTablesLoader !== 'undefined' && DataTablesLoader.isReady();
                case 'select2':
                    return typeof Select2Loader !== 'undefined' && Select2Loader.loaded;
                default:
                    return true;
            }
        },
        
        waitForDependencies: function(pluginId) {
            const plugin = this.plugins[pluginId];
            if (!plugin) return;
            
            const checkDeps = () => {
                if (this.areDependenciesMet(plugin.dependencies)) {
                    console.log('✅ Dependencies now available for:', plugin.name);
                    this.loadPlugin(pluginId);
                } else {
                    this.ensureDependenciesLoading(plugin.dependencies);
                    setTimeout(checkDeps, 200);
                }
            };
            
            checkDeps();
        },
        
        ensureDependenciesLoading: function(dependencies) {
            for (const dep of dependencies) {
                switch (dep) {
                    case 'jquery':
                        this.ensureJQueryLoading();
                        break;
                    case 'datatables':
                        this.ensureDataTablesLoading();
                        break;
                    case 'select2':
                        this.ensureSelect2Loading();
                        break;
                }
            }
        },
        
        ensureJQueryLoading: function() {
            if (typeof jQuery !== 'undefined' || this.dependencies.jquery.checking) return;
            
            this.dependencies.jquery.checking = true;
            console.log('⏳ Ensuring jQuery is loading...');
            
            const existingScripts = document.querySelectorAll('script[src*="jquery"]');
            if (existingScripts.length > 0) return;
            
            const script = document.createElement('script');
            script.src = 'https://code.jquery.com/jquery-3.6.0.min.js';
            script.integrity = 'sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=';
            script.crossOrigin = 'anonymous';
            script.onload = () => {
                this.dependencies.jquery.loaded = true;
                this.dependencies.jquery.checking = false;
                console.log('✅ jQuery loaded via safety system');
                this.onDependencyReady('jquery');
            };
            document.head.appendChild(script);
        },
        
        ensureDataTablesLoading: function() {
            if ((typeof DataTablesLoader !== 'undefined' && DataTablesLoader.isReady()) || 
                this.dependencies.datatables.checking) return;
            
            this.dependencies.datatables.checking = true;
            console.log('⏳ Ensuring DataTables is loading...');
            
            if (typeof DataTablesLoader !== 'undefined') {
                DataTablesLoader.load(() => {
                    this.dependencies.datatables.loaded = true;
                    this.dependencies.datatables.checking = false;
                    console.log('✅ DataTables now available via loader');
                    this.onDependencyReady('datatables');
                });
            } else {
                console.log('DataTablesLoader not available in safety system');
                this.dependencies.datatables.checking = false;
            }
        },
        
        ensureSelect2Loading: function() {
            if ((typeof Select2Loader !== 'undefined' && Select2Loader.loaded) || 
                this.dependencies.select2.checking) return;
            
            this.dependencies.select2.checking = true;
            console.log('⏳ Ensuring Select2 is loading...');
            
            if (typeof Select2Loader !== 'undefined') {
                Select2Loader.load(() => {
                    this.dependencies.select2.loaded = true;
                    this.dependencies.select2.checking = false;
                    console.log('✅ Select2 now available via loader');
                    this.onDependencyReady('select2');
                });
            } else {
                console.log('Select2Loader not available in safety system');
                this.dependencies.select2.checking = false;
            }
        },
        
        onDependencyReady: function(depName) {
            console.log('✅ Dependency ready:', depName);
            for (const pluginId in this.plugins) {
                const plugin = this.plugins[pluginId];
                if (!plugin.loaded && plugin.dependencies.includes(depName)) {
                    console.log('🔄 Re-checking plugin after dependency ready:', plugin.name);
                    this.enhancedDependencyCheck(pluginId);
                }
            }
        },
        
        loadPlugin: function(pluginId) {
            const plugin = this.plugins[pluginId];
            if (!plugin || plugin.loaded) return;
            
            console.log('🚀 Loading plugin:', plugin.name);
            
            const script = document.createElement('script');
            script.src = plugin.url;
            script.setAttribute('data-plugin-name', plugin.name);
            script.setAttribute('data-plugin-id', pluginId);
            
            script.onload = () => {
                plugin.loaded = true;
                console.log('✅ Plugin loaded successfully:', plugin.name);
                this.executePluginInit(plugin.name);
            };
            
            script.onerror = () => {
                console.error('❌ Failed to load plugin:', plugin.name);
            };
            
            document.head.appendChild(script);
            plugin.element = script;
        },
        
        executePluginInit: function(pluginName) {
            const initFunctionName = 'init' + pluginName.replace(/[^a-zA-Z0-9]/g, '');
            if (typeof window[initFunctionName] === 'function') {
                console.log('🔧 Executing plugin init function:', initFunctionName);
                try {
                    window[initFunctionName]();
                } catch (e) {
                    console.error('Error in plugin init function:', e);
                }
            }
        },
        
        loadAllReady: function() {
            console.log('🔧 Loading all ready plugins...');
            for (const pluginId in this.plugins) {
                this.enhancedDependencyCheck(pluginId);
            }
        }
    };
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            console.log('🔧 Plugin Safety System: DOM ready');
            window.pluginSafetySystem.loadAllReady();
        });
    } else {
        window.pluginSafetySystem.loadAllReady();
    }
    
    console.log('🔧 Plugin Safety System installed - UPDATED VERSION');
})();