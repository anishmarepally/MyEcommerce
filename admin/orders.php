
<?php
session_start();

require_once __DIR__ . '/../config/database.php';

/*
 * IMPORTANT:
 * Keep the same admin login/session check that is already
 * present at the top of your current orders.php file.
 */

function escape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $status = $_POST['status'] ?? '';

    $allowedStatuses = [
        'Pending',
        'Processing',
        'Shipped',
        'Completed',
        'Cancelled'
    ];

    if ($orderId && in_array($status, $allowedStatuses, true)) {
        $stmt = $pdo->prepare(
            'UPDATE orders SET STATUS = :status WHERE id = :id'
        );

        $stmt->execute([
            ':status' => $status,
            ':id' => $orderId
        ]);

        $message = 'Order status updated successfully.';
    } else {
        $message = 'Invalid order or status.';
    }
}

$stmt = $pdo->query(
    'SELECT
        id,
        customer_name,
        total,
        STATUS AS status,
        created_at
     FROM orders
     ORDER BY id DESC'
);

$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 25px;
            background: #f5f5f5;
        }

        h1 {
            margin-bottom: 20px;
        }

        .message {
            padding: 10px;
            margin-bottom: 15px;
            background: #e2f5e8;
            border: 1px solid #a5d6b2;
        }

        .table-container {
            overflow-x: auto;
            background: white;
            padding: 15px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        th, td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
        }

        th {
            background: #222;
            color: white;
        }

        select, button {
            padding: 7px;
        }

        button {
            cursor: pointer;
        }

        .details-link {
            display: inline-block;
            padding: 7px 10px;
            background: #1769aa;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }

        .details-link:hover {
            background: #104e80;
        }
    </style>
</head>

<body>

<h1>Manage Orders</h1>

<p>
    <a href="dashboard.php">Back to Admin Dashboard</a>
</p>

<?php if ($message !== ''): ?>
    <p class="message"><?= escape($message) ?></p>
<?php endif; ?>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Total</th>
                <th>Status</th>
                <th>Order Date</th>
                <th>Update Status</th>
                <th>Details</th>
            </tr>
        </thead>

        <tbody>
        <?php if (!$orders): ?>
            <tr>
                <td colspan="7">No orders found yet.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <tr>
                    <td><?= escape($order['id']) ?></td>

                    <td><?= escape($order['customer_name']) ?></td>

                    <td>
                        ₹<?= number_format((float) $order['total'], 2) ?>
                    </td>

                    <td><?= escape($order['status']) ?></td>

                    <td><?= escape($order['created_at']) ?></td>

                    <td>
                        <form method="POST" class="status-form">
                            <input
                                type="hidden"
                                name="id"
                                value="<?= (int) $order['id'] ?>"
                            >

                            <select name="status">
                                <?php foreach (
                                    ['Pending', 'Processing', 'Shipped', 'Completed', 'Cancelled']
                                    as $option
                                ): ?>
                                    <option
                                        value="<?= escape($option) ?>"
                                        <?= strtoupper($order['status']) === strtoupper($option)
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        <?= escape($option) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <button type="submit">Update</button>
                        </form>
                    </td>

                    <td>
                        <a
                            class="details-link"
                            href="order_details.php?id=<?= (int) $order['id'] ?>"
                        >
                            View Details
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>
