<?php
// Authentication is required to post
require('db.php');
require('authenticate.php');

// Define the input_filter function
function input_filter($data) {
    return htmlspecialchars(trim($data));
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST'):
    // Sanitize inputs
    $name = input_filter($_POST['name']);
    $alias = input_filter($_POST['alias']);
    $bio = input_filter($_POST['bio']);
    $powers = input_filter($_POST['powers']);
    $image_url = input_filter($_POST['image_url']);
    $affiliation = input_filter($_POST['affiliation']);
    $category_id = $_POST['category_id']; // Get category ID from form

    // Validate input
    if (empty($name) || empty($alias) || empty($bio) || empty($powers) || empty($affiliation) || empty($category_id)):
        $error = "All fields are required.";
    else:
        try {
            // Insert into the database using PDO
            $query = 'INSERT INTO superheroes (name, alias, bio, powers, image_url, affiliation, category_id) 
                      VALUES (:name, :alias, :bio, :powers, :image_url, :affiliation, :category_id)';
            $statement = $db->prepare($query);
            $statement->bindParam(':name', $name, PDO::PARAM_STR);
            $statement->bindParam(':alias', $alias, PDO::PARAM_STR);
            $statement->bindParam(':bio', $bio, PDO::PARAM_STR);
            $statement->bindParam(':powers', $powers, PDO::PARAM_STR);
            $statement->bindParam(':image_url', $image_url, PDO::PARAM_STR);
            $statement->bindParam(':affiliation', $affiliation, PDO::PARAM_STR);
            $statement->bindParam(':category_id', $category_id, PDO::PARAM_INT);
            $statement->execute();

            header('Location: index.php');
            exit();
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    endif;
endif;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <link rel="stylesheet" type="text/css" href="main.css">

    <title>Add New Superhero</title>
</head>
<body>
    <h1>Add New Superhero</h1>
          <nav>
    <div>
        <a href="index.php" id="home">Home</a>
        <a href="superhero_profile.php">Add new Avenger</a>
    </div>
    </nav>
    <form method="POST">
        <label for="name">Name</label>
        <input type="text" name="name" id="name" required>

        <label for="alias">Alias</label>
        <input type="text" name="alias" id="alias" required>

        <label for="bio">Bio</label>
        <textarea name="bio" id="bio" required></textarea>

        <label for="powers">Powers</label>
        <input type="text" name="powers" id="powers" required>

        <label for="image_url">Image URL</label>
        <input type="text" name="image_url" id="image_url">

        <label for="affiliation">Affiliation</label>
        <input type="text" name="affiliation" id="affiliation" required>

        <label for="category_id">Category</label>
        <select name="category_id" id="category_id" required>
            <?php
            // Fetch categories from the database
            $query = 'SELECT * FROM categories';
            $statement = $db->prepare($query);
            $statement->execute();
            $categories = $statement->fetchAll(PDO::FETCH_ASSOC);

            // Loop through categories to populate the dropdown
            foreach ($categories as $category) {
               echo '<option value="' . htmlspecialchars($category['category_id']) . '">' . htmlspecialchars($category['category_name']) . '</option>';
            }
            ?>
        </select>

        <button type="submit">Submit</button>

        
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