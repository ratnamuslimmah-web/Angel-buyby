
<?php
require_once "config/database.php";

// Checkout wajib login
if (!isset($_SESSION["user_id"])) {
    $_SESSION["cart_message"] = "Silakan login terlebih dahulu untuk checkout.";
    header("Location: login.php");
    exit;
}

$userId = (int) $_SESSION["user_id"];

// Ambil data pelanggan
$stmt = mysqli_prepare(
    $conn,
    "SELECT id, name, email, phone FROM users WHERE id = ?"
);
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit;
}

// Keranjang harus berisi produk
if (empty($_SESSION["cart"])) {
    header("Location: cart.php");
    exit;
}

$error = "";

// PROSES PEMBUATAN PESANAN
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $token = $_POST["csrf_token"] ?? "";

    if (
        !is_string($token) ||
        !hash_equals($_SESSION["csrf_token"], $token)
    ) {
        http_response_code(403);
        exit("Permintaan tidak valid. Silakan muat ulang halaman.");
    }

    $phone = trim($_POST["phone"] ?? "");
    $address = trim($_POST["customer_address"] ?? "");
    $paymentMethod = $_POST["payment_method"] ?? "";

    $allowedMethods = [
        "Cash on Delivery",
        "Bank Transfer"
    ];

    if ($phone === "" || !preg_match('/^[0-9+\-\s]{8,20}$/', $phone)) {
        $error = "Nomor telepon tidak valid.";
    } elseif ($address === "" || mb_strlen($address) > 2000) {
        $error = "Alamat wajib diisi dan maksimal 2000 karakter.";
    } elseif (!in_array($paymentMethod, $allowedMethods, true)) {
        $error = "Metode pembayaran tidak valid.";
    } else {

        try {
            mysqli_begin_transaction($conn);

            $cart = $_SESSION["cart"];
            $orderItems = [];
            $total = 0;

            // Periksa stok dan harga terbaru
            foreach ($cart as $productId => $quantity) {

                $productId = (int) $productId;
                $quantity = (int) $quantity;

                if ($productId < 1 || $quantity < 1) {
                    throw new Exception("Data keranjang tidak valid.");
                }

                $stmt = mysqli_prepare(
                    $conn,
                    "SELECT id, name, price, stock, status
                     FROM products
                     WHERE id = ?
                     FOR UPDATE"
                );

                mysqli_stmt_bind_param($stmt, "i", $productId);
                mysqli_stmt_execute($stmt);

                $product = mysqli_fetch_assoc(
                    mysqli_stmt_get_result($stmt)
                );

                mysqli_stmt_close($stmt);

                if (
                    !$product ||
                    $product["status"] !== "available"
                ) {
                    throw new Exception(
                        "Salah satu produk sudah tidak tersedia."
                    );
                }

                if ((int) $product["stock"] < $quantity) {
                    throw new Exception(
                        "Stok " . $product["name"] .
                        " tidak mencukupi. Stok tersedia: " .
                        (int) $product["stock"]
                    );
                }

                $subtotal = (float) $product["price"] * $quantity;

                $product["quantity"] = $quantity;
                $product["subtotal"] = $subtotal;

                $orderItems[] = $product;
                $total += $subtotal;
            }

            if (empty($orderItems)) {
                throw new Exception("Keranjang kamu kosong.");
            }

            // Buat kode pesanan unik
            $orderCode = "AB" . date("ymd") .
                strtoupper(bin2hex(random_bytes(3)));

            $notes = trim($_SESSION["cart_note"] ?? "");

            // Simpan pesanan utama
            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO orders
                (
                    user_id,
                    order_code,
                    total_price,
                    payment_method,
                    payment_status,
                    order_status,
                    customer_address,
                    notes
                )
                VALUES (?, ?, ?, ?, 'pending', 'pending', ?, ?)"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "isdsss",
                $userId,
                $orderCode,
                $total,
                $paymentMethod,
                $address,
                $notes
            );

            mysqli_stmt_execute($stmt);
            $orderId = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);

            // Simpan detail produk dan kurangi stok
            foreach ($orderItems as $item) {

                $productId = (int) $item["id"];
                $productName = $item["name"];
                $quantity = (int) $item["quantity"];
                $price = (float) $item["price"];
                $subtotal = (float) $item["subtotal"];

                $stmt = mysqli_prepare(
                    $conn,
                    "INSERT INTO order_details
                    (
                        order_id,
                        product_id,
                        product_name,
                        quantity,
                        price,
                        subtotal
                    )
                    VALUES (?, ?, ?, ?, ?, ?)"
                );

                mysqli_stmt_bind_param(
                    $stmt,
                    "iisidd",
                    $orderId,
                    $productId,
                    $productName,
                    $quantity,
                    $price,
                    $subtotal
                );

                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                $stmt = mysqli_prepare(
                    $conn,
                    "UPDATE products
                     SET stock = stock - ?
                     WHERE id = ? AND stock >= ?"
                );

                mysqli_stmt_bind_param(
                    $stmt,
                    "iii",
                    $quantity,
                    $productId,
                    $quantity
                );

                mysqli_stmt_execute($stmt);

                if (mysqli_stmt_affected_rows($stmt) !== 1) {
                    mysqli_stmt_close($stmt);
                    throw new Exception(
                        "Gagal memperbarui stok " . $productName
                    );
                }

                mysqli_stmt_close($stmt);
            }

            // Simpan nomor telepon pelanggan
            $stmt = mysqli_prepare(
                $conn,
                "UPDATE users SET phone = ? WHERE id = ?"
            );

            mysqli_stmt_bind_param($stmt, "si", $phone, $userId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            // Semua berhasil
            mysqli_commit($conn);

            // Kosongkan keranjang
            $_SESSION["cart"] = [];
            $_SESSION["cart_note"] = "";

            // Arahkan ke halaman sukses
            header(
                "Location: order_success.php?id=" . $orderId
            );
            exit;

        } catch (Throwable $e) {

            mysqli_rollback($conn);

            $error = $e instanceof mysqli_sql_exception
                ? "Pesanan belum berhasil diproses. Silakan coba kembali."
                : $e->getMessage();
        }
    }
}

