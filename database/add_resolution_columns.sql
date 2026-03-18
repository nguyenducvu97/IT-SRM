-- Add resolution columns to service_requests table
-- For staff resolve functionality

ALTER TABLE service_requests 
ADD COLUMN error_description TEXT NULL,
ADD COLUMN error_type VARCHAR(100) NULL,
ADD COLUMN replacement_materials TEXT NULL,
ADD COLUMN solution_method TEXT NULL;
