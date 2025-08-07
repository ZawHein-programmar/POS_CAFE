<?php
require_once '../auth/isLogin.php';
include 'layout/header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card mt-5">
                <div class="card-body text-center">
                    <h1 class="card-title text-success">Order Placed Successfully!</h1>
                    <p class="card-text">The order has been sent to the kitchen.</p>
                    <a href="index.php" class="btn btn-primary">Back to Tables Dashboard</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
include 'layout/footer.php'; 
?>