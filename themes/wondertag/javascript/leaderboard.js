/**
 * themes/wondertag/javascript/leaderboard.js
 *
 * Logic for fetching and rendering leaderboard data.
 * Built by Developer 5 (Frontend Interactive Developer).
 */

$(document).ready(function () {
  // Initial fetch for the sticky footer or dropdown (Dev 6 requirement)
  if ($("#lb-user-tokens").length) {
    $.get("xhr/leaderboard.php?action=user", function (response) {
      if (response.success) {
        $("#lb-user-tokens").text(response.data.tokens);
        $("#lb-user-rank").text("Rank " + response.data.rank);
        if ($("#lb-progress-bar").length) {
          $("#lb-progress-bar").css(
            "width",
            response.data.progress_percent + "%",
          );
        }
      }
    });
  }
});
