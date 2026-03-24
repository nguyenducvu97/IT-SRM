-- Add closed_at column to service_requests table
-- For tracking when requests are closed by users

ALTER TABLE service_requests 
ADD COLUMN closed_at TIMESTAMP NULL COMMENT 'When request was closed by user';
