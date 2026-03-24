-- Add additional rating columns to request_feedback table
-- For detailed feedback when closing requests

ALTER TABLE request_feedback 
ADD COLUMN ease_of_use INT NULL COMMENT 'Ease of use rating 1-5',
ADD COLUMN speed_stability INT NULL COMMENT 'Speed and stability rating 1-5', 
ADD COLUMN requirement_meeting INT NULL COMMENT 'Requirement meeting rating 1-5';
