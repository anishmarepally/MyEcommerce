
<?php
session_start();
require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $productId = (int)($_POST['product_id'] ?? 0);

    if ($action === 'add' && $productId > 0) {
        $stmt = $pdo->prepare(
            'SELECT id, stock FROM products WHERE id = ?'
        );
        $stmt->execute([$productId]);
        $product = $stmt->fetch();

        if ($product && (int)$product['stock'] > 0) {
            $currentQty = (int)($_SESSION['cart'][$productId] ?? 0);

            if ($currentQty < (int)$product['stock']) {
                $_SESSION['cart'][$productId] = $currentQty + 1;
                $message = 'Product added to cart.';
            } else {
                $message = 'You have reached the available stock limit.';
            }
        } else {
            $message = 'Product is unavailable.';
        }
    }

    if ($action === 'update' && $productId > 0) {
        $quantity = (int)($_POST['quantity'] ?? 1);

        $stmt = $pdo->prepare(
            'SELECT stock FROM products WHERE id = ?'
        );
        $stmt->execute([$productId]);
        $product = $stmt->fetch();

        if (!$product) {
            unset($_SESSION['cart'][$productId]);
            $message = 'Product is unavailable.';
        } elseif ($quantity <= 0) {
            unset($_SESSION['cart'][$productId]);
            $message = 'Product removed from cart.';
        } elseif ($quantity <= (int)$product['stock']) {
            $_SESSION['cart'][$productId] = $quantity;
            $message = 'Cart updated.';
        } else {
            $message = 'Requested quantity exceeds available stock.';
        }
    }

    if ($action === 'remove' && $productId > 0) {
        unset($_SESSION['cart'][$productId]);
        $message = 'Product removed from cart.';
    }

    header('Location: cart.php?message=' . urlencode($message));
    exit;
}

$message = $_GET['message'] ?? '';

$cart = $_SESSION['cart'];
$products = [];
$total = 0;

if (!empty($cart)) {
    $ids = array_map('intval', array_keys($cart));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $stmt = $pdo->prepare(
        "SELECT id, NAME AS name, price, stock
         FROM products WHERE id IN ($placeholders)"
    );
    $stmt->execute($ids);
    $products = $stmt->fetchAll();
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Shopping Cart | BarOdor</title>
<style>
* { box-sizing: border-box; }
body { margin: 0; font-family: Arial, sans-serif; background: #f8fafc; color: #172033; }
header { background: #242424; color: white; padding: 18px 6%; }
header a { color: white; text-decoration: none; margin-right: 20px; }
main { max-width: 1000px; margin: 35px auto; padding: 0 20px; }
.cart-item { background: white; border: 1px solid #e2e8f0; border-radius: 10px; padding: 20px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; gap: 20px; flex-wrap: wrap; }
input[type=number] { width: 75px; padding: 9px; }
button, .checkout { border: 0; border-radius: 6px; padding: 10px 15px; cursor: pointer; text-decoration: none; display: inline-block; }
.update { background: #2563eb; color: white; }
.remove { background: #fee2e2; color: #991b1b; }
.checkout { background: #15803d; color: white; margin-top: 10px; }
.total { text-align: right; background: white; padding: 20px; border-radius: 10px; }
.message { background: #dbeafe; padding: 12px; border-radius: 6px; }
.empty { background: white; padding: 25px; border-radius: 10px; }
@media(max-width: 600px) { .cart-item { align-items: flex-start; } .total { text-align: left; } }
</style>
</head>
<body>
<header>
    <h2>BarOdor - Shopping Cart</h2>
    <a href="index.php">Continue Shopping</a>
    <a href="cart.php">Cart</a>
</header>

<main>
    <h1>Your Shopping Cart</h1>

    <?php if ($message !== ''): ?>
        <p class="message"><?= e($message) ?></p>
    <?php endif; ?>

    <?php if (empty($products)): ?>
        <div class="empty">
            <p>Your cart is empty.</p>
            <a href="index.php">Browse Products</a>
        </div>
    <?php else: ?>
        <?php foreach ($products as $product): ?>
            <?php
            $id = (int)$product['id'];
            $quantity = (int)($_SESSION['cart'][$id] ?? 0);
            $subtotal = (float)$product['price'] * $quantity;
            $total += $subtotal;
            ?>
            <div class="cart-item">
                <div>
                    <h3><?= e($product['name']) ?></h3>
                    <p>Price: ₹<?= number_format((float)$product['price'], 2) ?></p>
                    <p>Subtotal: ₹<?= number_format($subtotal, 2) ?></p>
                    <p>Available stock: <?= (int)$product['stock'] ?></p>
                </div>

                <div>
                    <form method="POST" action="cart.php" style="margin-bottom:10px;">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="product_id" value="<?= $id ?>">
                        <label>
                            Quantity:
                            <input type="number" name="quantity"
                                   min="0" max="<?= (int)$product['stock'] ?>"
                                   value="<?= $quantity ?>" required>
                        </label>
                        <button class="update" type="submit">Update</button>
                    </form>

                    <form method="POST" action="cart.php">
                        <input type="hidden" name="action" value="remove">
                        <input type="hidden" name="product_id" value="<?= $id ?>">
                        <button class="remove" type="submit">Remove</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="total">
            <h2>Total: ₹<?= number_format($total, 2) ?></h2>
            <p>Delivery charges, if any, will be shown at checkout.</p>
            <a class="checkout" href="checkout.php">Proceed to Checkout</a>
        </div>
    <?php endif; ?>
</main>
</body>
</html>
