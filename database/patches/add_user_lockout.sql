-- Add account lockout fields to the users table
-- Run this on your MySQL server (XAMPP) to add the required columns

ALTER TABLE `users`
  ADD COLUMN `failed_attempts` INT NOT NULL DEFAULT 0 AFTER `is_active`,
  ADD COLUMN `locked_until` DATETIME NULL AFTER `failed_attempts`,
  ADD COLUMN `lock_count` INT NOT NULL DEFAULT 0 AFTER `locked_until`;

-- Optional: index locked_until for faster checks
ALTER TABLE `users`
  ADD INDEX `idx_users_locked_until` (`locked_until`);
