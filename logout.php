<?php
session_start();
session_unset();
session_destroy();

// Clear remember me cookie
if (isset($_COOKIE['remember_user'])) {
    setcookie('remember_user', '', time() - 3600, '/');
}
?>

<script>
localStorage.setItem('logout', Date.now());
window.location.href = '/';
</script>

<?php
exit;
