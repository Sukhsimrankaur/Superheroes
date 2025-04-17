<?php
session_start();
require 'db.php'; // Include the database connection file

$error = ''; // Initialize error message variable
$success = ''; 

// Check if the user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input to prevent XSS attacks
    $username = htmlspecialchars(trim($_POST['username']));
    $password = trim($_POST['password']);

    // Check for general admin login credentials
    if ($username === 'admin' && $password === 'admin123') {
        // Set session variables for general admin login
        $_SESSION['user_id'] = 1; // Set a generic ID for this general admin (use the actual user ID in production)
        $_SESSION['user_role'] = 'admin'; // Set the role to admin
        $_SESSION['last_activity'] = time(); // Store last activity time
        $_SESSION['login_time'] = time(); // Set login time for tracking

        // Redirect to dashboard
        header('Location: dashboard.php');
        exit();
    }

    // Check if username exists and the password is correct
    $query = 'SELECT * FROM users WHERE username = :username LIMIT 1';
    $stmt = $db->prepare($query);
    $stmt->bindParam(':username', $username, PDO::PARAM_STR);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {
        // Successful login
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['last_activity'] = time();
        $_SESSION['login_time'] = time();

        // Redirect to the dashboard
        header('Location: dashboard.php');
        exit();
    } else {
        // Invalid login
        $error = 'Invalid username or password.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="main.css">
                <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">

    <title>Login</title>
</head>
<body>
    <h1>Login</h1>
    <nav class="d-flex justify-content-between">
            <div>
                <a href="index.php" id="home" class="mx-3">Home</a>
                <a href="category_menu.php">Search</a>
            </div>
            <div>
                <a href="register.php">Register</a>
                <a href="login.php" class="mx-3">Admin Login</a>
            </div>
        </nav>

    <!-- Show error message if login fails -->
    <?php if (!empty($error)): ?>
        <div class="error-message">
            <p style="color: red;"><?= htmlspecialchars($error) ?></p>
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

    <p>Don't have an account? <a href="register.php" style="color: blue; font-weight: bold; text-decoration: none;">Register here</a>.</p>

</body>
</html>
