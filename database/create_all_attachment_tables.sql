-- Create all attachment tables for IT Service Request Management System
-- This script creates tables for storing attachments for different types of requests

-- Use the it_service_request database
USE it_service_request;

-- Create support_request_attachments table
-- Stores attachments for staff support requests
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

-- Create reject_request_attachments table
-- Stores attachments for staff reject requests
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

-- Create complete_request_attachments table
-- Stores attachments for request completion/resolution
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

-- Display success message
SELECT 'All attachment tables created successfully!' as message;
