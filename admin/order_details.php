
<?php
session_start();

require_once __DIR__ . '/../config/database.php';

// Protect this page: only logged-in admins may view orders.
// Adjust the session key if your existing admin login uses a different one.
if (empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

function escape($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$orderId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$orderId || $orderId < 1) {
    http_response_code(400);
    exit('Invalid order ID.');
}

// Fetch the order and customer details.
$stmt = $pdo->prepare(
    'SELECT id, customer_name, customer_phone, customer_address,
            total, STATUS AS status, created_at
     FROM orders
     WHERE id = ?'
);
$stmt->execute([$orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    http_response_code(404);
    exit('Order not found.');
}

// Fetch the items belonging to this order.
$stmt = $pdo->prepare(
    'SELECT product_id, product_name, quantity, price, subtotal
     FROM order_items
     WHERE order_id = ?
     ORDER BY id ASC'
);
$stmt->execute([$orderId]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Order Details #<?= (int)$order['id'] ?></title>

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            padding: 25px;
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            color: #172033;
        }

        main {
            max-width: 1000px;
            margin: auto;
        }

        .panel {
            background: white;
            padding: 22px;
            margin-bottom: 20px;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
        }

        .details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
        }

        .label {
            color: #64748b;
            font-size: 14px;
        }

        .value {
            margin-top: 7px;
            overflow-wrap: anywhere;
        }

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
        }

        th {
            background: #172033;
            color: white;
        }

        .total {
            text-align: right;
            font-size: 22px;
            font-weight: bold;
        }

        .back {
            display: inline-block;
            margin-bottom: 20px;
            color: #2563eb;
            text-decoration: none;
        }

        .empty {
            padding: 15px;
            background: #fff7ed;
            border-radius: 6px;
        }

        @media (max-width: 600px) {
            body { padding: 12px; }
            .panel { padding: 15px; }
        }
    </style>
</head>

<body>
<main>
    <a class="back" href="orders.php">&larr; Back to Manage Orders</a>

    <h1>Order #<?= (int)$order['id'] ?></h1>

    <section class="panel">
        <h2>Customer Details</h2>

        <div class="details">
            <div>
                <div class="label">Customer Name</div>
                <div class="value"><?= escape($order['customer_name']) ?></div>
            </div>

            <div>
                <div class="label">Phone Number</div>
                <div class="value">
                    <?= escape($order['customer_phone'] ?: 'Not provided') ?>
                </div>
            </div>

            <div>
                <div class="label">Order Status</div>
                <div class="value"><?= escape($order['status']) ?></div>
            </div>

            <div>
                <div class="label">Order Date</div>
                <div class="value"><?= escape($order['created_at']) ?></div>
            </div>

            <div>
                <div class="label">Delivery Address</div>
                <div class="value">
                    <?= nl2br(escape($order['customer_address'] ?: 'Not provided')) ?>
                </div>
            </div>
        </div>
    </section>

    <section class="panel">
        <h2>Purchased Products</h2>

        <?php if (!$items): ?>
            <p class="empty">
                No product details were recorded for this order.
                Older orders may not have item records.
            </p>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?= escape($item['product_name']) ?></td>
                            <td><?= (int)$item['quantity'] ?></td>
                            <td>₹<?= number_format((float)$item['price'], 2) ?></td>
                            <td>₹<?= number_format((float)$item['subtotal'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <p class="total">
            Order Total: ₹<?= number_format((float)$order['total'], 2) ?>
        </p>
    </section>

    <a class="back" href="orders.php">Back to Manage Orders</a>
</main>
</body>
</html>
