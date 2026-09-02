-- =============================================
-- Author: Prem A/L Murugiah
-- Student ID: 2113456
-- Educational reference implementation
-- 
-- Pickup Scheduling & Tracking Module
-- Standalone MySQL 8 Script
-- =============================================
--
-- Assumptions:
-- 1. This script creates the pickup_statuses and pickups tables
-- 2. Foreign keys reference existing tables (users, food_matches, donations)
-- 3. All times are stored in UTC
-- 4. The application timezone is configurable
-- 5. "food_matches" table is used instead of "matches" due to reserved keyword
--
-- Dependencies (must exist before running this script):
-- - users table with id column
-- - food_matches table with id, donor_id, recipient_id, donation_id columns
-- - donations table with id column
--
-- =============================================

START TRANSACTION;

-- =============================================
-- Table: pickup_statuses
-- Lookup table for pickup status codes
-- =============================================
CREATE TABLE IF NOT EXISTS pickup_statuses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE COMMENT 'Status code: scheduled, confirmed, completed, cancelled, expired_pickup',
    name VARCHAR(100) NOT NULL COMMENT 'Human-readable status name',
    description TEXT NULL COMMENT 'Optional description of the status',
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_pickup_statuses_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lookup table for pickup statuses';

-- =============================================
-- Table: pickups
-- Main table for pickup records
-- =============================================
CREATE TABLE IF NOT EXISTS pickups (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    match_id BIGINT UNSIGNED NOT NULL COMMENT 'Foreign key to food_matches table',
    pickup_status_id BIGINT UNSIGNED NOT NULL COMMENT 'Foreign key to pickup_statuses table',
    donor_id BIGINT UNSIGNED NOT NULL COMMENT 'Foreign key to users table (donor)',
    recipient_id BIGINT UNSIGNED NOT NULL COMMENT 'Foreign key to users table (recipient)',
    donation_id BIGINT UNSIGNED NOT NULL COMMENT 'Foreign key to donations table',
    pickup_address VARCHAR(255) NOT NULL COMMENT 'Donor pickup address',
    scheduled_at DATETIME NOT NULL COMMENT 'Scheduled pickup time (UTC)',
    confirmed_at DATETIME NULL COMMENT 'When pickup was confirmed (UTC)',
    completed_at DATETIME NULL COMMENT 'When pickup was completed (UTC)',
    cancelled_at DATETIME NULL COMMENT 'When pickup was cancelled (UTC)',
    expired_at DATETIME NULL COMMENT 'When pickup expired (UTC)',
    cancellation_reason TEXT NULL COMMENT 'Optional reason for cancellation',
    donation_release_status VARCHAR(50) NULL COMMENT 'Status of donation release: pending, success, failed',
    donation_released_at DATETIME NULL COMMENT 'When donation was released (UTC)',
    created_by BIGINT UNSIGNED NOT NULL COMMENT 'Foreign key to users table (who created the pickup)',
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    
    -- Foreign Key Constraints
    CONSTRAINT fk_pickups_match FOREIGN KEY (match_id) 
        REFERENCES food_matches (id) ON DELETE CASCADE,
    CONSTRAINT fk_pickups_status FOREIGN KEY (pickup_status_id) 
        REFERENCES pickup_statuses (id) ON DELETE RESTRICT,
    CONSTRAINT fk_pickups_donor FOREIGN KEY (donor_id) 
        REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_pickups_recipient FOREIGN KEY (recipient_id) 
        REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_pickups_donation FOREIGN KEY (donation_id) 
        REFERENCES donations (id) ON DELETE CASCADE,
    CONSTRAINT fk_pickups_created_by FOREIGN KEY (created_by) 
        REFERENCES users (id) ON DELETE CASCADE,
    
    -- Indexes for performance and query optimization
    INDEX idx_pickups_status (pickup_status_id),
    INDEX idx_pickups_donor (donor_id),
    INDEX idx_pickups_recipient (recipient_id),
    INDEX idx_pickups_donation (donation_id),
    INDEX idx_pickups_scheduled_at (scheduled_at),
    INDEX idx_pickups_address_scheduled (pickup_address, scheduled_at) COMMENT 'For conflict detection',
    INDEX idx_pickups_created_at (created_at),
    INDEX idx_pickups_donor_created (donor_id, created_at) COMMENT 'For donor history queries',
    INDEX idx_pickups_recipient_created (recipient_id, created_at) COMMENT 'For recipient history queries'
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Pickup scheduling and tracking records';

-- =============================================
-- Seed Data: pickup_statuses
-- Insert the five required status codes
-- =============================================
INSERT INTO pickup_statuses (code, name, description, created_at, updated_at) VALUES
('scheduled', 'Scheduled', 'Pickup has been scheduled by the recipient', NOW(), NOW()),
('confirmed', 'Confirmed', 'Pickup has been confirmed by the donor', NOW(), NOW()),
('completed', 'Completed', 'Pickup has been successfully completed', NOW(), NOW()),
('cancelled', 'Cancelled', 'Pickup was cancelled by donor, recipient, or admin', NOW(), NOW()),
('expired_pickup', 'Expired Pickup', 'Pickup expired due to lack of confirmation within time limit', NOW(), NOW())
ON DUPLICATE KEY UPDATE 
    name = VALUES(name),
    description = VALUES(description),
    updated_at = NOW();

COMMIT;

-- =============================================
-- Verification Queries (Optional - for testing)
-- =============================================
-- Uncomment to verify the setup:

-- SELECT * FROM pickup_statuses;
-- SHOW INDEX FROM pickups;
-- SHOW CREATE TABLE pickups;

-- =============================================
-- Development-Only Section
-- =============================================
-- WARNING: The following section contains destructive operations.
-- Only use this in a development environment and when you want to reset the module.
-- DO NOT run this in production without proper backup.

-- START TRANSACTION;
-- 
-- -- Drop tables in reverse order of creation (due to foreign keys)
-- DROP TABLE IF EXISTS pickups;
-- DROP TABLE IF EXISTS pickup_statuses;
-- 
-- COMMIT;

-- =============================================
-- End of Script
-- =============================================
