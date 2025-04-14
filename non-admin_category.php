<?php
require 'db.php';

if (!isset($_GET['category_id']) || !is_numeric($_GET['category_id'])) {
    die("Invalid category.");
}

$category_id = intval($_GET['category_id']);

try {
    // Fetch category name
    $stmt = $db->prepare("SELECT category_name FROM categories WHERE category_id = ?");
    $stmt->execute([$category_id]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$category) {
        die("Category not found.");
    }

    // Fetch superheroes under this category
    $stmt = $db->prepare("
        SELECT superhero_id, name, alias, image_url 
        FROM superheroes 
        WHERE category_id = ?
    ");
    $stmt->execute([$category_id]);
    $superheroes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($category['category_name']) ?> - Superheroes</title>
    <link rel="stylesheet" href="main.css">
</head>
<body style="font-family: Arial, sans-serif; margin: 20px; background-color: #f4f4f4; text-align: center;">
    <h1 style="color: #007bff; margin-bottom: 20px;">Category: <?= htmlspecialchars($category['category_name']) ?></h1>

      <a href="category_menu.php" style="
        display: inline-block;
        text-decoration: none;
        background-color: #007bff;
        color: white;
        padding: 10px 15px;
        border-radius: 8px;
        font-weight: bold;
        transition: background 0.3s ease;
    " onmouseover="this.style.backgroundColor='#0056b3'"
       onmouseout="this.style.backgroundColor='#007bff'">
        Back To Categories
    </a>

    <?php if (empty($superheroes)): ?>
        <p style="color: red;font-weight: bold;">No superheroes found in this category.</p>
    <?php else: ?>
        <ul style="
            list-style: none; 
            padding: 0; 
            display: flex; 
            flex-wrap: wrap; 
            justify-content: center; 
            margin-top: 20px;
        ">
            <?php foreach ($superheroes as $hero): ?>
                <li style="
                    background: white;
                    padding: 15px;
                    margin: 10px;
                    border-radius: 10px;
                    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                    width: 200px;
                    text-align: center;
                ">
                    <!-- Only show image if image_url is not empty -->
                        <?php if (!empty($hero['image_url'])): ?>
                            <img src="<?= htmlspecialchars($hero['image_url']) ?>" class="card-img-top" alt="<?= htmlspecialchars($hero['name'] ?? 'Unknown Name') ?>" style="max-height: 300px; object-fit: cover;"  style="max-height: 200px; width: 100%; object-fit: cover; border-radius: 8px;">
                        <?php endif; ?>
                    <strong><?= htmlspecialchars($hero['alias']) ?> (<?= htmlspecialchars($hero['name']) ?>)</strong>
                     <div style="margin-top: 10px;">
                        <!-- View Profile Link -->
                        <a href="cms.php?id=<?= $hero['superhero_id'] ?>" 
                           style="padding: 8px 12px; background-color: #007bff; color: white; text-decoration: none; border-radius: 4px;">
                            View Profile
                        </a>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</body>
</html>
