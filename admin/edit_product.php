
<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    exit('Invalid product ID.');
}

$stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
$stmt->execute([$id]);
$product = $stmt->fetch();


if (!$product) {
    exit('Product not found.');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $price = filter_var($_POST['price'] ?? '', FILTER_VALIDATE_FLOAT);
    $stock = filter_var($_POST['stock'] ?? '', FILTER_VALIDATE_INT);

    if (
        $name === '' || strlen($name) > 150 ||
        $price === false || $price < 0 ||
        $stock === false || $stock < 0
    ) {
        $error = 'Please enter valid details.';
    } else {
        $stmt = $pdo->prepare(
            'UPDATE products SET name = ?, price = ?, stock = ? WHERE id = ?'
        );
        $stmt->execute([$name, $price, $stock, $id]);

        header('Location: products.php?success=updated');
        exit;
    }

    $product['NAME'] = $name;
    $product['price'] = $_POST['price'];
    $product['stock'] = $_POST['stock'];
}

function escape($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Product</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            padding: 30px;
        }
        .box {
            max-width: 450px;
            margin: 30px auto;
            padding: 25px;
            background: white;
            border-radius: 10px;
        }
        label { display: block; margin-top: 16px; }
        input {
            box-sizing: border-box;
            width: 100%;
            padding: 12px;
            margin-top: 6px;
        }
        button {
            margin-top: 22px;
            padding: 12px 20px;
            background: #2563eb;
            color: white;
            border: none;
            cursor: pointer;
        }
        .error { color: red; }
    </style>
</head>
<body>
<div class="box">
    <h1>Edit Product</h1>

    <?php if ($error !== ''): ?>
        <p class="error"><?= escape($error) ?></p>
    <?php endif; ?>

    <form method="POST">
        <label for="name">Product Name</label>
        <input id="name" name="name" maxlength="150"
               value="<?= escape($product['NAME']) ?>" required>

        <label for="price">Price (₹)</label>
        <input id="price" type="number" name="price"
               min="0" step="0.01"
               value="<?= escape($product['price']) ?>" required>

        <label for="stock">Stock Quantity</label>
        <input id="stock" type="number" name="stock"
               min="0" step="1"
               value="<?= escape($product['stock']) ?>" required>

        <button type="submit">Save Changes</button>
    </form>

    <p><a href="products.php">Back to Products</a></p>
</div>
</body>
</html>
