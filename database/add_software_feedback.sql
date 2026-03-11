-- Add software_feedback column to request_feedback table
ALTER TABLE request_feedback 
ADD COLUMN software_feedback TEXT COMMENT 'Feedback about IT SRM software';
