// Tribbbal Internship Calendar — client-side interactivity

document.addEventListener('DOMContentLoaded', function () {
    // Confirm before any delete link fires (delete-event.php does the real work server-side)
    var deleteLinks = document.querySelectorAll('.js-confirm-delete');
    deleteLinks.forEach(function (link) {
        link.addEventListener('click', function (e) {
            var ok = window.confirm('Delete this event? This cannot be undone.');
            if (!ok) {
                e.preventDefault();
            }
        });
    });

    // Auto-dismiss flash messages after a few seconds
    var flash = document.querySelector('.flash');
    if (flash) {
        setTimeout(function () {
            flash.style.transition = 'opacity 0.4s ease';
            flash.style.opacity = '0';
            setTimeout(function () { flash.remove(); }, 400);
        }, 4000);
    }
});