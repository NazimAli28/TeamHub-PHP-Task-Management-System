-- Add pause_start_time column to designer_points table
ALTER TABLE designer_points ADD COLUMN pause_start_time DATETIME NULL AFTER is_paused;
