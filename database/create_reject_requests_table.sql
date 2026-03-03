-- Create reject_requests table for admin approval workflow
CREATE TABLE IF NOT EXISTS reject_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_request_id INT NOT NULL,
    rejected_by INT NOT NULL,
    reject_reason TEXT NOT NULL,
    reject_details TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    admin_reason TEXT NULL,
    processed_by INT NULL,
    processed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (service_request_id) REFERENCES service_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (rejected_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_service_request_id (service_request_id),
    INDEX idx_rejected_by (rejected_by),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);
