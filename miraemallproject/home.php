<?php
include "connect.php";
include "session.php";
if ($_SESSION["role"] != "customer") {
    header("Location: login.php");
}
if (isset($_POST["add_to_cart"])) {
    $product_id = $_POST["product_id"];
    $amount     = $_POST["amount"];
    $customer_id = $_SESSION["id"];
    $sql    = "SELECT * FROM products WHERE id = '$product_id'";
    $result = mysqli_query($conn, $sql);
    $product = mysqli_fetch_assoc($result);
    $total_price = $product["price"] * $amount;
    $tax         = $total_price * 0.18;
    $grand_total = $total_price + $tax;
    $sql = "INSERT INTO orders (status, customer_id, total, tax)
            VALUES ('pending', '$customer_id', '$grand_total', '$tax')";
    mysqli_query($conn, $sql);
    $order_id = mysqli_insert_id($conn);
    $sql = "INSERT INTO order_line (product_id, order_id, amount, total_price)
            VALUES ('$product_id', '$order_id', '$amount', '$total_price')";
    mysqli_query($conn, $sql);
    $order_line_id = mysqli_insert_id($conn);
    $sql = "INSERT INTO cart (order_id, order_line_id, customer_id, payment_method)
            VALUES ('$order_id', '$order_line_id', '$customer_id', 'cash')";
    mysqli_query($conn, $sql);
    $msg = "Product added to cart!";
}
$sql   = "SELECT * FROM shops";
$shops = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mirae - Home</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-dark bg-dark px-4">
    <span class="navbar-brand">Mirae Mall</span>
    <div>
        <span class="text-white me-3">Welcome, <?php echo $_SESSION["name"]; ?></span>
        <a href="cart.php" class="btn btn-outline-light btn-sm me-2">Cart</a>
        <a href="logout.php" class="btn btn-danger btn-sm">Log Out</a>
    </div>
</nav>

<div class="container mt-4">

    <?php if (isset($msg)): ?>
        <div class="alert alert-msg"><?php echo $msg; ?></div>
    <?php endif; ?>

    <h3>Available Shops</h3>
    <hr>

    <?php while ($shop = mysqli_fetch_assoc($shops)): ?>

        <h4 class="mt-4"><?php echo $shop["name"]; ?>
            <small class="text-muted fs-6"><?php echo $shop["type"]; ?> - <?php echo $shop["location"]; ?></small>
        </h4>

        <div class="row">
            <?php
            $shop_id = $shop["id"];
            $sql     = "SELECT * FROM products WHERE shop_id = '$shop_id'";
            $products = mysqli_query($conn, $sql);

            while ($product = mysqli_fetch_assoc($products)):
            ?>
                <div class="col-md-3 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo $product["name"]; ?></h5>
                            <p class="card-text"><?php echo $product["description"]; ?></p>
                            <p class="fw-bold text-primary">₺<?php echo $product["price"]; ?></p>
                            <p class="text-muted small">Stock: <?php echo $product["quantity"]; ?></p>
                        </div>
                        <div class="card-footer">
                            <form action="home.php" method="post">
                                <input type="hidden" name="product_id" value="<?php echo $product["id"]; ?>">
                                <div class="d-flex gap-2">
                                    <input type="number" name="amount" value="1" min="1"
                                           max="<?php echo $product["quantity"]; ?>"
                                           class="form-control form-control-sm" style="width:70px;">
                                    <button type="submit" name="add_to_cart" class="btn btn-primary btn-sm">
                                        Add to Cart
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

            <?php endwhile; ?>
        </div>

    <?php endwhile; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>