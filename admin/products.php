<?php
// Include database connection
require_once '../require/db.php';

// Handle form submissions for add, edit, delete
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$id = $_POST['id'] ?? $_GET['id'] ?? '';
$name = $_POST['name'] ?? '';
$second_categories_id = $_POST['second_categories_id'] ?? '';
$original_price = $_POST['original_price'] ?? '';
$status = $_POST['status'] ?? 'active';
$images = $_FILES['images'] ?? null;

// Image upload path
$target_dir = "../img/";

switch ($action) {
    case 'add':
        if (!empty($name) && !empty($second_categories_id) && !empty($original_price)) {
            $image_name = '';
            if ($images && $images['error'] == 0) {
                $image_name = basename($images["name"]);
                move_uploaded_file($images["tmp_name"], $target_dir . $image_name);
            }
            $stmt = $mysqli->prepare("INSERT INTO products (name, second_categories_id, original_price, status, images) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sidss", $name, $second_categories_id, $original_price, $status, $image_name);
            $stmt->execute();
        }
        header("Location: products.php");
        exit;
    case 'edit_form':
        $result = $mysqli->query("SELECT * FROM products WHERE id = $id");
        $product_to_edit = $result->fetch_assoc();
        break;
    case 'edit':
        if (!empty($id) && !empty($name) && !empty($second_categories_id) && !empty($original_price)) {
            $image_name = $_POST['existing_image'];
            if ($images && $images['error'] == 0) {
                $image_name = basename($images["name"]);
                move_uploaded_file($images["tmp_name"], $target_dir . $image_name);
            }
            $stmt = $mysqli->prepare("UPDATE products SET name = ?, second_categories_id = ?, original_price = ?, status = ?, images = ? WHERE id = ?");
            $stmt->bind_param("sidssi", $name, $second_categories_id, $original_price, $status, $image_name, $id);
            $stmt->execute();
        }
        header("Location: products.php");
        exit;
    case 'delete':
        if (!empty($id)) {
            $stmt = $mysqli->prepare("UPDATE products SET status = 'inactive' WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
        }
        header("Location: products.php");
        exit;
}

// Fetch all products with category names
$result = $mysqli->query("
    SELECT p.id, p.name, p.original_price, p.status, p.images, sc.name as second_category_name, p.second_categories_id
    FROM products p
    JOIN second_categories sc ON p.second_categories_id = sc.id
    ORDER BY p.id DESC
");
$products = $result->fetch_all(MYSQLI_ASSOC);

// Fetch all second categories for the dropdown
$second_categories_result = $mysqli->query(
    "SELECT second_categories.* , main_categories.name AS main_category_name
    FROM second_categories 
    LEFT JOIN main_categories ON main_categories.id = second_categories.main_categories_id
    ORDER BY name ASC"
);
$second_categories = $second_categories_result->fetch_all(MYSQLI_ASSOC);

// Include header
include 'layouts/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Products</h4>

                    <!-- Add/Edit Form -->
                    <form action="products.php" method="post" enctype="multipart/form-data" class="mb-3">
                        <input type="hidden" name="action" value="<?= isset($product_to_edit) ? 'edit' : 'add' ?>">
                        <input type="hidden" name="id" value="<?= $id ?>">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="name">Product Name</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?= isset($product_to_edit) ? htmlspecialchars($product_to_edit['name']) : '' ?>" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="second_categories_id">Category</label>
                                <select class="form-control" id="second_categories_id" name="second_categories_id" required>
                                    <option value="">Select Category</option>
                                    <?php foreach ($second_categories as $sc): ?>
                                        <option value="<?= $sc['id'] ?>" <?= (isset($product_to_edit) && $product_to_edit['second_categories_id'] == $sc['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($sc['name']) ?> (<?= htmlspecialchars($sc['main_category_name']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="original_price">Price</label>
                                <input type="number" step="0.01" class="form-control" id="original_price" name="original_price" value="<?= isset($product_to_edit) ? $product_to_edit['original_price'] : '' ?>" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="status">Status</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="active" <?= (isset($product_to_edit) && $product_to_edit['status'] == 'active') ? 'selected' : '' ?>>Active</option>
                                    <option value="inactive" <?= (isset($product_to_edit) && $product_to_edit['status'] == 'inactive') ? 'selected' : '' ?>>Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="images">Image</label>
                            <input type="file" class="form-control-file" id="images" name="images">
                            <?php if (isset($product_to_edit) && !empty($product_to_edit['images'])): ?>
                                <input type="hidden" name="existing_image" value="<?= htmlspecialchars($product_to_edit['images']) ?>">
                                <img src="<?= $target_dir . htmlspecialchars($product_to_edit['images']) ?>" alt="Product Image" width="100" class="mt-2">
                            <?php endif; ?>
                        </div>
                        <button type="submit" class="btn btn-primary"><?= isset($product_to_edit) ? 'Update' : 'Add' ?> Product</button>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-striped table-bordered zero-configuration">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Image</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $product): ?>
                                    <tr>
                                        <td><?= $product['id'] ?></td>
                                        <td>
                                            <?php if (!empty($product['images'])): ?>
                                                <img src="<?= $target_dir . htmlspecialchars($product['images']) ?>" alt="Product Image" width="50">
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($product['name']) ?></td>
                                        <td><?= htmlspecialchars($product['second_category_name']) ?></td>
                                        <td><?= $product['original_price'] ?></td>
                                        <td>
                                            <span class="badge <?= $product['status'] == 'inactive' ? 'badge-danger' : 'badge-success text-white' ?>"><?= ucfirst($product['status']) ?></span>
                                        </td>
                                        <td>
                                            <a href="products.php?action=edit_form&id=<?= $product['id'] ?>" class="btn btn-sm btn-info">Edit</a>
                                            <a href="products.php?action=delete&id=<?= $product['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
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