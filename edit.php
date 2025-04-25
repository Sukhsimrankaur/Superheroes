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
        $affiliation = input_filter(trim($_POST['affiliation']));
        $category_id = (int) $_POST['category_id'];

        // Handle file upload
        $image_url = null;
        if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] == 0) {
            $upload_dir = 'uploads/';
            $image_name = $_FILES['image_file']['name'];
            $image_temp = $_FILES['image_file']['tmp_name'];
            $image_extension = pathinfo($image_name, PATHINFO_EXTENSION);

            // Validate image type
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
            if (in_array(strtolower($image_extension), $allowed_extensions)) {
                // Generate a unique file name
                $image_url = $upload_dir . uniqid('hero_') . '.' . $image_extension;

                // Move the uploaded file to the uploads directory
                if (move_uploaded_file($image_temp, $image_url)) {
                    // If successful, the image URL will be updated
                    // Optionally delete the old image file if it's replaced
                    if (!empty($hero['image_url']) && file_exists($hero['image_url'])) {
                        unlink($hero['image_url']);
                    }


                } else {
                    $error = "Failed to upload the image.";
                }
            } else {
                $error = "Invalid image file. Only JPG, JPEG, PNG, and GIF files are allowed.";
            }
        } elseif (!empty($_POST['image_url'])) {
            // If an image URL is provided
            $image_url = input_filter(trim($_POST['image_url']));
        }

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
    <title>Edit Superhero Profile</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        h1 {
            text-align: center;
            margin-top: 30px;
        }

        footer {
            text-align: center;
            padding: 20px;
            background-color: #222;
            color: #fff;
            margin-top: 40px;
        }

        nav {
            display: flex;
            justify-content: space-between;
            padding: 10px;
            background-color: #333;
            color: #fff;
        }

        nav a {
            color: #fff;
            text-decoration: none;
            margin: 0 10px;
        }

        nav a:hover {
            text-decoration: underline;
        }

        form {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        }

        form label {
            display: block;
            margin: 10px 0 5px;
            font-weight: bold;
        }

        form input,
        form select,
        form textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }

        form input[type="checkbox"] {
            width: auto;
            margin-right: 5px;
        }

        form button {
            padding: 10px 20px;
            border: none;
            background-color: #4CAF50;
            color: white;
            font-size: 16px;
            cursor: pointer;
            border-radius: 4px;
            margin-right: 10px;
        }

        form button:hover {
            background-color: #45a049;
        }

        form .error-message {
            color: red;
            font-weight: bold;
            margin-top: 10px;
        }

        form img {
            max-width: 200px;
            max-height: 200px;
            object-fit: contain;
            margin-top: 15px;
        }

        form div {
            margin-top: 20px;
        }

        footer h5 {
            font-size: 14px;
            margin: 0;
        }
    </style>
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

    <form method="POST" enctype="multipart/form-data">
        <label for="name">Name</label>
        <input type="text" name="name" id="name" value="<?= htmlspecialchars($hero['name']) ?>" required>

        <label for="alias">Alias</label>
        <input type="text" name="alias" id="alias" value="<?= htmlspecialchars($hero['alias']) ?>">

        <label for="bio">Bio</label>
        <textarea name="bio" id="bio" required><?= $hero['bio'] ?></textarea>

        <label for="powers">Powers</label>
        <textarea name="powers" id="powers" required><?= $hero['powers'] ?></textarea>

        <label for="image_url">Image URL</label>
        <input type="text" name="image_url" id="image_url" value="<?= htmlspecialchars($hero['image_url']) ?>">

        <?php if (!empty($hero['image_url'])): ?>
    <label>Current Image</label>
    <img src="<?= htmlspecialchars($hero['image_url']) ?>" alt="Superhero Image">
    <div>
        <label for="delete_image">Delete Image</label>
        <input type="checkbox" name="delete_image" id="delete_image" value="1">
        <span>Check to delete the associated image.</span>
    </div>
<?php endif; ?>

<label for="image_file">Upload New Image</label>
<input type="file" name="image_file" id="image_file">
<span>Optional: Upload a new image file to replace the current one (if any).</span>


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
