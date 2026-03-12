-- IT Service Request Management Database
-- Created for XAMPP environment

-- Create database
CREATE DATABASE IF NOT EXISTS it_service_request;
USE it_service_request;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'staff', 'user') DEFAULT 'user',
    department VARCHAR(50),
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Service requests table
CREATE TABLE IF NOT EXISTS service_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    category_id INT,
    user_id INT,
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    status ENUM('open', 'in_progress', 'resolved', 'closed', 'cancelled', 'request_support', 'rejected') DEFAULT 'open',
    assigned_to INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (assigned_to) REFERENCES users(id)
);

-- Comments table
CREATE TABLE IF NOT EXISTS comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_request_id INT NOT NULL,
    user_id INT NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (service_request_id) REFERENCES service_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Attachments table
CREATE TABLE IF NOT EXISTS attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_request_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_size INT NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    uploaded_by INT NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (service_request_id) REFERENCES service_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
);

-- Resolutions table
CREATE TABLE IF NOT EXISTS resolutions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_request_id INT NOT NULL,
    error_description TEXT NOT NULL,
    error_type VARCHAR(100) NOT NULL,
    replacement_materials TEXT,
    solution_method TEXT NOT NULL,
    resolved_by INT NOT NULL,
    resolved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (service_request_id) REFERENCES service_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (resolved_by) REFERENCES users(id)
);

-- Insert default data
INSERT INTO categories (name, description) VALUES 
('Hardware', 'Hardware-related issues and requests'),
('Software', 'Software installation, updates, and troubleshooting'),
('Network', 'Network connectivity and access issues'),
('Security', 'Security-related concerns and access requests'),
('Account', 'User account management and permissions'),
('Other', 'Miscellaneous IT requests');

-- Insert default admin user (password: admin123)
INSERT INTO users (username, email, password_hash, full_name, role, department) VALUES 
('admin', 'admin@company.com', '$2y$10$SEhfdD8EiF8Ay9gFQZANQeNstaAuzqyXBhjkC4Em5olLfVc0l0p32', 'System Administrator', 'admin', 'IT');

-- Insert sample staff users (password: staff123 for staff, user123 for regular users)
INSERT INTO users (username, email, password_hash, full_name, role, department) VALUES 
('staff1', 'staff1@company.com', '$2y$10$XJNvvS8344Fi5Blrg4vZL.9OXXJ6MsUyPRBEHL/Nb3YwrV2vnQxPK', 'John Smith', 'staff', 'IT'),
('staff2', 'staff2@company.com', '$2y$10$XJNvvS8344Fi5Blrg4vZL.9OXXJ6MsUyPRBEHL/Nb3YwrV2vnQxPK', 'Jane Doe', 'staff', 'IT'),
('user1', 'user1@company.com', '$2y$10$gPhkITe1Oi101enIdp6BvOsS5IkzHE/GSTNlp1WnmmkDlRocuKXK6', 'Mike Johnson', 'user', 'Sales');
