<?php

// $user = json_decode($_COOKIE["user"], true);
session_start();
require_once '../require/common.php';
if (!isset($_SESSION['role'])) {
    header("location: $base_url");
    exit;
}
$user = [
    'id' => $_SESSION['id'] ?? null,
    'name' => $_SESSION['name'] ?? null,
    'role' => $_SESSION['role'] ?? null
];
if (!$user) {
    header("Location:../index.html?invalid=Please login first!");
} else {
    $url = $_SERVER['REQUEST_URI'];
    $arr = explode('/', $url);
    $code = 0;
    if ($arr[count($arr) - 2] !== "POS_Cafe") {
        $role_name = $arr[count($arr) - 2];
        switch ($role_name) {
            case 'admin':
                $code = "admin";
                break;
            case 'cashier':
                $code = "cashier";
                break;
            case 'kitchen':
                $code = "kitchen";
                break;
            case 'user':
                $code = "waiter";
                break;
            default:
                $code = 0;
                break;
        }
    }
    if ($code != $user['role']) {
        header("location:../401.html");
    }
}


if (isset($_POST["logout"])) {
    setcookie("user", "", -1, "/");
    header("location:../index.php");
}
