<?php
session_start(); // Start the session
require('db.php'); // Include the database connection file

// Initialize error variable
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs to prevent XSS attacks
    $username = htmlspecialchars(trim($_POST['username']));
    $email = htmlspecialchars(trim($_POST['email']));
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Check if passwords match
    if ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Check if the username or email already exists
        try {
            // Check for existing username
            $query = "SELECT COUNT(*) FROM users WHERE username = :username";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            $username_exists = $stmt->fetchColumn();

            // Check for existing email
            $query = "SELECT COUNT(*) FROM users WHERE email = :email";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $email_exists = $stmt->fetchColumn();

            if ($username_exists > 0) {
                $error = "This username is already in the database, try another.";
            } elseif ($email_exists > 0) {
                $error = "This email is already in the database, try another.";
            } else {
                // Hash the password before storing
                $password_hash = password_hash($password, PASSWORD_DEFAULT);

                // Assuming role is passed via a form or you have logic to define it
                $role = 'user'; // Automatically assign 'user' role to new registrations

                // Prepare the query to insert user data into the database
                $query = "INSERT INTO users (username, email, password_hash, role) VALUES (:username, :email, :password_hash, :role)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':username', $username);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':password_hash', $password_hash);
                $stmt->bindParam(':role', $role);

                // Execute the query
                $stmt->execute();

                // Set a success message in session to display on the next page
                $_SESSION['success_message'] = "Registration successful!.";

                // Redirect to index.php after successful registration
                header('Location: index.php');
                exit();
            }

        } catch (PDOException $e) {
            // Only show a custom error message, no database error message
            $error = "This entry is already in the database, try another.";
        }
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


    <title>Register</title>
</head>
<body>
    <h1>Register</h1>
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

    <!-- Show error message if registration fails -->
    <?php if (!empty($error)): ?>
        <div class="error-message">
            <p style="color: red;"><?= htmlspecialchars($error) ?></p>
        </div>
    <?php endif; ?>

    <!-- Registration form -->
    <form method="POST" action="register.php">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required value="<?= isset($username) ? htmlspecialchars($username) : '' ?>">

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required value="<?= isset($email) ? htmlspecialchars($email) : '' ?>">

        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>

        <label for="confirm_password">Confirm Password:</label>
        <input type="password" id="confirm_password" name="confirm_password" required>

       <!-- <label for="role">Select Role:</label>
<select name="role" id="role" required>
    <option value="">-- Select Role --</option>
    <option value="admin">Admin</option>
    <option value="user">User</option>
</select>-->

        <button type="submit">Register</button>
    </form>

    <p>Already have an account? <a href="login.php" style="color: blue; font-weight: bold; text-decoration: none;">Login here</a>.</p>

</body>
</html>
