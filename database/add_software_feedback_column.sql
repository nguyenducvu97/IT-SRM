-- Add software feedback column to request_feedback table
ALTER TABLE request_feedback 
ADD COLUMN software_feedback TEXT NULL COMMENT 'Feedback about IT SRM software';
