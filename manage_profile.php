<?php
session_start();
require 'db.php'; // Database connection

// Check if user is logged in and has the right role
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'editor')) {
    header('Location: login.php'); // Redirect if not authorized
    exit;
}

// Sorting logic
$sortColumn = isset($_GET['sort']) ? $_GET['sort'] : 'name';
$sortOrder = isset($_GET['order']) && $_GET['order'] == 'desc' ? 'desc' : 'asc';

// Validate sorting column
$validColumns = ['name', 'created_at', 'updated_at'];
if (!in_array($sortColumn, $validColumns)) {
    $sortColumn = 'name'; // Default to name if invalid
}

// Query to fetch superhero profiles
$query = "SELECT * FROM superhero_profiles ORDER BY $sortColumn $sortOrder";
$stmt = $pdo->prepare($query);
$stmt->execute();
$profiles = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Superhero Profiles</title>
    <style>
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th a { text-decoration: none; color: black; }
        .sort-arrow { margin-left: 5px; font-size: 12px; }
    </style>
</head>
<body>
    <h2>Manage Superhero Profiles</h2>

    <table>
        <thead>
            <tr>
                <th>
                    <a href="?sort=name&order=<?= ($sortColumn == 'name' && $sortOrder == 'asc') ? 'desc' : 'asc'; ?>">
                        Name <?= $sortColumn == 'name' ? ($sortOrder == 'asc' ? '↑' : '↓') : ''; ?>
                    </a>
                </th>
                <th>
                    <a href="?sort=created_at&order=<?= ($sortColumn == 'created_at' && $sortOrder == 'asc') ? 'desc' : 'asc'; ?>">
                        Created At <?= $sortColumn == 'created_at' ? ($sortOrder == 'asc' ? '↑' : '↓') : ''; ?>
                    </a>
                </th>
                <th>
                    <a href="?sort=updated_at&order=<?= ($sortColumn == 'updated_at' && $sortOrder == 'asc') ? 'desc' : 'asc'; ?>">
                        Updated At <?= $sortColumn == 'updated_at' ? ($sortOrder == 'asc' ? '↑' : '↓') : ''; ?>
                    </a>
                </th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($profiles as $profile): ?>
                <tr>
                    <td><?= htmlspecialchars($profile['name']); ?></td>
                    <td><?= htmlspecialchars($profile['created_at']); ?></td>
                    <td><?= htmlspecialchars($profile['updated_at']); ?></td>
                    <td>
                        <a href="view_profile.php?id=<?= $profile['id']; ?>">View</a> | 
                        <a href="edit_profile.php?id=<?= $profile['id']; ?>">Edit</a> | 
                        <a href="delete_profile.php?id=<?= $profile['id']; ?>" onclick="return confirm('Are you sure?');">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
