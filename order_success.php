
<?php
require_once "config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$orderId = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$orderId || $orderId < 1) {
    http_response_code(404);
    exit("Pesanan tidak ditemukan.");
}

$userId = (int) $_SESSION["user_id"];

// Ambil pesanan milik pelanggan yang sedang login
$stmt = mysqli_prepare(
    $conn,
    "SELECT *
     FROM orders
     WHERE id = ? AND user_id = ?"
);

mysqli_stmt_bind_param($stmt, "ii", $orderId, $userId);
mysqli_stmt_execute($stmt);

$order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$order) {
    http_response_code(404);
    exit("Pesanan tidak ditemukan.");
}

// Ambil detail produk pesanan
$stmt = mysqli_prepare(
    $conn,
    "SELECT product_name, quantity, price, subtotal
     FROM order_details
     WHERE order_id = ?"
);

mysqli_stmt_bind_param($stmt, "i", $orderId);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$orderItems = [];

while ($item = mysqli_fetch_assoc($result)) {
    $orderItems[] = $item;
}

mysqli_stmt_close($stmt);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Pesanan Berhasil | Angel Buyby</title>

    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        .success-container {
            max-width: 650px;
            margin: 55px auto;
            padding: 0 20px;
        }

        .success-card {
            background: white;
            border-radius: 22px;
            padding: 35px;
            border: 1px solid #f0e5df;
            box-shadow: 0 10px 35px rgba(73,52,47,.07);
        }

        .success-icon {
            width: 75px;
            height: 75px;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: #edf5ed;
            color: #64876a;
            font-size: 35px;
        }

        .success-card h1 {
            font-family: 'Playfair Display', serif;
            text-align: center;
            font-size: 30px;
            color: #49342f;
            margin-bottom: 10px;
        }

        .success-subtitle {
            text-align: center;
            color: #99847b;
            font-size: 13px;
            line-height: 1.7;
            margin-bottom: 25px;
        }

        .order-code {
            background: #fff6f2;
            border: 1px dashed #d7aaa1;
            border-radius: 14px;
            padding: 17px;
            text-align: center;
            margin-bottom: 25px;
        }

        .order-code span {
            display: block;
            color: #99847b;
            font-size: 12px;
            margin-bottom: 7px;
        }

        .order-code strong {
            color: #a9796d;
            font-size: 20px;
            letter-spacing: 1px;
        }

        .success-section {
            border-top: 1px solid #f0e8e2;
            padding-top: 20px;
            margin-top: 20px;
        }

        .success-section h2 {
            font-size: 16px;
            margin-bottom: 15px;
        }

        .success-row {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            padding: 9px 0;
            font-size: 13px;
            color: #806c63;
        }

        .success-row strong {
            color: #49342f;
            text-align: right;
        }

        .success-product {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            padding: 12px 0;
            border-bottom: 1px solid #f5eee9;
            font-size: 13px;
        }

        .success-product span:last-child {
            color: #a9796d;
            font-weight: 700;
            white-space: nowrap;
        }

        .success-total {
            display: flex;
            justify-content: space-between;
            padding-top: 18px;
            font-size: 16px;
            font-weight: 800;
        }

        .success-total strong:last-child {
            color: #a9796d;
        }

        .status-pending {
            display: inline-block;
            padding: 6px 12px;
            background: #fff2dc;
            color: #a36e29;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
        }

        .success-note {
            margin-top: 20px;
            padding: 15px;
            border-radius: 12px;
            background: #fff7f3;
            color: #806c63;
            font-size: 12px;
            line-height: 1.7;
        }

        .success-button {
            display: block;
            text-align: center;
            padding: 15px;
            margin-top: 25px;
            background: #795548;
            color: white;
            border-radius: 13px;
            font-size: 13px;
            font-weight: 700;
            transition: .2s;
        }

        .success-button:hover {
            background: #c98f87;
        }

        @media(max-width: 500px) {
            .success-card {
                padding: 23px 18px;
            }

            .success-card h1 {
                font-size: 25px;
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
        <a href="logout.php">Logout</a>
    </nav>
</header>

<main class="success-container">

    <section class="success-card">

        <div class="success-icon">✓</div>

        <h1>Pesanan Berhasil!</h1>

        <p class="success-subtitle">
            Terima kasih sudah memesan di Angel Buyby.
            Pesanan kamu sudah berhasil disimpan.
        </p>

        <div class="order-code">
            <span>Kode Pesanan</span>
            <strong><?= htmlspecialchars($order["order_code"]); ?></strong>
        </div>

        <div class="success-section">

            <h2>Informasi Pesanan</h2>

            <div class="success-row">
                <span>Tanggal</span>
                <strong>
                    <?= date("d-m-Y H:i", strtotime($order["created_at"])); ?>
                </strong>
            </div>

            <div class="success-row">
                <span>Metode pembayaran</span>
                <strong><?= htmlspecialchars($order["payment_method"]); ?></strong>
            </div>

            <div class="success-row">
                <span>Status pembayaran</span>
                <strong>
                    <span class="status-pending">
                        <?= htmlspecialchars(ucfirst($order["payment_status"])); ?>
                    </span>
                </strong>
            </div>

            <div class="success-row">
                <span>Status pesanan</span>
                <strong>
                    <?= htmlspecialchars(ucfirst($order["order_status"])); ?>
                </strong>
            </div>

        </div>

        <div class="success-section">

            <h2>Detail Produk</h2>

            <?php foreach ($orderItems as $item) : ?>

                <div class="success-product">

                    <span>
                        <?= htmlspecialchars($item["product_name"]); ?>
                        × <?= (int) $item["quantity"]; ?>
                    </span>

                    <span>
                        Rp <?= number_format($item["subtotal"], 0, ',', '.'); ?>
                    </span>

                </div>

            <?php endforeach; ?>

            <div class="success-total">
                <span>Total Pesanan</span>
                <strong>
                    Rp <?= number_format($order["total_price"], 0, ',', '.'); ?>
                </strong>
            </div>

        </div>

        <div class="success-section">

            <h2>Alamat Pengiriman</h2>

            <p style="font-size:13px;line-height:1.8;color:#806c63;">
                <?= nl2br(htmlspecialchars($order["customer_address"])); ?>
            </p>

        </div>

        <div class="success-note">
            <?php if ($order["payment_method"] === "Bank Transfer") : ?>
                Pembayaran transfer masih menunggu konfirmasi admin.
                Silakan hubungi admin untuk mendapatkan informasi rekening
                dan petunjuk pembayaran.
            <?php else : ?>
                Silakan siapkan pembayaran ketika pesanan diterima.
                Status pembayaran akan diperbarui setelah pembayaran diterima.
            <?php endif; ?>
        </div>

        <a href="index.php" class="success-button">
            Kembali ke Home ♡
        </a>

    </section>

</main>

<footer>
    <p>© 2026 Angel Buyby. A little sweetness in every sip ♡</p>
</footer>

</body>
</html>