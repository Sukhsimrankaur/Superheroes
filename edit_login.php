<?php
session_start();
require('db.php');

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Check if user ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: dashboard.php');
    exit();
}

$user_id = (int) $_GET['id'];

// Fetch user details
$query = 'SELECT * FROM users WHERE user_id = :user_id';
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// If user not found, redirect back to dashboard
if (!$user) {
    header('Location: dashboard.php');
    exit();
}

// Initialize variables
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $username = htmlspecialchars(trim($_POST['username']));
    $email = htmlspecialchars(trim($_POST['email']));
    $role = htmlspecialchars(trim($_POST['role']));
    $new_password = trim($_POST['password']);

    // Check if the username or email is already taken by another user
    $checkQuery = 'SELECT * FROM users WHERE (username = :username OR email = :email) AND user_id != :user_id';
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':username', $username);
    $checkStmt->bindParam(':email', $email);
    $checkStmt->bindParam(':user_id', $user_id);
    $checkStmt->execute();
    $existingUser = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($existingUser) {
        $error = 'Username or Email is already taken by another user.';
    } else {
        // Update query
        if (!empty($new_password)) {
            // Hash the new password
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $updateQuery = 'UPDATE users SET username = :username, email = :email, role = :role, password_hash = :password_hash WHERE user_id = :user_id';
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->bindParam(':password_hash', $password_hash);
        } else {
            // Update without changing the password
            $updateQuery = 'UPDATE users SET username = :username, email = :email, role = :role WHERE user_id = :user_id';
            $updateStmt = $db->prepare($updateQuery);
        }

        $updateStmt->bindParam(':username', $username);
        $updateStmt->bindParam(':email', $email);
        $updateStmt->bindParam(':role', $role);
        $updateStmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);

        if ($updateStmt->execute()) {
            $_SESSION['user_updated_success'] = 'User updated successfully!';
            header('Location: dashboard.php');
            exit();
        } else {
            $error = 'Failed to update user. Please try again.';
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
    <title>Edit User</title>
</head>
<body>
    <h2>Edit User</h2>

    <!-- Display success or error message -->
    <?php if (!empty($error)): ?>
        <div class="error-message" style="color: red;">
            <p><?= htmlspecialchars($error) ?></p>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" value="<?= htmlspecialchars($user['username']) ?>" required><br><br>

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required><br><br>

        <label for="role">Role:</label>
        <select name="role" id="role">
            <option value="user" <?= ($user['role'] === 'user') ? 'selected' : '' ?>>User</option>
            <option value="admin" <?= ($user['role'] === 'admin') ? 'selected' : '' ?>>Admin</option>
        </select><br><br>

        <label for="password">New Password (leave blank to keep current password):</label>
        <input type="password" id="password" name="password"><br><br>

        <button type="submit" name="update_user">Update User</button>
    </form>

    <p><a href="dashboard.php" style="color: blue; font-weight: bold; text-decoration: none;">Back to Dashboard</a></p>
</body>
</html>
