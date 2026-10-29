
<?php
session_start();
require_once __DIR__ . '/config/database.php';

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$error = '';
$success = false;
$orderId = null;

if (empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $error = 'Your cart is empty. Please add products first.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    $customerName = trim($_POST['customer_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if ($customerName === '' || $phone === '' || $address === '') {
        $error = 'Please fill in all fields.';
    } elseif (strlen($customerName) > 150 ||
              strlen($phone) > 20 || strlen($address) > 1000) {
        $error = 'One or more fields are too long.';
    } else {
        try {
            $pdo->beginTransaction();

            $total = 0;
            $orderItems = [];

            foreach ($_SESSION['cart'] as $id => $quantity) {
                $id = (int)$id;
                $quantity = (int)$quantity;

                if ($id < 1 || $quantity < 1) {
                    throw new Exception('Invalid cart item.');
                }

                $stmt = $pdo->prepare(
                    'SELECT id, NAME AS name, price, stock
                     FROM products WHERE id = ? FOR UPDATE'
                );
                $stmt->execute([$id]);
                $product = $stmt->fetch();

                if (!$product ||
                    (int)$product['stock'] < $quantity) {
                    throw new Exception(
                        'A product is unavailable or has insufficient stock. Please return to your cart.'
                    );
                }

                $subtotal = (float)$product['price'] * $quantity;
                $total += $subtotal;

                $orderItems[] = [
                    'id' => $id,
                    'quantity' => $quantity
                ];
            }

            if (!$orderItems) {
                throw new Exception('Your cart is empty.');
            }

            
$stmt = $pdo->prepare(
    'INSERT INTO orders
     (customer_name, customer_phone, customer_address, total, STATUS)
     VALUES (?, ?, ?, ?, ?)'
);

$stmt->execute([
    $customerName,
    $phone,
    $address,
    $total,
    'Pending'
]);

$orderId = (int)$pdo->lastInsertId();

// Save each purchased product for this order.
$itemStmt = $pdo->prepare(
    'INSERT INTO order_items
     (order_id, product_id, product_name, quantity, price, subtotal)
     VALUES (?, ?, ?, ?, ?, ?)'
);

foreach ($orderItems as $item) {
    $productId = (int)$item['id'];
    $quantity = (int)$item['quantity'];

    $productStmt = $pdo->prepare(
        'SELECT NAME AS name, price
         FROM products
         WHERE id = ?'
    );
    $productStmt->execute([$productId]);
    $product = $productStmt->fetch();

    if (!$product) {
        throw new Exception('Product not found.');
    }

    $price = (float)$product['price'];
    $subtotal = $price * $quantity;

    $itemStmt->execute([
        $orderId,
        $productId,
        $product['name'],
        $quantity,
        $price,
        $subtotal
    ]);
}


            // Reduce stock only after validating all items.
            foreach ($orderItems as $item) {
                $stmt = $pdo->prepare(
                    'UPDATE products
                     SET stock = stock - ?
                     WHERE id = ? AND stock >= ?'
                );
                $stmt->execute([
                    $item['quantity'],
                    $item['id'],
                    $item['quantity']
                ]);

                if ($stmt->rowCount() !== 1) {
                    throw new Exception(
                        'Stock changed while placing the order. Please try again.'
                    );
                }
            }

            $pdo->commit();

            $_SESSION['cart'] = [];
            $success = true;
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log($ex->getMessage());
            $error = $ex instanceof Exception
                ? $ex->getMessage()
                : 'Unable to place the order. Please check your database setup.';
        }
    }
}

// Calculate the preview total from current database prices.
$cartTotal = 0;
$cartProducts = [];

if (!$success && !empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $id => $quantity) {
        $id = (int)$id;
        $quantity = (int)$quantity;

        if ($id < 1 || $quantity < 1) {
            continue;
        }

        $stmt = $pdo->prepare(
            'SELECT id, NAME AS name, price, stock
             FROM products WHERE id = ?'
        );
        $stmt->execute([$id]);
        $product = $stmt->fetch();

        if ($product) {
            $product['quantity'] = $quantity;
            $product['subtotal'] = (float)$product['price'] * $quantity;
            $cartProducts[] = $product;
            $cartTotal += $product['subtotal'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Checkout | BarOdor</title>
<style>
* { box-sizing: border-box; }
body { margin: 0; font-family: Arial, sans-serif; background: #f8fafc; color: #242424; }
header { padding: 18px 6%; background: #172033; color: white; }
header a { color: white; text-decoration: none; }
main { max-width: 850px; margin: 35px auto; padding: 0 20px; }
.panel { background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 25px; margin-bottom: 20px; }
label { display: block; font-weight: bold; margin-top: 16px; }
input, textarea { width: 100%; padding: 12px; margin-top: 7px; border: 1px solid #cbd5e1; border-radius: 6px; font: inherit; }
textarea { min-height: 100px; resize: vertical; }
button { width: 100%; padding: 14px; margin-top: 20px; border: 0; border-radius: 7px; background: #2563eb; color: white; font-size: 16px; cursor: pointer; }
.error { padding: 12px; background: #fee2e2; color: #991b1b; border-radius: 6px; }
.success { padding: 18px; background: #dcfce7; color: #166534; border-radius: 8px; }
.line { display: flex; justify-content: space-between; gap: 15px; margin: 12px 0; }
.total { border-top: 1px solid #e2e8f0; padding-top: 15px; font-size: 20px; font-weight: bold; }
</style>
</head>
<body>
<header>
    <strong>MyEcommerce</strong>
    <span style="float:right"><a href="cart.php">Back to Cart</a></span>
</header>

<main>
    <h1>Checkout</h1>

    <?php if ($success): ?>
        <div class="success">
            <h2>Order placed successfully!</h2>
            <p>Your order number is #<?= (int)$orderId ?>.</p>
            <p>Order status: Pending</p>
            <a href="index.php">Continue shopping</a>
        </div>

    <?php elseif ($error !== '' && empty($cartProducts)): ?>
        <div class="panel">
            <p class="error"><?= e($error) ?></p>
            <p><a href="index.php">Return to shopping</a></p>
        </div>

    <?php else: ?>
        <?php if ($error !== ''): ?>
            <p class="error"><?= e($error) ?></p>
        <?php endif; ?>

        <div class="panel">
            <h2>Order Summary</h2>

            <?php foreach ($cartProducts as $item): ?>
                <div class="line">
                    <span>
                        <?= e($item['name']) ?> ×
                        <?= (int)$item['quantity'] ?>
                    </span>
                    <span>₹<?= number_format($item['subtotal'], 2) ?></span>
                </div>
            <?php endforeach; ?>

            <div class="line total">
                <span>Total</span>
                <span>₹<?= number_format($cartTotal, 2) ?></span>
            </div>
        </div>

        <form method="POST" class="panel">
            <h2>Delivery Details</h2>

            <label for="customer_name">Full Name</label>
            <input id="customer_name" name="customer_name"
                   maxlength="150" required>

            <label for="phone">Phone Number</label>
            <input id="phone" name="phone" type="tel"
                   maxlength="20" required>

            <label for="address">Delivery Address</label>
            <textarea id="address" name="address"
                      maxlength="1000" required></textarea>

            <button type="submit">Place Order — ₹<?= number_format($cartTotal, 2) ?></button>
            <p>Payment method: Cash on Delivery (demo checkout)</p>
        </form>
    <?php endif; ?>
</main>
</body>
</html>
