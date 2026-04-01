-- Create reject_request_attachments table
CREATE TABLE IF NOT EXISTS reject_request_attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_type VARCHAR(100) NOT NULL,
    file_size INT NOT NULL,
    reject_request_id INT NULL,
    uploaded_by INT NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (reject_request_id) REFERENCES reject_requests(id) ON DELETE SET NULL,
    FOREIGN KEY (uploaded_by) REFERENCES users(id),
    
    INDEX idx_reject_request_id (reject_request_id),
    INDEX idx_uploaded_by (uploaded_by)
);
