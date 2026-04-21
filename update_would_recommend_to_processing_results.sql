-- Update script to rename would_recommend column to processing_results
-- Run this script in your database to update the existing table

-- Add new column processing_results
ALTER TABLE request_feedback ADD COLUMN processing_results VARCHAR(10) AFTER feedback;

-- Copy data from would_recommend to processing_results
UPDATE request_feedback SET processing_results = would_recommend WHERE would_recommend IS NOT NULL;

-- Drop old column would_recommend
ALTER TABLE request_feedback DROP COLUMN would_recommend;

-- Verify the changes
DESCRIBE request_feedback;
