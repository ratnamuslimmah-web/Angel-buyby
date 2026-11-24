
<?php
require_once "config/database.php";

// Pastikan session keranjang tersedia
if (!isset($_SESSION["cart"]) || !is_array($_SESSION["cart"])) {
    $_SESSION["cart"] = [];
}

// PROSES FORM
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $token = $_POST["csrf_token"] ?? "";

    if (
        !is_string($token) ||
        !hash_equals($_SESSION["csrf_token"], $token)
    ) {
        http_response_code(403);
        exit("Permintaan tidak valid. Silakan muat ulang halaman.");
    }

    $action = $_POST["action"] ?? "";
    $id = filter_input(INPUT_POST, "product_id", FILTER_VALIDATE_INT);

    // TAMBAH PRODUK
    if ($action === "add" && $id && $id > 0) {

        $quantity = filter_input(INPUT_POST, "quantity", FILTER_VALIDATE_INT);

        if (!$quantity || $quantity < 1) {
            $quantity = 1;
        }

        $stmt = mysqli_prepare(
            $conn,
            "SELECT id, name, stock, status
             FROM products
             WHERE id = ?"
        );

        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);

        $product = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        mysqli_stmt_close($stmt);

        if (!$product || $product["status"] !== "available") {

            $_SESSION["cart_message"] = "Produk tidak tersedia.";

        } else {

            $currentQuantity = (int) ($_SESSION["cart"][$id] ?? 0);
            $newQuantity = $currentQuantity + $quantity;

            if ((int) $product["stock"] < 1) {

                $_SESSION["cart_message"] = "Stok produk habis.";

            } elseif ($newQuantity > (int) $product["stock"]) {

                $_SESSION["cart_message"] =
                    "Jumlah melebihi stok yang tersedia.";

            } else {

                $_SESSION["cart"][$id] = $newQuantity;

                $_SESSION["cart_message"] =
                    $product["name"] . " berhasil ditambahkan ke keranjang ♡";
            }
        }
    }

    // UPDATE JUMLAH PRODUK
    elseif ($action === "update" && $id && isset($_SESSION["cart"][$id])) {

        $quantity = filter_input(INPUT_POST, "quantity", FILTER_VALIDATE_INT);

        $stmt = mysqli_prepare(
            $conn,
            "SELECT stock, status
             FROM products
             WHERE id = ?"
        );

        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);

        $product = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        mysqli_stmt_close($stmt);

        if (
            !$product ||
            $product["status"] !== "available" ||
            (int) $product["stock"] < 1
        ) {

            unset($_SESSION["cart"][$id]);

            $_SESSION["cart_message"] = "Produk sudah tidak tersedia.";

        } elseif (
            $quantity === false ||
            $quantity === null ||
            $quantity < 1
        ) {

            unset($_SESSION["cart"][$id]);

            $_SESSION["cart_message"] = "Produk berhasil dihapus.";

        } elseif ($quantity <= (int) $product["stock"]) {

            $_SESSION["cart"][$id] = $quantity;

        } else {

            $_SESSION["cart_message"] =
                "Jumlah melebihi stok yang tersedia.";
        }
    }

    // HAPUS SATU PRODUK
    elseif ($action === "remove" && $id) {

        unset($_SESSION["cart"][$id]);

        $_SESSION["cart_message"] =
            "Produk berhasil dihapus dari keranjang.";
    }

    // KOSONGKAN SEMUA KERANJANG
    elseif ($action === "clear") {

        $_SESSION["cart"] = [];
        $_SESSION["cart_note"] = "";

        $_SESSION["cart_message"] =
            "Keranjang berhasil dikosongkan. Yuk pilih menu lagi ♡";
    }

    // SIMPAN CATATAN
    elseif ($action === "save_note") {

        $_SESSION["cart_note"] = trim($_POST["cart_note"] ?? "");

        $_SESSION["cart_message"] =
            "Catatan pesanan berhasil disimpan.";
    }

    header("Location: cart.php");
    exit;
}


// AMBIL DATA PRODUK DALAM KERANJANG
$cartItems = [];
$total = 0;
$totalItems = 0;

