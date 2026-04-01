-- Add unique constraint to prevent duplicate reject requests
ALTER TABLE reject_requests 
ADD UNIQUE INDEX unique_reject_per_request (service_request_id, rejected_by, status(20));

-- This prevents multiple pending reject requests from the same user for the same service request
