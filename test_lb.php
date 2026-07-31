<?php
require 'assets/init.php';

// Test direct query
$sql = "SELECT u.user_id, u.username, CONCAT(u.first_name, ' ', u.last_name) as name, u.avatar, 
        COALESCE(lt.total_tokens, 0) as points, lt.current_rank as `rank`, lt.rank_tier, lt.is_king_or_queen,
        u.joined, u.admin
        FROM Wo_Users u
        LEFT JOIN leaderboard_tokens lt ON u.user_id = lt.user_id
        WHERE u.active = '1' ORDER BY points DESC, u.user_id ASC LIMIT 7 OFFSET 0";
$r = mysqli_query($conn, $sql);
if (!$r) { echo "Error: " . mysqli_error($conn) . "\n"; exit; }
echo "Direct query rows: " . mysqli_num_rows($r) . "\n";
while ($row = mysqli_fetch_assoc($r)) {
    echo $row['user_id'] . " | " . $row['name'] . " | " . $row['points'] . " | rank:" . $row['rank'] . "\n";
}

echo "\n--- Testing prepared statement (no search) ---\n";
$limit = 7;
$offset = 0;
$sql2 = "SELECT u.user_id, u.username, CONCAT(u.first_name, ' ', u.last_name) as name, u.avatar, 
        COALESCE(lt.total_tokens, 0) as points, lt.current_rank as `rank`, lt.rank_tier, lt.is_king_or_queen,
        u.joined, u.admin
        FROM Wo_Users u
        LEFT JOIN leaderboard_tokens lt ON u.user_id = lt.user_id
        WHERE u.active = '1' ORDER BY points DESC, u.user_id ASC LIMIT ? OFFSET ?";
$stmt = mysqli_prepare($conn, $sql2);
if (!$stmt) { echo "Prepare error: " . mysqli_error($conn) . "\n"; exit; }
mysqli_stmt_bind_param($stmt, 'ii', $limit, $offset);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
echo "Prepared statement rows: " . mysqli_num_rows($res) . "\n";
while ($row = mysqli_fetch_assoc($res)) {
    echo $row['user_id'] . " | " . $row['name'] . " | " . $row['points'] . "\n";
}
mysqli_stmt_close($stmt);

echo "\n--- Testing Wo_GetLeaderboardData function ---\n";
$result = Wo_GetLeaderboardData($conn, ['limit' => 7]);
echo "Function returned: " . count($result) . " rows\n";
foreach ($result as $r) {
    echo $r['user_id'] . " | " . $r['name'] . " | " . $r['points'] . "\n";
}
