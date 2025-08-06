<?php
// Include database connection
require_once '../require/db.php';

// Handle form submissions for add, edit, delete
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$id = $_POST['id'] ?? $_GET['id'] ?? '';
$name = $_POST['name'] ?? '';

switch ($action) {
    case 'add':
        if (!empty($name)) {
            $stmt = $mysqli->prepare("INSERT INTO main_categories (name) VALUES (?)");
            $stmt->bind_param("s", $name);
            $stmt->execute();
        }
        header("Location: main_categories.php");
        exit;
    case 'edit_form':
        $result = $mysqli->query("SELECT * FROM main_categories WHERE id = $id");
        $category_to_edit = $result->fetch_assoc();
        break;
    case 'edit':
        if (!empty($id) && !empty($name)) {
            $stmt = $mysqli->prepare("UPDATE main_categories SET name = ? WHERE id = ?");
            $stmt->bind_param("si", $name, $id);
            $stmt->execute();
        }
        header("Location: main_categories.php");
        exit;
    case 'delete':
        if (!empty($id)) {
            $stmt = $mysqli->prepare("DELETE FROM main_categories WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
        }
        header("Location: main_categories.php");
        exit;
}

// Fetch all main categories
$result = $mysqli->query("SELECT * FROM main_categories ORDER BY id DESC");
$main_categories = $result->fetch_all(MYSQLI_ASSOC);

// Include header
include 'layouts/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Main Categories</h4>

                    <!-- Add/Edit Form -->
                    <form action="main_categories.php" method="post" class="form-inline mb-3">
                        <input type="hidden" name="action" value="<?= isset($category_to_edit) ? 'edit' : 'add' ?>">
                        <input type="hidden" name="id" value="<?= $id ?>">
                        <div class="form-group mx-sm-3 mb-2">
                            <label for="name" class="sr-only">Name</label>
                            <input type="text" class="form-control" id="name" name="name" placeholder="Category Name" value="<?= isset($category_to_edit) ? htmlspecialchars($category_to_edit['name']) : '' ?>" required>
                        </div>
                        <button type="submit" class="btn btn-primary mb-2"><?= isset($category_to_edit) ? 'Update' : 'Add' ?> Category</button>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-striped table-bordered zero-configuration">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Created At</th>
                                    <th>Updated At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($main_categories as $category): ?>
                                    <tr>
                                        <td><?= $category['id'] ?></td>
                                        <td><?= htmlspecialchars($category['name']) ?></td>
                                        <td><?= $category['created_at'] ?></td>
                                        <td><?= $category['updated_at'] ?></td>
                                        <td>
                                            <a href="main_categories.php?action=edit_form&id=<?= $category['id'] ?>" class="btn btn-sm btn-info">Edit</a>
                                            <a href="main_categories.php?action=delete&id=<?= $category['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
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