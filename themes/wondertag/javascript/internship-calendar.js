/**
 * Displays a confirmation dialog before deleting an event.
 *
 * @returns {boolean} True if the user confirms the action.
 */
function confirmDelete() {
    return window.confirm('Delete this event? This cannot be undone.');
}

/**
 * Automatically dismisses flash messages after 4 seconds.
 */
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
}

/**
 * Sends an AJAX request to the Internship Calendar endpoint.
 *
 * @param {string} action The action to perform.
 * @param {Object} data Additional request data.
 * @returns {Promise<Object>} JSON response from the server.
 */
async function sendRequest(action, data = {}) {

    try {

        const formData = new FormData();

        // Required action for the PHP endpoint
        formData.append('action', action);

        Object.entries(data).forEach(([key, value]) => {
            formData.append(key, value);
        });

        const response = await fetch(
            'xhr/internship_calendar.php',
            {
                method: 'POST',
                body: formData
            }
        );

        if (!response.ok) {
            throw new Error(`HTTP Error: ${response.status}`);
        }

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

/**
 * Save a new event.
 *
 * @param {Object} data
 * @returns {Promise<Object>}
 */
async function saveEvent(data) {
    return sendRequest('save_event', data);
}

/**
 * Update an existing event.
 *
 * @param {Object} data
 * @returns {Promise<Object>}
 */
async function updateEvent(data) {
    return sendRequest('update_event', data);
}

/**
 * Delete an event.
 *
 * @param {number|string} id
 * @returns {Promise<Object>}
 */
async function deleteEvent(id) {
    return sendRequest('delete_event', { id });
}

/**
 * Search events.
 *
 * @param {string} query
 * @returns {Promise<Object>}
 */
async function searchEvents(query) {
    return sendRequest('search_events', { query });
}

/**
 * Filter events.
 *
 * @param {Object} filters
 * @returns {Promise<Object>}
 */
async function filterEvents(filters) {
    return sendRequest('filter_events', filters);
}

/**
 * Toggle an event's completion status.
 *
 * @param {number|string} id
 * @returns {Promise<Object>}
 */
async function toggleCompletion(id) {
    return sendRequest('toggle_completion', { id });
}

document.addEventListener('DOMContentLoaded', () => {

    // Register delete confirmation for delete links
    const deleteLinks = document.querySelectorAll('.js-confirm-delete');

    deleteLinks.forEach(link => {

        link.addEventListener('click', event => {

            if (!confirmDelete()) {
                event.preventDefault();
            }

        });

    });

    dismissFlashMessage();

});