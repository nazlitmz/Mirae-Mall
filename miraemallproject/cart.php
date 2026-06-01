<?php
include "connect.php";
include "session.php";
if ($_SESSION["role"] != "customer") {
    header("Location: login.php");
}
$customer_id = $_SESSION["id"];
$sql = "SELECT cart.id, cart.payment_method,
               orders.id AS order_id, orders.status, orders.total, orders.tax, orders.order_date,
               order_line.amount, order_line.total_price,
               products.name AS product_name, products.price
        FROM cart
        JOIN orders     ON cart.order_id      = orders.id
        JOIN order_line ON cart.order_line_id = order_line.id
        JOIN products   ON order_line.product_id = products.id
        WHERE cart.customer_id = '$customer_id'
        ORDER BY orders.order_date DESC";

$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mirae - Cart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-dark bg-dark px-4">
    <span class="navbar-brand">Mirae Mall - My Cart</span>
    <div>
        <a href="home.php" class="btn btn-outline-light btn-sm me-2">Continue Shopping</a>
        <a href="logout.php" class="btn btn-danger btn-sm">Log Out</a>
    </div>
</nav>

<div class="container mt-4">
    <h3>My Cart</h3>
    <hr>

    <?php if (mysqli_num_rows($result) > 0): ?>

        <table class="table table-bordered table-hover">
            <thead class="table-dark">
                <tr>
                    <th>Order ID</th>
                    <th>Product</th>
                    <th>Unit Price</th>
                    <th>Amount</th>
                    <th>Subtotal</th>
                    <th>Tax (18%)</th>
                    <th>Total</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td>#<?php echo $row["order_id"]; ?></td>
                        <td><?php echo $row["product_name"]; ?></td>
                        <td>₺<?php echo $row["price"]; ?></td>
                        <td><?php echo $row["amount"]; ?></td>
                        <td>₺<?php echo $row["total_price"]; ?></td>
                        <td>₺<?php echo number_format($row["tax"], 2); ?></td>
                        <td class="fw-bold text-primary">₺<?php echo number_format($row["total"], 2); ?></td>
                        <td><?php echo ucfirst($row["payment_method"]); ?></td>
                        <td>
                            <?php
                            $status = $row["status"];
                            switch($status){
                                case "pending":
                                    $badge = "warning";
                                    break;
                                case "accepted":
                                    $badge = "primary";
                                    break;
                                case "delivered":
                                    $badge = "success";
                                    break;
                                case "cancelled":
                                    $badge = "danger";
                                    break;
                                default:
                                    $badge = "secondary";
                                    break;
                            }
                            ?>
                            <span class="badge bg-<?php echo $badge; ?>">
                                <?php echo ucfirst($status); ?>
                            </span>
                        </td>
                        <td><?php echo $row["order_date"]; ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

    <?php else: ?>
        <div class="alert alert-info">Your cart is empty. <a href="home.php">Continue shopping</a></div>
    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>