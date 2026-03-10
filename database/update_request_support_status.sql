-- Update existing database to add request_support and rejected status to service_requests table
-- Run this script to update existing database

USE it_service_request;

-- Modify the status ENUM to include request_support and rejected
ALTER TABLE service_requests 
MODIFY COLUMN status ENUM('open', 'in_progress', 'resolved', 'closed', 'cancelled', 'request_support', 'rejected') DEFAULT 'open';

-- Note: This will preserve existing data while adding the new status options
