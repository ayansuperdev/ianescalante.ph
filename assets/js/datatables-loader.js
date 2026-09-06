// assets/js/datatables-loader.js - UPDATED VERSION
(function() {
    'use strict';
    
    window.DataTablesLoader = {
        loaded: false,
        loading: false,
        callbacks: [],
        attempts: 0,
        maxAttempts: 3,
        
        // Load DataTables if not already loaded
        load: function(callback) {
            // If already loaded, execute callback immediately
            if (this.isReady()) {
                console.log('✅ DataTables already loaded, executing callback immediately');
                if (callback) callback();
                return;
            }
            
            // Add callback to queue
            if (callback) {
                this.callbacks.push(callback);
            }
            
            // If already loading, just wait
            if (this.loading) {
                console.log('⏳ DataTables already loading, queuing callback');
                return;
            }
            
            this.loading = true;
            this.attempts++;
            console.log('📊 Loading DataTables... Attempt ' + this.attempts);
            
            // First ensure jQuery is available
            if (typeof jQuery === 'undefined') {
                console.log('⏳ Waiting for jQuery before loading DataTables...');
                this.waitForJQuery(() => this.loadDataTables());
                return;
            }
            
            this.loadDataTables();
        },
        
        loadDataTables: function() {
            // Double-check if DataTables is already available
            if (this.isReady()) {
                console.log('✅ DataTables became available during load process');
                this.loading = false;
                this.loaded = true;
                this.executeCallbacks();
                return;
            }
            
            // Load DataTables CSS if not already loaded
            if (!document.querySelector('link[href*="datatables"]')) {
                const css = document.createElement('link');
                css.rel = 'stylesheet';
                css.href = 'https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css';
                document.head.appendChild(css);
                console.log('📊 DataTables CSS loaded');
            }
            
            // Load DataTables JS
            const script = document.createElement('script');
            script.src = 'https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js';
            script.setAttribute('data-datatables', 'loading');
            
            script.onload = () => {
                console.log('✅ DataTables script loaded, waiting for initialization...');
                this.waitForDataTablesInit();
            };
            
            script.onerror = () => {
                console.error('❌ Failed to load DataTables script');
                this.loading = false;
                this.retryOrFail();
            };
            
            document.head.appendChild(script);
        },
        
        waitForJQuery: function(callback) {
            let checkCount = 0;
            const maxChecks = 100; // 10 seconds max
            
            const checkJQuery = setInterval(() => {
                checkCount++;
                if (typeof jQuery !== 'undefined') {
                    clearInterval(checkJQuery);
                    console.log('✅ jQuery now available, loading DataTables');
                    callback();
                } else if (checkCount >= maxChecks) {
                    clearInterval(checkJQuery);
                    console.error('❌ jQuery loading timeout');
                    this.loading = false;
                }
            }, 100);
        },
        
        waitForDataTablesInit: function() {
            let checkCount = 0;
            const maxChecks = 100; // 10 seconds max
            
            const checkDataTables = setInterval(() => {
                checkCount++;
                if (this.isReady()) {
                    clearInterval(checkDataTables);
                    console.log('✅ DataTables fully initialized and ready');
                    this.loaded = true;
                    this.loading = false;
                    this.executeCallbacks();
                    
                    // Trigger custom event for other scripts
                    window.dispatchEvent(new CustomEvent('datatablesReady'));
                    
                    // Also set a global flag
                    window.dataTablesReady = true;
                } else if (checkCount >= maxChecks) {
                    clearInterval(checkDataTables);
                    console.error('❌ DataTables initialization timeout');
                    this.loading = false;
                    this.retryOrFail();
                }
            }, 100);
        },
        
        retryOrFail: function() {
            if (this.attempts < this.maxAttempts) {
                console.log('🔄 Retrying DataTables load in ' + (this.attempts * 1000) + 'ms...');
                setTimeout(() => {
                    this.load();
                }, this.attempts * 1000);
            } else {
                console.error('❌ Max DataTables load attempts reached');
                this.executeCallbacks(); // Still execute callbacks but they'll need to handle failure
            }
        },
        
        // Execute all queued callbacks
        executeCallbacks: function() {
            console.log('🔧 Executing ' + this.callbacks.length + ' DataTables callbacks');
            const callbacksToExecute = [...this.callbacks];
            this.callbacks = []; // Clear the queue
            
            callbacksToExecute.forEach(callback => {
                try {
                    callback();
                } catch (e) {
                    console.error('Error in DataTables callback:', e);
                }
            });
        },
        
        // Check if DataTables is ready - IMPROVED DETECTION
        isReady: function() {
            return typeof jQuery !== 'undefined' && 
                   (typeof jQuery.fn.dataTable !== 'undefined' ||
                    typeof jQuery.fn.DataTable !== 'undefined' ||
                    (jQuery.fn && (jQuery.fn.dataTable || jQuery.fn.DataTable)));
        },
        
        // Wait for DataTables to be ready
        whenReady: function(callback) {
            if (this.isReady()) {
                callback();
            } else {
                console.log('⏳ Queueing callback until DataTables is ready...');
                this.load(callback);
            }
        },
        
        // Safe DataTables initialization - USE THIS IN PLUGINS
        safeInit: function(tableSelector, options = {}) {
            this.whenReady(() => {
                try {
                    if (typeof jQuery !== 'undefined' && jQuery(tableSelector).length) {
                        console.log('🔧 Initializing DataTable on:', tableSelector);
                        // Try all possible DataTables initialization methods
                        const $table = jQuery(tableSelector);
                        
                        if (typeof jQuery.fn.DataTable !== 'undefined') {
                            $table.DataTable(options);
                        } else if (typeof jQuery.fn.dataTable !== 'undefined') {
                            $table.dataTable(options);
                        } else if ($table.dataTable) {
                            $table.dataTable(options);
                        } else {
                            console.warn('DataTables initialization methods not available');
                        }
                    } else {
                        console.warn('Table not found or jQuery not available:', tableSelector);
                    }
                } catch (e) {
                    console.error('Error initializing DataTable on', tableSelector, ':', e);
                }
            });
        },
        
        // Check if a table is already initialized
        isTableInitialized: function(tableSelector) {
            if (!this.isReady()) return false;
            try {
                const $table = jQuery(tableSelector);
                return $table.hasClass('dataTable') || 
                       $table.hasClass('dt-loaded') ||
                       $table.hasClass('dataTable') ||
                       ($table.closest('.dataTables_wrapper').length > 0);
            } catch (e) {
                return false;
            }
        }
    };
    
    // Auto-detect and pre-load DataTables if needed
    function autoDetectDataTablesNeed() {
        // Check for common DataTables indicators
        const indicators = [
            '[class*="datatable"]',
            '[data-datatables]',
            '[id*="datatable"]',
            '.dataTable',
            'table'
        ];
        
        for (const selector of indicators) {
            if (document.querySelector(selector)) {
                console.log('🔍 Auto-detected possible DataTables need via selector:', selector);
                return true;
            }
        }
        
        // Check for plugin scripts that might need DataTables
        const pluginScripts = document.querySelectorAll('script[src*="plugins/"]');
        for (const script of pluginScripts) {
            if (script.src.includes('datatable') || script.src.includes('DataTable')) {
                console.log('🔍 Auto-detected DataTables need via plugin script:', script.src);
                return true;
            }
        }
        
        return false;
    }
    
    // Auto-initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🔧 DataTables Loader: DOM ready');
            if (autoDetectDataTablesNeed()) {
                console.log('🔧 Auto-loading DataTables due to detected need');
                DataTablesLoader.load();
            }
        });
    } else {
        console.log('🔧 DataTables Loader: DOM already ready');
        if (autoDetectDataTablesNeed()) {
            console.log('🔧 Auto-loading DataTables due to detected need');
            DataTablesLoader.load();
        }
    }
    
    console.log('🔧 DataTables Loader installed - UPDATED VERSION');
})();