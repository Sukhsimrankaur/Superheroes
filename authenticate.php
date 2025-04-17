<?php
session_start();
require 'db.php'; // database connection

// Define the general fallback login credentials (hardcoded)
$general_username = 'general_admin'; // Username for fallback login
$general_password = 'general123'; // Password for fallback login

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // First, check if the user exists in the database
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    // If the user is found in the database
    if ($user) {
        // Check if the user is an admin
        if ($user['role'] === 'admin' && password_verify($password, $user['password'])) {
            // ✅ Admin login success — Set session
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['last_activity'] = time();
            $_SESSION['login_time'] = time(); // Needed for logout sync across tabs

            $_SESSION['login_success'] = "Welcome back, Admin!";
            header('Location: dashboard.php');
            exit();
        } else {
            // ❌ If it's not an admin, proceed to the fallback general login
            if ($username === $general_username && $password === $general_password) {
                // ✅ General login credentials are valid — Set session
                $_SESSION['user_id'] = 0; // Arbitrary user ID for general login
                $_SESSION['user_role'] = 'general'; // Fallback role for general login
                $_SESSION['last_activity'] = time();
                $_SESSION['login_time'] = time(); // Needed for logout sync across tabs

                $_SESSION['login_success'] = "Welcome, General User!";
                header('Location: dashboard.php');
                exit();
            } else {
                // ❌ Invalid credentials
                $_SESSION['error'] = "Invalid username or password.";
                header('Location: login.php');
                exit();
            }
        }
    } else {
        // ❌ If user doesn't exist in the database, try the general credentials
        if ($username === $general_username && $password === $general_password) {
            // ✅ General login credentials are valid — Set session
            $_SESSION['user_id'] = 0; // Arbitrary user ID for general login
            $_SESSION['user_role'] = 'general'; // Fallback role for general login
            $_SESSION['last_activity'] = time();
            $_SESSION['login_time'] = time(); // Needed for logout sync across tabs

            $_SESSION['login_success'] = "Welcome, General User!";
            header('Location: dashboard.php');
            exit();
        } else {
            // ❌ Invalid login
            $_SESSION['error'] = "Invalid username or password.";
            header('Location: login.php');
            exit();
        }
    }
}
