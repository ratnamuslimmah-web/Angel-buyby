
<?php
require_once "config/database.php";

$query = mysqli_query($conn, "
    SELECT * FROM products
    WHERE status = 'available'
    ORDER BY id ASC
    LIMIT 4
");

$cartCount = array_sum($_SESSION["cart"]);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Angel Buyby | Coffee & Dessert</title>

    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

<!-- NAVBAR -->
<header>

    <a href="index.php" class="logo">Angel Buyby ♡</a>

    <nav>
        <a href="index.php">Home</a>
        <a href="#products">Menu</a>
        <a href="#about">About</a>

        <a href="cart.php" class="cart-nav">
            Cart (<?= $cartCount; ?>)
        </a>

        <?php if (isset($_SESSION["user_id"])) : ?>

            <span class="nav-user">
                Hi, <?= htmlspecialchars($_SESSION["user_name"]); ?> ♡
            </span>

            <a href="logout.php" class="nav-logout">Logout</a>

        <?php else : ?>

            <a href="login.php">Login</a>
            <a href="register.php">Register</a>

        <?php endif; ?>
    </nav>

</header>


<!-- HERO -->
<section class="hero">

    <div class="hero-content">

        <small>WELCOME TO ANGEL BUYBY</small>

        <h1>
            A little sweetness
            <span>in every sip.</span>
        </h1>

        <p>
            Discover your favorite coffee and delicious desserts.
            Made with love, served with sweetness.
        </p>

        <a href="#products" class="btn">
            Explore Our Menu ♡
        </a>

    </div>

    <img
        src="https://images.unsplash.com/photo-1461023058943-07fcbe16d735?w=900"
        alt="Angel Buyby Coffee"
        class="hero-image"
    >

</section>


<!-- PRODUCTS -->
<section class="section" id="products">

    <h2 class="section-title">Our Sweet Menu</h2>

    <p class="section-desc">
        A little treat to make your day better ♡
    </p>

    <div class="product-grid">

        <?php if (mysqli_num_rows($query) > 0) : ?>

            <?php while ($product = mysqli_fetch_assoc($query)) : ?>

                <div class="product-card">

                    <a href="detail.php?id=<?= (int)$product['id']; ?>">

                        <img
                            src="assets/images/<?= htmlspecialchars($product['image'] ?? ''); ?>"
                            alt="<?= htmlspecialchars($product['name']); ?>"
                            onerror="this.src='https://placehold.co/400x300/FFF0E9/A9796D?text=Angel+Buyby'"
                        >

                    </a>

                    <div class="product-info">

                        <h3>
                            <?= htmlspecialchars($product['name']); ?>
                        </h3>

                        <p>
                            Rp <?= number_format($product['price'], 0, ',', '.'); ?>
                        </p>

                        <a
                            href="detail.php?id=<?= (int)$product['id']; ?>"
                            class="detail-link"
                        >
                            View Details →
                        </a>

                        <form method="POST" action="cart.php">

                            <input type="hidden" name="csrf_token"
                                value="<?= htmlspecialchars($_SESSION["csrf_token"]); ?>">

                            <input type="hidden" name="action" value="add">

                            <input type="hidden" name="product_id"
                                value="<?= (int)$product['id']; ?>">

                            <input type="hidden" name="quantity" value="1">

                            <button type="submit" class="add-cart-button">
                                + Add to Cart ♡
                            </button>

                        </form>

                    </div>

                </div>

            <?php endwhile; ?>

        <?php else : ?>

            <p>Belum ada produk yang tersedia.</p>

        <?php endif; ?>

    </div>

</section>


<!-- ABOUT -->
<section class="section" id="about">

    <h2 class="section-title">Made with Love ♡</h2>

    <p class="section-desc">
        Coffee, desserts, and little moments of happiness.
        Welcome to Angel Buyby.
    </p>

</section>


<!-- FOOTER -->
<footer>

    <p>
        © 2026 Copyright angel buyby
        by 24552011338_Ratna Muslimmah A_TIF RP 24 E CNS ♡
    </p>

</footer>

</body>
</html>