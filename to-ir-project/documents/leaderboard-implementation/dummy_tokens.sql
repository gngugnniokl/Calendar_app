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

