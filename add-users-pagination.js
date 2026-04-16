// Script to add usersPagination container dynamically
// Run this in browser console or add to page

function addUsersPaginationContainer() {
    console.log('=== ADDING USERS PAGINATION CONTAINER ===');
    
    // Find usersList element
    const usersList = document.getElementById('usersList');
    console.log('usersList found:', !!usersList);
    
    if (usersList) {
        // Check if usersPagination already exists
        const existingPagination = document.getElementById('usersPagination');
        console.log('existing usersPagination found:', !!existingPagination);
        
        if (!existingPagination) {
            // Create pagination container
            const paginationDiv = document.createElement('div');
            paginationDiv.id = 'usersPagination';
            paginationDiv.className = 'pagination';
            
            // Insert after usersList
            usersList.parentNode.insertBefore(paginationDiv, usersList.nextSibling);
            
            console.log('usersPagination container added successfully!');
            console.log('usersPagination exists:', !!document.getElementById('usersPagination'));
        } else {
            console.log('usersPagination already exists');
        }
    } else {
        console.error('usersList element not found!');
    }
}

// Auto-run when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', addUsersPaginationContainer);
} else {
    addUsersPaginationContainer();
}
