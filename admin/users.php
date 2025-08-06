<?php
// Include database connection
require_once '../require/db.php';

// Handle form submissions for add, edit, delete
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$id = $_POST['id'] ?? $_GET['id'] ?? '';
$user_name = $_POST['user_name'] ?? '';
$password = $_POST['password'] ?? '';
$name = $_POST['name'] ?? '';
$status = $_POST['status'] ?? 'active';
$role = $_POST['role'] ?? 'cashier';
$image = $_FILES['image'] ?? null;

// Image upload path
$target_dir = "../img/user/";
if (!file_exists($target_dir)) {
    mkdir($target_dir, 0777, true);
}

switch ($action) {
    case 'add':
        if (!empty($user_name) && !empty($password) && !empty($name)) {
            $image_name = '';
            if ($image && $image['error'] == 0) {
                $image_name = basename($image["name"]);
                move_uploaded_file($image["tmp_name"], $target_dir . $image_name);
            }
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $mysqli->prepare("INSERT INTO user (user_name, password, name, status, role, image) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $user_name, $hashed_password, $name, $status, $role, $image_name);
            $stmt->execute();
        }
        header("Location: users.php");
        exit;
    case 'edit_form':
        $result = $mysqli->query("SELECT * FROM user WHERE id = $id");
        $user_to_edit = $result->fetch_assoc();
        break;
    case 'edit':
        if (!empty($id) && !empty($user_name) && !empty($name)) {
            $image_name = $_POST['existing_image'];
            if ($image && $image['error'] == 0) {
                $image_name = basename($image["name"]);
                move_uploaded_file($image["tmp_name"], $target_dir . $image_name);
            }
            
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $mysqli->prepare("UPDATE user SET user_name = ?, password = ?, name = ?, status = ?, role = ?, image = ? WHERE id = ?");
                $stmt->bind_param("ssssssi", $user_name, $hashed_password, $name, $status, $role, $image_name, $id);
            } else {
                $stmt = $mysqli->prepare("UPDATE user SET user_name = ?, name = ?, status = ?, role = ?, image = ? WHERE id = ?");
                $stmt->bind_param("sssssi", $user_name, $name, $status, $role, $image_name, $id);
            }
            $stmt->execute();
        }
        header("Location: users.php");
        exit;
    case 'delete':
        if (!empty($id)) {
            $stmt = $mysqli->prepare("UPDATE user SET status = 'inactive' WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
        }
        header("Location: users.php");
        exit;
}

// Fetch all users
$result = $mysqli->query("SELECT * FROM user WHERE status = 'active' ORDER BY id DESC");
$users = $result->fetch_all(MYSQLI_ASSOC);

// Include header
include 'layouts/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Users</h4>
                    
                    <!-- Add/Edit Form -->
                    <form action="users.php" method="post" enctype="multipart/form-data" class="mb-3">
                        <input type="hidden" name="action" value="<?= isset($user_to_edit) ? 'edit' : 'add' ?>">
                        <input type="hidden" name="id" value="<?= $id ?>">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="user_name">Username</label>
                                <input type="text" class="form-control" id="user_name" name="user_name" value="<?= isset($user_to_edit) ? htmlspecialchars($user_to_edit['user_name']) : '' ?>" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="password">Password</label>
                                <input type="password" class="form-control" id="password" name="password" <?= isset($user_to_edit) ? '' : 'required' ?>>
                                <?php if (isset($user_to_edit)): ?><small class="form-text text-muted">Leave blank to keep current password.</small><?php endif; ?>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="name">Full Name</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?= isset($user_to_edit) ? htmlspecialchars($user_to_edit['name']) : '' ?>" required>
                            </div>
                            <div class="form-group col-md-3">
                                <label for="role">Role</label>
                                <select class="form-control" id="role" name="role">
                                    <option value="admin" <?= (isset($user_to_edit) && $user_to_edit['role'] == 'admin') ? 'selected' : '' ?>>Admin</option>
                                    <option value="cashier" <?= (isset($user_to_edit) && $user_to_edit['role'] == 'cashier') ? 'selected' : '' ?>>Cashier</option>
                                    <option value="kitchen" <?= (isset($user_to_edit) && $user_to_edit['role'] == 'kitchen') ? 'selected' : '' ?>>Kitchen</option>
                                    <option value="waiter" <?= (isset($user_to_edit) && $user_to_edit['role'] == 'waiter') ? 'selected' : '' ?>>Waiter</option>
                                </select>
                            </div>
                            <div class="form-group col-md-3">
                                <label for="status">Status</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="active" <?= (isset($user_to_edit) && $user_to_edit['status'] == 'active') ? 'selected' : '' ?>>Active</option>
                                    <option value="inactive" <?= (isset($user_to_edit) && $user_to_edit['status'] == 'inactive') ? 'selected' : '' ?>>Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="image">Image</label>
                            <input type="file" class="form-control-file" id="image" name="image">
                            <?php if (isset($user_to_edit) && !empty($user_to_edit['image'])): ?>
                            <input type="hidden" name="existing_image" value="<?= htmlspecialchars($user_to_edit['image']) ?>">
                            <img src="<?= $target_dir . htmlspecialchars($user_to_edit['image']) ?>" alt="User Image" width="100" class="mt-2">
                            <?php endif; ?>
                        </div>
                        <button type="submit" class="btn btn-primary"><?= isset($user_to_edit) ? 'Update' : 'Add' ?> User</button>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-striped table-bordered zero-configuration">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Image</th>
                                    <th>Username</th>
                                    <th>Name</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?= $user['id'] ?></td>
                                    <td>
                                        <?php if (!empty($user['image'])): ?>
                                        <img src="<?= $target_dir . htmlspecialchars($user['image']) ?>" alt="User Image" width="50">
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($user['user_name']) ?></td>
                                    <td><?= htmlspecialchars($user['name']) ?></td>
                                    <td><?= ucfirst($user['role']) ?></td>
                                    <td><?= ucfirst($user['status']) ?></td>
                                    <td>
                                        <a href="users.php?action=edit_form&id=<?= $user['id'] ?>" class="btn btn-sm btn-info">Edit</a>
                                        <a href="users.php?action=delete&id=<?= $user['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
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
