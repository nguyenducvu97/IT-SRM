// Date Filter functionality for IT Service Request
document.addEventListener('DOMContentLoaded', function() {
    console.log('Initializing date filter...');
    
    // Get date filter elements
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
        // Trigger requests reload
        if (window.app && typeof window.app.loadRequests === 'function') {
            window.app.loadRequests(1);
        }
    });
    
    endDate.addEventListener('change', function() {
        console.log('End date changed:', this.value);
        // Trigger requests reload
        if (window.app && typeof window.app.loadRequests === 'function') {
            window.app.loadRequests(1);
        }
    });
    
    clearDateFilter.addEventListener('click', function() {
        console.log('Clear date filter clicked');
        startDate.value = '';
        endDate.value = '';
        // Trigger requests reload
        if (window.app && typeof window.app.loadRequests === 'function') {
            window.app.loadRequests(1);
        }
    });
    
    console.log('Date filter initialized successfully');
});
