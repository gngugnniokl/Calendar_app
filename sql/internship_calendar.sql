-- =====================================================================
-- Tribbbal Internship Calendar — Full Database Export
-- =====================================================================
CREATE DATABASE IF NOT EXISTS internship_calendar CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE internship_calendar;

DROP TABLE IF EXISTS calendar_events;
CREATE TABLE calendar_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    week INT NOT NULL,
    day VARCHAR(20) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    event_date DATE NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO calendar_events (week, day, title, description, event_date) VALUES
(1, 'Monday', 'Day 1 — Welcome', 'Setup Environment', '2026-07-06'),
(1, 'Tuesday', 'Day 2 — PHP Basics', 'Variables, Arrays, Loops', '2026-07-07'),
(1, 'Wednesday', 'Day 3 — MySQL Basics', 'SELECT, INSERT, UPDATE, DELETE', '2026-07-08'),
(1, 'Thursday', 'Day 4 — Advanced PHP', 'Functions, Classes, Namespaces', '2026-07-09');

DROP TABLE IF EXISTS Wo_Users;
CREATE TABLE Wo_Users (
  user_id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(32) NOT NULL DEFAULT '',
  email VARCHAR(255) NOT NULL DEFAULT '',
  password VARCHAR(70) NOT NULL DEFAULT '',
  first_name VARCHAR(60) NOT NULL DEFAULT '',
  last_name VARCHAR(32) NOT NULL DEFAULT '',
  avatar VARCHAR(100) NOT NULL DEFAULT 'upload/photos/d-avatar.jpg',
  cover VARCHAR(100) NOT NULL DEFAULT 'upload/photos/d-cover.jpg',
  about TEXT,
  admin ENUM('0','1','2','3') NOT NULL DEFAULT '0',
  active INT DEFAULT 1,
  tribbbalGroup VARCHAR(100) DEFAULT '',
  Race VARCHAR(100) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

INSERT INTO Wo_Users (username, email, first_name, last_name, about, avatar, admin) VALUES
('intern1', 'intern1@example.com', 'Intern', 'One', 'First intern on the Tribbbal programme.', 'https://via.placeholder.com/150', '0'),
('mentor',  'mentor@example.com',  'Lead',   'Mentor', 'Programme lead and code reviewer.',       '', '2'),
('admin',   'admin@example.com',   'System', 'Admin',  'Platform Administrator',                 '', '1');

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
