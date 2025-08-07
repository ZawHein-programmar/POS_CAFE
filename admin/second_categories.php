<?php
require_once("../auth/isLogin.php");
require_once '../require/db.php';

// Handle form submissions for add, edit, delete
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$id = $_POST['id'] ?? $_GET['id'] ?? '';
$name = $_POST['name'] ?? '';
$main_category_id = $_POST['main_category_id'] ?? '';

switch ($action) {
    case 'add':
        if (!empty($name) && !empty($main_category_id)) {
            $stmt = $mysqli->prepare("INSERT INTO second_categories (name, main_categories_id) VALUES (?, ?)");
            $stmt->bind_param("si", $name, $main_category_id);
            $stmt->execute();
        }
        header("Location: second_categories.php");
        exit;
    case 'edit_form':
        $result = $mysqli->query("SELECT * FROM second_categories WHERE id = $id");
        $category_to_edit = $result->fetch_assoc();
        break;
    case 'edit':
        if (!empty($id) && !empty($name) && !empty($main_category_id)) {
            $stmt = $mysqli->prepare("UPDATE second_categories SET name = ?, main_categories_id = ? WHERE id = ?");
            $stmt->bind_param("sii", $name, $main_category_id, $id);
            $stmt->execute();
        }
        header("Location: second_categories.php");
        exit;
    case 'delete':
        if (!empty($id)) {
            $stmt = $mysqli->prepare("DELETE FROM second_categories WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
        }
        header("Location: second_categories.php");
        exit;
}

// Fetch all second categories with main category name
$result = $mysqli->query("
    SELECT sc.id, sc.name, sc.created_at, sc.updated_at, mc.name as main_category_name, sc.main_categories_id
    FROM second_categories sc
    JOIN main_categories mc ON sc.main_categories_id = mc.id
    ORDER BY sc.id DESC
");
$second_categories = $result->fetch_all(MYSQLI_ASSOC);

// Fetch all main categories for the dropdown
$main_categories_result = $mysqli->query("SELECT * FROM main_categories ORDER BY name ASC");
$main_categories = $main_categories_result->fetch_all(MYSQLI_ASSOC);

// Include header
include 'layouts/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Second Categories</h4>

                    <!-- Add/Edit Form -->
                    <form action="second_categories.php" method="post" class="form-inline mb-3">
                        <input type="hidden" name="action" value="<?= isset($category_to_edit) ? 'edit' : 'add' ?>">
                        <input type="hidden" name="id" value="<?= $id ?>">
                        <div class="form-group mx-sm-3 mb-2">
                            <label for="name" class="sr-only">Name</label>
                            <input type="text" class="form-control" id="name" name="name" placeholder="Category Name" value="<?= isset($category_to_edit) ? htmlspecialchars($category_to_edit['name']) : '' ?>" required>
                        </div>
                        <div class="form-group mx-sm-3 mb-2">
                            <label for="main_category_id" class="sr-only">Main Category</label>
                            <select class="form-control" id="main_category_id" name="main_category_id" required>
                                <option value="">Select Main Category</option>
                                <?php foreach ($main_categories as $mc): ?>
                                    <option value="<?= $mc['id'] ?>" <?= (isset($category_to_edit) && $category_to_edit['main_categories_id'] == $mc['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($mc['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary mb-2"><?= isset($category_to_edit) ? 'Update' : 'Add' ?> Category</button>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-striped table-bordered zero-configuration">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Main Category</th>
                                    <th>Created At</th>
                                    <th>Updated At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($second_categories as $category): ?>
                                    <tr>
                                        <td><?= $category['id'] ?></td>
                                        <td><?= htmlspecialchars($category['name']) ?></td>
                                        <td><?= htmlspecialchars($category['main_category_name']) ?></td>
                                        <td><?= $category['created_at'] ?></td>
                                        <td><?= $category['updated_at'] ?></td>
                                        <td>
                                            <a href="second_categories.php?action=edit_form&id=<?= $category['id'] ?>" class="btn btn-sm btn-info">Edit</a>
                                            <a href="second_categories.php?action=delete&id=<?= $category['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include 'layouts/footer.php';
?>