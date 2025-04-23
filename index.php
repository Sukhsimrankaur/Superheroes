<?php
session_start();
require 'db.php'; // Include the database connection

$success_message = '';
$logged_in_username = '';

// If there's a success message in the session, display it and clear it
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    $logged_in_username = $_SESSION['logged_in_username'] ?? '';
    
    // Clear message and username from session so it shows only once
    unset($_SESSION['success_message']);
    unset($_SESSION['logged_in_username']);
}

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
   <?php if (!empty($success_message)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($success_message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
   <?php endif; ?>

    <div class="container">
        <h1 class="mt-5">Welcome to the Superheroes Universe</h1>
        <nav class="d-flex justify-content-between">
            <div>
                <a href="index.php" id="home" class="mx-3">Home</a>
                <a href="category_menu.php">Search</a>
            </div>
            <div>
             <?php if (!isset($_SESSION['user_id'])): ?>
    <!-- No one is logged in -->
    <a href="register.php">Register</a>
    <a href="login.php" class="mx-3">Admin Login</a>

<?php else: ?>
    <!-- Someone is logged in -->
    <?php if (isset($_SESSION['logged_in_username'])): ?>
        <span class="mx-3">Welcome, <?= htmlspecialchars($_SESSION['logged_in_username']) ?></span>
    <?php endif; ?>

    <?php if ($_SESSION['user_role'] === 'admin'): ?>
        <a href="dashboard.php" class="mx-3">Dashboard</a>
    <?php else: ?>
        <!-- Regular user gets Register again if needed -->
        <a href="register.php" class="mx-3">Register</a>
    <?php endif; ?>

    <a href="logout.php" class="mx-3">Logout</a>
<?php endif; ?>


            </div>
        </nav>


        <!-- Check if there are superheroes available -->
        <?php if (empty($heroes)): ?>
            <div class="alert alert-warning" role="alert">
                No superheroes available.
            </div>
        <?php else: ?>
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
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Optional JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
