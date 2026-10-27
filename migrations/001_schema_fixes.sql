-- Schema fixes so the database matches the code.
-- Run once after importing db.sql:  mysql -u <user> -p <database> < migrations/001_schema_fixes.sql
-- Works on MySQL 5.7+ and MariaDB 10.2+.

-- Notifications can be marked as read
ALTER TABLE `notifications` ADD COLUMN `is_read` tinyint(1) NOT NULL DEFAULT 0;

-- Settings page saves theme and timezone
ALTER TABLE `users`
  ADD COLUMN `theme` varchar(20) NOT NULL DEFAULT 'system',
  ADD COLUMN `timezone` varchar(64) NOT NULL DEFAULT 'UTC';

-- License keys and API keys must be unique (they are looked up by value)
ALTER TABLE `licenses_new` ADD UNIQUE KEY `uniq_license_key` (`key`);
ALTER TABLE `api_keys` ADD UNIQUE KEY `uniq_api_key` (`api_key`);

-- Credit purchases record how many credits were bought
ALTER TABLE `payments` ADD COLUMN `credits` int(11) NOT NULL DEFAULT 0;

-- Sellix products keep their title; orders are upserted by Sellix order ID
ALTER TABLE `sellix_products` ADD COLUMN `title` varchar(255) DEFAULT NULL;
ALTER TABLE `sellix_orders` ADD UNIQUE KEY `uniq_sellix_order` (`sellix_order_id`);

-- One linked Roblox account per user, with its numeric Roblox user ID
ALTER TABLE `roblox_accounts`
  ADD COLUMN `roblox_user_id` bigint(20) DEFAULT NULL,
  ADD UNIQUE KEY `uniq_roblox_account_user` (`user_id`);

-- Roblox links made through Roblox OAuth are verified; typed-in usernames are not
ALTER TABLE `roblox_accounts` ADD COLUMN `verified` tinyint(1) NOT NULL DEFAULT 0;

-- usage_logs pointed at the old `licenses` table, so logging checks of current
-- licenses (licenses_new) failed. Drop that constraint.
ALTER TABLE `usage_logs` DROP FOREIGN KEY `usage_logs_ibfk_1`;
