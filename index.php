<?php
require 'db.php'; // Include the database connection

// Fetch superheroes data from the database
$query = 'SELECT * FROM superheroes';  // Fixed typo: used correct table name "superheroes"
$stmt = $db->prepare($query);
$stmt->execute();
$heroes = $stmt->fetchAll(PDO::FETCH_ASSOC);


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="main.css">
    <title>Avengers Universe</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container">
        <h1 class="mt-5">Welcome to the Superheroes Universe</h1>
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

        <!-- Superhero List -->
        <div class="row justify-content-center mt-5">
            <?php foreach ($heroes as $hero): ?>
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <!-- Only show image if image_url is not empty -->
                        <?php if (!empty($hero['image_url'])): ?>
                            <img src="<?= htmlspecialchars($hero['image_url']) ?>" class="card-img-top" alt="<?= htmlspecialchars($hero['name'] ?? 'Unknown Name') ?>" style="max-height: 300px; object-fit: cover;">
                        <?php endif; ?>

                        <div class="card-body">
                            <!-- Display superhero's name and alias -->
                            <h5 class="card-title"><?= htmlspecialchars($hero['name'] ?? 'Unknown Name') ?></h5>
                            <p class="card-text"><?= htmlspecialchars($hero['alias'] ?? 'Unknown Alias') ?></p>

                            <!-- View and Comment buttons -->
                            <a href="cms.php?id=<?= $hero['superhero_id'] ?>" class="btn btn-primary">View Profile</a>
                            <a href="comment_form.php?hero_id=<?= $hero['superhero_id'] ?>&category_id=<?= $hero['category_id'] ?>" class="btn btn-secondary mt-2">Comment</a>

                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Optional JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
