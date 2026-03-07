// Runtime patch for support and reject request display functions
(function() {
    // Wait for app to be available
    function waitForApp() {
        if (typeof app !== 'undefined' && app.displaySupportRequests) {
            patchDisplayFunctions();
        } else {
            setTimeout(waitForApp, 100);
        }
    }

    function patchDisplayFunctions() {
        // Store original functions
        const originalDisplaySupportRequests = app.displaySupportRequests.bind(app);
        const originalDisplayRejectRequests = app.displayRejectRequests.bind(app);

        // Patch displaySupportRequests
        app.displaySupportRequests = function(supportRequests) {
            // Call original function first
            originalDisplaySupportRequests(supportRequests);
            
            // After original function runs, add edit/delete buttons to admin
            setTimeout(() => {
                const container = document.getElementById('supportRequestsList');
                if (container && app.currentUser && app.currentUser.role === 'admin') {
                    const supportItems = container.querySelectorAll('.support-request');
                    supportItems.forEach(item => {
                        const supportId = item.dataset.supportId;
                        const actionsDiv = item.querySelector('.request-actions');
                        if (actionsDiv && !actionsDiv.querySelector('.edit-support-btn')) {
                            const editBtn = document.createElement('button');
                            editBtn.className = 'btn btn-secondary btn-sm edit-support-btn';
                            editBtn.innerHTML = '<i class="fas fa-edit"></i> Sửa';
                            editBtn.onclick = () => app.editSupportRequest(supportId);
                            
                            const deleteBtn = document.createElement('button');
                            deleteBtn.className = 'btn btn-danger btn-sm delete-support-btn';
                            deleteBtn.innerHTML = '<i class="fas fa-trash"></i> Xóa';
                            deleteBtn.onclick = () => app.deleteSupportRequest(supportId);
                            
                            actionsDiv.insertBefore(editBtn, actionsDiv.firstChild);
                            actionsDiv.insertBefore(deleteBtn, actionsDiv.firstChild);
                        }
                    });
                }
            }, 100);
        };

        // Patch displayRejectRequests
        app.displayRejectRequests = function(rejectRequests) {
            // Call original function first
            originalDisplayRejectRequests(rejectRequests);
            
            // After original function runs, add edit/delete buttons to admin
            setTimeout(() => {
                const container = document.getElementById('rejectRequestsList');
                if (container && app.currentUser && app.currentUser.role === 'admin') {
                    const rejectItems = container.querySelectorAll('.reject-request');
                    rejectItems.forEach(item => {
                        const rejectId = item.dataset.rejectId;
                        const actionsDiv = item.querySelector('.request-actions');
                        if (actionsDiv && !actionsDiv.querySelector('.edit-reject-btn')) {
                            const editBtn = document.createElement('button');
                            editBtn.className = 'btn btn-secondary btn-sm edit-reject-btn';
                            editBtn.innerHTML = '<i class="fas fa-edit"></i> Sửa';
                            editBtn.onclick = () => app.editRejectRequest(rejectId);
                            
                            const deleteBtn = document.createElement('button');
                            deleteBtn.className = 'btn btn-danger btn-sm delete-reject-btn';
                            deleteBtn.innerHTML = '<i class="fas fa-trash"></i> Xóa';
                            deleteBtn.onclick = () => app.deleteRejectRequest(rejectId);
                            
                            actionsDiv.insertBefore(editBtn, actionsDiv.firstChild);
                            actionsDiv.insertBefore(deleteBtn, actionsDiv.firstChild);
                        }
                    });
                }
            }, 100);
        };
    }

    // Start waiting for app
    waitForApp();
})();
