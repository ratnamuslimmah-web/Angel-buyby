
<?php
require_once "config/database.php";

$error = "";

if (isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    $stmt = mysqli_prepare(
        $conn,
        "SELECT id, name, email, password, role
         FROM users
         WHERE email = ?"
    );

    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    if ($user && password_verify($password, $user["password"])) {

        session_regenerate_id(true);

        $_SESSION["user_id"] = $user["id"];
        $_SESSION["user_name"] = $user["name"];
        $_SESSION["user_email"] = $user["email"];
        $_SESSION["user_role"] = $user["role"];

        header("Location: index.php");
        exit;

    } else {
        $error = "Email atau password salah.";
    }

    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Angel Buyby</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="auth-container">

    <div class="auth-card">
        <a href="index.php" class="auth-logo">Angel Buyby ♡</a>

        <h1>Welcome Back!</h1>
        <p class="auth-subtitle">
            We're happy to see you again.
        </p>

        <?php if (isset($_GET["registered"])) : ?>
            <div class="message success">
                Registration successful! Please login.
            </div>
        <?php endif; ?>

        <?php if ($error !== "") : ?>
            <div class="message error">
                <?= htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">

            <label>Email Address</label>
            <input type="email" name="email"
                placeholder="example@email.com"
                required>

            <label>Password</label>
            <input type="password" name="password"
                placeholder="Enter your password"
                required>

            <button type="submit" class="auth-button">
                Login ♡
            </button>

        </form>

        <p class="auth-switch">
            Don't have an account?
            <a href="register.php">Register here</a>
        </p>

        <a href="index.php" class="back-home">
            ← Back to Home
        </a>
    </div>

</div>

</body>
</html>