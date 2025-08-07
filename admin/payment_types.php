<?php
require_once("../auth/isLogin.php");
require_once '../require/db.php';

// Handle form submissions for add, edit, delete
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$id = $_POST['id'] ?? $_GET['id'] ?? '';
$name = $_POST['name'] ?? '';

switch ($action) {
    case 'add':
        if (!empty($name)) {
            $stmt = $mysqli->prepare("INSERT INTO payment_type (name) VALUES (?)");
            $stmt->bind_param("s", $name);
            $stmt->execute();
        }
        header("Location: payment_types.php");
        exit;
    case 'edit_form':
        $result = $mysqli->query("SELECT * FROM payment_type WHERE id = $id");
        $type_to_edit = $result->fetch_assoc();
        break;
    case 'edit':
        if (!empty($id) && !empty($name)) {
            $stmt = $mysqli->prepare("UPDATE payment_type SET name = ? WHERE id = ?");
            $stmt->bind_param("si", $name, $id);
            $stmt->execute();
        }
        header("Location: payment_types.php");
        exit;
    case 'delete':
        if (!empty($id)) {
            $stmt = $mysqli->prepare("UPDATE payment_type SET status = 'inactive' WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
        }
        header("Location: payment_types.php");
        exit;
}

// Fetch all payment types
$result = $mysqli->query("SELECT * FROM payment_type  ORDER BY id DESC");
$payment_types = $result->fetch_all(MYSQLI_ASSOC);

// Include header
include 'layouts/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Payment Types</h4>

                    <!-- Add/Edit Form -->
                    <form action="payment_types.php" method="post" class="form-inline mb-3">
                        <input type="hidden" name="action" value="<?= isset($type_to_edit) ? 'edit' : 'add' ?>">
                        <input type="hidden" name="id" value="<?= $id ?>">
                        <div class="form-group mx-sm-3 mb-2">
                            <label for="name" class="sr-only">Name</label>
                            <input type="text" class="form-control" id="name" name="name" placeholder="Payment Type Name" value="<?= isset($type_to_edit) ? htmlspecialchars($type_to_edit['name']) : '' ?>" required>
                        </div>
                        <button type="submit" class="btn btn-primary mb-2"><?= isset($type_to_edit) ? 'Update' : 'Add' ?> Payment Type</button>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-striped table-bordered zero-configuration">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payment_types as $type): ?>
                                    <tr>
                                        <td><?= $type['id'] ?></td>
                                        <td><?= htmlspecialchars($type['name']) ?></td>
                                        <td><?= $type['created_at'] ?></td>
                                        <td>
                                            <a href="payment_types.php?action=edit_form&id=<?= $type['id'] ?>" class="btn btn-sm btn-info">Edit</a>
                                            <a href="payment_types.php?action=delete&id=<?= $type['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
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