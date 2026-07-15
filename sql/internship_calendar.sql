-- =====================================================================
-- Tribbbal Internship Calendar — Full Database Export
-- Database: internship_calendar
-- Table:    calendar_events
-- Source:   Tribbbal_IT_Internship_Calendar.xlsx (8-Week Calendar sheet)
-- =====================================================================

CREATE DATABASE IF NOT EXISTS internship_calendar
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE internship_calendar;

DROP TABLE IF EXISTS calendar_events;

CREATE TABLE calendar_events (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    week              INT NOT NULL,
    day               VARCHAR(20) NOT NULL,
    title             VARCHAR(255) NOT NULL,
    description       TEXT,
    success_criteria  TEXT,
    traps             TEXT,
    event_date        DATE NOT NULL,
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
-- WEEK 1 — Fundamentals & Environment
-- Goal: local env running, architecture understood, first visual change
-- Dates below start on Monday, July 6, 2026 — adjust to your real cohort
-- start date by editing the event_date column (or via Edit Event in the app).
-- ---------------------------------------------------------------------

INSERT INTO calendar_events (week, day, title, description, success_criteria, traps, event_date) VALUES
(1, 'Monday', 'Day 1 — Welcome & Setup',
 'Install PHP, MySQL, Node.js, VS Code. Tour tribbbal_project (Main / Art / Events). Read README.md + Quickstart.md. Run ./start-all.sh to bring up ports 8000/8001/8002/3001. Open localhost:8000.',
 ':8000 loads from a server they started themselves.',
 'Environment / PATH issues — fix these completely before moving on.',
 '2026-07-06'),

(1, 'Tuesday', 'Day 2 — Architecture & Git',
 'Explain the shared MySQL, separate-frontends model. Create a Git branch (feature/intern-name-test), commit, and push. If new to PHP, spend ~2 hours on a PHP tutorial.',
 'Can draw the architecture from memory and has pushed a branch.',
 'Lock in the rule "never commit to main" starting now.',
 '2026-07-07'),

(1, 'Wednesday', 'Day 3 — Themes & Templates',
 'Tour themes/wondertag/layout/. Learn that .phtml files mix PHP variables with HTML. Change a button or header text on :8000, commit it, then revert locally to practice undoing changes.',
 'A visible change was made, committed, and cleanly reverted.',
 'Watch for editing the wrong platform''s files.',
 '2026-07-08'),

(1, 'Thursday', 'Day 4 — Other Platforms',
 'Replicate the Day 3 change on :8001 or :8002. Practice browser Dev Tools: Network, Console, Elements. Debug a simple CSS/JS issue.',
 'The same change now exists on a second platform, and they can inspect it with Dev Tools.',
 'Use "why not on the other site?" as a teaching moment about the multi-platform setup.',
 '2026-07-09'),

(1, 'Friday', 'Day 5 — Review & Mini-Project',
 'Quiz on the 3-platform architecture. Mini-project: build a promo banner shown only to logged-out users, driven by PHP checking login status.',
 'Banner correctly shows/hides based on login state. Hold a 15-minute end-of-week-1 check-in: what was confusing, what clicked?',
 'None noted for this day — use the check-in to surface any hidden gaps.',
 '2026-07-10'),

(2, 'Monday', 'Day 6 — Database & Auth',
 'Open the shared MySQL database via phpMyAdmin/DBeaver. Look at the Users table. Explore sessions across the 3 platforms — create an account on :8000, then log in on :8001.',
 'One account can log into two platforms, and they can explain why.',
 'Credentials are read-only at this stage — for looking, not editing.',
 '2026-07-13'),

(2, 'Tuesday', 'Day 7 — Backend & Routing',
 'Locate where backend scripts and APIs live. Tour assets/includes/functions_one.php. Write a PHP function that fetches user data and echoes it on a test page.',
 'The function prints real data from the Users table.',
 'Must use parameterized queries — no string-concatenated SQL.',
 '2026-07-14'),

(2, 'Wednesday', 'Day 8 — Amazon S3 & Media',
 'Discuss why absolute s3.amazonaws.com URLs are used. Study the Wo_GetMedia function. Find where profile pictures render and how the S3 URL is built.',
 'Can point to where the S3 URL is built and explain why it must be absolute.',
 'NEVER call parse_url() on S3 links inside .phtml — it breaks cross-subdomain images.',
 '2026-07-15'),

(2, 'Thursday', 'Day 9 — Debugging & Workflows',
 'Read logs: /tmp/artgallery.log, /tmp/events.log, and PHP error logs. Explore .agents/workflows/ (/deploy-status, /health-check). Find and fix a planted bug using only the logs.',
 'Diagnosed the bug from the logs, without being told the answer.',
 'Follow read -> hypothesize -> fix. Not guess-and-check.',
 '2026-07-16'),

(2, 'Friday', 'Day 10 — Capstone: Feature End-to-End',
 'Add a "Fun Fact" profile field end to end: add a DB column, add the input in settings .phtml, write the PHP save logic, and display it on the public profile page.',
 'A saved Fun Fact appears on the public profile — the full loop works.',
 'Also run the End-of-Week-2 Go/No-Go checkpoint before moving into Week 3 real work.',
 '2026-07-17'),

-- ---------------------------------------------------------------------
-- WEEKS 3–4 — Real work, lighter supervision
-- Goal: own a category of low-risk work; spot-check rather than hand-hold
-- ---------------------------------------------------------------------

(3, 'Monday', 'Bug Triage & Lighter Supervision',
 'Assign bug triage: small, low-priority bugs (CSS alignment, typos, simple JS errors). Every change goes through a Pull Request, reviewed by you, loosening as trust builds. Start a weekly 15-minute 1:1. "When to ask" rule: try for ~15-20 minutes, then ask — never guess on anything touching the DB or S3.',
 'A few small PRs are merged, and they start picking up tickets without being individually assigned.',
 'Never let them guess on anything touching the database or S3.',
 '2026-07-20'),

(4, 'Monday', 'Bug Triage & Lighter Supervision (Week 4)',
 'Continue bug triage with progressively lighter review. Keep the weekly 1:1 rhythm going: what went well, what was hard, one thing to improve.',
 'They are picking up tickets independently and PR quality is improving week over week.',
 'Don''t skip the weekly 1:1 even as things get smoother.',
 '2026-07-27'),

-- ---------------------------------------------------------------------
-- WEEKS 5–6 — A small scoped project
-- Goal: the muscle of owning something, not just reacting
-- ---------------------------------------------------------------------

(5, 'Monday', 'Own a Small Scoped Project',
 'Hand them one self-contained, low-blast-radius project with a clear definition of done: automating a repetitive internal task, a small genuinely useful feature (scoped like Day 10), or a cleanup/audit. They plan it, ask questions, and execute — you stay available but don''t hover. Also pair them on a larger feature to shadow a senior dev.',
 'The project is progressing toward a clearly defined "done."',
 'Keep the blast radius low — no unsupervised DB writes.',
 '2026-08-03'),

(6, 'Monday', 'Own a Small Scoped Project (Week 6)',
 'Continue executing the scoped project from Week 5. Check in on planning quality and how they''re handling blockers on their own.',
 'The project ships, or hits its defined "done," and they can demo it.',
 'Watch for silent scope creep — the project should stay self-contained.',
 '2026-08-10'),

-- ---------------------------------------------------------------------
-- WEEKS 7–8 — Ownership & wind-down
-- Goal: mostly independent, then a clean exit
-- ---------------------------------------------------------------------

(7, 'Monday', 'Ownership & Wind-down',
 'They now run their ticket category and project largely solo; you review by exception. Code reviews go both ways — they submit PRs and review others'' PRs to absorb team coding standards. Begin documentation handoff for what they built or changed.',
 'Work is starting to be documented, and reviews are happening in both directions.',
 'Don''t leave documentation until the final days — start it now.',
 '2026-08-17'),

(8, 'Monday', 'Ownership & Wind-down (Final Week)',
 'Finish the documentation handoff. Run a two-way feedback conversation: honest strengths and growth areas, and collect their feedback to improve future onboarding. If a future role is possible, flag it now.',
 'Work is documented and handed off cleanly, and feedback has gone both directions.',
 'Don''t let the exit be rushed — a clean handoff protects the next person.',
 '2026-08-24');
