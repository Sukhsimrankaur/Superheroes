<?php
session_start(); // REQUIRED for CAPTCHA session check


// Get the superhero ID from the URL
if (isset($_GET['id'])) {
    $hero_id = $_GET['id'];

    // Fetch superhero details
    $query = 'SELECT superheroes.*, categories.category_name FROM superheroes 
              LEFT JOIN categories ON superheroes.category_id = categories.category_id 
              WHERE superhero_id = :hero_id';
    $statement = $db->prepare($query);
    $statement->bindParam(':hero_id', $hero_id, PDO::PARAM_INT);
    $statement->execute();
    $hero = $statement->fetch(PDO::FETCH_ASSOC);

    // Initialize form data
    $comment = '';
    $name = '';
    $error = '';
    $success = '';
    $delete_error = '';
    $delete_success = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_token'])) {
    $token = $_POST['delete_token'];

    if (!empty($_SESSION['deletion_tokens'][$hero_id]) && in_array($token, $_SESSION['deletion_tokens'][$hero_id])) {
        $stmt = $db->prepare("DELETE FROM comments WHERE deletion_token = :token");
        $stmt->execute([':token' => $token]);

        // Remove token from session
        $_SESSION['deletion_tokens'][$hero_id] = array_filter(
            $_SESSION['deletion_tokens'][$hero_id],
            fn($t) => $t !== $token
        );

        // ✅ Store success in session and redirect
        $_SESSION['delete_success'] = "Comment deleted successfully.";
        header("Location: cms.php?id=" . $hero_id);
        exit();
    } else {
        // ✅ Store error in session and redirect
        $_SESSION['delete_error'] = "Invalid deletion token.";
        header("Location: cms.php?id=" . $hero_id);
        exit();
    }
}

    // Handle comment submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_token'])) {
        $comment = trim($_POST['comment'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $captcha_input = $_POST['captcha'] ?? '';

        if ($captcha_input !== ($_SESSION['captcha'] ?? '')) {
            $error = 'CAPTCHA incorrect. Please try again.';
        } elseif (empty($comment) || empty($name)) {
            $error = 'Name and comment are required.';
        } else {
            $deletion_token = bin2hex(random_bytes(16));

            $stmt = $db->prepare("INSERT INTO comments (user_name, comment_text, superhero_id, posted_at, status, deletion_token) 
                                  VALUES (:name, :comment, :hero_id, NOW(), 'approved', :token)");
            $stmt->execute([
                ':name' => $name,
                ':comment' => $comment,
                ':hero_id' => $hero_id,
                ':token' => $deletion_token
            ]);

            $_SESSION['deletion_tokens'][$hero_id][] = $deletion_token;

            $success = 'Comment submitted successfully!';
            $comment = '';
            $name = '';
        }
    }

    // Fetch comments
    $stmt = $db->prepare("SELECT * FROM comments WHERE superhero_id = :hero_id AND status = 'approved' ORDER BY posted_at DESC");
    $stmt->execute([':hero_id' => $hero_id]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // If hero not found
    if (!$hero) {
        echo "Hero not found.";
        exit();
    }
} else {
    echo "No hero ID specified.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($hero['name']) ?> - Profile</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="main.css">
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; color: #333; }
        header { background-color: rosybrown; color: #fff; padding: 10px 0; text-align: center; }
        .container { width: 80%; margin: 20px auto; padding: 20px; background-color: #fff; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); }
        img { max-width: 300px; border-radius: 10px; margin-bottom: 20px; }
        .hero-info p { font-size: 18px; line-height: 1.6; }
        .hero-info strong { font-weight: bold; color: #2980b9; }
        .comment-form textarea { width: 100%; font-size: 16px; }
        .comment { border-top: 1px solid #ccc; padding: 10px 0; }
        .comment strong { color: #555; }
        .comment small { color: #999; }
        .delete-button { color: red; cursor: pointer; padding: 4px 8px; background: none; border: 1px solid red; border-radius: 3px; }
        .delete-button:hover { background-color: #f8d7da; }

    </style>
</head>
<body>
    <header>
        <h1><?= htmlspecialchars($hero['name']) ?> - Profile</h1>
    </header>
    <nav class="d-flex justify-content-between">
        <div>
            <a href="index.php" class="mx-3">Home</a>
            <a href="category_menu.php">Search</a>
        </div>
        <div>
            <a href="register.php">Register</a>
            <a href="login.php" class="mx-3">Admin Login</a>
        </div>
    </nav>

    <div class="container">
        <?php if (!empty($hero['image_url'])): ?>
            <img src="<?= htmlspecialchars($hero['image_url']) ?>" alt="<?= htmlspecialchars($hero['name']) ?>">
        <?php endif; ?>

        <div class="hero-info">
            <h2>Alias: <?= htmlspecialchars($hero['alias']) ?></h2>
            <p><strong>Bio:</strong> <?= htmlspecialchars($hero['bio']) ?></p>
            <p><strong>Powers:</strong> <?= htmlspecialchars($hero['powers']) ?></p>
            <p><strong>Affiliation:</strong> <?= htmlspecialchars($hero['affiliation']) ?></p>
            <p><strong>Category:</strong> <?= htmlspecialchars($hero['category_name']) ?></p>

            <div class="comment-form">
                <h2>Leave a Comment</h2>

                <?php if ($error): ?>
                    <div style="color: red;"><?= htmlspecialchars($error) ?></div>
                <?php elseif ($success): ?>
                    <div style="color: green;"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>

                <?php if ($delete_success): ?>
                    <div style="color: green;"><?= htmlspecialchars($delete_success) ?></div>
                <?php elseif ($delete_error): ?>
                    <div style="color: red;"><?= htmlspecialchars($delete_error) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <label for="name">Name:</label><br>
                    <input type="text" name="name" id="name" value="<?= htmlspecialchars($name) ?>" required><br><br>

                    <label for="comment">Comment:</label><br>
                    <textarea name="comment" id="comment" rows="4" required><?= htmlspecialchars($comment) ?></textarea><br><br>

                    <label for="captcha">Enter CAPTCHA:</label><br>
                    <img src="captcha.php" alt="CAPTCHA"><br>
                    <input type="text" name="captcha" id="captcha" required><br><br>

                    <button type="submit">Submit Comment</button>
                </form>
            </div>

            <h3>Recent Comments</h3>
            <?php if ($comments): ?>
                <?php foreach ($comments as $c): ?>
                    <div class="comment">
                        <strong><?= htmlspecialchars($c['user_name']) ?></strong><br>
                        <?= nl2br(htmlspecialchars($c['comment_text'])) ?><br>
                        <small>Posted at <?= $c['posted_at'] ?></small>

                        <?php if (!empty($_SESSION['deletion_tokens'][$hero_id]) && in_array($c['deletion_token'], $_SESSION['deletion_tokens'][$hero_id])): ?>
                           <form method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this comment?');">
    <input type="hidden" name="delete_token" value="<?= $c['deletion_token'] ?>">
    <button type="submit" class="delete-button">Delete</button>
</form>


                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No comments yet. Be the first to comment!</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
