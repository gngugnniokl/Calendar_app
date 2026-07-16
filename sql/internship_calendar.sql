-- =====================================================================
-- Tribbbal Internship Calendar — Full Database Export (with Auth)
-- =====================================================================
CREATE DATABASE IF NOT EXISTS internship_calendar CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE internship_calendar;

SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------------
-- Wo_Users (extended with auth columns)
-- -----------------------------------------------------------------
DROP TABLE IF EXISTS Wo_Users;
CREATE TABLE Wo_Users (
  user_id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(32) NOT NULL DEFAULT '',
  email VARCHAR(255) NOT NULL DEFAULT '',
  password VARCHAR(255) NOT NULL DEFAULT '',
  first_name VARCHAR(60) NOT NULL DEFAULT '',
  last_name VARCHAR(32) NOT NULL DEFAULT '',
  avatar VARCHAR(100) NOT NULL DEFAULT 'upload/photos/d-avatar.jpg',
  cover VARCHAR(100) NOT NULL DEFAULT 'upload/photos/d-cover.jpg',
  about TEXT,
  admin ENUM('0','1','2','3') NOT NULL DEFAULT '0',
  active INT DEFAULT 1,
  tribbbalGroup VARCHAR(100) DEFAULT '',
  Race VARCHAR(100) DEFAULT '',
  ip_address VARCHAR(100) DEFAULT '',
  lastseen INT DEFAULT 0,
  banned TINYINT DEFAULT 0,
  banned_reason VARCHAR(500) DEFAULT '',
  status ENUM('0','1') DEFAULT '0',
  email_code VARCHAR(32) DEFAULT '',
  time_code_sent INT DEFAULT 0,
  phone_number VARCHAR(32) DEFAULT '',
  sms_code INT DEFAULT 0,
  verified ENUM('0','1') DEFAULT '0',
  two_factor TINYINT DEFAULT 0,
  two_factor_verified TINYINT DEFAULT 0,
  two_factor_hash VARCHAR(50) DEFAULT '',
  two_factor_method VARCHAR(50) DEFAULT 'two_factor',
  last_login_data TEXT,
  start_up ENUM('0','1') DEFAULT '0',
  language VARCHAR(31) DEFAULT 'english',
  showlastseen ENUM('0','1') DEFAULT '1',
  registered VARCHAR(32) DEFAULT '0/0000',
  joined INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO Wo_Users (username, email, password, first_name, last_name, about, avatar, admin) VALUES
('intern1', 'intern1@example.com', '$2y$12$aoICqfBI1Q.JuF4rWrYpBuXLTIj78qvJ.VGI.8aNjlFf1NQIdY..G', 'Intern', 'One', 'First intern on the Tribbbal programme.', 'https://via.placeholder.com/150', '0'),
('mentor',  'mentor@example.com',  '$2y$12$aoICqfBI1Q.JuF4rWrYpBuXLTIj78qvJ.VGI.8aNjlFf1NQIdY..G', 'Lead',   'Mentor', 'Programme lead and code reviewer.',       '', '2'),
('admin',   'admin@example.com',   '$2y$12$aoICqfBI1Q.JuF4rWrYpBuXLTIj78qvJ.VGI.8aNjlFf1NQIdY..G', 'System', 'Admin',  'Platform Administrator',                 '', '1');

-- -----------------------------------------------------------------
-- calendar_events
-- -----------------------------------------------------------------
DROP TABLE IF EXISTS calendar_events;
CREATE TABLE calendar_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    week INT NOT NULL,
    day VARCHAR(20) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    event_date DATE NOT NULL,
    is_completed TINYINT(1) NOT NULL DEFAULT 0,
    KEY idx_calendar_events_user (user_id),
    KEY idx_calendar_events_date (event_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO calendar_events (user_id, week, day, title, description, event_date, is_completed) VALUES
(NULL, 1, 'Monday', 'Day 1 — Welcome', 'Setup Environment', '2026-07-06', 1),
(NULL, 1, 'Tuesday', 'Day 2 — PHP Basics', 'Variables, Arrays, Loops', '2026-07-07', 1),
(NULL, 1, 'Wednesday', 'Day 3 — MySQL Basics', 'SELECT, INSERT, UPDATE, DELETE', '2026-07-08', 0),
(NULL, 1, 'Thursday', 'Day 4 — Advanced PHP', 'Functions, Classes, Namespaces', '2026-07-09', 0),
(NULL, 1, 'Friday', 'Day 5 — Mini Project', 'Build a CRUD app', '2026-07-10', 0);

-- -----------------------------------------------------------------
-- calendar_nudges
-- -----------------------------------------------------------------
DROP TABLE IF EXISTS calendar_nudges;
CREATE TABLE calendar_nudges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_nudge_pair (sender_id, receiver_id),
    KEY idx_nudge_receiver (receiver_id),
    FOREIGN KEY (sender_id) REFERENCES Wo_Users(user_id),
    FOREIGN KEY (receiver_id) REFERENCES Wo_Users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------------
-- Wo_Posts
-- -----------------------------------------------------------------
DROP TABLE IF EXISTS Wo_Posts;
CREATE TABLE Wo_Posts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL DEFAULT 0,
  postText TEXT,
  time INT NOT NULL DEFAULT 0,
  active INT NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO Wo_Posts (user_id, postText, time) VALUES
(1, 'Week 1 Day 1 complete! Setup my dev environment and cloned the repo. #InternLife', UNIX_TIMESTAMP() - 86400),
(1, 'Just finished Day 2: PHP is actually fun. Love the variable syntax. @mentor', UNIX_TIMESTAMP() - 3600),
(2, 'Great progress from the interns today! Keep it up. #Tribbbal #Mentorship', UNIX_TIMESTAMP());

-- -----------------------------------------------------------------
-- Wo_AppsSessions — session-based authentication
-- -----------------------------------------------------------------
DROP TABLE IF EXISTS Wo_AppsSessions;
CREATE TABLE Wo_AppsSessions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL DEFAULT 0,
  session_id VARCHAR(255) NOT NULL DEFAULT '',
  platform VARCHAR(50) DEFAULT 'web',
  platform_details TEXT,
  time INT DEFAULT 0,
  KEY idx_session_id (session_id),
  KEY idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------------
-- Wo_Bad_Login — brute-force protection
-- -----------------------------------------------------------------
DROP TABLE IF EXISTS Wo_Bad_Login;
CREATE TABLE Wo_Bad_Login (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ip VARCHAR(100) NOT NULL DEFAULT '',
  time INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------------
-- Wo_Config — site configuration key-value store
-- -----------------------------------------------------------------
DROP TABLE IF EXISTS Wo_Config;
CREATE TABLE Wo_Config (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  value TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO Wo_Config (name, value) VALUES
('prevent_system', '1'),
('user_registration', '1'),
('site_url', 'http://localhost:6060'),
('maintenance_mode', '0'),
('remember_device', '0');

SET FOREIGN_KEY_CHECKS = 1;
