<?php require_once("./auth/isLogin.php");?>
<?php
if ($user['role'] == "admin") {
    header("location:./admin/index.php");
} elseif ($user['role'] == "cashier") {
    header("location:./cashier/indexs.php");
} elseif ($user['role'] ==  "kitchen") {
    header("location:./kitchen/index.php");
} else {
    header("location:./user/index.php");
}
