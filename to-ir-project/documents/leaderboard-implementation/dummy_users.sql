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