// Tampilkan ringkasan keranjang
$checkoutItems = [];
$total = 0;

foreach ($_SESSION["cart"] as $productId => $quantity) {

    $productId = (int) $productId;
    $quantity = (int) $quantity;

    $stmt = mysqli_prepare(
        $conn,
        "SELECT id, name, price, image, stock
         FROM products
         WHERE id = ? AND status = 'available'"
    );

    mysqli_stmt_bind_param($stmt, "i", $productId);
    mysqli_stmt_execute($stmt);

    $product = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$product || (int) $product["stock"] < 1) {
        unset($_SESSION["cart"][$productId]);
        continue;
    }

    $product["quantity"] = min(
        $quantity,
        (int) $product["stock"]
    );

    $_SESSION["cart"][$productId] = $product["quantity"];

    $product["subtotal"] =
        (float) $product["price"] * $product["quantity"];

    $total += $product["subtotal"];
    $checkoutItems[] = $product;
}

if (empty($checkoutItems)) {
    header("Location: cart.php");
    exit;
}

$cartNote = $_SESSION["cart_note"] ?? "";

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Checkout | Angel Buyby</title>

    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        .checkout-container {
            max-width: 1050px;
            margin: 40px auto 80px;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 1.2fr .8fr;
            gap: 25px;
        }

        .checkout-card {
            background: white;
            padding: 27px;
            border-radius: 20px;
            margin-bottom: 20px;
            border: 1px solid #f0e5df;
            box-shadow: 0 5px 20px rgba(73,52,47,.05);
        }

        .checkout-card h2 {
            font-family: 'Playfair Display', serif;
            font-size: 22px;
            margin-bottom: 20px;
            color: #49342f;
        }

        .checkout-card label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            margin: 15px 0 8px;
        }

        .checkout-card input,
        .checkout-card textarea {
            width: 100%;
            padding: 13px;
            border: 1px solid #eadbd1;
            border-radius: 11px;
            background: #fffaf7;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            outline: none;
        }

        .checkout-card input:focus,
        .checkout-card textarea:focus {
            border-color: #c98f87;
            box-shadow: 0 0 0 3px rgba(201,143,135,.12);
        }

        .checkout-card textarea {
            resize: vertical;
            min-height: 100px;
        }

        .payment-option {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px;
            margin-bottom: 12px;
            border: 1px solid #eadbd1;
            border-radius: 13px;
            cursor: pointer;
        }

        .payment-option input {
            width: auto;
            accent-color: #a9796d;
        }

        .payment-option span {
            font-size: 13px;
            font-weight: 700;
        }

        .checkout-product {
            display: flex;
            align-items: center;
            gap: 13px;
            padding: 13px 0;
            border-bottom: 1px solid #f0e8e2;
        }

        .checkout-product img {
            width: 65px;
            height: 65px;
            object-fit: cover;
            border-radius: 10px;
        }

        .checkout-product-info {
            flex: 1;
            min-width: 0;
        }

        .checkout-product-info h3 {
            font-size: 13px;
            margin-bottom: 5px;
        }

        .checkout-product-info p {
            font-size: 12px;
            color: #99847b;
        }

        .checkout-product strong {
            font-size: 13px;
            color: #a9796d;
            white-space: nowrap;
        }

        .checkout-summary-row {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            padding: 12px 0;
            font-size: 13px;
            color: #806c63;
        }

        .checkout-grand-total {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            padding-top: 18px;
            margin-top: 10px;
            border-top: 1px solid #eadbd1;
            font-size: 16px;
            font-weight: 800;
        }

        .checkout-grand-total strong:last-child {
            color: #a9796d;
        }

        .checkout-submit {
            width: 100%;
            padding: 16px;
            border: none;
            border-radius: 13px;
            background: #795548;
            color: white;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            font-weight: 800;
            cursor: pointer;
            transition: .25s;
            margin-top: 18px;
        }

        .checkout-submit:hover {
            background: #c98f87;
            transform: translateY(-2px);
        }

        .checkout-error {
            background: #fff0ed;
            border: 1px solid #f3d0ca;
            color: #a23e34;
            padding: 13px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 18px;
        }

        .checkout-note {
            background: #fff7f3;
            padding: 13px;
            border-radius: 10px;
            color: #806c63;
            font-size: 12px;
            line-height: 1.7;
            margin-top: 15px;
        }

        .checkout-back {
            display: inline-block;
            margin-bottom: 20px;
            color: #a9796d;
            font-size: 13px;
            font-weight: 700;
        }

        @media(max-width: 750px) {
            .checkout-container {
                grid-template-columns: 1fr;
                margin-top: 25px;
            }
        }
    </style>
