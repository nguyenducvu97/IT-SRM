-- Add estimated_completion column to service_requests table
-- This column will store the estimated completion time for requests

ALTER TABLE service_requests 
ADD COLUMN estimated_completion DATETIME NULL 
AFTER assigned_at;

-- Add index for better performance
CREATE INDEX idx_estimated_completion ON service_requests(estimated_completion);

-- Update existing records with default estimated completion (optional)
-- UPDATE service_requests SET estimated_completion = DATE_ADD(created_at, INTERVAL 3 DAY) 
-- WHERE estimated_completion IS NULL AND status IN ('open', 'in_progress');
