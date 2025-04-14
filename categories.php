<?php
require 'db.php';
require 'authenticate.php'; // Restrict access to logged-in users

// Fetch categories from database
$query = 'SELECT * FROM categories';
$statement = $db->prepare($query);
$statement->execute();
$categories = $statement->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Categories</title>
    <link rel="stylesheet" href="main.css">
</head>
<body>
    <h1>Manage Categories</h1>
    <a href="index.php">Back to Home</a>

    <form action="manage_categories.php" method="post">
        <label for="category_name">Category Name:</label>
        <input type="text" name="category_name" id="category_name" required>
        <input type="hidden" name="category_id" id="category_id">
        
        <button type="submit" name="action" value="create">Create Category</button>
        <button type="submit" name="action" value="update">Update Category</button>
    </form>

    <h2>Existing Categories</h2>
    <ul>
        <?php foreach ($categories as $category): ?>
            <li>
                <?= htmlspecialchars($category['category_name']) ?>
                <button onclick="editCategory(<?= $category['category_id'] ?>, '<?= addslashes($category['category_name']) ?>')">Edit</button>
                <a href="manage_categories.php?action=delete&id=<?= $category['category_id'] ?>" onclick="return confirm('Are you sure?')">Delete</a>
            </li>
        <?php endforeach; ?>
    </ul>

    <script>
        function editCategory(id, name) {
            document.getElementById("category_name").value = name;
            document.getElementById("category_id").value = id;
        }
    </script>
</body>
</html>
