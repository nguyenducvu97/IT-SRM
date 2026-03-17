-- Create reject_request_attachments table
CREATE TABLE IF NOT EXISTS reject_request_attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reject_request_id INT NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    filename VARCHAR(255) NOT NULL,
    file_size INT NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (reject_request_id) REFERENCES reject_requests(id) ON DELETE CASCADE,
    
    INDEX idx_reject_request_id (reject_request_id),
    INDEX idx_filename (filename),
    INDEX idx_uploaded_at (uploaded_at)
);
