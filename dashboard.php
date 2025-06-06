<?php
session_start(); 

define('SESSION_TIMEOUT', 500); // 8 minutes

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// CHECK FOR SESSION TIMEOUT
if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit();
} else {
    $_SESSION['last_activity'] = time();
}

// CHECK FOR CROSS-TAB LOGOUT
$logout_time = file_exists('logout_flag.txt') ? (int)file_get_contents('logout_flag.txt') : 0;

if (isset($_SESSION['login_time']) && $_SESSION['login_time'] < $logout_time) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit();
}

// REDIRECT NON-ADMINS
if ($_SESSION['user_role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

// Success message (show once)
if (isset($_SESSION['login_success'])) {
    echo '<div id="success-message" style="color: green; font-weight: bold;">' . $_SESSION['login_success'] . '</div>';
    unset($_SESSION['login_success']);
}

?>
<?php
require('db.php');

// Check if the user is an admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

// Initialize variables for user management
$error = '';
$success = '';

// Fetch all users from the database
$query = 'SELECT * FROM users WHERE role = "user"';
$stmt = $db->prepare($query);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle Add User form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $username = htmlspecialchars(trim($_POST['username']));
    $email = htmlspecialchars(trim($_POST['email']));
    $password = trim($_POST['password']);
    $role = htmlspecialchars(trim($_POST['role']));

    // Check if username or email already exists
    $checkQuery = 'SELECT * FROM users WHERE username = :username OR email = :email LIMIT 1';
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':username', $username);
    $checkStmt->bindParam(':email', $email);
    $checkStmt->execute();
    $existingUser = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($existingUser) {
    // If user exists, store error message in the session and redirect back
    $_SESSION['error_message'] = "Username or Email already exists. Please try again with different details.";
    header("Location: dashboard.php");
    exit();
    } else {
        // Hash the password before saving it
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // Insert the new user into the database
        $insertQuery = 'INSERT INTO users (username, email, password_hash, role, created_at) VALUES (:username, :email, :password_hash, :role, NOW())';
        $insertStmt = $db->prepare($insertQuery);
        $insertStmt->bindParam(':username', $username);
        $insertStmt->bindParam(':email', $email);
        $insertStmt->bindParam(':password_hash', $password_hash);
        $insertStmt->bindParam(':role', $role);
        $insertStmt->execute();

        $_SESSION['success_message'] = 'User successfully added!';
        header("Location: dashboard.php");
        exit();


    }
}

// Handle Delete User
if (isset($_GET['delete_user'])) {
    $user_id = (int) $_GET['delete_user'];

    // Delete the user from the database
    $deleteQuery = 'DELETE FROM users WHERE user_id = :user_id';
    $deleteStmt = $db->prepare($deleteQuery);
    $deleteStmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $deleteStmt->execute();

    $_SESSION['success_message'] = 'User successfully deleted!';
    header("Location: dashboard.php");
    exit();
}

// Category Management Section
$categoryQuery = "SELECT * FROM categories";
$categoryStatement = $db->prepare($categoryQuery);
$categoryStatement->execute();
$categories = $categoryStatement->fetchAll(PDO::FETCH_ASSOC);

// Handling form actions (create or update for categories)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $category_name = trim($_POST['category_name'] ?? '');
    $category_id = $_POST['category_id'] ?? '';

    if (empty($category_name)) {
        $_SESSION['error_message'] = "Category name is required.";
        header("Location: dashboard.php");
        exit();
    }

    try {
        if ($action === 'create') {
            $query = "INSERT INTO categories (category_name) VALUES (:category_name)";
            $stmt = $db->prepare($query);
            $stmt->execute(['category_name' => $category_name]);
            $_SESSION['success_message'] = 'Category successfully created!';
        } elseif ($action === 'update' && !empty($category_id)) {
            $query = "UPDATE categories SET category_name = :category_name WHERE category_id = :category_id";
            $stmt = $db->prepare($query);
            $stmt->execute(['category_name' => $category_name, 'category_id' => $category_id]);
            $_SESSION['success_message'] = 'Category successfully updated!';
        }
        header("Location: dashboard.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error_message'] = 'Error: ' . $e->getMessage();
        header("Location: dashboard.php");
        exit();
    }
}

