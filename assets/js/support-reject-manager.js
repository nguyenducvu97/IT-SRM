// Support and Reject Request Edit/Delete Functions
class SupportRejectManager {
    constructor(app) {
        this.app = app;
    }

    async editSupportRequest(supportId) {
        try {
            const response = await this.app.apiCall(`api/support_requests.php?action=get&id=${supportId}`);
            
            if (response.success) {
                const supportRequest = response.data;
                this.showEditSupportModal(supportRequest);
            } else {
                this.app.showNotification(response.message || 'Lỗi khi tải thông tin yêu cầu hỗ trợ', 'error');
            }
        } catch (error) {
            this.app.showNotification('Lỗi khi tải thông tin yêu cầu hỗ trợ', 'error');
        }
    }

    async deleteSupportRequest(supportId) {
        if (!confirm('Bạn có chắc chắn muốn xóa yêu cầu hỗ trợ này?')) {
            return;
        }
        
        try {
            const response = await fetch(`api/support_requests.php?id=${supportId}`, {
                method: 'DELETE'
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.app.showNotification('Xóa yêu cầu hỗ trợ thành công', 'success');
                this.app.loadSupportRequests();
            } else {
                this.app.showNotification(data.message || 'Lỗi khi xóa yêu cầu hỗ trợ', 'error');
            }
        } catch (error) {
            this.app.showNotification('Lỗi khi xóa yêu cầu hỗ trợ', 'error');
        }
    }

    async editRejectRequest(rejectId) {
        try {
            const response = await this.app.apiCall(`api/reject_requests.php?action=get&id=${rejectId}`);
            
            if (response.success) {
                const rejectRequest = response.data;
                this.showEditRejectModal(rejectRequest);
            } else {
                this.app.showNotification(response.message || 'Lỗi khi tải thông tin yêu cầu từ chối', 'error');
            }
        } catch (error) {
            this.app.showNotification('Lỗi khi tải thông tin yêu cầu từ chối', 'error');
        }
    }

    async deleteRejectRequest(rejectId) {
        if (!confirm('Bạn có chắc chắn muốn xóa yêu cầu từ chối này?')) {
            return;
        }
        
        try {
            const response = await fetch(`api/reject_requests.php?id=${rejectId}`, {
                method: 'DELETE'
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.app.showNotification('Xóa yêu cầu từ chối thành công', 'success');
                this.app.loadRejectRequests();
            } else {
                this.app.showNotification(data.message || 'Lỗi khi xóa yêu cầu từ chối', 'error');
            }
        } catch (error) {
            this.app.showNotification('Lỗi khi xóa yêu cầu từ chối', 'error');
        }
    }

    showEditSupportModal(supportRequest) {
        // Create modal if it doesn't exist
        let modal = document.getElementById('editSupportRequestModal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'editSupportRequestModal';
            modal.className = 'modal';
            modal.innerHTML = `
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Chỉnh sửa yêu cầu hỗ trợ</h3>
                        <span class="close">&times;</span>
                    </div>
                    <div class="modal-body">
                        <form id="editSupportRequestForm">
                            <input type="hidden" id="editSupportRequestId">
                            <div class="form-group">
                                <label for="editSupportType">Loại hỗ trợ *</label>
                                <select id="editSupportType" required>
                                    <option value="">Chọn loại hỗ trợ</option>
                                    <option value="equipment">Thiết bị</option>
                                    <option value="person">Nhân sự</option>
                                    <option value="department">Phòng ban</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="editSupportDetails">Chi tiết hỗ trợ *</label>
                                <textarea id="editSupportDetails" rows="3" required></textarea>
                            </div>
                            <div class="form-group">
                                <label for="editSupportReason">Lý do hỗ trợ *</label>
                                <textarea id="editSupportReason" rows="3" required></textarea>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                                <button type="button" class="btn btn-secondary cancel-edit-support">Hủy</button>
                            </div>
                        </form>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }

        // Populate form with support request data
        document.getElementById('editSupportRequestId').value = supportRequest.id;
        document.getElementById('editSupportType').value = supportRequest.support_type;
        document.getElementById('editSupportDetails').value = supportRequest.support_details;
        document.getElementById('editSupportReason').value = supportRequest.support_reason;

        // Show modal
        modal.style.display = 'flex';

        // Bind events
        const form = document.getElementById('editSupportRequestForm');
        form.onsubmit = (e) => this.handleEditSupportSubmit(e);

        const cancelBtn = modal.querySelector('.cancel-edit-support');
        cancelBtn.onclick = () => this.closeEditSupportModal();

        const closeBtn = modal.querySelector('.close');
        closeBtn.onclick = () => this.closeEditSupportModal();
    }

    showEditRejectModal(rejectRequest) {
        // Create modal if it doesn't exist
        let modal = document.getElementById('editRejectRequestModal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'editRejectRequestModal';
            modal.className = 'modal';
            modal.innerHTML = `
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Chỉnh sửa yêu cầu từ chối</h3>
                        <span class="close">&times;</span>
                    </div>
                    <div class="modal-body">
                        <form id="editRejectRequestForm">
                            <input type="hidden" id="editRejectRequestId">
                            <div class="form-group">
                                <label for="editRejectReason">Lý do từ chối *</label>
                                <textarea id="editRejectReason" rows="3" required></textarea>
                            </div>
                            <div class="form-group">
                                <label for="editRejectDetails">Chi tiết từ chối</label>
                                <textarea id="editRejectDetails" rows="3"></textarea>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                                <button type="button" class="btn btn-secondary cancel-edit-reject">Hủy</button>
                            </div>
                        </form>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }

        // Populate form with reject request data
        document.getElementById('editRejectRequestId').value = rejectRequest.id;
        document.getElementById('editRejectReason').value = rejectRequest.reject_reason;
        document.getElementById('editRejectDetails').value = rejectRequest.reject_details || '';

        // Show modal
        modal.style.display = 'flex';

        // Bind events
        const form = document.getElementById('editRejectRequestForm');
        form.onsubmit = (e) => this.handleEditRejectSubmit(e);

        const cancelBtn = modal.querySelector('.cancel-edit-reject');
        cancelBtn.onclick = () => this.closeEditRejectModal();

        const closeBtn = modal.querySelector('.close');
        closeBtn.onclick = () => this.closeEditRejectModal();
    }

    async handleEditSupportSubmit(e) {
        e.preventDefault();
        
        const formData = {
            action: 'update',
            id: document.getElementById('editSupportRequestId').value,
            support_type: document.getElementById('editSupportType').value,
            support_details: document.getElementById('editSupportDetails').value,
            support_reason: document.getElementById('editSupportReason').value
        };

        try {
            const response = await this.app.apiCall('api/support_requests.php', {
                method: 'PUT',
                body: JSON.stringify(formData)
            });

            if (response.success) {
                this.app.showNotification('Cập nhật yêu cầu hỗ trợ thành công', 'success');
                this.closeEditSupportModal();
                this.app.loadSupportRequests();
            } else {
                this.app.showNotification(response.message || 'Lỗi khi cập nhật yêu cầu hỗ trợ', 'error');
            }
        } catch (error) {
            this.app.showNotification('Lỗi khi cập nhật yêu cầu hỗ trợ', 'error');
        }
    }

    async handleEditRejectSubmit(e) {
        e.preventDefault();
        
        const formData = {
            action: 'update',
            id: document.getElementById('editRejectRequestId').value,
            reject_reason: document.getElementById('editRejectReason').value,
            reject_details: document.getElementById('editRejectDetails').value
        };

        try {
            const response = await this.app.apiCall('api/reject_requests.php', {
                method: 'PUT',
                body: JSON.stringify(formData)
            });

            if (response.success) {
                this.app.showNotification('Cập nhật yêu cầu từ chối thành công', 'success');
                this.closeEditRejectModal();
                this.app.loadRejectRequests();
            } else {
                this.app.showNotification(response.message || 'Lỗi khi cập nhật yêu cầu từ chối', 'error');
            }
        } catch (error) {
            this.app.showNotification('Lỗi khi cập nhật yêu cầu từ chối', 'error');
        }
    }

    closeEditSupportModal() {
        const modal = document.getElementById('editSupportRequestModal');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    closeEditRejectModal() {
        const modal = document.getElementById('editRejectRequestModal');
        if (modal) {
            modal.style.display = 'none';
        }
    }
}

// Initialize and attach to app when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    if (typeof app !== 'undefined') {
        app.supportRejectManager = new SupportRejectManager(app);
        
        // Add methods to app instance
        app.editSupportRequest = (id) => app.supportRejectManager.editSupportRequest(id);
        app.deleteSupportRequest = (id) => app.supportRejectManager.deleteSupportRequest(id);
        app.editRejectRequest = (id) => app.supportRejectManager.editRejectRequest(id);
        app.deleteRejectRequest = (id) => app.supportRejectManager.deleteRejectRequest(id);
    }
});
