<?php
require_once '../require/db.php';
session_start();

if (!isset($_SESSION['waiter_id']) || !isset($_SESSION['table_id'])) {
    header("Location: login.php");
    exit;
}

// Handle Remove from Cart
if (isset($_POST['remove_from_cart'])) {
    $product_id = $_POST['product_id'];
    unset($_SESSION['cart'][$product_id]);
    header("Location: cart.php");
    exit;
}

// Handle Update Quantity
if (isset($_POST['update_quantity'])) {
    $product_id = $_POST['product_id'];
    $quantity = $_POST['quantity'];
    if ($quantity > 0) {
        $_SESSION['cart'][$product_id]['quantity'] = $quantity;
    } else {
        unset($_SESSION['cart'][$product_id]);
    }
    header("Location: cart.php");
    exit;
}

// Handle Place Order
if (isset($_POST['place_order']) && !empty($_SESSION['cart'])) {
    $table_id = $_SESSION['table_id'];
    $waiter_id = $_SESSION['waiter_id'];
    $order_date = date('Y-m-d');
    $total_amount = 0;

    foreach ($_SESSION['cart'] as $item) {
        $total_amount += $item['price'] * $item['quantity'];
    }

    // Insert into orders table
    $stmt = $mysqli->prepare("INSERT INTO orders (table_id, user_id, order_date, status, kitchen_status, total_amount) VALUES (?, ?, ?, 'pending', 'pending', ?)");
    $stmt->bind_param("iisd", $table_id, $waiter_id, $order_date, $total_amount);
    $stmt->execute();
    $order_id = $stmt->insert_id;

    // Insert into order_items table
    $stmt = $mysqli->prepare("INSERT INTO order_items (order_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)");
    foreach ($_SESSION['cart'] as $product_id => $item) {
        $stmt->bind_param("iiid", $order_id, $product_id, $item['quantity'], $item['price']);
        $stmt->execute();
    }
    
    // Update table status to occupied
    $stmt = $mysqli->prepare("UPDATE tables SET status = 'occupied' WHERE id = ?");
    $stmt->bind_param("i", $table_id);
    $stmt->execute();

    // Clear the cart and redirect to the order details page
    unset($_SESSION['cart']);
    unset($_SESSION['table_id']);
    header("Location: order_details.php?order_id=$order_id");
    exit;
}


include 'layout/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Your Cart (Table: <?= $_SESSION['table_id'] ?>)</h4>
                    <a href="menu.php" class="btn btn-secondary mb-3">Back to Menu</a>
                    
                    <?php if (empty($_SESSION['cart'])): ?>
                        <p>Your cart is empty.</p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Total</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total = 0;
                                foreach ($_SESSION['cart'] as $id => $item): 
                                    $item_total = $item['price'] * $item['quantity'];
                                    $total += $item_total;
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['name']) ?></td>
                                    <td>$<?= number_format($item['price'], 2) ?></td>
                                    <td>
                                        <form action="cart.php" method="post" class="d-inline">
                                            <input type="hidden" name="product_id" value="<?= $id ?>">
                                            <input type="number" name="quantity" value="<?= $item['quantity'] ?>" min="1" class="form-control" style="width: 80px; display: inline-block;">
                                            <button type="submit" name="update_quantity" class="btn btn-sm btn-info">Update</button>
                                        </form>
                                    </td>
                                    <td>$<?= number_format($item_total, 2) ?></td>
                                    <td>
                                        <form action="cart.php" method="post" class="d-inline">
                                            <input type="hidden" name="product_id" value="<?= $id ?>">
                                            <button type="submit" name="remove_from_cart" class="btn btn-sm btn-danger">Remove</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="3" class="text-right">Total:</th>
                                    <th>$<?= number_format($total, 2) ?></th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <form action="cart.php" method="post">
                        <button type="submit" name="place_order" class="btn btn-lg btn-success float-right">Place Order</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'layout/footer.php'; ?>