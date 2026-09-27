// Multi-tab logout detection
window.addEventListener('storage', function(e) {
    if (e.key === 'logout' && e.newValue) {
        window.location.href = '/';
    }
});

// Periodic session check
setInterval(function() {
    fetch('../api/check_session.php')
        .then(response => response.json())
        .then(data => {
            if (!data.logged_in) {
                localStorage.setItem('logout', Date.now());
                window.location.href = '/';
            }
        })
        .catch(error => console.error('Session check failed:', error));
}, 30000); // Check every 30 seconds
