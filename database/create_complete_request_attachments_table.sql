-- Create complete_request_attachments table
CREATE TABLE IF NOT EXISTS complete_request_attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_request_id INT NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    filename VARCHAR(255) NOT NULL,
    file_size INT NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (service_request_id) REFERENCES service_requests(id) ON DELETE CASCADE,
    
    INDEX idx_service_request_id (service_request_id),
    INDEX idx_filename (filename),
    INDEX idx_uploaded_at (uploaded_at)
);
