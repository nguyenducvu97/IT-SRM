-- Create support_request_attachments table
CREATE TABLE IF NOT EXISTS support_request_attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    support_request_id INT NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    filename VARCHAR(255) NOT NULL,
    file_size INT NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (support_request_id) REFERENCES support_requests(id) ON DELETE CASCADE,
    
    INDEX idx_support_request_id (support_request_id),
    INDEX idx_filename (filename),
    INDEX idx_uploaded_at (uploaded_at)
);
