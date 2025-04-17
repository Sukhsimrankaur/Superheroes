<?php
session_start();
require 'db.php'; // database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Fetch admin from database
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // ✅ CREDENTIALS ARE VALID — SET SESSION
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_role'] = $user['role']; // Should be 'admin'
        $_SESSION['last_activity'] = time();
        $_SESSION['login_time'] = time(); // 🔐 Needed for logout sync across tabs

        $_SESSION['login_success'] = "Welcome back, Admin!";
        header('Location: dashboard.php');
        exit();
    } else {
        // ❌ Invalid login
        $_SESSION['error'] = "Invalid username or password.";
        header('Location: login.php');
        exit();
    }
}
?>
