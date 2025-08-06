<?php
require_once '../require/db.php';
session_start();

if (!isset($_SESSION['waiter_id'])) {
    header("Location: login.php");
    exit;
}

// Set table_id for the order
if (isset($_GET['table_id'])) {
    $_SESSION['table_id'] = $_GET['table_id'];
}

// If no table is selected, redirect to dashboard
if (!isset($_SESSION['table_id'])) {
    header("Location: index.php");
    exit;
}


// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle Add to Cart
if (isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];
    $product_name = $_POST['product_name'];
    $product_price = $_POST['product_price'];

    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id]['quantity']++;
    } else {
        $_SESSION['cart'][$product_id] = [
            'name' => $product_name,
            'price' => $product_price,
            'quantity' => 1
        ];
    }
    header("Location: menu.php");
    exit;
}


// Fetch categories and products
$main_categories_result = $mysqli->query("SELECT * FROM main_categories ORDER BY name ASC");
$main_categories = $main_categories_result->fetch_all(MYSQLI_ASSOC);

$products_by_category = [];
foreach ($main_categories as $mc) {
    $stmt = $mysqli->prepare("
        SELECT sc.id, sc.name 
        FROM second_categories sc 
        WHERE sc.main_categories_id = ? 
        ORDER BY sc.name ASC
    ");
    $stmt->bind_param("i", $mc['id']);
    $stmt->execute();
    $second_categories_result = $stmt->get_result();
    $second_categories = $second_categories_result->fetch_all(MYSQLI_ASSOC);

    $products_by_second_category = [];
    foreach ($second_categories as $sc) {
        $stmt = $mysqli->prepare("
            SELECT p.id, p.name, p.original_price, p.images,
                d.percent AS discount_percent,
                d.start_date, d.end_date
            FROM products p
            LEFT JOIN discounts d ON d.product_id = p.id
                AND d.start_date <= CURDATE() AND d.end_date >= CURDATE()
            WHERE p.second_categories_id = ? AND p.status = 'active'
            ORDER BY p.name ASC
        ");
        $stmt->bind_param("i", $sc['id']);
        $stmt->execute();
        $products_result = $stmt->get_result();
        $products = [];
        while ($row = $products_result->fetch_assoc()) {
            if ($row['discount_percent'] !== null) {
                $row['discounted_price'] = round($row['original_price'] * (1 - $row['discount_percent'] / 100), 2);
            } else {
                $row['discounted_price'] = null;
            }
            $products[] = $row;
        }
        if (!empty($products)) {
            $products_by_second_category[$sc['name']] = $products;
        }
    }
    if (!empty($products_by_second_category)) {
        $products_by_category[$mc['name']] = $products_by_second_category;
    }
}


include 'layout/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="card-title">Menu (Table: <?= $_SESSION['table_id'] ?>)</h4>
                        <a href="cart.php" class="btn btn-primary">View Cart (<?= count($_SESSION['cart']) ?>)</a>
                    </div>
                    <hr>

                    <div id="accordion-one" class="accordion">
                        <?php foreach ($products_by_category as $main_cat_name => $second_categories): ?>
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0" data-toggle="collapse" data-target="#collapse-<?= str_replace(' ', '', $main_cat_name) ?>" aria-expanded="true" aria-controls="collapse-<?= str_replace(' ', '', $main_cat_name) ?>">
                                        <i class="fa" aria-hidden="true"></i> <?= htmlspecialchars($main_cat_name) ?>
                                    </h5>
                                </div>
                                <div id="collapse-<?= str_replace(' ', '', $main_cat_name) ?>" class="collapse show" data-parent="#accordion-one">
                                    <div class="card-body">
                                        <?php foreach ($second_categories as $sec_cat_name => $products): ?>
                                            <h5><?= htmlspecialchars($sec_cat_name) ?></h5>
                                            <div class="row">
                                                <?php foreach ($products as $product): ?>
                                                    <div class="col-md-3 col-sm-6">
                                                        <div class="card">
                                                            <?php if (!empty($product['images'])): ?>
                                                                <img class="card-img-top" src="../img/<?= htmlspecialchars($product['images']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" style="height: 300px; width: 100%; object-fit: cover; border-radius: 8px;">
                                                            <?php endif; ?>
                                                            <div class="card-body">
                                                                <h5 class="card-title"><?= htmlspecialchars($product['name']) ?></h5>
                                                                <?php if ($product['discounted_price'] !== null): ?>
                                                                    <p class="card-text">
                                                                        <span style="text-decoration: line-through; color: #888;">
                                                                            $<?= number_format($product['original_price'], 2) ?>
                                                                        </span>
                                                                        <span style="color: #d9534f; font-weight: bold;">
                                                                            $<?= number_format($product['discounted_price'], 2) ?>
                                                                        </span>
                                                                        <span class="badge badge-success"><?= $product['discount_percent'] ?>% OFF</span>
                                                                    </p>
                                                                <?php else: ?>
                                                                    <p class="card-text">$<?= number_format($product['original_price'], 2) ?></p>
                                                                <?php endif; ?>
                                                                <form action="menu.php" method="post">
                                                                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                                                    <input type="hidden" name="product_name" value="<?= htmlspecialchars($product['name']) ?>">
                                                                    <input type="hidden" name="product_price" value="<?= $product['discounted_price'] !== null ? $product['discounted_price'] : $product['original_price'] ?>">
                                                                    <button type="submit" name="add_to_cart" class="btn btn-success">Add to Cart</button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <hr>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'layout/footer.php'; ?>