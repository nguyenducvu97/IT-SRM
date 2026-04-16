// Script to add departmentsPagination container dynamically
// Run this in browser console or add to page

function addDepartmentsPaginationContainer() {
    console.log('=== ADDING DEPARTMENTS PAGINATION CONTAINER ===');
    
    // Find departmentsList element
    const departmentsList = document.getElementById('departmentsList');
    console.log('departmentsList found:', !!departmentsList);
    
    if (departmentsList) {
        // Check if departmentsPagination already exists
        const existingPagination = document.getElementById('departmentsPagination');
        console.log('existing departmentsPagination found:', !!existingPagination);
        
        if (!existingPagination) {
            // Create pagination container
            const paginationDiv = document.createElement('div');
            paginationDiv.id = 'departmentsPagination';
            paginationDiv.className = 'pagination';
            
            // Insert after departmentsList
            departmentsList.parentNode.insertBefore(paginationDiv, departmentsList.nextSibling);
            
            console.log('departmentsPagination container added successfully!');
            console.log('departmentsPagination exists:', !!document.getElementById('departmentsPagination'));
        } else {
            console.log('departmentsPagination already exists');
        }
    } else {
        console.error('departmentsList element not found!');
    }
}

// Auto-run when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', addDepartmentsPaginationContainer);
} else {
    addDepartmentsPaginationContainer();
}
