// Session Timeout Warning System
// Shows a warning dialog at 20 minutes of inactivity

(function () {
    'use strict';

    // Configuration: 20 minutes = 1200 seconds
    const SESSION_TIMEOUT = 1200000; // 20 minutes in milliseconds
    const WARNING_TIME = SESSION_TIMEOUT - 60000; // Show warning 1 minute before timeout

    let timeoutTimer;
    let warningTimer;
    let warningShown = false;

    // Reset the session timers
    function resetTimers() {
        clearTimeout(timeoutTimer);
        clearTimeout(warningTimer);
        warningShown = false;

        // Set warning timer (19 minutes)
        warningTimer = setTimeout(showWarning, WARNING_TIME);

        // Set timeout timer (20 minutes)
        timeoutTimer = setTimeout(logoutUser, SESSION_TIMEOUT);
    }

    // Show warning dialog
    function showWarning() {
        if (warningShown) return;
        warningShown = true;

        swal({
            title: "Sesión por Expirar",
            text: "La sesión está apunto de cerrar. ¿Desea continuar viendo?",
            type: "warning",
            showCancelButton: true,
            confirmButtonText: "Continuar Viendo",
            cancelButtonText: "Cerrar Sesión",
            closeOnConfirm: true,
            closeOnCancel: true
        }, function (isConfirm) {
            if (isConfirm) {
                // User wants to continue - keep session alive
                keepSessionAlive();
            } else {
                // User wants to logout
                logoutUser();
            }
        });
    }

    // Keep session alive by making a request to the server
    function keepSessionAlive() {
        fetch('./keep-alive.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'same-origin'
        })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Reset timers for another 20 minutes
                    resetTimers();
                } else {
                    // Session already expired on server
                    logoutUser();
                }
            })
            .catch(error => {
                console.error('Error keeping session alive:', error);
                logoutUser();
            });
    }

    // Logout user and redirect to login
    function logoutUser() {
        window.location.href = './logout.php';
    }

    // Events that indicate user activity
    const activityEvents = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'];

    // Debounce function to avoid too many resets
    let activityTimeout;
    function onActivity() {
        clearTimeout(activityTimeout);
        activityTimeout = setTimeout(() => {
            if (!warningShown) {
                resetTimers();
            }
        }, 1000); // Debounce for 1 second
    }

    // Attach activity listeners
    activityEvents.forEach(event => {
        document.addEventListener(event, onActivity, true);
    });

    // Initialize timers when page loads
    resetTimers();

})();
