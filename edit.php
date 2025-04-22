<?php
// Authentication is required for the editing 
require('db.php');

// Check if hero id is valid
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
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
    return htmlspecialchars(trim($data));
}

// Handle form submission for update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // If the user clicked "Update Hero"
    if (isset($_POST['update'])) {
        $name = input_filter(trim($_POST['name']));
        $alias = input_filter(trim($_POST['alias']));
        $bio = input_filter(trim($_POST['bio']));
        $powers = input_filter(trim($_POST['powers']));
        $image_url = input_filter(trim($_POST['image_url']));
        $affiliation = input_filter(trim($_POST['affiliation']));
        $category_id = input_filter(trim($_POST['category_id']));

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
     <!-- TinyMCE WYSIWYG CDN -->
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        tinymce.init({
            selector: 'textarea#wysiwyg', // We'll give textareas this class
            height: 300,
            menubar: false,
            plugins: 'lists link image preview code',
            toolbar: 'undo redo | styles | bold italic underline | alignleft aligncenter alignright | bullist numlist outdent indent | link image | code preview'
        });
    </script>

    <title>Edit Superhero Profile</title>
</head>
<body>
    <h1>Edit Superhero Profile</h1>
    <nav>
        <a href="index.php">Home</a>
        <a href="superhero_profile.php?id=<?= $hero['superhero_id'] ?>">New Profile</a>
    </nav>
    <form method="POST">
        <label for="name">Name</label>
        <input type="text" name="name" id="name" class="wysiwyg" value="<?= htmlspecialchars($hero['name']) ?>" required>

        <label for="alias">Alias</label>
        <input type="text" name="alias" id="alias" class="wysiwyg" value="<?= htmlspecialchars($hero['alias']) ?>">

        <label for="bio">Bio</label>
        <textarea name="bio" id="bio" class="wysiwyg" required><?= htmlspecialchars($hero['bio']) ?></textarea>

        <label for="powers">Powers</label>
        <textarea name="powers" id="powers" class="wysiwyg" required><?= htmlspecialchars($hero['powers']) ?></textarea>

        <label for="image_url">Image URL</label>
        <input type="text" name="image_url" id="image_url" class="wysiwyg" value="<?= htmlspecialchars($hero['image_url']) ?>">

        <label for="affiliation">Affiliation</label>
        <input type="text" name="affiliation" id="affiliation"  class="wysiwyg"value="<?= htmlspecialchars($hero['affiliation']) ?>">

        <label for="category_id">Category</label>
        <select name="category_id" id="category_id" class="wysiwyg">
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
