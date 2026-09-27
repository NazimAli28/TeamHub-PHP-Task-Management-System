// Navbar functionality
function toggleNavMenu() {
    const navMenu = document.getElementById('nav-menu');
    const toggleButton = document.querySelector('.nav-toggle');
    const expanded = toggleButton.getAttribute('aria-expanded') === 'true';
    toggleButton.setAttribute('aria-expanded', !expanded);
    navMenu.classList.toggle('active');
}

// Dark mode functionality
function toggleDarkMode() {
    document.body.classList.toggle("dark");
    localStorage.setItem("darkMode", document.body.classList.contains("dark"));
    
    // Dispatch event for other components to listen to
    const event = new CustomEvent('darkModeToggle', {
        detail: { isDark: document.body.classList.contains("dark") }
    });
    window.dispatchEvent(event);
}

// Initialize dark mode on page load
document.addEventListener('DOMContentLoaded', function() {
    if (localStorage.getItem("darkMode") === "true") {
        document.body.classList.add("dark");
    }
    
    // Close mobile menu when clicking outside
    document.addEventListener('click', function(event) {
        const navMenu = document.getElementById('nav-menu');
        const toggleButton = document.querySelector('.nav-toggle');
        
        if (navMenu && toggleButton && 
            !navMenu.contains(event.target) && 
            !toggleButton.contains(event.target) &&
            navMenu.classList.contains('active')) {
            toggleNavMenu();
        }
    });
    
    // Handle escape key to close menu
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            const navMenu = document.getElementById('nav-menu');
            if (navMenu && navMenu.classList.contains('active')) {
                toggleNavMenu();
            }
        }
    });
});

// Responsive adjustments
window.addEventListener('resize', function() {
    const navMenu = document.getElementById('nav-menu');
    const toggleButton = document.querySelector('.nav-toggle');
    
    if (window.innerWidth > 768 && navMenu && navMenu.classList.contains('active')) {
        navMenu.classList.remove('active');
        if (toggleButton) {
            toggleButton.setAttribute('aria-expanded', 'false');
        }
    }
});
