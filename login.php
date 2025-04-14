<?php
session_start(); // Start the session
require 'db.php';

$error = ''; // Variable to hold error messages
$success = ''; // Variable to hold success messages

// Check if the user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input to prevent XSS attacks
    $username = htmlspecialchars(trim($_POST['username']));
    $password = trim($_POST['password']); // No need to sanitize password

    try {
        // Prepare the query to fetch user data by username
        $query = 'SELECT * FROM users WHERE username = :username LIMIT 1';
        $statement = $db->prepare($query);
        $statement->bindParam(':username', $username, PDO::PARAM_STR);
        $statement->execute();
        $user = $statement->fetch(PDO::FETCH_ASSOC); // Get the user data

        // Check if the user exists and password is correct
        if ($user && password_verify($password, $user['password_hash'])) {
            // Check if the user is an admin
            if ($user['role'] !== 'admin') {
                $error = "Access denied. Only admins can log in.";
            } else {
                // Successful login, store user data in session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_role'] = $user['role']; // Optional: role for access control
                $_SESSION['last_activity'] = time(); // Store the last activity time

                session_regenerate_id(true); // Regenerate session ID to prevent session fixation

                // Set sucess message
                $_SESSION['login_success'] = 'Login sucessful! Welcome to the dashboard.';
                
                // Redirect the user to the dashboard page after a brief message
                header('Location: dashboard.php');
                exit();
            }
        } else {
            // Invalid credentials, display error message
            $error = 'Invalid username or password.';
        }
    } catch (PDOException $e) {
        // Database error, handle it
        $error = 'Database error: ' . $e->getMessage();
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="main.css">
    <title>Login</title>
</head>
<body>
    <h1>Admin Login</h1>
     <nav style="text-align: center;">
    <a href="index.php" style="text-decoration: none; color: white;">Home</a>
</nav>


    <!-- Display error message if login fails -->
    <?php if (!empty($error)): ?>
        <div class="error-message">
            <p style="color: black;"><?= htmlspecialchars($error) ?></p>
        </div>
    <?php endif; ?>

    <!-- Login form -->
    <form method="POST" action="login.php">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required>

        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>

        <button type="submit">Login</button>
    </form>

    <!-- Link to the registration page if the user doesn't have an account -->
    <p>Don't have an account? <a href="register.php" style="color: blue; font-weight: bold; text-decoration: none;">Register here</a>.</p>
</body>
</html>
