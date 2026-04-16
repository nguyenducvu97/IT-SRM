// Script to add categoryPagination container dynamically
// Run this in browser console or add to page

function addCategoryPaginationContainer() {
    console.log('=== ADDING CATEGORY PAGINATION CONTAINER ===');
    
    // Find categoryRequestsList element
    const categoryRequestsList = document.getElementById('categoryRequestsList');
    console.log('categoryRequestsList found:', !!categoryRequestsList);
    
    if (categoryRequestsList) {
        // Check if categoryPagination already exists
        const existingPagination = document.getElementById('categoryPagination');
        console.log('existing categoryPagination found:', !!existingPagination);
        
        if (!existingPagination) {
            // Create pagination container
            const paginationDiv = document.createElement('div');
            paginationDiv.id = 'categoryPagination';
            paginationDiv.className = 'pagination';
            
            // Insert after categoryRequestsList
            categoryRequestsList.parentNode.insertBefore(paginationDiv, categoryRequestsList.nextSibling);
            
            console.log('categoryPagination container added successfully!');
            console.log('categoryPagination exists:', !!document.getElementById('categoryPagination'));
        } else {
            console.log('categoryPagination already exists');
        }
    } else {
        console.error('categoryRequestsList element not found!');
    }
}

// Auto-run when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', addCategoryPaginationContainer);
} else {
    addCategoryPaginationContainer();
}
