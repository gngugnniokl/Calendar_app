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
-- calendar_events (With support for curriculum trackers)
-- -----------------------------------------------------------------
DROP TABLE IF EXISTS calendar_events;
CREATE TABLE calendar_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    week INT NOT NULL,
    day VARCHAR(20) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    success_criteria TEXT,
    traps TEXT,
    event_date DATE NOT NULL,
    is_completed TINYINT(1) NOT NULL DEFAULT 0,
    KEY idx_calendar_events_user (user_id),
    KEY idx_calendar_events_date (event_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------------
-- calendar_nudges (Dev 5 Strict Requirements Applied)
-- -----------------------------------------------------------------
DROP TABLE IF EXISTS calendar_nudges;
CREATE TABLE calendar_nudges (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    sender_id    INT NOT NULL,
    receiver_id  INT NOT NULL,
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_sender_receiver (sender_id, receiver_id),
    CONSTRAINT fk_calendar_nudges_sender
        FOREIGN KEY (sender_id) REFERENCES Wo_Users(user_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_calendar_nudges_receiver
        FOREIGN KEY (receiver_id) REFERENCES Wo_Users(user_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Seed Data: Internship Curriculum Schedules
-- ---------------------------------------------------------------------
INSERT INTO calendar_events (week, day, title, description, success_criteria, traps, event_date, is_completed) VALUES
(1, 'Monday', 'Day 1 — Welcome & Setup',
 'Install PHP, MySQL, Node.js, VS Code. Tour tribbbal_project (Main / Art / Events). Read README.md + Quickstart.md. Run ./start-all.sh to bring up ports 8000/8001/8002/3001. Open localhost:8000.',
 ':8000 loads from a server they started themselves.',
 'Environment / PATH issues — fix these completely before moving on.',
 '2026-07-06', 1),

(1, 'Tuesday', 'Day 2 — Architecture & Git',
 'Explain the shared MySQL, separate-frontends model. Create a Git branch (feature/intern-name-test), commit, and push. If new to PHP, spend ~2 hours on a PHP tutorial.',
 'Can draw the architecture from memory and has pushed a branch.',
 'Lock in the rule "never commit to main" starting now.',
 '2026-07-07', 1),

(1, 'Wednesday', 'Day 3 — Themes & Templates',
 'Tour themes/wondertag/layout/. Learn that .phtml files mix PHP variables with HTML. Change a button or header text on :8000, commit it, then revert locally to practice undoing changes.',
 'A visible change was made, committed, and cleanly reverted.',
 'Watch for editing the wrong platform''s files.',
 '2026-07-08', 0),

(1, 'Thursday', 'Day 4 — Other Platforms',
 'Replicate the Day 3 change on :8001 or :8002. Practice browser Dev Tools: Network, Console, Elements. Debug a simple CSS/JS issue.',
 'The same change now exists on a second platform, and they can inspect it with Dev Tools.',
 'Use "why not on the other site?" as a teaching moment about the multi-platform setup.',
 '2026-07-09', 0),

(1, 'Friday', 'Day 5 — Review & Mini-Project',
 'Quiz on the 3-platform architecture. Mini-project: build a promo banner shown only to logged-out users, driven by PHP checking login status.',
 'Banner correctly shows/hides based on login state. Hold a 15-minute end-of-week-1 check-in: what was confusing, what clicked?',
 'None noted for this day — use the check-in to surface any hidden gaps.',
 '2026-07-10', 0),

(2, 'Monday', 'Day 6 — Database & Auth',
 'Open the shared MySQL database via phpMyAdmin/DBeaver. Look at the Users table. Explore sessions across the 3 platforms — create an account on :8000, then log in on :8001.',
 'One account can log into two platforms, and they can explain why.',
 'Credentials are read-only at this stage — for looking, not editing.',
 '2026-07-13', 0),

(2, 'Tuesday', 'Day 7 — Backend & Routing',
 'Locate where backend scripts and APIs live. Tour assets/includes/functions_one.php. Write a PHP function that fetches user data and echoes it on a test page.',
 'The function prints real data from the Users table.',
 'Must use parameterized queries — no string-concatenated SQL.',
 '2026-07-14', 0),

(2, 'Wednesday', 'Day 8 — Amazon S3 & Media',
 'Discuss why absolute s3.amazonaws.com URLs are used. Study the Wo_GetMedia function. Find where profile pictures render and how the S3 URL is built.',
 'Can point to where the S3 URL is built and explain why it must be absolute.',
 'NEVER call parse_url() on S3 links inside .phtml — it breaks cross-subdomain images.',
 '2026-07-15', 0),

(2, 'Thursday', 'Day 9 — Debugging & Workflows',
 'Read logs: /tmp/artgallery.log, /tmp/events.log, and PHP error logs. Explore .agents/workflows/ (/deploy-status, /health-check). Find and fix a planted bug using only the logs.',
 'Diagnosed the bug from the logs, without being told the answer.',
 'Follow read -> hypothesize -> fix. Not guess-and-check.',
 '2026-07-16', 0),

(2, 'Friday', 'Day 10 — Capstone: Feature End-to-End',
 'Add a "Fun Fact" profile field end to end: add a DB column, add the input in settings .phtml, write the PHP save logic, and display it on the public profile page.',
 'A saved Fun Fact appears on the public profile — the full loop works.',
 'Also run the End-of-Week-2 Go/No-Go checkpoint before moving into Week 3 real work.',
 '2026-07-17', 0),

(3, 'Monday', 'Bug Triage & Lighter Supervision',
 'Assign bug triage: small, low-priority bugs (CSS alignment, typos, simple JS errors). Every change goes through a Pull Request, reviewed by you, loosening as trust builds. Start a weekly 15-minute 1:1. "When to ask" rule: try for ~15-20 minutes, then ask — never guess on anything touching the DB or S3.',
 'A few small PRs are merged, and they start picking up tickets without being individually assigned.',
 'Never let them guess on anything touching the database or S3.',
 '2026-07-20', 0),

(4, 'Monday', 'Bug Triage & Lighter Supervision (Week 4)',
 'Continue bug triage with progressively lighter review. Keep the weekly 1:1 rhythm going: what went well, what was hard, one thing to improve.',
 'They are picking up tickets independently and PR quality is improving week over week.',
 'Don''t skip the weekly 1:1 even as things get smoother.',
 '2026-07-27', 0),

(8, 'Monday', 'Ownership & Wind-down (Final Week)',
 'Finish the documentation handoff. Run a two-way feedback conversation: honest strengths and growth areas, and collect their feedback to improve future onboarding. If a future role is possible, flag it now.',
 'Work is documented and handed off cleanly, and feedback has gone both directions.',
 'Don''t let the exit be rushed — a clean handoff protects the next person.',
 '2026-08-24', 0);

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