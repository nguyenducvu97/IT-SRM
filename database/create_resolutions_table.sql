-- Create resolutions table for storing request resolution details
CREATE TABLE IF NOT EXISTS resolutions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_request_id INT NOT NULL,
    error_description TEXT NOT NULL,
    error_type VARCHAR(50) NOT NULL,
    replacement_materials TEXT,
    solution_method TEXT NOT NULL,
    resolved_by INT NOT NULL,
    resolved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (service_request_id) REFERENCES service_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (resolved_by) REFERENCES users(id),
    
    INDEX idx_service_request_id (service_request_id),
    INDEX idx_resolved_by (resolved_by)
);
