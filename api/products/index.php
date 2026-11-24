
<?php

require_once __DIR__ . "/../config/database.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Fungsi untuk mengirim response JSON
function response($success, $message, $data = null, $statusCode = 200)
{
    http_response_code($statusCode);

    echo json_encode([
        "success" => $success,
        "message" => $message,
        "data" => $data
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    exit;
}

// Membaca HTTP method
$method = $_SERVER["REQUEST_METHOD"];

// Mengambil ID produk dari URL
$id = isset($_GET["id"]) ? filter_var($_GET["id"], FILTER_VALIDATE_INT) : null;

// Membaca data JSON dari request body
$input = json_decode(file_get_contents("php://input"), true);

// Validasi JSON untuk POST dan PUT
if (in_array($method, ["POST", "PUT"])) {
    if (!is_array($input)) {
        response(false, "Format JSON tidak valid", null, 400);
    }
}

try {

    // =========================
    // READ: GET
    // =========================
    if ($method === "GET") {

        // GET satu produk berdasarkan ID
        if (isset($_GET["id"])) {

            if (!$id || $id < 1) {
                response(false, "ID produk tidak valid", null, 400);
            }

            $stmt = mysqli_prepare(
                $conn,
                "SELECT * FROM products WHERE id = ?"
            );

            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);
            $product = mysqli_fetch_assoc($result);

            if (!$product) {
                response(false, "Produk tidak ditemukan", null, 404);
            }

            response(true, "Detail produk berhasil ditemukan", $product);

        } else {

            // GET semua produk
            $query = mysqli_query(
                $conn,
                "SELECT * FROM products ORDER BY id ASC"
            );

            $products = [];

            while ($row = mysqli_fetch_assoc($query)) {
                $products[] = $row;
            }

            response(true, "Daftar produk berhasil ditampilkan", $products);
        }
    }

    // =========================
    // CREATE: POST
    // =========================
    elseif ($method === "POST") {

        $name = trim($input["name"] ?? "");
        $description = trim($input["description"] ?? "");
        $price = $input["price"] ?? null;
        $stock = $input["stock"] ?? 0;
        $categoryId = $input["category_id"] ?? null;
        $image = $input["image"] ?? null;
        $status = $input["status"] ?? "available";

        if ($name === "" || !is_numeric($price) || $price <= 0) {
            response(false, "Nama dan harga produk wajib diisi dengan benar", null, 400);
        }

        if (!filter_var($stock, FILTER_VALIDATE_INT) && $stock !== 0 && $stock !== "0") {
            response(false, "Stok harus berupa bilangan bulat", null, 400);
        }

        if ($stock < 0) {
            response(false, "Stok tidak boleh negatif", null, 400);
        }

        if (!in_array($status, ["available", "unavailable"])) {
            response(false, "Status produk tidak valid", null, 400);
        }

        if ($categoryId !== null && filter_var($categoryId, FILTER_VALIDATE_INT) === false) {
            response(false, "Kategori tidak valid", null, 400);
        }

        $price = (float) $price;
        $stock = (int) $stock;

        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO products
            (category_id, name, description, price, stock, image, status)
            VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "issdiss",
            $categoryId,
            $name,
            $description,
            $price,
            $stock,
            $image,
            $status
        );

        mysqli_stmt_execute($stmt);

        $newId = mysqli_insert_id($conn);

        response(true, "Produk berhasil ditambahkan", [
            "id" => $newId,
            "name" => $name,
            "price" => $price,
            "stock" => $stock
        ], 201);
    }

    // =========================
    // UPDATE: PUT
    // =========================
    elseif ($method === "PUT") {

        if (!$id || $id < 1) {
            response(false, "ID produk wajib diisi dan harus valid", null, 400);
        }

        $name = trim($input["name"] ?? "");
        $description = trim($input["description"] ?? "");
        $price = $input["price"] ?? null;
        $stock = $input["stock"] ?? 0;
        $categoryId = $input["category_id"] ?? null;
        $image = $input["image"] ?? null;
        $status = $input["status"] ?? "available";

        if ($name === "" || !is_numeric($price) || $price <= 0) {
            response(false, "Nama dan harga produk wajib diisi dengan benar", null, 400);
        }

        if (!filter_var($stock, FILTER_VALIDATE_INT) && $stock !== 0 && $stock !== "0") {
            response(false, "Stok harus berupa bilangan bulat", null, 400);
        }

        if ($stock < 0) {
            response(false, "Stok tidak boleh negatif", null, 400);
        }

        if (!in_array($status, ["available", "unavailable"])) {
            response(false, "Status produk tidak valid", null, 400);
        }

        if ($categoryId !== null && filter_var($categoryId, FILTER_VALIDATE_INT) === false) {
            response(false, "Kategori tidak valid", null, 400);
        }

        $price = (float) $price;
        $stock = (int) $stock;

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE products
            SET category_id = ?, name = ?, description = ?,
                price = ?, stock = ?, image = ?, status = ?
            WHERE id = ?"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "issdissi",
            $categoryId,
            $name,
            $description,
            $price,
            $stock,
            $image,
            $status,
            $id
        );

        mysqli_stmt_execute($stmt);

        if (mysqli_stmt_affected_rows($stmt) === 0) {
            $check = mysqli_prepare(
                $conn,
                "SELECT id FROM products WHERE id = ?"
            );

            mysqli_stmt_bind_param($check, "i", $id);
            mysqli_stmt_execute($check);

            $result = mysqli_stmt_get_result($check);

            if (!mysqli_fetch_assoc($result)) {
                response(false, "Produk tidak ditemukan", null, 404);
            }
        }

        response(true, "Produk berhasil diperbarui", [
            "id" => $id,
            "name" => $name,
            "description" => $description,
            "price" => $price,
            "stock" => $stock,
            "category_id" => $categoryId,
            "image" => $image,
            "status" => $status
        ]);
    }

    // =========================
    // DELETE: DELETE
    // =========================
    elseif ($method === "DELETE") {

        if (!$id || $id < 1) {
            response(false, "ID produk wajib diisi dan harus valid", null, 400);
        }

        $stmt = mysqli_prepare(
            $conn,
            "DELETE FROM products WHERE id = ?"
        );

        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);

        if (mysqli_stmt_affected_rows($stmt) === 0) {
            response(false, "Produk tidak ditemukan", null, 404);
        }

        response(true, "Produk berhasil dihapus", [
            "id" => $id
        ]);
    }

    // =========================
    // METHOD TIDAK DIDUKUNG
    // =========================
    else {
        header("Allow: GET, POST, PUT, DELETE");
        response(false, "HTTP method tidak didukung", null, 405);
    }

} catch (mysqli_sql_exception $e) {

    response(false, "Terjadi kesalahan pada database. Periksa data yang dikirim.", null, 500);

}