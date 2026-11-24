
<?php
require_once "config/database.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    if (
        $name === "" ||
        $email === "" ||
        $password === "" ||
        $confirm_password === ""
    ) {
        $error = "Semua kolom wajib diisi kecuali nomor telepon.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid.";
    } elseif (strlen($password) < 8) {
        $error = "Password minimal 8 karakter.";
    } elseif ($password !== $confirm_password) {
        $error = "Konfirmasi password tidak cocok.";
    } else {

        $check = mysqli_prepare(
            $conn,
            "SELECT id FROM users WHERE email = ?"
        );

        mysqli_stmt_bind_param($check, "s", $email);
        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);

        if (mysqli_stmt_num_rows($check) > 0) {
            $error = "Email sudah terdaftar. Gunakan email lain.";
        } else {

            $hashed_password = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $insert = mysqli_prepare(
                $conn,
                "INSERT INTO users (name, email, phone, password)
                 VALUES (?, ?, ?, ?)"
            );

            mysqli_stmt_bind_param(
                $insert,
                "ssss",
                $name,
                $email,
                $phone,
                $hashed_password
            );

            if (mysqli_stmt_execute($insert)) {
                header("Location: login.php?registered=1");
                exit;
            } else {
                $error = "Registrasi gagal. Silakan coba kembali.";
            }

            mysqli_stmt_close($insert);
        }

        mysqli_stmt_close($check);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Angel Buyby</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="auth-container">

    <div class="auth-card">
        <a href="index.php" class="auth-logo">Angel Buyby ♡</a>

        <h1>Create Account</h1>
        <p class="auth-subtitle">
            Join us and enjoy your sweetest moments.
        </p>

        <?php if ($error !== "") : ?>
            <div class="message error">
                <?= htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="register.php">

            <label>Full Name</label>
            <input type="text" name="name"
                placeholder="Your full name"
                value="<?= htmlspecialchars($_POST['name'] ?? ''); ?>"
                required>

            <label>Email Address</label>
            <input type="email" name="email"
                placeholder="example@email.com"
                value="<?= htmlspecialchars($_POST['email'] ?? ''); ?>"
                required>

            <label>Phone Number</label>
            <input type="tel" name="phone"
                placeholder="08xxxxxxxxxx"
                value="<?= htmlspecialchars($_POST['phone'] ?? ''); ?>">

            <label>Password</label>
            <input type="password" name="password"
                placeholder="Minimum 8 characters"
                minlength="8" required>

            <label>Confirm Password</label>
            <input type="password" name="confirm_password"
                placeholder="Repeat your password"
                minlength="8" required>

            <button type="submit" class="auth-button">
                Create Account ♡
            </button>

        </form>

        <p class="auth-switch">
            Already have an account?
            <a href="login.php">Login here</a>
        </p>

        <a href="index.php" class="back-home">
            ← Back to Home
        </a>
    </div>

</div>

</body>
</html>