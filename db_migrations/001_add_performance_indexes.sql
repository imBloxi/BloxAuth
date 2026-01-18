-- ============================================================================
-- Database Performance Optimization Migration
-- Created: 2024
-- Description: Adds indexes for frequently queried columns to improve performance
-- ============================================================================

-- Add index on licenses.key for faster license lookups
ALTER TABLE `licenses` 
ADD INDEX IF NOT EXISTS `idx_licenses_key` (`key`);

-- Add index on licenses_new.key for faster license lookups
ALTER TABLE `licenses_new` 
ADD INDEX IF NOT EXISTS `idx_licenses_new_key` (`key`);

-- Add index on licenses_new.roblox_user_id for user-based lookups
ALTER TABLE `licenses_new` 
ADD INDEX IF NOT EXISTS `idx_licenses_new_roblox_user_id` (`roblox_user_id`);

-- Add index on api_keys.api_key for faster API authentication
ALTER TABLE `api_keys` 
ADD INDEX IF NOT EXISTS `idx_api_keys_key` (`api_key`);

-- Add index on api_keys.is_active for filtering active keys
ALTER TABLE `api_keys` 
ADD INDEX IF NOT EXISTS `idx_api_keys_active` (`is_active`);

-- Add index on users.email for faster login queries
ALTER TABLE `users` 
ADD INDEX IF NOT EXISTS `idx_users_email` (`email`);

-- Add composite index on usage_logs for temporal and license-based queries
ALTER TABLE `usage_logs` 
ADD INDEX IF NOT EXISTS `idx_usage_logs_created_license` (`created_at`, `license_id`);

-- Add index on usage_logs.roblox_id for user activity tracking
ALTER TABLE `usage_logs` 
ADD INDEX IF NOT EXISTS `idx_usage_logs_roblox_id` (`roblox_id`);

-- Add composite index on licenses_new for validation queries
ALTER TABLE `licenses_new` 
ADD INDEX IF NOT EXISTS `idx_licenses_new_validation` (`key`, `is_revoked`, `is_banned`);

-- Add index on licenses_new.valid_until for expiration checks
ALTER TABLE `licenses_new` 
ADD INDEX IF NOT EXISTS `idx_licenses_new_valid_until` (`valid_until`);

-- Add index on license_logs.timestamp for temporal queries
ALTER TABLE `license_logs` 
ADD INDEX IF NOT EXISTS `idx_license_logs_timestamp` (`timestamp`);

-- Add composite index on license_logs for license activity tracking
ALTER TABLE `license_logs` 
ADD INDEX IF NOT EXISTS `idx_license_logs_license_timestamp` (`license_id`, `timestamp`);

-- Add index on notifications.is_read for filtering unread notifications
ALTER TABLE `notifications` 
ADD INDEX IF NOT EXISTS `idx_notifications_is_read` (`is_read`);

-- Add composite index on notifications for user unread notifications
ALTER TABLE `notifications` 
ADD INDEX IF NOT EXISTS `idx_notifications_user_read` (`user_id`, `is_read`, `created_at`);

-- Add index on rate_limits.timestamp for cleanup queries
ALTER TABLE `rate_limits` 
ADD INDEX IF NOT EXISTS `idx_rate_limits_timestamp` (`timestamp`);

-- Add index on login_logs.created_at for activity reports
ALTER TABLE `login_logs` 
ADD INDEX IF NOT EXISTS `idx_login_logs_created_at` (`created_at`);

-- Add composite index on login_logs for user activity tracking
ALTER TABLE `login_logs` 
ADD INDEX IF NOT EXISTS `idx_login_logs_user_created` (`user_id`, `created_at`);

-- Add index on user_activity_logs.timestamp for recent activity queries
ALTER TABLE `user_activity_logs` 
ADD INDEX IF NOT EXISTS `idx_user_activity_logs_timestamp` (`timestamp`);

-- Add composite index on user_activity_logs for user activity history
ALTER TABLE `user_activity_logs` 
ADD INDEX IF NOT EXISTS `idx_user_activity_logs_user_timestamp` (`user_id`, `timestamp`);

-- ============================================================================
-- Performance Notes:
-- ============================================================================
-- 1. These indexes significantly improve query performance for:
--    - License validation (licenses.key, licenses_new.key)
--    - User authentication (users.email, api_keys.api_key)
--    - Activity tracking (usage_logs, login_logs timestamps)
--    - Notification filtering (notifications.is_read)
--
-- 2. Composite indexes are ordered by:
--    - Equality conditions first (e.g., user_id, license_id)
--    - Range conditions last (e.g., timestamps)
--
-- 3. Monitor index usage with:
--    SHOW INDEX FROM table_name;
--    EXPLAIN SELECT ... queries;
--
-- 4. Consider dropping unused indexes to reduce write overhead
-- ============================================================================
