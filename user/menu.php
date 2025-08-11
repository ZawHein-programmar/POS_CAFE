<?php
require_once '../auth/isLogin.php';
require_once '../require/db.php';

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
    // Preserve table_id and any pagination params on redirect
    $redirectQuery = $_GET;
    $redirectQuery['table_id'] = $_SESSION['table_id'];
    header('Location: menu.php?' . http_build_query($redirectQuery));
    exit;
}


// Fetch categories and products (build structure with second category ids)
$main_categories_result = $mysqli->query("SELECT id, name FROM main_categories ORDER BY name ASC");
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

    $second_cat_entries = [];
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
            $second_cat_entries[] = [
                'id' => $sc['id'],
                'name' => $sc['name'],
                'products' => $products,
            ];
        }
    }
    if (!empty($second_cat_entries)) {
        $products_by_category[] = [
            'name' => $mc['name'],
            'second_categories' => $second_cat_entries,
        ];
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
                        <?php foreach ($products_by_category as $mainCat): ?>
                            <?php $main_cat_name = $mainCat['name']; ?>
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0" data-toggle="collapse" data-target="#collapse-<?= str_replace(' ', '', $main_cat_name) ?>" aria-expanded="true" aria-controls="collapse-<?= str_replace(' ', '', $main_cat_name) ?>">
                                        <i class="fa" aria-hidden="true"></i> <?= htmlspecialchars($main_cat_name) ?>
                                    </h5>
                                </div>
                                <div id="collapse-<?= str_replace(' ', '', $main_cat_name) ?>" class="collapse show" data-parent="#accordion-one">
                                    <div class="card-body">
                                        <?php foreach ($mainCat['second_categories'] as $secCat): ?>
                                            <?php
                                                $sec_cat_id = (int)$secCat['id'];
                                                $sec_cat_name = $secCat['name'];
                                                $products = $secCat['products'];
                                                $perPage = 3;
                                                $pageParam = 'page_sc_' . $sec_cat_id;
                                                $currentPage = isset($_GET[$pageParam]) ? max(1, (int)$_GET[$pageParam]) : 1;
                                                $totalProducts = count($products);
                                                $totalPages = (int)ceil($totalProducts / $perPage);
                                                $offset = ($currentPage - 1) * $perPage;
                                                $pagedProducts = array_slice($products, $offset, $perPage);

                                                // Build base query for links (preserve other params and table_id)
                                                $baseQuery = $_GET;
                                                $baseQuery['table_id'] = $_SESSION['table_id'];
                                            ?>
                                            <h5><?= htmlspecialchars($sec_cat_name) ?></h5>
                                            <div class="row">
                                                <?php foreach ($pagedProducts as $product): ?>
                                                    <div class="col-lg-4 col-md-4 col-sm-6 col-12 mb-3">
                                                        <div class="card h-100">
                                                            <?php if (!empty($product['images'])): ?>
                                                                <img class="card-img-top" src="../img/<?= htmlspecialchars($product['images']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" style="height: 160px; width: 100%; object-fit: cover; border-radius: 8px;">
                                                            <?php endif; ?>
                                                            <div class="card-body">
                                                                <h6 class="card-title mb-2"><?= htmlspecialchars($product['name']) ?></h6>
                                                                <?php if ($product['discounted_price'] !== null): ?>
                                                                    <p class="card-text mb-2">
                                                                        <span style="text-decoration: line-through; color: #888;">
                                                                            $<?= number_format($product['original_price'], 2) ?>
                                                                        </span>
                                                                        <span style="color: #d9534f; font-weight: bold;">
                                                                            $<?= number_format($product['discounted_price'], 2) ?>
                                                                        </span>
                                                                        <span class="badge badge-success"><?= $product['discount_percent'] ?>% OFF</span>
                                                                    </p>
                                                                <?php else: ?>
                                                                    <p class="card-text mb-2">$<?= number_format($product['original_price'], 2) ?></p>
                                                                <?php endif; ?>
                                                                <form action="menu.php" method="post">
                                                                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                                                    <input type="hidden" name="product_name" value="<?= htmlspecialchars($product['name']) ?>">
                                                                    <input type="hidden" name="product_price" value="<?= $product['discounted_price'] !== null ? $product['discounted_price'] : $product['original_price'] ?>">
                                                                    <button type="submit" name="add_to_cart" class="btn btn-success btn-sm">Add to Cart</button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>

                                            <?php if ($totalPages > 1): ?>
                                                <nav aria-label="Page navigation" class="mb-4">
                                                    <ul class="pagination pagination-sm">
                                                        <?php
                                                            // Prev
                                                            $prevQuery = $baseQuery;
                                                            $prevQuery[$pageParam] = max(1, $currentPage - 1);
                                                            $nextQuery = $baseQuery;
                                                            $nextQuery[$pageParam] = min($totalPages, $currentPage + 1);
                                                        ?>
                                                        <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                                                            <a class="page-link" href="menu.php?<?= http_build_query($prevQuery) ?>">Prev</a>
                                                        </li>
                                                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                                            <?php $pageQuery = $baseQuery; $pageQuery[$pageParam] = $i; ?>
                                                            <li class="page-item <?= $i == $currentPage ? 'active' : '' ?>">
                                                                <a class="page-link" href="menu.php?<?= http_build_query($pageQuery) ?>"><?= $i ?></a>
                                                            </li>
                                                        <?php endfor; ?>
                                                        <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                                                            <a class="page-link" href="menu.php?<?= http_build_query($nextQuery) ?>">Next</a>
                                                        </li>
                                                    </ul>
                                                </nav>
                                            <?php endif; ?>

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