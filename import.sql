USE internship_calendar;

INSERT INTO Wo_Users (
  username,
  email,
  password,
  first_name,
  last_name,
  about,
  avatar,
  admin,
  active,
  verified,
  joined
)
SELECT
  seed.username,
  seed.email,
  seed.password,
  seed.first_name,
  seed.last_name,
  seed.about,
  seed.avatar,
  seed.admin,
  seed.active,
  seed.verified,
  seed.joined
FROM (
  SELECT 'atlas_alpha' AS username, 'atlas_alpha@example.com' AS email, '$2y$12$aoICqfBI1Q.JuF4rWrYpBuXLTIj78qvJ.VGI.8aNjlFf1NQIdY..G' AS password, 'Atlas' AS first_name, 'Alpha' AS last_name, 'First leaderboard seed user.' AS about, 'https://via.placeholder.com/150/1f2937/ffffff?text=A1' AS avatar, '0' AS admin, 1 AS active, '1' AS verified, UNIX_TIMESTAMP('2026-07-01 08:00:00') AS joined
  UNION ALL
  SELECT 'bloom_beta', 'bloom_beta@example.com', '$2y$12$aoICqfBI1Q.JuF4rWrYpBuXLTIj78qvJ.VGI.8aNjlFf1NQIdY..G', 'Bloom', 'Beta', 'Second leaderboard seed user.', 'https://via.placeholder.com/150/1f2937/ffffff?text=B2', '0', 1, '1', UNIX_TIMESTAMP('2026-07-01 08:05:00')
  UNION ALL
  SELECT 'comet_charlie', 'comet_charlie@example.com', '$2y$12$aoICqfBI1Q.JuF4rWrYpBuXLTIj78qvJ.VGI.8aNjlFf1NQIdY..G', 'Comet', 'Charlie', 'Third leaderboard seed user.', 'https://via.placeholder.com/150/1f2937/ffffff?text=C3', '0', 1, '1', UNIX_TIMESTAMP('2026-07-01 08:10:00')
  UNION ALL
  SELECT 'delta_dawn', 'delta_dawn@example.com', '$2y$12$aoICqfBI1Q.JuF4rWrYpBuXLTIj78qvJ.VGI.8aNjlFf1NQIdY..G', 'Delta', 'Dawn', 'Fourth leaderboard seed user.', 'https://via.placeholder.com/150/1f2937/ffffff?text=D4', '0', 1, '1', UNIX_TIMESTAMP('2026-07-01 08:15:00')
  UNION ALL
  SELECT 'ember_echo', 'ember_echo@example.com', '$2y$12$aoICqfBI1Q.JuF4rWrYpBuXLTIj78qvJ.VGI.8aNjlFf1NQIdY..G', 'Ember', 'Echo', 'Fifth leaderboard seed user.', 'https://via.placeholder.com/150/1f2937/ffffff?text=E5', '0', 1, '1', UNIX_TIMESTAMP('2026-07-01 08:20:00')
  UNION ALL
  SELECT 'fable_flux', 'fable_flux@example.com', '$2y$12$aoICqfBI1Q.JuF4rWrYpBuXLTIj78qvJ.VGI.8aNjlFf1NQIdY..G', 'Fable', 'Flux', 'Sixth leaderboard seed user.', 'https://via.placeholder.com/150/1f2937/ffffff?text=F6', '0', 1, '1', UNIX_TIMESTAMP('2026-07-01 08:25:00')
  UNION ALL
  SELECT 'glow_gamma', 'glow_gamma@example.com', '$2y$12$aoICqfBI1Q.JuF4rWrYpBuXLTIj78qvJ.VGI.8aNjlFf1NQIdY..G', 'Glow', 'Gamma', 'Seventh leaderboard seed user.', 'https://via.placeholder.com/150/1f2937/ffffff?text=G7', '0', 1, '1', UNIX_TIMESTAMP('2026-07-01 08:30:00')
  UNION ALL
  SELECT 'harbor_henry', 'harbor_henry@example.com', '$2y$12$aoICqfBI1Q.JuF4rWrYpBuXLTIj78qvJ.VGI.8aNjlFf1NQIdY..G', 'Harbor', 'Henry', 'Eighth leaderboard seed user.', 'https://via.placeholder.com/150/1f2937/ffffff?text=H8', '0', 1, '1', UNIX_TIMESTAMP('2026-07-01 08:35:00')
  UNION ALL
  SELECT 'indigo_iris', 'indigo_iris@example.com', '$2y$12$aoICqfBI1Q.JuF4rWrYpBuXLTIj78qvJ.VGI.8aNjlFf1NQIdY..G', 'Indigo', 'Iris', 'Ninth leaderboard seed user.', 'https://via.placeholder.com/150/1f2937/ffffff?text=I9', '0', 1, '1', UNIX_TIMESTAMP('2026-07-01 08:40:00')
  UNION ALL
  SELECT 'juno_jet', 'juno_jet@example.com', '$2y$12$aoICqfBI1Q.JuF4rWrYpBuXLTIj78qvJ.VGI.8aNjlFf1NQIdY..G', 'Juno', 'Jet', 'Tenth leaderboard seed user.', 'https://via.placeholder.com/150/1f2937/ffffff?text=J10', '0', 1, '1', UNIX_TIMESTAMP('2026-07-01 08:45:00')
) AS seed
LEFT JOIN Wo_Users existing
  ON existing.email = seed.email
  OR existing.username = seed.username
