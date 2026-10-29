
<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        exit('Invalid request. Please reload the page.');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $price = filter_var($_POST['price'] ?? '', FILTER_VALIDATE_FLOAT);
        $stock = filter_var($_POST['stock'] ?? '', FILTER_VALIDATE_INT);

        if ($name === '' || strlen($name) > 150 ||
            $price === false || $price < 0 ||
            $stock === false || $stock < 0) {
            $message = 'Enter valid product details.';
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO products (name, price, stock)
                 VALUES (?, ?, ?)'
            );
            $stmt->execute([$name, $price, $stock]);

            header('Location: products.php?success=added');
            exit;
        }
    }

    if ($action === 'delete') {
        $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);

        if ($id !== false && $id > 0) {
            $stmt = $pdo->prepare('DELETE FROM products WHERE id = ?');
            $stmt->execute([$id]);

            header('Location: products.php?success=deleted');
            exit;
        }

        $message = 'Invalid product ID.';
    }
}


if (($_GET['success'] ?? '') === 'added') {
    $message = 'Product added successfully.';
} elseif (($_GET['success'] ?? '') === 'deleted') {
    $message = 'Product deleted successfully.';
} elseif (($_GET['success'] ?? '') === 'updated') {
    $message = 'Product updated successfully.';
}



$products = $pdo->query(
    'SELECT id, name, price, stock FROM products ORDER BY id DESC'
)->fetchAll();

function escape($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Products | MyEcommerce Admin</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            color: #172033;
        }
        header {
            background: #172033;
            color: white;
            padding: 20px 28px;
        }
        nav {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            background: white;
            padding: 16px 28px;
        }
        a { color: #2563eb; text-decoration: none; }
        main { padding: 28px; }
        .panel {
            background: white;
            padding: 22px;
            border-radius: 10px;
            margin-bottom: 24px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 14px;
            align-items: end;
        }
        label { display: block; margin-bottom: 7px; }
        input {
            width: 100%;
            padding: 11px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
        }
        button {
            padding: 11px 16px;
            border: 0;
            border-radius: 6px;
            cursor: pointer;
            color: white;
            background: #2563eb;
        }
        .delete { background: #dc2626; }
        .message { color: #15803d; }
        .table-wrap { overflow-x: auto; }
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 550px;
        }
        th, td {
            padding: 13px;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
        }
        th { background: #f8fafc; }
    </style>
</head>
<body>
<header>
    <h2>MyEcommerce Admin</h2>
    <p>Product Management</p>
</header>

<nav>
    <a href="dashboard.php">Dashboard</a>
    <a href="products.php">Products</a>
    <a href="orders.php">Orders</a>
    <a href="logout.php">Logout</a>
</nav>

<main>
    <h1>Manage Products</h1>

    <?php if ($message !== ''): ?>
        <p class="message"><?= escape($message) ?></p>
    <?php endif; ?>

    <section class="panel">
        <h2>Add New Product</h2>

        <form method="POST">
            <input type="hidden" name="csrf_token"
                   value="<?= escape($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="action" value="add">

            <div class="form-grid">
                <div>
                    <label for="name">Product Name</label>
                    <input id="name" name="name" maxlength="150"
                           required>
                </div>
                <div>
                    <label for="price">Price (₹)</label>
                    <input id="price" type="number" name="price"
                           min="0" step="0.01" required>
                </div>
                <div>
                    <label for="stock">Stock Quantity</label>
                    <input id="stock" type="number" name="stock"
                           min="0" step="1" required>
                </div>
                <button type="submit">Add Product</button>
            </div>
        </form>
    </section>

    <section class="panel">
        <h2>All Products</h2>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$products): ?>
                    <tr>
                        <td colspan="5">No products added yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?= escape($product['id']) ?></td>
                            <td><?= escape($product['name']) ?></td>
                            <td>₹<?= number_format(
                                (float)$product['price'], 2
                            ) ?></td>
                            <td><?= escape($product['stock']) ?></td>
                            <td>
                                <a href="edit_product.php?id=<?= escape($product['id']) ?>">Edit</a>
                                <form method="POST"
                                      onsubmit="return confirm('Delete this product?');">
                                    <input type="hidden"
                                           name="csrf_token"
                                           value="<?= escape($_SESSION['csrf_token']) ?>">
                                    <input type="hidden" name="action"
                                           value="delete">
                                    <input type="hidden" name="id"
                                           value="<?= escape($product['id']) ?>
                                           
                                    <button class="delete" type="submit">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
</body>
</html>
