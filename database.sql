-- CircuLens Database Schema
-- Version: 1.0
-- Run: mysql -u root -p < database.sql

CREATE DATABASE IF NOT EXISTS circulens
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE circulens;

-- =============================================
-- Table: admin
-- =============================================
CREATE TABLE IF NOT EXISTS admin (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(50)  UNIQUE NOT NULL,
    password   VARCHAR(255) NOT NULL,
    email      VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- Table: users
-- =============================================
CREATE TABLE IF NOT EXISTS users (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    name           VARCHAR(100) NOT NULL,
    email          VARCHAR(100) UNIQUE NOT NULL,
    password       VARCHAR(255),
    oauth_provider VARCHAR(20),
    oauth_id       VARCHAR(100),
    reset_token    VARCHAR(100),
    reset_expires  DATETIME,
    is_active      TINYINT(1)   DEFAULT 1,
    created_at     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_reset_token (reset_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- Table: circulars
-- =============================================
CREATE TABLE IF NOT EXISTS circulars (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    title         VARCHAR(255) NOT NULL,
    description   TEXT,
    pdf_path      VARCHAR(500),
    circular_type ENUM('academic','examination','events','placement','timetable','general') DEFAULT 'general',
    source        ENUM('admin','scraped') DEFAULT 'admin',
    source_url    VARCHAR(500),
    content_hash  VARCHAR(64),
    is_active     TINYINT(1)   DEFAULT 1,
    created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_type        (circular_type),
    INDEX idx_active      (is_active),
    INDEX idx_hash        (content_hash),
    INDEX idx_created     (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- Table: preferences
-- =============================================
CREATE TABLE IF NOT EXISTS preferences (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT           NOT NULL,
    degree         VARCHAR(50),
    department     VARCHAR(100),
    semester       VARCHAR(10),
    circular_types JSON,
    updated_at     TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- Table: notifications
-- =============================================
CREATE TABLE IF NOT EXISTS notifications (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT        NOT NULL,
    circular_id INT        NOT NULL,
    is_sent     TINYINT(1) DEFAULT 0,
    is_read     TINYINT(1) DEFAULT 0,
    sent_at     DATETIME,
    created_at  TIMESTAMP  DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_circular (user_id, circular_id),
    FOREIGN KEY (user_id)     REFERENCES users(id)     ON DELETE CASCADE,
    FOREIGN KEY (circular_id) REFERENCES circulars(id) ON DELETE CASCADE,
    INDEX idx_user_read (user_id, is_read),
    INDEX idx_unsent    (is_sent)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- Sample Data
-- =============================================

-- Admin account: username=admin, password=admin123
INSERT INTO admin (username, password, email) VALUES
('admin', '$2y$10$QbhZJGgLj/DSD0dTJnpC/u5RSc5TsT.UQAAwXh2X4F/pmbPL.bhFe', 'admin@circulens.com')
ON DUPLICATE KEY UPDATE id = id;

-- Sample circulars
INSERT INTO circulars (title, description, circular_type, source, is_active) VALUES
('Academic Calendar 2024-25',
 'GTU Academic Calendar for the year 2024-25 including important dates for examinations, holidays, and institutional events.',
 'academic', 'admin', 1),

('Winter Examination 2024 Schedule',
 'Schedule for Winter Examination December 2024. All students must check their timetable and hall ticket details.',
 'examination', 'admin', 1),

('Campus Placement Drive - TCS',
 'Tata Consultancy Services is conducting a campus placement drive for BE/BTech final year students. Register before the deadline.',
 'placement', 'admin', 1),

('Annual Tech Fest - TechVision 2024',
 'Annual technical festival TechVision 2024. Register your teams for coding, robotics, paper presentation, and other competitions.',
 'events', 'admin', 1),

('Revised Timetable - Odd Semester 2024',
 'Revised lecture timetable for odd semester 2024-25. Students are requested to note changes in their schedules.',
 'timetable', 'admin', 1),

('Scholarship Applications Open - Merit 2024',
 'Applications invited for merit-based scholarships for the academic year 2024-25. Eligible students can apply online.',
 'academic', 'admin', 1),

('Summer Internship Guidelines 2025',
 'Guidelines for mandatory summer internship for BE third-year students. Industry partners and application procedure included.',
 'placement', 'admin', 1),

('Mid-Semester Examination Schedule',
 'Mid-semester internal examination schedule for all branches. Students must carry their I-Card and admit card.',
 'examination', 'admin', 1)
ON DUPLICATE KEY UPDATE id = id;

-- Sample user: email=user@test.com, password=user123
INSERT INTO users (name, email, password, is_active) VALUES
('Test User', 'user@test.com', '$2y$10$TTyONz6Qh9acldBQIyvWsO4tH.DCEQh0GBV1fLylEiy1KPngEZlEq', 1)
ON DUPLICATE KEY UPDATE id = id;
