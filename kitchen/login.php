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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kitchen Login - POS Cafe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 400px;
            width: 100%;
        }
        .login-header {
            background: linear-gradient(135deg, #fd7e14 0%, #e55a00 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .login-body {
            padding: 2rem;
        }
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
        }
        .form-control:focus {
            border-color: #fd7e14;
            box-shadow: 0 0 0 0.2rem rgba(253, 126, 20, 0.25);
        }
        .btn-login {
            background: linear-gradient(135deg, #fd7e14 0%, #e55a00 100%);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            width: 100%;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(253, 126, 20, 0.3);
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <i class="fas fa-utensils fa-3x mb-3"></i>
            <h3>Kitchen Login</h3>
            <p class="mb-0">POS Cafe Kitchen Panel</p>
        </div>
        <div class="login-body">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            <form action="login.php" method="post">
                <div class="mb-3">
                    <label for="user_name" class="form-label">
                        <i class="fas fa-user"></i> Username
                    </label>
                    <input type="text" class="form-control" id="user_name" name="user_name" required>
                </div>
                <div class="mb-4">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-login btn-warning">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 