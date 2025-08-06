<?php
require_once '../require/db.php';
session_start();

if (isset($_POST['user_name'])) {
    $user_name = $_POST['user_name'];
    $password = $_POST['password'];

    $stmt = $mysqli->prepare("SELECT id, user_name, password, name, role FROM user WHERE user_name = ? AND role = 'kitchen'");
    $stmt->bind_param("s", $user_name);
    $stmt->execute();
    $result = $stmt->get_result();
    $kitchen_staff = $result->fetch_assoc();

    if ($kitchen_staff && password_verify($password, $kitchen_staff['password'])) {
        $_SESSION['kitchen_id'] = $kitchen_staff['id'];
        $_SESSION['kitchen_name'] = $kitchen_staff['name'];
        $_SESSION['role'] = $kitchen_staff['role'];
        $_SESSION['id'] = $kitchen_staff['id'];
        $_SESSION['name'] = $kitchen_staff['name'];
        header("Location: index.php");
        exit;
    } else {
        $error = "Invalid username or password.";
    }
}

include 'layout/header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card mt-5">
                <div class="card-body">
                    <h3 class="card-title text-center">Kitchen Staff Login</h3>
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    <form action="login.php" method="post">
                        <div class="form-group">
                            <label for="user_name">Username</label>
                            <input type="text" name="user_name" id="user_name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" name="password" id="password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">Login</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'layout/footer.php'; ?> 