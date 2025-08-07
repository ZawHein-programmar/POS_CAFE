<?php
require_once("../auth/isLogin.php");
require '../require/db.php';
require '../require/common_function.php';

require './layouts/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Admin Dashboard</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Total Users</h5>
                                        <h3 class="mb-0">
                                            <?php
                                            $stmt = $mysqli->prepare("SELECT COUNT(*) as count FROM user");
                                            $stmt->execute();
                                            $result = $stmt->get_result();
                                            echo $result->fetch_assoc()['count'];
                                            ?>
                                        </h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-users fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Total Products</h5>
                                        <h3 class="mb-0">
                                            <?php
                                            $stmt = $mysqli->prepare("SELECT COUNT(*) as count FROM products");
                                            $stmt->execute();
                                            $result = $stmt->get_result();
                                            echo $result->fetch_assoc()['count'];
                                            ?>
                                        </h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-box fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Total Orders</h5>
                                        <h3 class="mb-0">
                                            <?php
                                            $stmt = $mysqli->prepare("SELECT COUNT(*) as count FROM orders");
                                            $stmt->execute();
                                            $result = $stmt->get_result();
                                            echo $result->fetch_assoc()['count'];
                                            ?>
                                        </h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-shopping-cart fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Total Tables</h5>
                                        <h3 class="mb-0">
                                            <?php
                                            $stmt = $mysqli->prepare("SELECT COUNT(*) as count FROM tables");
                                            $stmt->execute();
                                            $result = $stmt->get_result();
                                            echo $result->fetch_assoc()['count'];
                                            ?>
                                        </h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-table fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require './layouts/footer.php';
?>