foreach ($_SESSION["cart"] as $id => $quantity) {

    $id = (int) $id;
    $quantity = (int) $quantity;

    if ($id < 1 || $quantity < 1) {
        unset($_SESSION["cart"][$id]);
        continue;
    }

    $stmt = mysqli_prepare(
        $conn,
        "SELECT id, name, price, image, stock
         FROM products
         WHERE id = ? AND status = 'available'"
    );

    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);

    $product = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    mysqli_stmt_close($stmt);

    if (!$product || (int) $product["stock"] < 1) {
        unset($_SESSION["cart"][$id]);
        continue;
    }

    $quantity = min($quantity, (int) $product["stock"]);

    $_SESSION["cart"][$id] = $quantity;

    $product["quantity"] = $quantity;
    $product["subtotal"] = (float) $product["price"] * $quantity;

    $cartItems[] = $product;

    $total += $product["subtotal"];
    $totalItems += $quantity;
}

$cartCount = $totalItems;

$message = $_SESSION["cart_message"] ?? "";
unset($_SESSION["cart_message"]);

$cartNote = $_SESSION["cart_note"] ?? "";

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Keranjang | Angel Buyby</title>

    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        /* TOMBOL CHECKOUT AKTIF */
        a.checkout-button {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            text-decoration: none;
            cursor: pointer;
            opacity: 1;
            background: #795548;
            color: #ffffff;
            border-radius: 12px;
            padding: 15px 22px;
            font-family: 'DM Sans', sans-serif;
            font-size: 13px;
            font-weight: 700;
            transition: .25s;
            border: none;
        }

        a.checkout-button:hover {
            background: #c98f87;
            transform: translateY(-2px);
        }

        a.checkout-button span {
            font-size: 18px;
        }

        /* TOMBOL KOSONGKAN KERANJANG */
        .clear-cart-wrapper {
            display: flex;
            justify-content: flex-end;
            padding: 13px 0;
            border-bottom: 1px solid #f0e8e2;
        }

        .clear-cart-button {
            border: 1px solid #ead4d0;
            background: #fff7f5;
            color: #bd7775;
            padding: 9px 15px;
            border-radius: 20px;
            font-family: 'DM Sans', sans-serif;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: .25s;
        }

        .clear-cart-button:hover {
            background: #f8e4e1;
            color: #985d59;
        }

        .cart-message {
            background: #fff3ee;
            color: #95685e;
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 18px;
            font-size: 13px;
        }

        @media(max-width: 550px) {
            .clear-cart-button {
                font-size: 11px;
                padding: 8px 12px;
            }
        }
    </style>

</head>

<body class="cart-page">

<header class="cart-header">

    <a href="index.php" class="cart-back">‹</a>

    <div>
        <h1>Keranjang</h1>
        <p>Angel Buyby · Coffee & Dessert</p>
    </div>

    <a href="index.php#products" class="cart-add-more">
        + Menu
    </a>

</header>


