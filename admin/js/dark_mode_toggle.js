document.addEventListener('DOMContentLoaded', function () {
  const toggleCheckboxes = document.querySelectorAll('.theme-switch__checkbox');

  // Function to apply theme based on isDark boolean
  function applyTheme(isDark) {
    if (isDark) {
      document.body.classList.add('dark');
    } else {
      document.body.classList.remove('dark');
    }
  }

  // Load saved theme from localStorage
  const savedTheme = localStorage.getItem('theme');
  const isDark = savedTheme === 'dark';

  // Apply saved theme
  applyTheme(isDark);

  // Set checkbox state accordingly
  toggleCheckboxes.forEach(checkbox => {
    checkbox.checked = isDark;

    // Add event listener for toggle change
    checkbox.addEventListener('change', function () {
      const isChecked = this.checked;
      applyTheme(isChecked);
      localStorage.setItem('theme', isChecked ? 'dark' : 'light');
    });
  });
});
