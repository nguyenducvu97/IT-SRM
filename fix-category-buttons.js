// Fix category buttons to prevent navigation
// This script should be loaded after app.js

document.addEventListener('DOMContentLoaded', function() {
    console.log('Fixing category button click events...');
    
    // Remove existing click handlers from category items
    function fixCategoryButtons() {
        const categoryItems = document.querySelectorAll('.category-item');
        
        categoryItems.forEach(item => {
            const categoryId = item.dataset.categoryId;
            
            // Remove the onclick from the main category item
            item.removeAttribute('onclick');
            
            // Add proper event listener for category click (only on the info part)
            const categoryInfo = item.querySelector('.category-info');
            if (categoryInfo) {
                categoryInfo.addEventListener('click', function(e) {
                    // Only trigger if not clicking on buttons
                    if (!e.target.closest('.category-actions')) {
                        const categoryName = categoryInfo.querySelector('h4').textContent;
                        if (window.app && window.app.showCategoryRequests) {
                            window.app.showCategoryRequests(categoryId, categoryName);
                        }
                    }
                });
                categoryInfo.style.cursor = 'pointer';
            }
            
            // Fix edit button
            const editBtn = item.querySelector('.btn-secondary');
            if (editBtn) {
                editBtn.removeAttribute('onclick');
                editBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const categoryName = item.querySelector('h4').textContent;
                    const description = item.querySelector('p').textContent;
                    
                    if (window.app && window.app.editCategory) {
                        window.app.editCategory(categoryId, categoryName, description);
                    }
                });
            }
            
            // Fix delete button
            const deleteBtn = item.querySelector('.btn-danger');
            if (deleteBtn) {
                deleteBtn.removeAttribute('onclick');
                deleteBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    if (window.app && window.app.deleteCategory) {
                        window.app.deleteCategory(categoryId);
                    }
                });
            }
        });
    }
    
    // Run immediately
    fixCategoryButtons();
    
    // Also run when categories are loaded (for dynamic content)
    if (window.app) {
        const originalDisplayCategories = window.app.displayCategories;
        window.app.displayCategories = function(categories) {
            originalDisplayCategories.call(this, categories);
            // Fix buttons after display
            setTimeout(fixCategoryButtons, 100);
        };
    }
    
    // Also observe for changes
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList' && mutation.target.classList.contains('category-item')) {
                setTimeout(fixCategoryButtons, 100);
            }
        });
    });
    
    const categoriesList = document.getElementById('categoriesList');
    if (categoriesList) {
        observer.observe(categoriesList, { childList: true, subtree: true });
    }
    
    console.log('Category button fixes applied');
});
