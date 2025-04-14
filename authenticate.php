<?php
// Start the session at the very beginning of the script
session_start();

// Admin login credentials
define('ADMIN_LOGIN', 'wally');
define('ADMIN_PASSWORD', 'mypass');

// Check if the user is logged in or if authentication headers are not set
if (!isset($_SESSION['user_id']) &&
    (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW']) ||
     $_SERVER['PHP_AUTH_USER'] !== ADMIN_LOGIN || $_SERVER['PHP_AUTH_PW'] !== ADMIN_PASSWORD)) {
    // If not authenticated, force a login popup using HTTP Basic Authentication
    header('HTTP/1.1 401 Unauthorized');
    header('WWW-Authenticate: Basic realm="Superhero Database"');
    exit("Access Denied: Username and password required.");
}
?>