// Handle deletion of category
if (isset($_GET['action']) && $_GET['action'] === 'delete' && !empty($_GET['id'])) {
    try {
        $query = "DELETE FROM categories WHERE category_id = :category_id";
        $stmt = $db->prepare($query);
        $stmt->execute(['category_id' => $_GET['id']]);
        $_SESSION['success_message'] = 'Category successfully deleted!';
        header("Location: dashboard.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error_message'] = 'Error: ' . $e->getMessage();
        header("Location: dashboard.php");
        exit();
    }
}


// Check if user is logged in and has admin role
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

/* Set default sorting options
$sort_column = 'name'; // Default column to sort by
$sort_order = 'ASC';   // Default order

// Check if sorting parameters are provided in the GET request
if (isset($_GET['sort_by'])) {
    $valid_columns = ['name', 'alias', 'affiliation'];
    $sort_column = in_array($_GET['sort_by'], $valid_columns) ? $_GET['sort_by'] : 'name';
}

if (isset($_GET['sort_order']) && ($_GET['sort_order'] === 'ASC' || $_GET['sort_order'] === 'DESC')) {
    $sort_order = $_GET['sort_order'];
}

// Query to fetch superheroes from the database with sorting
$query = "SELECT * FROM superheroes ORDER BY $sort_column $sort_order";
$stmt = $db->prepare($query);
$stmt->execute();
$superheroes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Toggle sort order for the column
$toggle_order = ($sort_order === 'ASC') ? 'DESC' : 'ASC';

*/



// Define the input_filter function
function input_filter($data) {
    return htmlspecialchars(trim($data));
}

function resizeImage($filePath) {
    list($width, $height, $type) = getimagesize($filePath);
    $maxWidth = 800;

    if ($width > $maxWidth) {
        $newWidth = $maxWidth;
        $newHeight = ($height / $width) * $newWidth;

        // Create the image resource based on file type
        switch ($type) {
            case IMAGETYPE_JPEG:
                $image = imagecreatefromjpeg($filePath);
                $outputFunction = 'imagejpeg';
                break;
            case IMAGETYPE_PNG:
                $image = imagecreatefrompng($filePath);
                $outputFunction = 'imagepng';
                break;
            case IMAGETYPE_GIF:
                $image = imagecreatefromgif($filePath);
                $outputFunction = 'imagegif';
                break;
            default:
                throw new Exception("Unsupported image type");
        }

        // Resize the image
        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($resizedImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        // Save the resized image back in the same format
        $outputFunction($resizedImage, $filePath);

        // Free up memory
        imagedestroy($image);
        imagedestroy($resizedImage);
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize form inputs
    $name = input_filter($_POST['name']);
    $alias = input_filter($_POST['alias']);
    $bio = input_filter($_POST['bio']);
    $powers = input_filter($_POST['powers']);
    $affiliation = input_filter($_POST['affiliation']);
    $category_id = $_POST['category_id'];

    // Handle image URL (if provided)
    $image_url = input_filter($_POST['image_url']);
    if (empty($image_url) && isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        // Handle file upload if no URL is provided
        $image = $_FILES['image'];
        $imageInfo = getimagesize($image['tmp_name']);
        
        // Check if the uploaded file is an image
        if ($imageInfo !== false) {
            $uploadDirectory = 'uploads/';
            $imageName = uniqid() . '.' . pathinfo($image['name'], PATHINFO_EXTENSION);
            $targetPath = $uploadDirectory . $imageName;
            
            if (move_uploaded_file($image['tmp_name'], $targetPath)) {
                resizeImage($targetPath);  // Resize image
                $image_url = $targetPath;  // Use the upload path as image_url
            } else {
                $error = "Image upload failed.";
            }
        } else {
            $error = "Uploaded file is not a valid image.";
        }
    }

    // If no error, insert the superhero into the database
    if (empty($error)) {
        try {
            $query = 'INSERT INTO superheroes (name, alias, bio, powers, image_url, affiliation, category_id) 
                      VALUES (:name, :alias, :bio, :powers, :image_url, :affiliation, :category_id)';
            $statement = $db->prepare($query);
            $statement->bindParam(':name', $name, PDO::PARAM_STR);
            $statement->bindParam(':alias', $alias, PDO::PARAM_STR);
            $statement->bindParam(':bio', $bio, PDO::PARAM_STR);
            $statement->bindParam(':powers', $powers, PDO::PARAM_STR);
            $statement->bindParam(':image_url', $image_url, PDO::PARAM_STR);  // Use image URL (either uploaded or provided)
            $statement->bindParam(':affiliation', $affiliation, PDO::PARAM_STR);
            $statement->bindParam(':category_id', $category_id, PDO::PARAM_INT);
            $statement->execute();
            
            $_SESSION['success_message'] = 'Superhero successfully created!';
            header('Location: dashboard.php');
            exit();
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}



// Default sort by name
$sort_column = 'name'; 
$sort_order = 'ASC'; // Default sort order (ascending)

// Allowed columns for sorting
$allowed_columns = ['name', 'alias', 'affiliation'];

// Check if sorting parameters are passed via URL
if (isset($_GET['sort_by']) && in_array($_GET['sort_by'], $allowed_columns)) {
    $sort_column = $_GET['sort_by'];
}

// Check if sort_order is valid (ASC or DESC)
if (isset($_GET['sort_order']) && ($_GET['sort_order'] == 'ASC' || $_GET['sort_order'] == 'DESC')) {
    $sort_order = $_GET['sort_order'];
}

// Toggle the sort order (if it's ASC, make it DESC and vice versa)
$toggle_order = ($sort_order === 'ASC') ? 'DESC' : 'ASC';

// Fetch superheroes with sorting
$query = "SELECT * FROM superheroes ORDER BY $sort_column $sort_order";
$stmt = $db->prepare($query);
$stmt->execute();
$superheroes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle Delete Superhero
if (isset($_GET['delete_superhero'])) {
    $superhero_id = (int) $_GET['delete_superhero'];

    // Delete the superhero from the database
    $deleteQuery = 'DELETE FROM superheroes WHERE superhero_id = :superhero_id';
    $deleteStmt = $db->prepare($deleteQuery);
    $deleteStmt->bindParam(':superhero_id', $superhero_id, PDO::PARAM_INT);
    $deleteStmt->execute();

    $_SESSION['success_message'] = 'Superhero successfully deleted!';
    header("Location: dashboard.php");
    exit();
}



?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background-color: #f4f4f4;
        }
        h1, h2, h3 {
            color: #333;
        }
        nav {
            margin-bottom: 20px;
            padding: 10px;
            background-color: #333;
            text-align: center;
        }
        nav a {
            color: white;
            font-weight: bold;
            text-decoration: none;
            padding: 10px 20px;
            margin: 0 10px;
            background-color: #555;
            border-radius: 5px;
        }
        table {
            width: 100%;
            margin: 20px 0;
            border-collapse: collapse;
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        th, td {
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
        }
        th {
            background-color: #f4f4f4;
        }
        .form-section {
            margin-top: 20px;
            background-color: #fff;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            border-radius: 5px;
        }
        .form-section label {
            display: block;
            margin-bottom: 5px;
        }
        .form-section input, .form-section select {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .form-section button {
            padding: 10px 15px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .form-section button:hover {
            background-color: #45a049;
        }
        .error-message, .success-message {
            margin: 10px 0;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .success-message {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error-message p, .success-message p {
            margin: 0;
        }
         .sort-indicator {
            font-size: 0.8em;
        }
        .up, .down {
            margin-left: 5px;
            color: #007BFF;
        }
        .up:before {
            content: '↑';
        }
        .down:before {
            content: '↓';
        }
    </style>
</head>
<body>
    <h1>Welcome to the Dashboard</h1>

<!-- JavaScript to hide the success message after 35 seconds -->
<script>
    setTimeout(function() {
        var message = document.getElementById('success-message');
        if (message) {
            message.style.display = 'none';
        }
    }, 35000); // 35 seconds in milliseconds
</script>

    <nav><a href="index.php">Home</a>
        <a href="category_menu.php">Search</a>
            </nav>
    <!-- Display success message -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="success-message">
            <p><?= htmlspecialchars($_SESSION['success_message']) ?></p>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <!-- Display error message -->
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="error-message">
            <p><?= htmlspecialchars($_SESSION['error_message']) ?></p>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>


    <!-- User Management Section -->
    <h2>Registered Users</h2>
    <table>
        <tr>
            <th>Username</th>
            <th>Email</th>
            <th>Role</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($users as $user): ?>
            <tr>
                <td><?= htmlspecialchars($user['username']) ?></td>
                <td><?= htmlspecialchars($user['email']) ?></td>
                <td><?= htmlspecialchars($user['role']) ?></td>
                <td>
                    <a href="edit_login.php?id=<?= $user['user_id'] ?>">Edit</a> |
                    <a href="?delete_user=<?= $user['user_id'] ?>" onclick="return confirm('Are you sure you want to delete this user?')">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <!-- Add New User Form -->
    <div class="form-section">
        <h2>Add New User</h2>
        <form method="POST" action="dashboard.php">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required><br><br>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required><br><br>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required><br><br>

            <label for="role">Role:</label>
            <select name="role" id="role">
                <option value="user">User</option>
                <option value="admin">Admin</option>
            </select><br><br>

            <button type="submit" name="add_user">Add User</button>
        </form>
    </div>

    <!-- Category Management Section -->
    <div class="form-section">
        <h2>Manage Categories</h2>
        <form action="dashboard.php" method="post">
            <label for="category_name">Category Name:</label>
            <input type="text" name="category_name" id="category_name" required>

            <input type="hidden" name="category_id" id="category_id"> <!-- For update action -->

            <button type="submit" name="action" value="create">Create Category</button>
            <button type="submit" name="action" value="update">Update Category</button>
        </form>
    </div>

    <h3>Existing Categories</h3>
    <table>
        <tr>
            <th>Category Name</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($categories as $category): ?>
            <tr>
                <td><?= htmlspecialchars($category['category_name']) ?></td>
                <td>
                    <a href="dashboard.php?action=delete&id=<?= $category['category_id'] ?>" onclick="return confirm('Are you sure?')">Delete</a>
                    <button onclick="editCategory(<?= $category['category_id'] ?>, '<?= addslashes($category['category_name']) ?>')">Edit</button>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <script>
    function editCategory(id, name) {
        document.getElementById("category_name").value = name;
        document.getElementById("category_id").value = id;
    }
    </script>

      <!--<h1>Superheroes List</h1>

    Sorting Form (optional) 
    <form action="dashboard.php" method="GET" style="margin-bottom: 20px;">
        <label for="sort_by">Sort by:</label>
        <select name="sort_by" id="sort_by" onchange="this.form.submit()">
            <option value="name" <?= $sort_column == 'name' ? 'selected' : '' ?>>Name</option>
            <option value="alias" <?= $sort_column == 'alias' ? 'selected' : '' ?>>Alias</option>
            <option value="affiliation" <?= $sort_column == 'affiliation' ? 'selected' : '' ?>>Affiliation</option>
        </select>
        <input type="hidden" name="sort_order" value="<?= $toggle_order ?>">
    </form>

    <table>
        <thead>
            <tr>
                <th><a href="?sort_by=name&sort_order=<?= $toggle_order ?>">Name
                    <?php if ($sort_column === 'name') { echo ($sort_order === 'ASC' ? '<span class="up"></span>' : '<span class="down"></span>'); } ?>
                </a></th>
                <th><a href="?sort_by=alias&sort_order=<?= $toggle_order ?>">Alias
                    <?php if ($sort_column === 'alias') { echo ($sort_order === 'ASC' ? '<span class="up"></span>' : '<span class="down"></span>'); } ?>
                </a></th>
                <th><a href="?sort_by=affiliation&sort_order=<?= $toggle_order ?>">Affiliation
                    <?php if ($sort_column === 'affiliation') { echo ($sort_order === 'ASC' ? '<span class="up"></span>' : '<span class="down"></span>'); } ?>
                </a></th>
                <th>Powers</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($superheroes as $superhero): ?>
                <tr>
                    <td><?= htmlspecialchars($superhero['name']) ?></td>
                    <td><?= htmlspecialchars($superhero['alias']) ?></td>
                    <td><?= htmlspecialchars($superhero['affiliation']) ?></td>
                    <td><?= htmlspecialchars($superhero['powers']) ?></td>
            <?php endforeach; ?>
        </tbody>
    </table>
      -->
    <h3>Add New Superhero</h3>
     <div class="form-section">

    <form method="POST" enctype="multipart/form-data">
        <label for="name">Name</label>
        <input type="text" name="name" id="name" required>

        <label for="alias">Alias</label>
        <input type="text" name="alias" id="alias" required>

        <label for="bio">Bio</label>
        <textarea name="bio" id="bio" required></textarea>

        <label for="powers">Powers</label>
        <input type="text" name="powers" id="powers" required>

         <!-- Image Upload -->
    <label for="image">Upload Image:</label>
    <input type="file" name="image" id="image">

    <!-- Image URL -->
    <label for="image_url">Or, Enter Image URL:</label>
    <input type="text" name="image_url" id="image_url" placeholder="Enter image URL">

        <label for="affiliation">Affiliation</label>
        <input type="text" name="affiliation" id="affiliation" required>

        <label for="category_id">Category</label>
        <select name="category_id" id="category_id" required>
              <option value="" disabled selected>Select a Category</option>
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
    </div>
    </form>

    


    <h2>Registered Superheroes</h2>
<table>
     <label for="sort_by">Applied sorting:</label>
    <select name="sort_by" id="sort_by" onchange="this.form.submit()">
        <option value="name" <?= $sort_column == 'name' ? 'selected' : '' ?>>Name</option>
        <option value="alias" <?= $sort_column == 'alias' ? 'selected' : '' ?>>Alias</option>
        <option value="affiliation" <?= $sort_column == 'affiliation' ? 'selected' : '' ?>>Affiliation</option>
    </select>
    <input type="hidden" name="sort_order" value="<?= $sort_order ?>">
    <tr>
           <th><a href="?sort_by=name&sort_order=<?= $toggle_order ?>">Name
                <?php if ($sort_column === 'name') { echo ($sort_order === 'ASC' ? '<span class="up"></span>' : '<span class="down"></span>'); } ?>
            </a></th>
        <th><a href="?sort_by=alias&sort_order=<?= $toggle_order ?>">Alias
                <?php if ($sort_column === 'alias') { echo ($sort_order === 'ASC' ? '<span class="up"></span>' : '<span class="down"></span>'); } ?>
            </a></th>
        <th>Bio</th>
        <th>Powers</th>
        <th>Image URL</th>
       <th><a href="?sort_by=affiliation&sort_order=<?= $toggle_order ?>">Affiliation
                <?php if ($sort_column === 'affiliation') { echo ($sort_order === 'ASC' ? '<span class="up"></span>' : '<span class="down"></span>'); } ?>
            </a></th>
        <th>Action</th>
    </tr>
    <?php foreach ($superheroes as $superhero): ?>
        <tr>
            <td><?= htmlspecialchars($superhero['name']) ?></td>
            <td><?= htmlspecialchars($superhero['alias']) ?></td>
            <td><?= htmlspecialchars($superhero['bio']) ?></td>
            <td><?= htmlspecialchars($superhero['powers']) ?></td>
            <td><?php if (!empty($superhero['image_url'])): ?>
                    <img src="<?= htmlspecialchars($superhero['image_url']) ?>" alt="<?= htmlspecialchars($superhero['name']) ?>" width="100" />
                <?php else: ?>
                    <span>No image available</span>
                <?php endif; ?></td>
            <td><?= htmlspecialchars($superhero['affiliation']) ?></td>
            <td>
                <a href="edit.php?id=<?= $superhero['superhero_id'] ?>">Edit</a> |
                <a href="?delete_superhero=<?= $superhero['superhero_id'] ?>" onclick="return confirm('Are you sure you want to delete this superhero?')">Delete</a>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

<script>
// Save scroll position before page unload
window.addEventListener("beforeunload", function () {
    localStorage.setItem("scrollY", window.scrollY);
});

// Restore scroll position on load
window.addEventListener("load", function () {
    const scrollY = localStorage.getItem("scrollY");
    if (scrollY !== null) {
        window.scrollTo(0, parseInt(scrollY));
        localStorage.removeItem("scrollY"); // Optional: clear after use
    }
});
</script>
    <p><a href="logout.php" style="color: blue; font-weight: bold; text-decoration: none;">Logout</a></p>

</body>
</html>




