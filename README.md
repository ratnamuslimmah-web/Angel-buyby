# ☕ Angel Buyby — Coffee & Dessert Shop

**A little sweetness in every sip.**

Angel Buyby merupakan website toko kopi dan dessert berbasis web yang dikembangkan menggunakan PHP dan MySQL. Website ini menyediakan fitur autentikasi pengguna, katalog produk, keranjang belanja, hingga proses pemesanan.

Project ini dibuat untuk memenuhi tugas Ujian Tengah Semester (UTS).

---

## 📌 Informasi Project

| Keterangan | Detail |
|---|---|
| Nama Project | Angel Buyby |
| Kategori | Coffee & Dessert Shop |
| Bahasa Pemrograman | PHP, JavaScript |
| Frontend | HTML, CSS, JavaScript |
| Backend | PHP |
| Database | MySQL |
| Web Server | Apache (XAMPP) |
| API Testing | Bruno |

## ✨ Fitur Website

### 1. Autentikasi
- Registrasi akun pengguna.
- Login pengguna.
- Logout.
- Validasi data login dan registrasi.

### 2. Produk
- Menampilkan katalog produk.
- Menampilkan detail produk.
- Menampilkan informasi harga dan stok.

### 3. Keranjang Belanja
- Menambahkan produk ke keranjang.
- Mengubah jumlah produk.
- Menghapus produk dari keranjang.
- Menghitung total belanja.

### 4. Checkout
- Mengisi informasi pemesanan.
- Memilih metode pembayaran.
- Menyimpan pesanan ke database.
- Mengurangi stok produk setelah pesanan berhasil.

## 🛠️ Teknologi yang Digunakan

- HTML
- CSS
- JavaScript
- PHP
- MySQL
- XAMPP
- Bruno
- Git dan GitHub

---

# 📡 Project 2 — CRUD REST API

Project ini juga menyediakan API untuk mengelola data produk Angel Buyby.

**Base URL (Local):**

```text
http://localhost/angel-buyby/api/products/index.php
```

## Daftar Endpoint

| Method | Endpoint | Fungsi |
|---|---|---|
| GET | `/api/products/index.php` | Menampilkan seluruh produk |
| GET | `/api/products/index.php?id=1` | Menampilkan detail produk |
| POST | `/api/products/index.php` | Menambahkan produk |
| PUT | `/api/products/index.php?id=1` | Memperbarui produk |
| DELETE | `/api/products/index.php?id=1` | Menghapus produk |

## Contoh JSON POST

```json
{
  "category_id": 3,
  "name": "Test API Brownies",
  "description": "Produk percobaan CRUD API Angel Buyby",
  "price": 15000,
  "stock": 10,
  "image": "test-api.jpg",
  "status": "available"
}
```

## Pengujian API dengan Bruno

Pengujian dilakukan menggunakan aplikasi Bruno dengan hasil:

| No. | Pengujian | Hasil |
|---|---|---|
| 1 | GET All Products | 200 OK |
| 2 | GET Detail Product | 200 OK |
| 3 | POST Create Product | 201 Created |
| 4 | PUT Update Product | 200 OK |
| 5 | DELETE Product | 200 OK |
| 6 | GET Verify Deleted Product | 404 Not Found |

Status `404 Not Found` pada pengujian terakhir menunjukkan bahwa produk yang sebelumnya dihapus sudah tidak ditemukan.

## Dokumentasi Pengujian API

Screenshot hasil pengujian API dapat dilihat pada folder `screenshots/`.

- `get-all.png`
- `get-detail.png`
- `post-create.png`
- `put-update.png`
- `delete-product.png`
- `verify-delete.png`

---

# 🚀 Cara Menjalankan Project

## 1. Persiapan

Pastikan perangkat sudah memiliki:
- XAMPP
- Web browser
- Git (opsional)

## 2. Clone Repository

```bash
git clone https://github.com/USERNAME/angel-buyby.git
```

Ganti `USERNAME` dengan username GitHub pemilik repository.

## 3. Pindahkan Folder Project

Letakkan folder `angel-buyby` ke:

```text
C:\xampp\htdocs\
```

## 4. Jalankan XAMPP

Aktifkan:
- Apache
- MySQL

## 5. Buat Database

1. Buka `http://localhost/phpmyadmin`.
2. Buat database dengan nama `angel_buyby`.
3. Impor file database SQL yang disediakan dalam repository.

## 6. Konfigurasi Database

Buat file `config/database.php` berdasarkan `config/database.example.php`.

Sesuaikan konfigurasi koneksi database dengan pengaturan XAMPP lokal.

Untuk API, buat `api/config/database.php` berdasarkan file contoh konfigurasi API jika tersedia.

## 7. Jalankan Website

Buka browser dan akses:

http://localhost/angel-buyby/

## 8. Jalankan API

Akses endpoint berikut:

http://localhost/angel-buyby/api/products/index.php

Pengujian endpoint lainnya dapat dilakukan melalui Bruno.

---

## 📂 Struktur Folder

```text
angel-buyby/
│
├── api/
│   └── products/
│       └── index.php
│
├── assets/
│   ├── css/
│   │   └── style.css
│   ├── images/
│   └── js/
│       └── script.js
│
├── config/
│   └── database.example.php
│
├── Angel Buyby API/
│   ├── opencollection.yml
│   ├── Product.yml
│   ├── Detail Product.yml
│   ├── POST Create Product.yml
│   ├── Update Product.yml
│   └── Verify Deleted Product.yml
│
├── screenshots/
│
├── cart.php
├── checkout.php
├── detail.php
├── index.php
├── login.php
├── logout.php
├── order_success.php
├── register.php
└── README.md
```

---

## 👩‍💻 Developer

**Ratna Muslimmah Ahmad**

Project Ujian Tengah Semester (UTS) — Teknik Informatika.

---

© 2026 Angel Buyby. All Rights Reserved.
