class KPIExportForm {
    constructor() {
        this.selectedType = 'summary';
        this.init();
    }

    init() {
        this.setupDateInputs();
        this.setupExportTypeSelection();
        this.setupExportButton();
        this.loadStaffList();
    }

    setupDateInputs() {
        const today = new Date();
        const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
        
        const startDateInput = document.getElementById('startDate');
        const endDateInput = document.getElementById('endDate');
        
        if (startDateInput) startDateInput.value = firstDay.toISOString().split('T')[0];
        if (endDateInput) endDateInput.value = lastDay.toISOString().split('T')[0];
    }

    setupExportTypeSelection() {
        const cards = document.querySelectorAll('.export-type-card');
        
        cards.forEach(card => {
            card.addEventListener('click', () => {
                // Remove selected class from all cards
                cards.forEach(c => c.classList.remove('selected'));
                
                // Add selected class to clicked card
                card.classList.add('selected');
                
                // Update selected type
                this.selectedType = card.dataset.type;
                
                // Show/hide staff selection
                const staffSection = document.getElementById('staffSelectSection');
                if (this.selectedType === 'staff') {
                    staffSection.style.display = 'block';
                } else {
                    staffSection.style.display = 'none';
                }
            });
        });

        // Select summary by default
        const summaryCard = document.querySelector('[data-type="summary"]');
        if (summaryCard) summaryCard.click();
    }

    setupExportButton() {
        const exportBtn = document.getElementById('exportBtn');
        if (exportBtn) {
            exportBtn.addEventListener('click', () => this.handleExport());
        }
    }

    async loadStaffList() {
        try {
            const response = await this.apiCall('api/kpi_export.php?action=get_staff_list');
            
            if (response.success) {
                const select = document.getElementById('staffSelect');
                if (select) {
                    // Clear existing options except the first one
                    while (select.children.length > 1) {
                        select.removeChild(select.lastChild);
                    }
                    
                    response.staff.forEach(staff => {
                        const option = document.createElement('option');
                        option.value = staff.id;
                        option.textContent = staff.full_name;
                        select.appendChild(option);
                    });
                }
            }
        } catch (error) {
            console.error('Error loading staff:', error);
            this.showNotification('Lỗi khi tải danh sách nhân viên', 'error');
        }
    }

    async handleExport() {
        const startDate = document.getElementById('startDate').value;
        const endDate = document.getElementById('endDate').value;
        
        if (!startDate || !endDate) {
            this.showNotification('Vui lòng chọn khoảng thời gian', 'error');
            return;
        }

        if (this.selectedType === 'staff') {
            const staffId = document.getElementById('staffSelect').value;
            if (!staffId) {
                this.showNotification('Vui lòng chọn nhân viên', 'error');
                return;
            }
            this.exportKPI(this.selectedType, startDate, endDate, staffId);
        } else {
            this.exportKPI(this.selectedType, startDate, endDate);
        }
    }

    async exportKPI(type, startDate, endDate, staffId = null) {
        this.showLoading('Ðang xuát báo cáo...');
        
        try {
            let action = type === 'staff' ? 'export_staff_details' : `export_${type}`;
            let url = `api/kpi_export.php?action=${action}&start_date=${startDate}&end_date=${endDate}`;
            if (staffId) {
                url += `&staff_id=${staffId}`;
            }

            // Create download link
            const link = document.createElement('a');
            link.href = url;
            link.style.display = 'none';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            // Show success message after delay
            setTimeout(() => {
                this.hideLoading();
                this.showNotification('File Excel đã được xuất thành công!', 'success');
            }, 2000);

        } catch (error) {
            console.error('Export error:', error);
            this.hideLoading();
            this.showNotification('Lỗi khi xuất file Excel', 'error');
        }
    }

    async apiCall(url, options = {}) {
        const defaultOptions = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'include'
        };

        const finalOptions = { ...defaultOptions, ...options };

        try {
            const response = await fetch(url, finalOptions);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                return await response.json();
            } else {
                return { success: true };
            }
        } catch (error) {
            console.error('API call error:', error);
            throw error;
        }
    }

    showLoading(message = 'Đang tải...') {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            overlay.style.display = 'flex';
            const messageElement = overlay.querySelector('h5');
            if (messageElement) messageElement.textContent = message;
        }
    }

    hideLoading() {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            overlay.style.display = 'none';
        }
    }

    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        document.body.appendChild(notification);

        // Auto remove after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 5000);
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    new KPIExportForm();
});
