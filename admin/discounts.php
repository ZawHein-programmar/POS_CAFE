<?php
// Include database connection
require_once '../require/db.php';

// Handle form submissions for add, edit, delete
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$id = $_POST['id'] ?? $_GET['id'] ?? '';
$name = $_POST['name'] ?? '';
$percent = $_POST['percent'] ?? '';
$product_id = $_POST['product_id'] ?? '';
$start_date = $_POST['start_date'] ?? '';
$end_date = $_POST['end_date'] ?? '';

switch ($action) {
    case 'add':
        if (!empty($name) && !empty($percent) && !empty($product_id) && !empty($start_date) && !empty($end_date)) {
            $stmt = $mysqli->prepare("INSERT INTO discounts (name, percent, product_id, start_date, end_date) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sdiss", $name, $percent, $product_id, $start_date, $end_date);
            $stmt->execute();
        }
        header("Location: discounts.php");
        exit;
    case 'edit_form':
        $result = $mysqli->query("SELECT * FROM discounts WHERE id = $id");
        $discount_to_edit = $result->fetch_assoc();
        break;
    case 'edit':
        if (!empty($id) && !empty($name) && !empty($percent) && !empty($product_id) && !empty($start_date) && !empty($end_date)) {
            $stmt = $mysqli->prepare("UPDATE discounts SET name = ?, percent = ?, product_id = ?, start_date = ?, end_date = ? WHERE id = ?");
            $stmt->bind_param("sdissi", $name, $percent, $product_id, $start_date, $end_date, $id);
            $stmt->execute();
        }
        header("Location: discounts.php");
        exit;
    case 'delete':
        if (!empty($id)) {
            $stmt = $mysqli->prepare("DELETE FROM discounts WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
        }
        header("Location: discounts.php");
        exit;
}

// Fetch all discounts with product name
$result = $mysqli->query("
    SELECT d.id, d.name, d.percent, d.start_date, d.end_date, p.name as product_name, d.product_id
    FROM discounts d
    JOIN products p ON d.product_id = p.id
    ORDER BY d.id DESC
");
$discounts = $result->fetch_all(MYSQLI_ASSOC);

// Fetch all products for the dropdown
$products_result = $mysqli->query("SELECT * FROM products ORDER BY name ASC");
$products = $products_result->fetch_all(MYSQLI_ASSOC);

// Include header
include 'layouts/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Discounts</h4>
                    
                    <!-- Add/Edit Form -->
                    <form action="discounts.php" method="post" class="mb-3">
                        <input type="hidden" name="action" value="<?= isset($discount_to_edit) ? 'edit' : 'add' ?>">
                        <input type="hidden" name="id" value="<?= $id ?>">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="name">Discount Name</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?= isset($discount_to_edit) ? htmlspecialchars($discount_to_edit['name']) : '' ?>" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="percent">Percentage</label>
                                <input type="number" step="0.01" class="form-control" id="percent" name="percent" value="<?= isset($discount_to_edit) ? $discount_to_edit['percent'] : '' ?>" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label for="product_id">Product</label>
                                <select class="form-control" id="product_id" name="product_id" required>
                                    <option value="">Select Product</option>
                                    <?php foreach ($products as $product): ?>
                                    <option value="<?= $product['id'] ?>" <?= (isset($discount_to_edit) && $discount_to_edit['product_id'] == $product['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($product['name']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="start_date">Start Date</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" value="<?= isset($discount_to_edit) ? $discount_to_edit['start_date'] : '' ?>" required>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="end_date">End Date</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" value="<?= isset($discount_to_edit) ? $discount_to_edit['end_date'] : '' ?>" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary"><?= isset($discount_to_edit) ? 'Update' : 'Add' ?> Discount</button>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-striped table-bordered zero-configuration">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Product</th>
                                    <th>Percentage</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($discounts as $discount): ?>
                                <tr>
                                    <td><?= $discount['id'] ?></td>
                                    <td><?= htmlspecialchars($discount['name']) ?></td>
                                    <td><?= htmlspecialchars($discount['product_name']) ?></td>
                                    <td><?= $discount['percent'] ?>%</td>
                                    <td><?= $discount['start_date'] ?></td>
                                    <td><?= $discount['end_date'] ?></td>
                                    <td>
                                        <a href="discounts.php?action=edit_form&id=<?= $discount['id'] ?>" class="btn btn-sm btn-info">Edit</a>
                                        <a href="discounts.php?action=delete&id=<?= $discount['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
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
