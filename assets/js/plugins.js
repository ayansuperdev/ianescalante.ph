// assets/js/plugins.js
document.addEventListener('DOMContentLoaded', function() {
    // Plugin upload form handling
    const uploadForm = document.getElementById('uploadPluginForm');
    if (uploadForm) {
        uploadForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const progressBar = document.querySelector('.progress-bar');
            const statusText = document.querySelector('.status-text');
            const uploadProgress = document.querySelector('.upload-progress');
            
            // Show progress UI
            uploadProgress.classList.remove('d-none');
            statusText.textContent = 'Uploading...';
            
            const xhr = new XMLHttpRequest();
            xhr.open('POST', this.action, true);
            
            // Upload progress
            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    const percentComplete = (e.loaded / e.total) * 100;
                    progressBar.style.width = percentComplete + '%';
                }
            });
            
            // Request completed
            xhr.addEventListener('load', function() {
                if (xhr.status >= 200 && xhr.status < 300) {
                    // Success - redirect will happen via server
                } else {
                    statusText.textContent = 'Upload failed. Please try again.';
                    progressBar.style.backgroundColor = 'var(--danger-color)';
                }
            });
            
            // Handle errors
            xhr.addEventListener('error', function() {
                statusText.textContent = 'Upload failed. Please try again.';
                progressBar.style.backgroundColor = 'var(--danger-color)';
            });
            
            xhr.send(formData);
        });
    }
});

// For debugging - remove in production
console.log('Plugin JS functions available:');
console.log(Object.keys(window).filter(key => 
    typeof window[key] === 'function' && 
    key.startsWith('plugin_')
));

// Trigger alert to confirm loading
if(typeof window.pluginDebug !== 'undefined') {
    alert('Plugin JS loaded successfully! Available functions: ' + 
        Object.keys(window).filter(key => 
            typeof window[key] === 'function' && 
            key.startsWith('plugin_')
        ).join(', ')
    );
}