</head>

<body>

<header>
    <a href="index.php" class="logo">Angel Buyby ♡</a>

    <nav>
        <a href="index.php">Home</a>
        <a href="index.php#products">Menu</a>
        <a href="cart.php">Cart</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<main class="checkout-container">

    <div>

        <a href="cart.php" class="checkout-back">
            ← Kembali ke Keranjang
        </a>

        <section class="checkout-card">

            <h2>Informasi Pengiriman ♡</h2>

            <?php if ($error !== "") : ?>
                <div class="checkout-error">
                    <?= htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="checkoutForm">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars($_SESSION["csrf_token"]); ?>"
                >

                <label>Nama pelanggan</label>
                <input
                    type="text"
                    value="<?= htmlspecialchars($user["name"]); ?>"
                    readonly
                >

                <label for="phone">Nomor telepon</label>
                <input
                    type="tel"
                    id="phone"
                    name="phone"
                    value="<?= htmlspecialchars($_POST["phone"] ?? $user["phone"] ?? ""); ?>"
                    placeholder="Contoh: 081234567890"
                    maxlength="20"
                    required
                >

                <label for="customer_address">Alamat lengkap</label>
                <textarea
                    id="customer_address"
                    name="customer_address"
                    placeholder="Masukkan nama jalan, nomor rumah, RT/RW, kelurahan, kecamatan, dan patokan..."
                    maxlength="2000"
                    required
                ><?= htmlspecialchars($_POST["customer_address"] ?? ""); ?></textarea>

                <label>Metode pembayaran</label>

                <label class="payment-option">
                    <input
                        type="radio"
                        name="payment_method"
                        value="Cash on Delivery"
                        <?= ($_POST["payment_method"] ?? "Cash on Delivery") === "Cash on Delivery" ? "checked" : ""; ?>
                    >
                    <span>💵 Cash on Delivery (COD)</span>
                </label>

                <label class="payment-option">
                    <input
                        type="radio"
                        name="payment_method"
                        value="Bank Transfer"
                        <?= ($_POST["payment_method"] ?? "") === "Bank Transfer" ? "checked" : ""; ?>
                    >
                    <span>🏦 Transfer Bank (Manual)</span>
                </label>

                <div class="checkout-note">
                    Pembayaran transfer belum terhubung dengan payment gateway.
                    Petunjuk rekening dan konfirmasi pembayaran perlu diberikan
                    oleh admin secara manual.
                </div>

                <button type="submit" class="checkout-submit">
                    Buat Pesanan ♡
                </button>

            </form>

        </section>

    </div>


    <div>

        <section class="checkout-card">

            <h2>Ringkasan Pesanan</h2>

            <?php foreach ($checkoutItems as $item) : ?>

                <div class="checkout-product">

                    <img
                        src="assets/images/<?= htmlspecialchars($item["image"] ?? ""); ?>"
                        alt="<?= htmlspecialchars($item["name"]); ?>"
                        onerror="this.onerror=null;this.src='https://placehold.co/100x100/FFF0E9/A9796D?text=Buyby';"
                    >

                    <div class="checkout-product-info">
                        <h3><?= htmlspecialchars($item["name"]); ?></h3>
                        <p><?= (int) $item["quantity"]; ?> × Rp <?= number_format($item["price"], 0, ',', '.'); ?></p>
                    </div>

                    <strong>
                        Rp <?= number_format($item["subtotal"], 0, ',', '.'); ?>
                    </strong>

                </div>

            <?php endforeach; ?>

            <div class="checkout-summary-row">
                <span>Total item</span>
                <strong>
                    <?= array_sum(array_column($checkoutItems, "quantity")); ?> item
                </strong>
            </div>

            <div class="checkout-summary-row">
                <span>Catatan pesanan</span>
            </div>

            <p style="font-size:12px;color:#806c63;line-height:1.7;">
                <?= $cartNote !== ""
                    ? nl2br(htmlspecialchars($cartNote))
                    : "Tidak ada catatan."; ?>
            </p>

            <div class="checkout-summary-row">
                <span>Ongkos kirim</span>
                <span>Belum dihitung</span>
            </div>

            <div class="checkout-grand-total">
                <span>Total pesanan</span>
                <strong>
                    Rp <?= number_format($total, 0, ',', '.'); ?>
                </strong>
            </div>

            <p class="checkout-note">
                Total di atas adalah harga produk. Ongkos kirim belum termasuk
                dan perlu dikonfirmasi secara terpisah.
            </p>

        </section>

    </div>

</main>

<footer>
    <p>© 2026 Angel Buyby. A little sweetness in every sip ♡</p>
</footer>

</body>
</html>