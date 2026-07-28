-- =====================================================================
-- Leaderboard Schema — tables, triggers, stored procedure, defaults
-- =====================================================================
CREATE DATABASE IF NOT EXISTS internship_calendar
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE internship_calendar;

SET FOREIGN_KEY_CHECKS = 0;
DROP TRIGGER IF EXISTS trg_token_transactions_before_insert;
DROP TRIGGER IF EXISTS trg_token_transactions_after_insert;
DROP PROCEDURE IF EXISTS recalculateRanks;
DROP TABLE IF EXISTS token_transactions;
DROP TABLE IF EXISTS leaderboard_tokens;
DROP TABLE IF EXISTS leaderboard_config;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- leaderboard_config
-- ---------------------------------------------------------------------
CREATE TABLE leaderboard_config (
  config_key VARCHAR(100) NOT NULL PRIMARY KEY,
  config_value TEXT DEFAULT NULL,
  value_type ENUM('int', 'decimal', 'bool', 'string', 'json') NOT NULL DEFAULT 'string',
  description VARCHAR(255) DEFAULT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO leaderboard_config (config_key, config_value, value_type, description) VALUES
  ('king_queen_threshold_tokens', '100000', 'int', 'Token balance required to enter the King/Queen tier.'),
  ('reward_population_cap', '1000000', 'int', 'Only the first one million members are eligible for the tier flag.'),
  ('first_king_queen_cash_reward_usd', '500', 'int', 'One-time cash reward for the first member to reach the milestone.'),
  ('first_king_queen_profit_share_percent', '0.5', 'decimal', 'Reward share for the first milestone achiever.'),
  ('recalc_batch_size', '5000', 'int', 'Batch size for rebuilds and large ranking refreshes.'),
  ('top_rank_cache_limit', '7', 'int', 'Default leaderboard slice for the featured view.'),
  ('last_recalc_run_at', NULL, 'string', 'Most recent recalculation timestamp.'),
  ('first_king_queen_user_id', NULL, 'int', 'User id of the first member to reach the threshold.'),
  ('first_king_queen_hit_at', NULL, 'string', 'Timestamp when the first user reached the threshold.');

-- ---------------------------------------------------------------------
-- leaderboard_tokens
-- ---------------------------------------------------------------------
CREATE TABLE leaderboard_tokens (
  user_id INT NOT NULL,
  total_tokens BIGINT NOT NULL DEFAULT 0,
  total_earned BIGINT NOT NULL DEFAULT 0,
  total_spent BIGINT NOT NULL DEFAULT 0,
  current_rank INT DEFAULT NULL,
  rank_tier VARCHAR(32) NOT NULL DEFAULT 'rookie',
  is_king_or_queen TINYINT(1) NOT NULL DEFAULT 0,
  is_first_to_threshold TINYINT(1) NOT NULL DEFAULT 0,
  threshold_hit_at DATETIME DEFAULT NULL,
  last_transaction_at DATETIME DEFAULT NULL,
  last_recalculated_at DATETIME DEFAULT NULL,
  recalculation_batch_id BIGINT DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id),
  KEY idx_leaderboard_tokens_rank (current_rank),
  KEY idx_leaderboard_tokens_balance (total_tokens),
  KEY idx_leaderboard_tokens_threshold (is_king_or_queen, total_tokens),
  KEY idx_leaderboard_tokens_threshold_hit (threshold_hit_at),
  KEY idx_leaderboard_tokens_recalculated (last_recalculated_at),
  CONSTRAINT fk_leaderboard_tokens_user
    FOREIGN KEY (user_id) REFERENCES Wo_Users(user_id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- token_transactions
-- ---------------------------------------------------------------------
CREATE TABLE token_transactions (
  transaction_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT NOT NULL,
  amount BIGINT NOT NULL,
  reason VARCHAR(255) NOT NULL,
  reference_type VARCHAR(64) DEFAULT NULL,
  reference_id VARCHAR(128) DEFAULT NULL,
  balance_after BIGINT DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (transaction_id),
  KEY idx_token_transactions_user_created (user_id, created_at, transaction_id),
  KEY idx_token_transactions_created (created_at),
  KEY idx_token_transactions_reference (reference_type, reference_id),
  CONSTRAINT fk_token_transactions_user
    FOREIGN KEY (user_id) REFERENCES Wo_Users(user_id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT chk_token_transactions_amount
    CHECK (amount <> 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DELIMITER $$

CREATE TRIGGER trg_token_transactions_before_insert
BEFORE INSERT ON token_transactions
FOR EACH ROW
BEGIN
  DECLARE v_current_balance BIGINT DEFAULT 0;

  INSERT IGNORE INTO leaderboard_tokens (user_id)
  VALUES (NEW.user_id);

  SELECT COALESCE(total_tokens, 0)
    INTO v_current_balance
  FROM leaderboard_tokens
  WHERE user_id = NEW.user_id
  LIMIT 1;

  SET NEW.balance_after = v_current_balance + NEW.amount;
END$$

CREATE TRIGGER trg_token_transactions_after_insert
AFTER INSERT ON token_transactions
FOR EACH ROW
BEGIN
  DECLARE v_threshold BIGINT DEFAULT 100000;
  DECLARE v_population_cap INT DEFAULT 1000000;
  DECLARE v_threshold_config BIGINT DEFAULT NULL;
  DECLARE v_population_cap_config BIGINT DEFAULT NULL;

  SELECT CAST(config_value AS UNSIGNED)
    INTO v_threshold_config
  FROM leaderboard_config
  WHERE config_key = 'king_queen_threshold_tokens'
  LIMIT 1;

  SELECT CAST(config_value AS UNSIGNED)
    INTO v_population_cap_config
  FROM leaderboard_config
  WHERE config_key = 'reward_population_cap'
  LIMIT 1;

  SET v_threshold = COALESCE(v_threshold_config, 100000);
  SET v_population_cap = COALESCE(v_population_cap_config, 1000000);

  UPDATE leaderboard_tokens
  SET total_tokens = total_tokens + NEW.amount,
      total_earned = total_earned + CASE WHEN NEW.amount > 0 THEN NEW.amount ELSE 0 END,
      total_spent = total_spent + CASE WHEN NEW.amount < 0 THEN ABS(NEW.amount) ELSE 0 END,
      last_transaction_at = CASE
        WHEN last_transaction_at IS NULL OR NEW.created_at > last_transaction_at THEN NEW.created_at
        ELSE last_transaction_at
      END,
      threshold_hit_at = CASE
        WHEN threshold_hit_at IS NULL AND NEW.balance_after >= v_threshold THEN NEW.created_at
        ELSE threshold_hit_at
      END,
      is_king_or_queen = CASE
        WHEN NEW.user_id <= v_population_cap AND NEW.balance_after >= v_threshold THEN 1
        ELSE 0
      END,
      rank_tier = CASE
        WHEN NEW.user_id <= v_population_cap AND NEW.balance_after >= v_threshold THEN 'king_queen'
        WHEN NEW.balance_after >= (v_threshold / 2) THEN 'contender'
        WHEN NEW.balance_after > 0 THEN 'rising'
        ELSE 'rookie'
      END,
      updated_at = CURRENT_TIMESTAMP
  WHERE user_id = NEW.user_id;
END$$

CREATE PROCEDURE recalculateRanks()
BEGIN
  DECLARE v_threshold BIGINT DEFAULT 100000;
  DECLARE v_population_cap INT DEFAULT 1000000;
  DECLARE v_run_id BIGINT DEFAULT UNIX_TIMESTAMP(CURRENT_TIMESTAMP(6));
  DECLARE v_threshold_config BIGINT DEFAULT NULL;
  DECLARE v_population_cap_config BIGINT DEFAULT NULL;
  DECLARE v_first_user_id INT DEFAULT NULL;
  DECLARE v_first_hit_at DATETIME DEFAULT NULL;
  DECLARE v_last_recalc VARCHAR(32) DEFAULT NULL;

  SELECT CAST(config_value AS UNSIGNED)
    INTO v_threshold_config
  FROM leaderboard_config
  WHERE config_key = 'king_queen_threshold_tokens'
  LIMIT 1;

  SELECT CAST(config_value AS UNSIGNED)
    INTO v_population_cap_config
  FROM leaderboard_config
  WHERE config_key = 'reward_population_cap'
  LIMIT 1;

  SET v_threshold = COALESCE(v_threshold_config, 100000);
  SET v_population_cap = COALESCE(v_population_cap_config, 1000000);
  SET v_last_recalc = DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i:%s');

  INSERT IGNORE INTO leaderboard_tokens (user_id)
  SELECT user_id
  FROM Wo_Users;

  DROP TEMPORARY TABLE IF EXISTS tmp_leaderboard_aggregate;
  CREATE TEMPORARY TABLE tmp_leaderboard_aggregate AS
  SELECT
    u.user_id,
    COALESCE(SUM(t.amount), 0) AS total_tokens,
    COALESCE(SUM(CASE WHEN t.amount > 0 THEN t.amount ELSE 0 END), 0) AS total_earned,
    COALESCE(SUM(CASE WHEN t.amount < 0 THEN ABS(t.amount) ELSE 0 END), 0) AS total_spent,
    MAX(t.created_at) AS last_transaction_at
  FROM Wo_Users u
  LEFT JOIN token_transactions t
    ON t.user_id = u.user_id
  GROUP BY u.user_id;

  UPDATE leaderboard_tokens lt
  JOIN tmp_leaderboard_aggregate agg
    ON agg.user_id = lt.user_id
  SET lt.total_tokens = agg.total_tokens,
      lt.total_earned = agg.total_earned,
      lt.total_spent = agg.total_spent,
      lt.last_transaction_at = agg.last_transaction_at,
      lt.last_recalculated_at = NOW(),
      lt.recalculation_batch_id = v_run_id,
      lt.threshold_hit_at = NULL,
      lt.is_king_or_queen = 0,
      lt.is_first_to_threshold = 0,
      lt.rank_tier = CASE
        WHEN agg.total_tokens >= v_threshold THEN 'king_queen'
        WHEN agg.total_tokens >= (v_threshold / 2) THEN 'contender'
        WHEN agg.total_tokens > 0 THEN 'rising'
        ELSE 'rookie'
      END;

  DROP TEMPORARY TABLE IF EXISTS tmp_threshold_hits;
  CREATE TEMPORARY TABLE tmp_threshold_hits AS
  SELECT
    user_id,
    MIN(hit_at) AS threshold_hit_at
  FROM (
    SELECT
      user_id,
      created_at AS hit_at,
      running_balance
    FROM (
      SELECT
        user_id,
        transaction_id,
        created_at,
        SUM(amount) OVER (
          PARTITION BY user_id
          ORDER BY created_at, transaction_id
          ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW
        ) AS running_balance
      FROM token_transactions
    ) running_transactions
    WHERE running_balance >= v_threshold
  ) threshold_events
  GROUP BY user_id;

  UPDATE leaderboard_tokens lt
  JOIN tmp_threshold_hits th
    ON th.user_id = lt.user_id
  SET lt.threshold_hit_at = th.threshold_hit_at,
      lt.is_king_or_queen = CASE WHEN lt.user_id <= v_population_cap THEN 1 ELSE 0 END;

  IF (SELECT COUNT(*) FROM tmp_threshold_hits) > 0 THEN
    SELECT th.user_id, th.threshold_hit_at
      INTO v_first_user_id, v_first_hit_at
    FROM tmp_threshold_hits th
    ORDER BY th.threshold_hit_at ASC, th.user_id ASC
    LIMIT 1;

    UPDATE leaderboard_tokens
    SET is_first_to_threshold = CASE WHEN user_id = v_first_user_id THEN 1 ELSE 0 END
    WHERE threshold_hit_at IS NOT NULL;
  END IF;

  INSERT INTO leaderboard_config (config_key, config_value, value_type, description)
  VALUES
    ('last_recalc_run_at', v_last_recalc, 'string', 'Most recent recalculation timestamp.'),
    ('first_king_queen_user_id', IFNULL(CAST(v_first_user_id AS CHAR), NULL), 'int', 'User id of the first member to reach the threshold.'),
    ('first_king_queen_hit_at', IFNULL(DATE_FORMAT(v_first_hit_at, '%Y-%m-%d %H:%i:%s'), NULL), 'string', 'Timestamp when the first user reached the threshold.')
  ON DUPLICATE KEY UPDATE
    config_value = VALUES(config_value),
    value_type = VALUES(value_type),
    description = VALUES(description),
    updated_at = CURRENT_TIMESTAMP;
END$$

DELIMITER ;

-- ---------------------------------------------------------------------
-- One-time rebuild so the schema is immediately usable after import.
-- ---------------------------------------------------------------------
INSERT IGNORE INTO leaderboard_tokens (user_id)
SELECT user_id
FROM Wo_Users;

CALL recalculateRanks();
