
<?php

require_once "config/database.php";

// Hapus seluruh data session
$_SESSION = [];

// Hapus cookie session jika digunakan
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        "",
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Hancurkan session
session_destroy();

// Kembali ke homepage
header("Location: index.php");
exit;

?>