// Date Filter Handler for IT Service Request
(function() {
    'use strict';
    
    console.log('Date Filter Handler loaded');
    
    // Wait for DOM to be ready
    function initDateFilter() {
        const startDate = document.getElementById('startDate');
        const endDate = document.getElementById('endDate');
        const clearDateFilter = document.getElementById('clearDateFilter');
        
        if (!startDate || !endDate || !clearDateFilter) {
            console.log('Date filter elements not found');
            return;
        }
        
        // Add event listeners
        startDate.addEventListener('change', function() {
            console.log('Start date changed:', this.value);
            triggerRequestsReload();
        });
        
        endDate.addEventListener('change', function() {
            console.log('End date changed:', this.value);
            triggerRequestsReload();
        });
        
        clearDateFilter.addEventListener('click', function() {
            console.log('Clear date filter clicked');
            startDate.value = '';
            endDate.value = '';
            triggerRequestsReload();
        });
        
        console.log('Date filter initialized successfully');
    }
    
    // Function to trigger requests reload
    function triggerRequestsReload() {
        // Try to call the main app's loadRequests function
        if (window.app && typeof window.app.loadRequests === 'function') {
            console.log('Calling window.app.loadRequests(1)');
            window.app.loadRequests(1);
        } else {
            // Fallback: try to trigger search input change event
            const requestSearch = document.getElementById('requestSearch');
            if (requestSearch) {
                console.log('Triggering search input change event');
                requestSearch.dispatchEvent(new Event('input'));
            } else {
                // Last resort: reload the page
                console.log('App and search input not available, reloading page');
                window.location.reload();
            }
        }
    }
    
    // Initialize when DOM is ready
    function initializeDateFilter() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initDateFilter);
        } else {
            initDateFilter();
        }
    }
    
    // Wait a bit for the main app to be fully initialized
    setTimeout(initializeDateFilter, 100);
})();