WHERE existing.user_id IS NULL;

USE internship_calendar;

INSERT INTO token_transactions (
  user_id,
  amount,
  reason,
  reference_type,
  reference_id,
  created_at
)
SELECT u.user_id, seed.amount, seed.reason, seed.reference_type, seed.reference_id, seed.created_at
FROM (
  SELECT 'atlas_alpha' AS username, 25000 AS amount, 'Kickoff bonus' AS reason, 'seed' AS reference_type, 'atlas-alpha-001' AS reference_id, '2026-07-01 09:00:00' AS created_at
  UNION ALL
  SELECT 'atlas_alpha', 40000, 'Milestone bonus', 'seed', 'atlas-alpha-002', '2026-07-01 10:00:00'
  UNION ALL
  SELECT 'atlas_alpha', 40000, 'Performance bonus', 'seed', 'atlas-alpha-003', '2026-07-01 11:00:00'
  UNION ALL
  SELECT 'bloom_beta', 20000, 'Kickoff bonus', 'seed', 'bloom-beta-001', '2026-07-01 12:00:00'
  UNION ALL
  SELECT 'bloom_beta', 30000, 'Weekly bonus', 'seed', 'bloom-beta-002', '2026-07-02 09:00:00'
  UNION ALL
  SELECT 'bloom_beta', 25000, 'Delivery bonus', 'seed', 'bloom-beta-003', '2026-07-02 10:00:00'
  UNION ALL
  SELECT 'bloom_beta', 15000, 'Retention bonus', 'seed', 'bloom-beta-004', '2026-07-02 11:00:00'
  UNION ALL
  SELECT 'comet_charlie', 60000, 'Kickoff bonus', 'seed', 'comet-charlie-001', '2026-07-02 12:00:00'
  UNION ALL
  SELECT 'comet_charlie', 45000, 'Quarterly bonus', 'seed', 'comet-charlie-002', '2026-07-03 09:00:00'
  UNION ALL
  SELECT 'delta_dawn', 5000, 'Starter bonus', 'seed', 'delta-dawn-001', '2026-07-03 10:00:00'
  UNION ALL
  SELECT 'delta_dawn', 7000, 'Cleanup bonus', 'seed', 'delta-dawn-002', '2026-07-03 11:00:00'
  UNION ALL
  SELECT 'ember_echo', 80000, 'Kickoff bonus', 'seed', 'ember-echo-001', '2026-07-03 12:00:00'
  UNION ALL
  SELECT 'ember_echo', -5000, 'Issue correction', 'seed', 'ember-echo-002', '2026-07-04 09:00:00'
  UNION ALL
  SELECT 'ember_echo', 30000, 'Recovery bonus', 'seed', 'ember-echo-003', '2026-07-04 10:00:00'
  UNION ALL
  SELECT 'fable_flux', 120000, 'Launch bonus', 'seed', 'fable-flux-001', '2026-07-04 11:00:00'
  UNION ALL
  SELECT 'glow_gamma', 40000, 'Starter bonus', 'seed', 'glow-gamma-001', '2026-07-04 12:00:00'
  UNION ALL
  SELECT 'glow_gamma', 20000, 'Consistency bonus', 'seed', 'glow-gamma-002', '2026-07-05 09:00:00'
  UNION ALL
  SELECT 'harbor_henry', 150000, 'Launch bonus', 'seed', 'harbor-henry-001', '2026-07-05 10:00:00'
  UNION ALL
  SELECT 'indigo_iris', 10000, 'Starter bonus', 'seed', 'indigo-iris-001', '2026-07-05 11:00:00'
  UNION ALL
  SELECT 'indigo_iris', 50000, 'Project bonus', 'seed', 'indigo-iris-002', '2026-07-05 12:00:00'
  UNION ALL
  SELECT 'indigo_iris', 30000, 'Completion bonus', 'seed', 'indigo-iris-003', '2026-07-06 09:00:00'
  UNION ALL
  SELECT 'juno_jet', 1000, 'Starter bonus', 'seed', 'juno-jet-001', '2026-07-06 10:00:00'
  UNION ALL
  SELECT 'juno_jet', 2000, 'Micro bonus', 'seed', 'juno-jet-002', '2026-07-06 11:00:00'
  UNION ALL
  SELECT 'juno_jet', 1500, 'Consistency bonus', 'seed', 'juno-jet-003', '2026-07-06 12:00:00'
) AS seed
JOIN Wo_Users u
  ON u.username = seed.username
LEFT JOIN token_transactions existing
  ON existing.reference_type = seed.reference_type
  AND existing.reference_id = seed.reference_id
WHERE existing.transaction_id IS NULL;

CALL recalculateRanks();

