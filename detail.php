
<?php
require_once "config/database.php";

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    http_response_code(404);
    exit("Produk tidak ditemukan.");
}

$stmt = mysqli_prepare(
    $conn,
    "SELECT products.*, categories.name AS category_name
     FROM products
     LEFT JOIN categories ON products.category_id = categories.id
     WHERE products.id = ? AND products.status = 'available'"
);

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$product = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$product) {
    http_response_code(404);
    exit("Produk tidak tersedia.");
}

$cartCount = array_sum($_SESSION["cart"] ?? []);
$stock = (int) $product["stock"];
$image = !empty($product["image"])
    ? "assets/images/" . $product["image"]
    : "https://placehold.co/700x600/FFF0E9/A9796D?text=Angel+Buyby";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($product["name"]); ?> | Angel Buyby</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --cream: #fffaf5;
            --pink: #f4d9d8;
            --pink-dark: #c98789;
            --mocha: #795548;
            --brown: #49352e;
            --text: #5e4a43;
            --muted: #9b8980;
            --white: #ffffff;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--cream);
            color: var(--brown);
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        /* NAVBAR */
        .detail-navbar {
            width: 100%;
            padding: 20px 8%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(255, 250, 245, 0.96);
            border-bottom: 1px solid #f0e6df;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .detail-logo {
            font-family: 'Playfair Display', serif;
            font-size: 26px;
            font-weight: 800;
            color: var(--mocha);
        }

        .detail-logo span {
            color: var(--pink-dark);
        }

        .detail-nav {
            display: flex;
            align-items: center;
            gap: 27px;
            font-size: 13px;
            font-weight: 600;
            color: var(--text);
        }

        .detail-nav a {
            transition: 0.2s;
        }

        .detail-nav a:hover {
            color: var(--pink-dark);
        }

        .nav-cart {
            background: var(--pink);
            padding: 10px 17px;
            border-radius: 30px;
            color: var(--brown) !important;
        }

        .nav-user {
            color: var(--pink-dark);
        }

        /* MAIN */
        .detail-main {
            max-width: 1150px;
            margin: 55px auto 85px;
            padding: 0 25px;
        }

        .breadcrumb {
            margin-bottom: 25px;
            color: var(--muted);
            font-size: 13px;
        }

        .breadcrumb a:hover {
            color: var(--pink-dark);
        }

        .detail-card {
            background: var(--white);
            border-radius: 30px;
            padding: 35px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
            align-items: center;
            box-shadow: 0 15px 45px rgba(91, 63, 49, 0.07);
            border: 1px solid #f4eae3;
        }

        /* PRODUCT IMAGE */
        .detail-image {
            position: relative;
            background: #fff1eb;
            border-radius: 25px;
            overflow: hidden;
            min-height: 420px;
        }

        .detail-image img {
            width: 100%;
            height: 420px;
            object-fit: cover;
            display: block;
            transition: transform 0.5s ease;
        }

        .detail-image:hover img {
            transform: scale(1.04);
        }

        .image-label {
            position: absolute;
            top: 20px;
            left: 20px;
            background: rgba(255, 255, 255, 0.92);
            padding: 9px 16px;
            border-radius: 30px;
            color: var(--mocha);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1px;
        }

        /* PRODUCT INFO */
        .detail-info {
            padding: 10px 5px;
        }

        .detail-category {
            display: inline-block;
            background: #f9e8e6;
            color: var(--pink-dark);
            padding: 8px 17px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 20px;
        }

        .detail-info h1 {
            font-family: 'Playfair Display', serif;
            font-size: clamp(30px, 4vw, 43px);
            line-height: 1.2;
            color: var(--brown);
            margin-bottom: 15px;
        }

        .detail-price {
            font-size: 27px;
            font-weight: 800;
            color: var(--pink-dark);
            margin-bottom: 22px;
        }

        .detail-description {
            font-size: 14px;
            line-height: 1.9;
            color: var(--muted);
            margin-bottom: 25px;
        }

        .detail-stock {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            font-weight: 700;
            color: #64876a;
            background: #edf5ed;
            padding: 9px 14px;
            border-radius: 30px;
            margin-bottom: 25px;
        }

        .detail-stock::before {
            content: "";
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #79a77e;
        }

        /* QUANTITY */
        .detail-cart-form {
            border-top: 1px solid #f0e8e2;
            padding-top: 23px;
        }

        .quantity-label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 12px;
            color: var(--brown);
        }

        .quantity-control {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 22px;
        }

        .quantity-control input {
            width: 100px;
            height: 45px;
            text-align: center;
            border: 1px solid #eadbd3;
            background: #fffaf7;
            border-radius: 12px;
            font-family: inherit;
            font-size: 15px;
            font-weight: 700;
            color: var(--brown);
            outline: none;
        }

        .quantity-control input:focus {
            border-color: var(--pink-dark);
            box-shadow: 0 0 0 3px rgba(201, 135, 137, 0.12);
        }

        .quantity-hint {
            font-size: 11px;
            color: var(--muted);
        }

        /* BUTTONS */
        .detail-buttons {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .add-cart-button {
            width: 100%;
            border: none;
            background: var(--mocha);
            color: white;
            padding: 16px 22px;
            border-radius: 15px;
            font-family: inherit;
            font-size: 14px;
            font-weight: 800;
            cursor: pointer;
            transition: 0.25s;
        }

        .add-cart-button:hover {
            background: var(--pink-dark);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(201, 135, 137, 0.2);
        }

        .back-menu {
            display: block;
            text-align: center;
            width: 100%;
            padding: 14px;
            border-radius: 15px;
            background: #fff3ef;
            color: var(--mocha);
            font-size: 13px;
            font-weight: 700;
            transition: 0.2s;
        }

        .back-menu:hover {
            background: var(--pink);
        }

        .out-of-stock {
            color: #bd6666;
            background: #fff0f0;
            padding: 15px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 700;
            margin: 20px 0;
        }

        /* FOOTER */
        .detail-footer {
            text-align: center;
            padding: 25px 15px;
            color: var(--muted);
            font-size: 12px;
            border-top: 1px solid #f0e6df;
        }

        /* RESPONSIVE */
        @media (max-width: 850px) {
            .detail-navbar {
                padding: 18px 5%;
            }

            .detail-nav {
                gap: 13px;
                font-size: 11px;
            }

            .detail-card {
                gap: 30px;
                padding: 25px;
            }

            .detail-image,
            .detail-image img {
                min-height: 340px;
                height: 340px;
            }
        }

        @media (max-width: 650px) {
            .detail-navbar {
                flex-direction: column;
                gap: 15px;
            }

            .detail-logo {
                font-size: 24px;
            }

            .detail-nav {
                flex-wrap: wrap;
                justify-content: center;
                gap: 15px;
            }

            .detail-main {
                margin: 30px auto 50px;
                padding: 0 15px;
            }

            .detail-card {
                grid-template-columns: 1fr;
                padding: 18px;
                gap: 25px;
                border-radius: 22px;
            }

            .detail-image,
            .detail-image img {
                min-height: 270px;
                height: 270px;
            }

            .detail-info {
                padding: 0 5px 10px;
            }

            .detail-info h1 {
                font-size: 32px;
            }

            .detail-price {
                font-size: 24px;
            }
        }
    </style>
</head>

<body>

<header class="detail-navbar">

    <a href="index.php" class="detail-logo">
        Angel Buyby <span>♡</span>
    </a>

    <nav class="detail-nav">
        <a href="index.php">Home</a>
        <a href="index.php#products">Menu</a>
        <a href="index.php#about">About</a>

        <a href="cart.php" class="nav-cart">
            Cart (<?= (int) $cartCount; ?>)
        </a>

        <?php if (isset($_SESSION["user_id"])) : ?>
            <span class="nav-user">
                Hi, <?= htmlspecialchars($_SESSION["user_name"]); ?> ♡
            </span>
            <a href="logout.php">Logout</a>
        <?php else : ?>
            <a href="login.php">Login</a>
            <a href="register.php">Register</a>
        <?php endif; ?>
    </nav>

</header>

<main class="detail-main">

    <div class="breadcrumb">
        <a href="index.php">Home</a> /
        <a href="index.php#products">Menu</a> /
        <span><?= htmlspecialchars($product["name"]); ?></span>
    </div>

    <section class="detail-card">

        <div class="detail-image">

            <span class="image-label">ANGEL BUYBY SPECIAL ♡</span>

            <img
                src="<?= htmlspecialchars($image); ?>"
                alt="<?= htmlspecialchars($product["name"]); ?>"
                onerror="this.onerror=null; this.src='https://placehold.co/700x600/FFF0E9/A9796D?text=Angel+Buyby';"
            >

        </div>

        <div class="detail-info">

            <span class="detail-category">
                <?= htmlspecialchars($product["category_name"] ?? "Other"); ?>
            </span>

            <h1><?= htmlspecialchars($product["name"]); ?></h1>

            <p class="detail-price">
                Rp <?= number_format((float) $product["price"], 0, ',', '.'); ?>
            </p>

            <p class="detail-description">
                <?= nl2br(htmlspecialchars($product["description"] ?? "A little sweetness in every sip.")); ?>
            </p>

            <?php if ($stock > 0) : ?>

                <p class="detail-stock">
                    <?= $stock; ?> items available
                </p>

                <form method="POST" action="cart.php" class="detail-cart-form">

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars($_SESSION["csrf_token"]); ?>"
                    >

                    <input type="hidden" name="action" value="add">

                    <input
                        type="hidden"
                        name="product_id"
                        value="<?= (int) $product["id"]; ?>"
                    >

                    <label for="quantity" class="quantity-label">
                        Choose your quantity
                    </label>

                    <div class="quantity-control">

                        <input
                            type="number"
                            id="quantity"
                            name="quantity"
                            value="1"
                            min="1"
                            max="<?= $stock; ?>"
                            required
                        >

                        <span class="quantity-hint">
                            Maximum <?= $stock; ?> items
                        </span>

                    </div>

                    <div class="detail-buttons">

                        <button type="submit" class="add-cart-button">
                            Add to Cart ♡
                        </button>

                        <a href="index.php#products" class="back-menu">
                            ← Back to Menu
                        </a>

                    </div>

                </form>

            <?php else : ?>

                <p class="out-of-stock">
                    Sorry, this product is out of stock.
                </p>

                <a href="index.php#products" class="back-menu">
                    ← Back to Menu
                </a>

            <?php endif; ?>

        </div>

    </section>

</main>

<footer class="detail-footer">
    <p>© 2026 Angel Buyby. A little sweetness in every sip ♡</p>
</footer>

</body>
</html>