<main class="gofood-cart">

    <?php if ($message !== "") : ?>

        <div class="cart-message">
            <?= htmlspecialchars($message); ?>
        </div>

    <?php endif; ?>


    <?php if (count($cartItems) > 0) : ?>

        <section class="cart-shop-card">

            <div class="shop-heading">

                <div class="shop-icon">♡</div>

                <div>
                    <h2>Angel Buyby</h2>
                    <p>Pesanan kamu</p>
                </div>

                <span class="shop-count">
                    <?= $totalItems; ?> item
                </span>

            </div>


            <!-- KOSONGKAN KERANJANG -->
            <div class="clear-cart-wrapper">

                <form method="POST"
                    onsubmit="return confirm('Yakin ingin menghapus semua produk dari keranjang?');">

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars($_SESSION["csrf_token"]); ?>"
                    >

                    <input type="hidden" name="action" value="clear">

                    <button type="submit" class="clear-cart-button">
                        ♡ Kosongkan Keranjang
                    </button>

                </form>

            </div>


            <!-- DAFTAR PRODUK -->
            <div class="cart-product-list">

                <?php foreach ($cartItems as $item) : ?>

                    <article class="gofood-item">

                        <img
                            src="assets/images/<?= htmlspecialchars($item["image"] ?? ""); ?>"
                            alt="<?= htmlspecialchars($item["name"]); ?>"
                            onerror="this.onerror=null; this.src='https://placehold.co/120x120/FFF0E9/A9796D?text=Buyby';"
                        >

                        <div class="gofood-item-info">

                            <h3>
                                <?= htmlspecialchars($item["name"]); ?>
                            </h3>

                            <p class="gofood-item-price">
                                Rp <?= number_format($item["price"], 0, ',', '.'); ?>
                            </p>

                            <p class="gofood-subtotal">
                                Subtotal:
                                Rp <?= number_format($item["subtotal"], 0, ',', '.'); ?>
                            </p>


                            <!-- TAMBAH DAN KURANGI JUMLAH -->
                            <form method="POST" class="gofood-quantity">

                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= htmlspecialchars($_SESSION["csrf_token"]); ?>"
                                >

                                <input type="hidden" name="action" value="update">

                                <input
                                    type="hidden"
                                    name="product_id"
                                    value="<?= (int) $item["id"]; ?>"
                                >

                                <button
                                    type="submit"
                                    name="quantity"
                                    value="<?= $item["quantity"] - 1; ?>"
                                    class="quantity-btn"
                                    <?= $item["quantity"] <= 1 ? "disabled" : ""; ?>
                                >
                                    −
                                </button>

                                <span>
                                    <?= (int) $item["quantity"]; ?>
                                </span>

                                <button
                                    type="submit"
                                    name="quantity"
                                    value="<?= $item["quantity"] + 1; ?>"
                                    class="quantity-btn"
                                    <?= $item["quantity"] >= $item["stock"] ? "disabled" : ""; ?>
                                >
                                    +
                                </button>

                            </form>


                            <!-- HAPUS PRODUK -->
                            <form method="POST" class="gofood-remove">

                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= htmlspecialchars($_SESSION["csrf_token"]); ?>"
                                >

                                <input type="hidden" name="action" value="remove">

                                <input
                                    type="hidden"
                                    name="product_id"
                                    value="<?= (int) $item["id"]; ?>"
                                >

                                <button
                                    type="submit"
                                    onclick="return confirm('Hapus produk ini dari keranjang?');"
                                >
                                    Hapus
                                </button>

                            </form>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>


            <a href="index.php#products" class="add-more-product">
                + Tambah menu lainnya
            </a>

        </section>


        <!-- CATATAN PESANAN -->
        <section class="cart-note-card">

            <h2>Catatan untuk pesanan</h2>

            <p>Punya permintaan khusus? Tulis di sini.</p>

            <form method="POST">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars($_SESSION["csrf_token"]); ?>"
                >

                <input type="hidden" name="action" value="save_note">

                <textarea
                    name="cart_note"
                    rows="3"
                    maxlength="500"
                    placeholder="Contoh: Less sugar, no ice, atau pisahkan dessert..."
                ><?= htmlspecialchars($cartNote); ?></textarea>

                <button type="submit" class="save-note-btn">
                    Simpan catatan
                </button>

            </form>

        </section>


        <!-- RINGKASAN PEMBAYARAN -->
        <section class="cart-payment-card">

            <h2>Ringkasan pembayaran</h2>

            <div class="payment-row">
                <span>Total item</span>
                <span><?= $totalItems; ?> item</span>
            </div>

            <div class="payment-row">
                <span>Total harga menu</span>
                <span>
                    Rp <?= number_format($total, 0, ',', '.'); ?>
                </span>
            </div>

            <div class="payment-row">
                <span>Ongkos kirim</span>
                <span class="free-delivery">
                    Dihitung saat checkout
                </span>
            </div>

            <div class="payment-total">
                <strong>Total sementara</strong>

                <strong>
                    Rp <?= number_format($total, 0, ',', '.'); ?>
                </strong>
            </div>

        </section>


        <!-- CHECKOUT BAR -->
        <div class="cart-bottom-bar">

            <div class="bottom-total">
                <span>Total belanja</span>

                <strong>
                    Rp <?= number_format($total, 0, ',', '.'); ?>
                </strong>
            </div>

            <!-- TOMBOL CHECKOUT SUDAH AKTIF -->
            <a href="checkout.php" class="checkout-button">
                Lanjut ke Checkout
                <span>›</span>
            </a>

        </div>


    <?php else : ?>


        <!-- KERANJANG KOSONG -->
        <section class="gofood-empty">

            <div class="empty-illustration">🛍️</div>

            <h2>Keranjangmu masih kosong</h2>

            <p>
                Sepertinya kamu belum memilih menu favorit.
                Yuk, cari sesuatu yang manis hari ini!
            </p>

            <a href="index.php#products" class="browse-menu-btn">
                Lihat menu Angel Buyby
            </a>

        </section>

    <?php endif; ?>

</main>


<footer>
    <p>© 2026 Angel Buyby. A little sweetness in every sip ♡</p>
</footer>

</body>
</html>