<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header(header: 'Location: login.php');
    exit;
}
