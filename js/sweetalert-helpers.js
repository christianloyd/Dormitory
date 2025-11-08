/**
 * SweetAlert2 Helper Functions
 * Dormitory Management System
 *
 * Centralized helpers for consistent alert/notification handling across the application
 */

const AlertHelper = {
    /**
     * Display a success message with auto-dismiss
     * @param {string} title - Alert title
     * @param {string} message - Alert message
     * @param {function} callback - Optional callback after alert closes
     * @param {number} timer - Auto-dismiss timer in ms (default: 1500)
     */
    success: (title, message, callback = null, timer = 1500) => {
        return Swal.fire({
            title: title,
            text: message,
            icon: 'success',
            timer: timer,
            showConfirmButton: false
        }).then(() => {
            if (callback) callback();
        });
    },

    /**
     * Display an error message
     * @param {string} title - Alert title (default: 'Error')
     * @param {string} message - Error message
     */
    error: (title = 'Error', message) => {
        return Swal.fire({
            title: title,
            text: message,
            icon: 'error',
            confirmButtonText: 'OK',
            confirmButtonColor: '#d33'
        });
    },

    /**
     * Display a warning message
     * @param {string} title - Alert title
     * @param {string} message - Warning message
     */
    warning: (title, message) => {
        return Swal.fire({
            title: title,
            text: message,
            icon: 'warning',
            confirmButtonText: 'OK',
            confirmButtonColor: '#f0ad4e'
        });
    },

    /**
     * Display an info message
     * @param {string} title - Alert title
     * @param {string} message - Info message
     */
    info: (title, message) => {
        return Swal.fire({
            title: title,
            text: message,
            icon: 'info',
            confirmButtonText: 'OK',
            confirmButtonColor: '#5bc0de'
        });
    },

    /**
     * Display a confirmation dialog
     * @param {string} title - Confirmation title
     * @param {string} message - Confirmation message
     * @param {string} confirmText - Confirm button text (default: 'Yes')
     * @param {string} cancelText - Cancel button text (default: 'Cancel')
     */
    confirm: (title, message, confirmText = 'Yes', cancelText = 'Cancel') => {
        return Swal.fire({
            title: title,
            text: message,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: confirmText,
            cancelButtonText: cancelText,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d'
        });
    },

    /**
     * Display a delete confirmation dialog
     * @param {string} entityName - Name of the entity to delete
     * @param {string} entityType - Type of entity (default: 'item')
     */
    confirmDelete: (entityName, entityType = 'item') => {
        return Swal.fire({
            title: 'Are you sure?',
            text: `Do you want to delete ${entityName}?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d'
        });
    },

    /**
     * Display a loading/processing indicator
     * @param {string} title - Loading title (default: 'Processing...')
     * @param {string} message - Loading message
     */
    loading: (title = 'Processing...', message = 'Please wait') => {
        return Swal.fire({
            title: title,
            text: message,
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
    },

    /**
     * Close any open SweetAlert
     */
    close: () => {
        Swal.close();
    },

    /**
     * Display a toast notification (small notification in corner)
     * @param {string} message - Toast message
     * @param {string} icon - Icon type ('success', 'error', 'warning', 'info')
     * @param {string} position - Position (default: 'top-end')
     * @param {number} timer - Auto-dismiss timer in ms (default: 3000)
     */
    toast: (message, icon = 'success', position = 'top-end', timer = 3000) => {
        const Toast = Swal.mixin({
            toast: true,
            position: position,
            showConfirmButton: false,
            timer: timer,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });

        return Toast.fire({
            icon: icon,
            title: message
        });
    }
};

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AlertHelper;
}
