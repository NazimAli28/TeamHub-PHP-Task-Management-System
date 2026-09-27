-- Add pause/resume columns to designer_points table
ALTER TABLE designer_points
ADD COLUMN is_paused TINYINT(1) DEFAULT 0,
ADD COLUMN paused_duration INT DEFAULT 0;

-- Create pause_logs table
CREATE TABLE IF NOT EXISTS pause_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    designer_point_id INT NOT NULL,
    pause_time DATETIME NOT NULL,
    resume_time DATETIME DEFAULT NULL,
    FOREIGN KEY (designer_point_id) REFERENCES designer_points(id) ON DELETE CASCADE
);

-- Add index for better performance
CREATE INDEX idx_pause_logs_task_id ON pause_logs(designer_point_id);
CREATE INDEX idx_pause_logs_time ON pause_logs(pause_time, resume_time); 