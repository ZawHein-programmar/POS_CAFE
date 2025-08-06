<?php
// Include database connection
require_once '../require/db.php';

// Handle form submissions for add, edit, delete
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$id = $_POST['id'] ?? $_GET['id'] ?? '';
$name = $_POST['name'] ?? '';
$status = $_POST['status'] ?? 'available';

switch ($action) {
    case 'add':
        if (!empty($name)) {
            $stmt = $mysqli->prepare("INSERT INTO tables (name, status) VALUES (?, ?)");
            $stmt->bind_param("ss", $name, $status);
            $stmt->execute();
        }
        header("Location: tables.php");
        exit;
    case 'edit_form':
        $result = $mysqli->query("SELECT * FROM tables WHERE id = $id");
        $table_to_edit = $result->fetch_assoc();
        break;
    case 'edit':
        if (!empty($id) && !empty($name)) {
            $stmt = $mysqli->prepare("UPDATE tables SET name = ?, status = ? WHERE id = ?");
            $stmt->bind_param("ssi", $name, $status, $id);
            $stmt->execute();
        }
        header("Location: tables.php");
        exit;
    case 'delete':
        if (!empty($id)) {
            $stmt = $mysqli->prepare("DELETE FROM tables WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
        }
        header("Location: tables.php");
        exit;
}

// Fetch all tables
$result = $mysqli->query("SELECT * FROM tables ORDER BY id ASC");
$tables = $result->fetch_all(MYSQLI_ASSOC);

// Include header
include 'layouts/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Tables</h4>
                    
                    <!-- Add/Edit Form -->
                    <form action="tables.php" method="post" class="form-inline mb-3">
                        <input type="hidden" name="action" value="<?= isset($table_to_edit) ? 'edit' : 'add' ?>">
                        <input type="hidden" name="id" value="<?= $id ?>">
                        <div class="form-group mx-sm-3 mb-2">
                            <label for="name" class="sr-only">Table Name</label>
                            <input type="text" class="form-control" id="name" name="name" placeholder="Table Name" value="<?= isset($table_to_edit) ? htmlspecialchars($table_to_edit['name']) : '' ?>" required>
                        </div>
                        <div class="form-group mx-sm-3 mb-2">
                            <label for="status" class="sr-only">Status</label>
                            <select class="form-control" id="status" name="status">
                                <option value="available" <?= (isset($table_to_edit) && $table_to_edit['status'] == 'available') ? 'selected' : '' ?>>Available</option>
                                <option value="occupied" <?= (isset($table_to_edit) && $table_to_edit['status'] == 'occupied') ? 'selected' : '' ?>>Occupied</option>
                                <option value="reserved" <?= (isset($table_to_edit) && $table_to_edit['status'] == 'reserved') ? 'selected' : '' ?>>Reserved</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary mb-2"><?= isset($table_to_edit) ? 'Update' : 'Add' ?> Table</button>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-striped table-bordered zero-configuration">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tables as $table): ?>
                                <tr>
                                    <td><?= $table['id'] ?></td>
                                    <td><?= htmlspecialchars($table['name']) ?></td>
                                    <td><?= ucfirst($table['status']) ?></td>
                                    <td>
                                        <a href="tables.php?action=edit_form&id=<?= $table['id'] ?>" class="btn btn-sm btn-info">Edit</a>
                                        <a href="tables.php?action=delete&id=<?= $table['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
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
