
<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$productCount = (int) $pdo
    ->query('SELECT COUNT(*) FROM products')
    ->fetchColumn();

$orderCount = (int) $pdo
    ->query('SELECT COUNT(*) FROM orders')
    ->fetchColumn();

$totalSales = (float) $pdo
    ->query("SELECT COALESCE(SUM(total), 0)
             FROM orders WHERE status = 'Completed'")
    ->fetchColumn();

$name = htmlspecialchars($_SESSION['admin_name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard | MyEcommerce</title>
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
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        header h2 { margin: 0; }
        nav {
            background: white;
            padding: 16px 28px;
            display: flex;
            gap: 22px;
            flex-wrap: wrap;
        }
        a { color: #2563eb; text-decoration: none; }
        main { padding: 28px; }
        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 18px;
        }
        .card {
            background: white;
            border-radius: 10px;
            padding: 22px;
            box-shadow: 0 3px 12px #00000008;
        }
        .card p { color: #64748b; }
        .value { font-size: 28px; font-weight: bold; }
    </style>
</head>
<body>
    <header>
        <h2>MyEcommerce Admin</h2>
        <span>Welcome, <?= $name ?></span>
    </header>

    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="products.php">Products</a>
        <a href="orders.php">Orders</a>
        <a href="logout.php">Logout</a>
    </nav>

    <main>
        <h1>Dashboard</h1>
        <p>Here's an overview of your store.</p>

        <section class="cards">
            <div class="card">
                <p>Total Products</p>
                <div class="value"><?= $productCount ?></div>
            </div>
            <div class="card">
                <p>Total Orders</p>
                <div class="value"><?= $orderCount ?></div>
            </div>
            <div class="card">
                <p>Completed Sales</p>
                <div class="value">
                    ₹<?= number_format($totalSales, 2) ?>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
