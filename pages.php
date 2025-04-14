<?php
require 'db.php';
require 'authenticate.php'; // Only admins can assign categories

// Fetch pages and categories
// Fetch pages and their associated categories
$query = 'SELECT p.page_id, p.title, c.category_name
          FROM pages p
          JOIN pages_categories pc ON p.page_id = pc.page_id
          JOIN categories c ON pc.category_id = c.category_id';

$statement = $db->prepare($query);
$statement->execute();
$pages = $statement->fetchAll(PDO::FETCH_ASSOC);

$statement = $db->prepare($query);
$statement->execute();
$pages = $statement->fetchAll(PDO::FETCH_ASSOC);

// Fetch categories for dropdown
$query = "SELECT * FROM categories";
$statement = $db->prepare($query);
$statement->execute();
$categories = $statement->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Pages</title>
    <link rel="stylesheet" href="main.css">
</head>
<body>
    <h1>Manage Pages</h1>
    <a href="index.php">Back to Home</a>

    <table border="1">
        <tr>
            <th>Page Title</th>
            <th>Category</th>
            <th>Assign Category</th>
        </tr>
        <?php foreach ($pages as $page): ?>
            <tr>
                <td><?= htmlspecialchars($page['title']) ?></td>
                <td><?= htmlspecialchars($page['category_name'] ?? 'Uncategorized') ?></td>
                <td>
                    <form action="assign_category.php" method="post">
                        <input type="hidden" name="page_id" value="<?= $page['page_id'] ?>">
                        <select name="category_id">
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['category_id'] ?>" <?= ($category['category_id'] == $page['category_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category['category_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit">Assign</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
