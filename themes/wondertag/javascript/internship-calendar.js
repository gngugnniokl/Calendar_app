(() => {
    "use strict";

    const XHR_URL = 'xhr/internship_calendar.php';

    /**
     * Automatically dismisses flash messages after 4 seconds.
     */
    function dismissFlashMessage() {
        const flash = document.querySelector('.flash');
        if (!flash) return;

        setTimeout(() => {
            flash.style.transition = 'opacity 0.4s ease';
            flash.style.opacity = '0';
            setTimeout(() => { flash.remove(); }, 400);
        }, 4000);
    }

    /**
     * Displays a user-friendly message.
     * @param {string} message 
     * @param {string} type 'success' or 'error'
     */
    function showMessage(message, type = 'success') {
        alert(`${type.toUpperCase()}: ${message}`);
    }

    /**
     * Sets the loading state of a button.
     * @param {HTMLButtonElement} button 
     * @param {boolean} isLoading 
     */
    function setLoading(button, isLoading) {
        if (!button) return;
        
        button.disabled = isLoading;
        const originalText = button.dataset.originalText || button.textContent;
        
        if (isLoading) {
            button.dataset.originalText = originalText;
            button.textContent = 'Loading...';
        } else {
            button.textContent = originalText;
        }
    }

    /**
     * Sends an AJAX request to the server.
     *
     * @param {string} action The action to perform.
     * @param {Object} data Additional request data.
     * @returns {Promise<Object>} JSON response from the server.
     */
    async function sendRequest(action, data = {}) {
        try {
            const formData = new FormData();
            formData.append('action', action);

            Object.entries(data).forEach(([key, value]) => {
                if (value !== undefined && value !== null) {
                    formData.append(key, value);
                }
            });

            const response = await fetch(XHR_URL, {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                throw new Error(`Request failed. HTTP Error: ${response.status}`);
            }

            const result = await response.json();
            return result;

        } catch (error) {
            console.error('AJAX Error:', error);
            return {
                success: false,
                message: 'Unable to connect. Please try again.',
                data: null
            };
        }
    }

    /**
     * Save a new event.
     * @param {Object} data
     * @returns {Promise<Object>}
     */
    async function saveEvent(data) {
        return sendRequest('save_event', data);
    }

    /**
     * Update an existing event.
     * @param {Object} data
     * @returns {Promise<Object>}
     */
    async function updateEvent(data) {
        return sendRequest('update_event', data);
    }

    /**
     * Delete an event.
     * @param {number|string} id
     * @returns {Promise<Object>}
     */
    async function deleteEvent(id) {
        return sendRequest('delete_event', { id });
    }

    /**
     * Search events.
     * @param {string} query
     * @returns {Promise<Object>}
     */
    async function searchEvents(query) {
        return sendRequest('search_events', { query });
    }

    /**
     * Filter events.
     * @param {Object} filters
     * @returns {Promise<Object>}
     */
    async function filterEvents(filters) {
        return sendRequest('filter_events', filters);
    }

    /**
     * Toggle an event's completion status.
     * @param {number|string} id
     * @returns {Promise<Object>}
     */
    async function toggleCompletion(id) {
        return sendRequest('toggle_completion', { id });
    }

    /**
     * Opens the event modal.
     */
    function openModal() {
        const modal = document.getElementById('event-modal');
        if (modal) {
            modal.style.display = 'flex';
            // slight delay to allow display to apply before opacity transition
            setTimeout(() => modal.classList.add('show'), 10);
        }
    }

    /**
     * Closes the event modal.
     */
    function closeModal() {
        const modal = document.getElementById('event-modal');
        if (modal) {
            modal.classList.remove('show');
            setTimeout(() => { modal.style.display = 'none'; }, 250);
        }
    }

    /**
     * Clears the modal form for creating a new event.
     */
    function clearForm() {
        const form = document.getElementById('event-modal-form');
        if (form) {
            form.reset();
            const idInput = form.querySelector('[name="id"]');
            if (idInput) {
                idInput.value = '';
            }
            
            const titleEl = document.getElementById('event-modal-title');
            if (titleEl) {
                titleEl.textContent = 'Create Event';
            }
        }
    }

    /**
     * Populates the modal form for editing an existing event.
     * @param {Object} data 
     */
    function populateForm(data) {
        const form = document.getElementById('event-modal-form');
        if (form) {
            Object.keys(data).forEach(key => {
                const input = form.querySelector(`[name="${key}"]`);
                if (input) {
                    input.value = data[key];
                }
            });
            
            const titleEl = document.getElementById('event-modal-title');
            if (titleEl) {
                titleEl.textContent = 'Edit Event';
            }
        }
    }

    /**
     * Debounces a function call.
     * @param {Function} func 
     * @param {number} wait 
     * @returns {Function}
     */
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    document.addEventListener('DOMContentLoaded', () => {

        dismissFlashMessage();

        // Search Input Setup
        const searchInput = document.getElementById('event-search');
        if (searchInput) {
            searchInput.addEventListener('keyup', debounce(async (e) => {
                const query = e.target.value;
                const result = await searchEvents(query);
                if (result.success) {
                    console.log('Search results retrieved:', result.data);
                } else {
                    showMessage(result.message, 'error');
                }
            }, 300));
        }

        // Filters Setup
        const filterElements = document.querySelectorAll('.event-filter');
        filterElements.forEach(el => {
            el.addEventListener('change', async () => {
                const filters = {
                    week: document.querySelector('[name="filter_week"]')?.value || '',
                    day: document.querySelector('[name="filter_day"]')?.value || '',
                    date_from: document.querySelector('[name="filter_date_from"]')?.value || '',
                    date_to: document.querySelector('[name="filter_date_to"]')?.value || ''
                };
                const result = await filterEvents(filters);
                if (result.success) {
                    console.log('Filtered events retrieved:', result.data);
                } else {
                    showMessage(result.message, 'error');
                }
            });
        });

        // Toggle Completion Flow
        document.addEventListener('change', async (e) => {
            const toggle = e.target.closest('.js-toggle-completion');
            if (toggle) {
                const id = toggle.dataset.id;
                
                // Optimistic UI could be done here, but let's wait for server
                setLoading(toggle, true);
                
                const result = await toggleCompletion(id);
                
                setLoading(toggle, false);
                
                if (result.success) {
                    showMessage(result.message);
                    toggle.checked = result.data.completed == 1;
                } else {
                    showMessage(result.message, 'error');
                    toggle.checked = !toggle.checked; // Revert visually
                }
            }
        });

        // Delete Flow
        document.addEventListener('click', async (e) => {
            const deleteBtn = e.target.closest('.js-confirm-delete');
            if (deleteBtn) {
                e.preventDefault();
                
                if (window.confirm('Delete this event? This cannot be undone.')) {
                    const id = deleteBtn.dataset.id;
                    setLoading(deleteBtn, true);
                    
                    const result = await deleteEvent(id);
                    
                    if (result.success) {
                        showMessage(result.message);
                        const row = deleteBtn.closest('.event-row, li, .event-detail, .timeline-item');
                        if (row) {
                            row.remove();
                        } else {
                            window.location.reload();
                        }
                    } else {
                        setLoading(deleteBtn, false);
                        showMessage(result.message, 'error');
                    }
                }
            }
        });

        // Modal triggers
        document.addEventListener('click', (e) => {
            const createBtn = e.target.closest('.js-open-create-modal');
            if (createBtn) {
                e.preventDefault();
                clearForm();
                openModal();
            }
            
            const editBtn = e.target.closest('.js-open-edit-modal');
            if (editBtn) {
                e.preventDefault();
                // Assuming data is passed as data-attributes on the button
                const data = Object.assign({}, editBtn.dataset);
                populateForm(data);
                openModal();
            }
            
            const closeBtn = e.target.closest('.js-close-modal');
            if (closeBtn) {
                e.preventDefault();
                closeModal();
            }
        });

        // Form Submission Flow
        const eventForm = document.getElementById('event-modal-form');
        if (eventForm) {
            eventForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                
                const formData = new FormData(eventForm);
                const data = Object.fromEntries(formData.entries());
                const submitButton = eventForm.querySelector('button[type="submit"]');
                
                setLoading(submitButton, true);
                
                let result;
                if (data.id && data.id !== '0' && data.id !== '') {
                    result = await updateEvent(data);
                } else {
                    result = await saveEvent(data);
                }

                setLoading(submitButton, false);

                if (result.success) {
                    showMessage(result.message);
                    closeModal();
                    window.location.reload();
                } else {
                    showMessage(result.message, 'error');
                }
            });
        }
    });

})();