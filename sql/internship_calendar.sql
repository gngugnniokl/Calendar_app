-- ============================================================
-- internship_calendar.sql
-- Dev 1 — Database & Migrations
-- Adds user-scoped calendar events and the nudge system table
-- ============================================================

-- 1. Add user_id to calendar_events
--    NULL = programme-wide (visible on every user's timeline)
--    N    = assigned to that specific user only
ALTER TABLE calendar_events ADD COLUMN user_id INT DEFAULT NULL AFTER id;
CREATE INDEX idx_calendar_events_user ON calendar_events(user_id);
CREATE INDEX idx_calendar_events_date ON calendar_events(event_date);

-- 2. Create calendar_nudges table
--    UNIQUE(sender_id, receiver_id) enforces one active nudge per
--    direction at a time — prevents duplicate nudges at the DB level
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

-- 3. Seed data — updated to include user_id
--    NULL = programme-wide events, visible on all timelines
INSERT INTO calendar_events (user_id, week, day, title, description, event_date) VALUES
(NULL, 1, 'Monday',    'Day 1 — Welcome',      'Setup Environment',              '2026-07-06'),
(NULL, 1, 'Tuesday',   'Day 2 — PHP Basics',   'Variables, Arrays, Loops',       '2026-07-07'),
(NULL, 1, 'Wednesday', 'Day 3 — MySQL Basics', 'SELECT, INSERT, UPDATE, DELETE', '2026-07-08'),
(NULL, 1, 'Thursday',  'Day 4 — Advanced PHP', 'Functions, Classes, Namespaces', '2026-07-09'),
(NULL, 1, 'Friday',    'Day 5 — Mini Project', 'Build a CRUD app',               '2026-07-10');
