-- Add new columns to request_feedback table
ALTER TABLE request_feedback 
ADD COLUMN ease_of_use INT COMMENT 'Ease of use rating 1-5',
ADD COLUMN speed_stability INT COMMENT 'Speed and stability rating 1-5',
ADD COLUMN requirement_meeting INT COMMENT 'Requirement meeting rating 1-5';

-- Add constraints for the new rating columns
ALTER TABLE request_feedback 
ADD CONSTRAINT chk_ease_of_use CHECK (ease_of_use IS NULL OR (ease_of_use >= 1 AND ease_of_use <= 5)),
ADD CONSTRAINT chk_speed_stability CHECK (speed_stability IS NULL OR (speed_stability >= 1 AND speed_stability <= 5)),
ADD CONSTRAINT chk_requirement_meeting CHECK (requirement_meeting IS NULL OR (requirement_meeting >= 1 AND requirement_meeting <= 5));
