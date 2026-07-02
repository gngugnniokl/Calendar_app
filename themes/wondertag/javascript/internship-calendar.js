/** 
 * @returns {boolean}
 */
function confirmDelete() {
    return window.confirm('Delete this event? This cannot be undone.'); //confirms delete
}

function dismissFlashMessage() {
    const flash = document.querySelector('.flash');

    if (!flash) {
        return;
    }

    setTimeout(() => {

        flash.style.transition = 'opacity 0.4s ease';
        flash.style.opacity = '0';

        setTimeout(() => {
            flash.remove();
        }, 400);

    }, 4000);
} // automatically dismisses a flash message

/**
 *
 * @param {string} action
 * @param {Object} data
 * @returns {Promise<Object>}
 */
async function sendRequest(action, data = {}) {

    try {

        const formData = new FormData();

        // Add the action
        formData.append('action', action);

        // Add any additional request data
        Object.entries(data).forEach(([key, value]) => {
            formData.append(key, value);
        });

        const response = await fetch(
            'layout/internship_calendar/xhr/internship_calendar.php',
            {
                method: 'POST',
                body: formData
            }
        );

        return await response.json();

    } catch (error) {

        console.error('AJAX Error:', error);

        return {
            success: false,
            message: 'An unexpected error occurred.',
            data: null
        };

    }

}

// Event Listeners

document.addEventListener('DOMContentLoaded', () => {

    // Register delete confirmation on all delete links
    const deleteLinks = document.querySelectorAll('.js-confirm-delete');

    deleteLinks.forEach(link => {

        link.addEventListener('click', event => {

            if (!confirmDelete()) {
                event.preventDefault();
            }

        });

    });

    // Automatically hide flash messages
    dismissFlashMessage();

});