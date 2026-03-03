-- Add accepted_at column to service_requests table
-- This will track when a staff member accepts a request

ALTER TABLE service_requests 
ADD COLUMN accepted_at TIMESTAMP NULL AFTER assigned_to;

-- Update existing requests that are already assigned to have accepted_at set to updated_at
UPDATE service_requests 
SET accepted_at = updated_at 
WHERE assigned_to IS NOT NULL AND accepted_at IS NULL;
