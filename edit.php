<?php
// Authentication is required for the editing 
require('db.php');

$hero_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($hero_id === false || $hero_id === null) {
    header('Location: index.php');
    exit();
}

$id = (int) $_GET['id'];

// Retrieve the superhero
$query = 'SELECT * FROM superheroes WHERE superhero_id = :id';
$statement = $db->prepare($query);
$statement->bindParam(':id', $id, PDO::PARAM_INT);
$statement->execute();
$hero = $statement->fetch(PDO::FETCH_ASSOC);

// If the superhero is not found, redirect to index.php
if (!$hero) {
    header('Location: index.php');
    exit();
}

// Define the input_filter function
function input_filter($data) {
    return htmlspecialchars(trim($data)); // Only sanitize non-HTML fields
}

// Handle form submission for update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // If the user clicked "Update Hero"
    if (isset($_POST['update'])) {
        // Sanitize only the non-HTML fields
        $name = input_filter(trim($_POST['name']));
        $alias = input_filter(trim($_POST['alias']));
        $bio = trim($_POST['bio']); // Raw HTML content, don't sanitize
        $powers = trim($_POST['powers']); // Raw HTML content, don't sanitize
        $image_url = input_filter(trim($_POST['image_url']));
        $affiliation = input_filter(trim($_POST['affiliation']));
        $category_id = (int) $_POST['category_id'];

        // Validate inputs
        if (strlen($name) < 1 || strlen($bio) < 1 || strlen($powers) < 1) {
            $error = "All fields (except image) are required.";
        } else {
            // Update the superhero in the database
            $query = 'UPDATE superheroes SET name = :name, alias = :alias, bio = :bio, powers = :powers, image_url = :image_url, affiliation = :affiliation, category_id = :category_id WHERE superhero_id = :id';
            $statement = $db->prepare($query);
            $statement->bindParam(':name', $name);
            $statement->bindParam(':alias', $alias);
            $statement->bindParam(':bio', $bio);
            $statement->bindParam(':powers', $powers);
            $statement->bindParam(':image_url', $image_url);
            $statement->bindParam(':affiliation', $affiliation);
            $statement->bindParam(':category_id', $category_id, PDO::PARAM_INT);
            $statement->bindParam(':id', $id, PDO::PARAM_INT);
            $statement->execute();

            // Redirect to the superhero profile page after updating
            header('Location: dashboard.php?id=' . $id);
            exit();
        }
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
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="main.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">


    <title>Edit Superhero Profile</title>
</head>
<body>
    <h1>Edit Superhero Profile</h1>
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
    <form method="POST">
        <label for="name">Name</label>
        <input type="text" name="name" id="name" value="<?= htmlspecialchars($hero['name']) ?>" required>

        <label for="alias">Alias</label>
        <input type="text" name="alias" id="alias" value="<?= htmlspecialchars($hero['alias']) ?>">

         <label for="bio">Bio</label>
        <!-- Use a regular textarea for bio field -->
        <textarea name="bio" id="bio" required><?= $hero['bio'] ?></textarea>

        <label for="powers">Powers</label>
        <!-- Use a regular textarea for powers field -->
        <textarea name="powers" id="powers" required><?= $hero['powers'] ?></textarea>

        <label for="image_url">Image URL</label>
        <input type="text" name="image_url" id="image_url" value="<?= htmlspecialchars($hero['image_url']) ?>">

        <label for="affiliation">Affiliation</label>
        <input type="text" name="affiliation" id="affiliation" value="<?= htmlspecialchars($hero['affiliation']) ?>">

        <label for="category_id">Category</label>
        <select name="category_id" id="category_id">
            <?php foreach ($categories as $category): ?>
                <option value="<?= $category['category_id'] ?>" <?= ($hero['category_id'] == $category['category_id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($category['category_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <!-- Update and Delete buttons -->
        <button type="submit" name="update">Update Hero</button>
        <button type="submit" name="delete" onclick="return confirm('Are you sure you want to delete this superhero?');">Delete Hero</button>
    </form>

    <?php if (isset($error)): ?>
        <div class="error-message">
            <p><?= htmlspecialchars($error) ?></p>
        </div>
    <?php endif; ?>

    <footer>
        <h5>Copywrong-2025 No Rights Reserved</h5>
    </footer>
</body>
</html>
