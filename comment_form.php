<?php
session_start();
require 'db.php'; // Your DB connection

$hero_id = $_GET['hero_id'] ?? null;
$category_id = $_GET['category_id'] ?? '';

$comment = '';
$name = '';
$age = '';
$category_id = '';
$error = '';
$success = '';

// Generate a deletion token for non-logged-in users
function generateDeletionToken() {
    return bin2hex(random_bytes(16)); // Random 32-character hexadecimal string
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $comment = trim($_POST['comment']);
    $name = trim($_POST['name']);
    $age = $_POST['age'];
    $category_id = $_POST['category_id'];
    $captcha_input = $_POST['captcha'];

    if ($captcha_input !== $_SESSION['captcha']) {
        $error = 'CAPTCHA incorrect. Please try again.';
    } elseif (empty($comment) || empty($name) || empty($age) || empty($category_id)) {
        $error = 'All fields are required.';
    } else {
        // Generate a token for non-logged-in users
        $deletion_token = generateDeletionToken();

        // Insert into comments table
        $query = "INSERT INTO comments (user_name, comment_text, age, category_id, superhero_id, posted_at, status, deletion_token)
                  VALUES (:name, :comment, :age, :category_id, :hero_id, NOW(), 'approved', :deletion_token)";

        $stmt = $db->prepare($query);
        $stmt->bindValue(':name', $name);
        $stmt->bindValue(':comment', $comment);
        $stmt->bindValue(':age', $age);
        $stmt->bindValue(':category_id', $category_id);
        $stmt->bindValue(':hero_id', $hero_id);
        $stmt->bindValue(':deletion_token', $deletion_token);

        $stmt->execute();

        $success = 'Comment submitted successfully!';
        $comment = '';
        $name = '';
        $age = '';
        $category_id = '';
    }
}

// Handle comment deletion
if (isset($_GET['delete_comment_id']) && isset($_GET['deletion_token'])) {
    $delete_comment_id = $_GET['delete_comment_id'];
    $deletion_token = $_GET['deletion_token'];

    // Check if the deletion token matches for the comment
    $query = "SELECT deletion_token FROM comments WHERE comment_id = :comment_id";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':comment_id', $delete_comment_id);
    $stmt->execute();
    $stored_token = $stmt->fetchColumn();

    if ($stored_token === $deletion_token) {
        // Token matches, delete the comment
        $deleteQuery = "DELETE FROM comments WHERE comment_id = :comment_id";
        $stmt = $db->prepare($deleteQuery);
        $stmt->bindValue(':comment_id', $delete_comment_id);
        $stmt->execute();

        $success = 'Comment deleted successfully!';
    } else {
        $error = 'Invalid or expired deletion token.';
    }
}

// Retrieve categories for the dropdown
$query = 'SELECT * FROM categories';
$categories = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Submit a Comment</title>
    <link rel="stylesheet" href="main.css">
</head>
<body>

    <h1>Leave a Comment</h1>
    <nav>
        <a href="index.php">Home</a>
    </nav>

    <?php if ($error): ?>
        <div style="color: red;"><?= htmlspecialchars($error) ?></div>
    <?php elseif ($success): ?>
        <div style="color: green;"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST" action="comment_form.php?hero_id=<?= urlencode($hero_id) ?>">
        <label for="name">Your Name:</label>
        <input type="text" name="name" id="name" value="<?= htmlspecialchars($name) ?>" required><br>

        <label for="age">Your Age:</label>
        <select name="age" id="age" required>
            <option value="">Select Age</option>
            <?php for ($i = 10; $i <= 100; $i++): ?>
                <option value="<?= $i ?>" <?= $age == $i ? 'selected' : '' ?>><?= $i ?></option>
            <?php endfor; ?>
        </select><br>

        <label for="comment">Comment:</label><br>
        <textarea name="comment" id="comment" rows="4" cols="50" required><?= htmlspecialchars($comment) ?></textarea><br>

        <label for="category_id">Category:</label>
        <select name="category_id" id="category_id" required>
            <option value="">Select Category</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?= $category['category_id'] ?>" <?= ($category_id == $category['category_id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($category['category_name']) ?>
                </option>
            <?php endforeach; ?>
        </select><br>

        <label for="captcha">Enter the CAPTCHA:</label><br>
        <img src="captcha.php" alt="CAPTCHA image"><br>
        <input type="text" name="captcha" id="captcha" required><br><br>

        <button type="submit">Submit Comment</button>

        <?php if ($hero_id): ?>
            <h2>Recent Comments</h2>
            <?php
            // Fetch recent comments for all categories for the current superhero
            $commentsQuery = "SELECT c.*, cat.category_name 
                              FROM comments c
                              JOIN categories cat ON c.category_id = cat.category_id
                              WHERE c.superhero_id = :hero_id
                              ORDER BY c.posted_at DESC";
            $stmt = $db->prepare($commentsQuery);
            $stmt->bindValue(':hero_id', $hero_id);
            $stmt->execute();
            $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            ?>

            <?php if ($comments): ?>
                <?php foreach ($comments as $comment): ?>
                    <div style="border-bottom: 1px solid #ccc; margin-bottom: 10px; padding-bottom: 10px;">
                        <strong><?= htmlspecialchars($comment['user_name']) ?> (<?= htmlspecialchars($comment['age']) ?> yrs)</strong>
                        <em>[<?= htmlspecialchars($comment['category_name']) ?>]</em><br>
                        <?= nl2br(htmlspecialchars($comment['comment_text'])) ?><br>
                        <small>Posted at <?= $comment['posted_at'] ?></small><br>
                        
                        <!-- Add Delete button with the deletion token -->
                        <a href="comment_form.php?hero_id=<?= urlencode($hero_id) ?>&delete_comment_id=<?= $comment['comment_id'] ?>&deletion_token=<?= $comment['deletion_token'] ?>" style="color: red;">Delete</a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No comments yet. Be the first to comment!</p>
            <?php endif; ?>
        <?php endif; ?>

    </form>

</body>
</html>
