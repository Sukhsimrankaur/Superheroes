<?php
require 'db.php';

// Sanitize text input
$search = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

// Validate category_id as integer
$selected_category = filter_input(INPUT_GET, 'category_id', FILTER_VALIDATE_INT);
if ($selected_category === false) {
    $selected_category = null; // or some safe default
}

// Validate pagination page as integer, default to 1
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
if ($page === false || $page < 1) {
    $page = 1;
}

$per_page = 5; // Results per page
$offset = ($page - 1) * $per_page;

$where = [];
$params = [];

// Search condition
if (!empty($search)) {
    $where[] = "(name LIKE ? OR alias LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Category filter condition
if (!empty($selected_category) && is_numeric($selected_category)) {
    $where[] = "category_id = ?";
    $params[] = $selected_category;
}

// Build WHERE clause
$where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Fetch total results for pagination
$total_stmt = $db->prepare("SELECT COUNT(*) FROM superheroes $where_clause");
$total_stmt->execute($params);
$total_results = $total_stmt->fetchColumn();
$total_pages = ceil($total_results / $per_page);

// Fetch paginated superheroes
$sql = "SELECT superhero_id, name, alias, image_url FROM superheroes $where_clause LIMIT $per_page OFFSET $offset";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$superheroes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch categories for dropdown
$cat_stmt = $db->query("SELECT * FROM categories");
$categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="main.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <title>Browse Superheroes</title>
</head>
<body>
    <h1>Browse Superheroes</h1>
    <nav class="d-flex justify-content-between">
        <div>
            <a href="index.php" id="home" class="mx-3">Home</a>
            <a href="category_menu.php">Search</a>
        </div>
        <div>
            <a href="register.php">Register</a>
        </div>
    </nav>

    <!-- Search Form and Category Filter -->
    <form id="filterForm" method="GET" style="margin: 20px auto;">
        <input type="text" name="search" placeholder="Search by keyword" value="<?= htmlspecialchars($search) ?>" style="padding: 8px;">
        
        <!-- Submit button (Search functionality) -->
        <button type="submit" style="padding: 8px 12px; background-color: #007bff; color: white; border: none; border-radius: 4px;">Search</button>
    </form>

    <!-- Category Filter Form (Separate from Search) -->
    <form method="GET" style="margin: 20px auto;">
        <select name="category_id" style="padding: 8px;" onchange="this.form.submit();">
            <option value="">All Categories</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['category_id'] ?>" <?= ($selected_category == $cat['category_id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['category_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>

    <!-- Display Categories for Quick Access -->
    <p style="text-align: center; font-size: 1.2rem; font-weight: bold; margin-top: 20px;">
        <?php foreach ($categories as $category): ?>
            <a href="non-admin_category.php?category_id=<?= htmlspecialchars($category['category_id']) ?>" 
               style="display: inline-block; padding: 10px 15px; margin: 5px; text-decoration: none; color: white; background-color: #007bff; border-radius: 8px; transition: background 0.3s ease;"
               onmouseover="this.style.backgroundColor='#0056b3'" onmouseout="this.style.backgroundColor='#007bff'">
                <?= htmlspecialchars($category['category_name']) ?>
            </a>
        <?php endforeach; ?>
    </p>

    <!-- List Superheroes -->
    <div style="text-align: center; margin-top: 30px;">
        <?php if (empty($superheroes)): ?>
            <p>No superheroes found for this search.</p>
        <?php else: ?>
            <ul style="list-style: none; padding: 0; display: flex; flex-wrap: wrap; justify-content: center;">
                <?php foreach ($superheroes as $hero): ?>
                    <li style="background: white; padding: 15px; margin: 10px; border-radius: 10px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); width: 200px; text-align: center;">
                        <?php if (!empty($hero['image_url'])): ?>
                            <img src="<?= htmlspecialchars($hero['image_url']) ?>" class="card-img-top" alt="<?= htmlspecialchars($hero['name'] ?? 'Unknown Name') ?>" style="max-height: 200px; width: 100%; object-fit: cover; border-radius: 8px;">
                        <?php endif; ?>
                        <strong><?= htmlspecialchars($hero['alias']) ?> (<?= htmlspecialchars($hero['name']) ?>)</strong>
                        <!-- View Profile Button -->
                        <div style="margin-top: 10px;">
                            <a href="cms.php?id=<?= $hero['superhero_id'] ?>" 
                               style="padding: 8px 12px; background-color: #007bff; color: white; text-decoration: none; border-radius: 4px;">
                                View Profile
                            </a>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <!-- Pagination Links -->
    <?php if ($total_pages > 1): ?>
        <div style="text-align: center; margin-top: 20px;">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?search=<?= urlencode($search) ?>&category_id=<?= urlencode($selected_category) ?>&page=<?= $i ?>"
                   style="margin: 0 5px; padding: 8px 12px; border-radius: 4px; background-color: <?= ($page == $i) ? '#0056b3' : '#007bff' ?>; color: white; text-decoration: none;">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>

</body>
</html>
