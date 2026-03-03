-- Create request_rejections table for tracking rejected requests
CREATE TABLE IF NOT EXISTS request_rejections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_request_id INT NOT NULL,
    rejected_by INT NOT NULL,
    reject_reason TEXT NOT NULL,
    reject_details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (service_request_id) REFERENCES service_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (rejected_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_service_request_id (service_request_id),
    INDEX idx_rejected_by (rejected_by)
);
