<?php
require('db.php');  // Make sure you include the database connection

// Get the superhero ID from the URL
if (isset($_GET['id'])) {
    $hero_id = $_GET['id'];  // Ensure this variable matches what is used in the query

    // Fetch superhero details based on the hero_id
    $query = 'SELECT superheroes.*, categories.category_name FROM
    superheroes 
     LEFT JOIN categories ON superheroes.category_id = categories.category_id 
      WHERE superhero_id = :hero_id';  // Join with categories table
    $statement = $db->prepare($query);
    $statement->bindParam(':hero_id', $hero_id, PDO::PARAM_INT);  // Bind the correct variable
    $statement->execute();

    $hero = $statement->fetch(PDO::FETCH_ASSOC);

    // If no hero found, handle the error
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
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($hero['name']) ?> - Profile</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            color: #333;
            margin: 0;
            padding: 0;
        }
        header {
            background-color: rosybrown;
            color: #fff;
            padding: 10px 0;
            text-align: center;
        }
        h1 {
            color: #2c3e50;
        }
        img {
            max-width: 300px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .container {
            width: 80%;
            margin: 0 auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .hero-info p {
            font-size: 18px;
            line-height: 1.6;
        }
        .hero-info strong {
            font-weight: bold;
            color: #2980b9;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            font-size: 16px;
            color: #2980b9;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <header>
        <h1><?= htmlspecialchars($hero['name']) ?> - Profile</h1>
    </header>
    <div class="container">
        <?php if (!empty($hero['image_url'])): ?>
                            <img src="<?= htmlspecialchars($hero['image_url']) ?>" class="card-img-top" alt="<?= htmlspecialchars($hero['name'] ?? 'Unknown Name') ?>" style="max-height: 300px; object-fit: cover;">
                        <?php endif; ?>
        <div class="hero-info">
            <h2>Alias: <?= htmlspecialchars($hero['alias']) ?></h2>
            <p><strong>Bio:</strong> <?= htmlspecialchars($hero['bio']) ?></p>
            <p><strong>Powers:</strong> <?= htmlspecialchars($hero['powers']) ?></p>
            <p><strong>Affiliation:</strong> <?= htmlspecialchars($hero['affiliation']) ?></p>
            <p><strong>Category:</strong> <?= htmlspecialchars($hero['category_name']) ?></p>
        </div>
        <a class="back-link" href="index.php">Back to Home</a>
    </div>
</body>
</html>
