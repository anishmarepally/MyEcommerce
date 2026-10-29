
<?php
session_start();

require_once __DIR__ . '/config/database.php';

// Fetch existing products. No database records are changed.
$stmt = $pdo->query(
    'SELECT id, NAME AS name, price, stock
     FROM products
     ORDER BY id DESC'
);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate the cart quantity.
$cartCount = 0;
foreach ($_SESSION['cart'] ?? [] as $quantity) {
    $cartCount += (int)$quantity;
}

function e($value) {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

// Map existing product names to image filenames.
$imageFiles = [
    'kasturi' => 'kasturi.jpg',
    'majboova' => 'majboova.jpg',
    'white oud' => 'white-oud.jpg',
    'jannat-ul-firdouse' => 'jannat-ul-firdouse.jpg',
    'shanaya' => 'shanaya.jpg'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>BarOdor | Premium Fragrances</title>

    <style>
        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #faf9f6;
            color: #242424;
        }

        a {
            text-decoration: none;
        }

        header {
            background: #18251f;
            color: white;
            padding: 18px 7%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .brand {
            color: white;
            font-size: 25px;
            font-weight: 800;
            letter-spacing: 0.5px;
        }

        .brand span {
            color: #d9b978;
        }

        nav {
            display: flex;
            align-items: center;
            gap: 25px;
            flex-wrap: wrap;
        }

        nav a {
            color: white;
            font-size: 14px;
        }

        nav a:hover {
            color: #e4c98f;
        }

        .cart-link {
            border: 1px solid #d9b978;
            padding: 10px 15px;
            border-radius: 5px;
        }

        .hero {
            min-height: 390px;
            padding: 75px 7%;
            display: flex;
            align-items: center;
            background: linear-gradient(115deg, #e9e3d6, #f8f4eb);
        }

        .hero-content {
            max-width: 650px;
        }

        .eyebrow {
            color: #79613b;
            text-transform: uppercase;
            font-size: 12px;
            font-weight: bold;
            letter-spacing: 3px;
        }

        .hero h1 {
            font-family: Georgia, serif;
            font-size: clamp(38px, 6vw, 64px);
            line-height: 1.1;
            margin: 18px 0;
            color: #18251f;
        }

        .hero p {
            color: #5d625d;
            font-size: 17px;
            line-height: 1.8;
            max-width: 520px;
        }

        .shop-button {
            display: inline-block;
            background: #18251f;
            color: white;
            padding: 14px 25px;
            border-radius: 5px;
            margin-top: 15px;
            font-weight: bold;
            transition: background 0.2s;
        }

        .shop-button:hover {
            background: #354a3e;
        }

        main {
            max-width: 1400px;
            margin: auto;
            padding: 55px 7%;
        }

        .section-heading {
            text-align: center;
            margin-bottom: 35px;
        }

        .section-heading h2 {
            font-family: Georgia, serif;
            font-size: clamp(30px, 4vw, 42px);
            margin: 10px 0;
        }

        .section-heading p {
            color: #777;
            line-height: 1.6;
        }

        .products {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 24px;
        }

        .product {
            background: white;
            border: 1px solid #ece8df;
            border-radius: 10px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            min-width: 0;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .product:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 28px rgba(24, 37, 31, 0.10);
        }

        .product-image {
            height: 250px;
            padding: 15px;
            background: #f1eee7;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
            transition: transform 0.3s;
        }

        .product:hover .product-image img {
            transform: scale(1.04);
        }

        .image-placeholder {
            color: #777;
            text-align: center;
            font-size: 14px;
        }

        .product-info {
            padding: 20px;
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        .product h3 {
            font-family: Georgia, serif;
            font-size: 20px;
            margin: 0 0 12px;
            overflow-wrap: anywhere;
        }

        .price {
            font-size: 24px;
            font-weight: bold;
            color: #263c30;
            margin: 0 0 12px;
        }

        .stock {
            font-size: 13px;
            color: #647067;
            margin: 0 0 15px;
        }

        .out-of-stock {
            color: #b42318;
        }

        .buy-button {
            width: 100%;
            padding: 13px;
            margin-top: auto;
            background: #18251f;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.2s;
        }

        .buy-button:hover {
            background: #354a3e;
        }

        .buy-button:disabled {
            background: #aaa;
            cursor: not-allowed;
        }

        .empty {
            text-align: center;
            background: white;
            border: 1px solid #ece8df;
            padding: 35px;
            border-radius: 8px;
        }

        .benefits {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            padding: 25px 7%;
            background: #f0ede5;
            text-align: center;
        }

        .benefit h3 {
            font-size: 16px;
        }

        .benefit p {
            color: #666;
            font-size: 13px;
            line-height: 1.6;
        }

        footer {
            background: #18251f;
            color: white;
            padding: 30px 7%;
            text-align: center;
        }

        footer p {
            font-size: 13px;
            line-height: 1.7;
            color: #d5dcd7;
        }

        @media (max-width: 1000px) {
            .products {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .product-image {
                height: 230px;
            }
        }

        @media (max-width: 600px) {
            header {
                padding: 18px 5%;
            }

            nav {
                width: 100%;
                gap: 17px;
            }

            .hero {
                min-height: 320px;
                padding: 45px 5%;
            }

            main {
                padding: 40px 5%;
            }

            .products {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 12px;
            }

            .product-image {
                height: 165px;
                padding: 8px;
            }

            .product-info {
                padding: 12px;
            }

            .product h3 {
                font-size: 17px;
            }

            .price {
                font-size: 20px;
            }

            .buy-button {
                padding: 11px 5px;
                font-size: 12px;
            }

            .benefits {
                grid-template-columns: 1fr;
                gap: 5px;
            }
        }
    </style>
</head>

<body>

<header>
    <a class="brand" href="index.php">
        Bar<span>Odor</span>
    </a>

    <nav>
        <a href="index.php">Home</a>
        <a href="#products">Shop</a>
        <a class="cart-link" href="cart.php">
            Cart (<?= (int)$cartCount ?>)
        </a>
    </nav>
</header>

<section class="hero">
    <div class="hero-content">
        <div class="eyebrow">Discover your signature scent</div>

        <h1>Fragrance that leaves an impression.</h1>

        <p>
            Explore your favourite fragrances with a collection
            designed for everyday moments and special occasions.
        </p>

        <a class="shop-button" href="#products">Explore Collection &rarr;</a>
    </div>
</section>

<main id="products">
    <div class="section-heading">
        <div class="eyebrow">Our Collection</div>
        <h2>Find Your Favourite</h2>
        <p>Explore our fragrances and choose the one you love.</p>
    </div>

    <?php if (empty($products)): ?>
        <div class="empty">
            <h3>No products available yet</h3>
            <p>Please check back later.</p>
        </div>
    <?php else: ?>

        <div class="products">
            <?php foreach ($products as $product): ?>
                <?php
                $productKey = strtolower(trim($product['name']));
                $imageFile = $imageFiles[$productKey] ?? '';
                $imagePath = __DIR__ . '/assets/images/' . $imageFile;
                ?>

                <article class="product">
                    <div class="product-image">
                        <?php if (
                            $imageFile !== '' &&
                            is_file($imagePath)
                        ): ?>
                            <img
                                src="assets/images/<?= e($imageFile) ?>"
                                alt="<?= e($product['name']) ?>"
                                loading="lazy"
                            >
                        <?php else: ?>
                            <div class="image-placeholder">
                                Product image unavailable
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="product-info">
                        <h3><?= e($product['name']) ?></h3>

                        <p class="price">
                            ₹<?= number_format(
                                (float)$product['price'],
                                2
                            ) ?>
                        </p>

                        <p class="stock <?= (int)$product['stock'] < 1 ? 'out-of-stock' : '' ?>">
                            <?php if ((int)$product['stock'] > 0): ?>
                                In stock: <?= (int)$product['stock'] ?>
                            <?php else: ?>
                                Out of stock
                            <?php endif; ?>
                        </p>

                        <form action="cart.php" method="POST">
                            <input
                                type="hidden"
                                name="product_id"
                                value="<?= (int)$product['id'] ?>"
                            >

                            <input
                                type="hidden"
                                name="action"
                                value="add"
                            >

                            <button
                                class="buy-button"
                                type="submit"
                                <?= (int)$product['stock'] < 1 ? 'disabled' : '' ?>
                            >
                                <?= (int)$product['stock'] > 0
                                    ? 'Add to Cart'
                                    : 'Out of Stock' ?>
                            </button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

    <?php endif; ?>
</main>

<section class="benefits">
    <div class="benefit">
        <h3>Our Collection</h3>
        <p>Explore fragrances selected for different tastes.</p>
    </div>

    <div class="benefit">
        <h3>Easy Shopping</h3>
        <p>Add your favourites to your cart in a few clicks.</p>
    </div>

    <div class="benefit">
        <h3>Simple Checkout</h3>
        <p>Review your order before completing checkout.</p>
    </div>
</section>

<footer>
    <strong>BarOdor</strong>
    <p>&copy; <?= date('Y') ?> BarOdor. All rights reserved.</p>
</footer>

</body>
</html>
