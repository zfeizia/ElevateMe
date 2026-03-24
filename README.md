# ⚡ ElevateMe - Sistem Manajemen Event & Tiket

> Tugas CPMK-01 — Pemrograman Web II  
> Program Studi Informatika, Universitas Jenderal Soedirman  
> Paket 7: Sistem Manajemen Event & Tiket

---

## 📋 Tentang Aplikasi

**ElevateMe** adalah platform manajemen event berbasis PHP OOP + PDO murni (tanpa framework) untuk reservasi tiket bootcamp, workshop, dan seminar pengembangan diri.

---

## ✅ Fitur Utama
**Multi-Role Authentication:** Sistem login untuk Admin, Organizer, dan Attendee.
* **Jelajah Event:** Katalog event dengan filter dinamis (Online/Offline, Kategori, Harga).
* **Manajemen Tiket:** Sistem harga *Early Bird* dan Reguler.
* **Check-in System:** Pemindaian tiket secara sistem untuk Organizer.
* **Real-time Dashboard:** Laporan kehadiran dan *progress bar* penjualan tiket.

---

## 🛠 Teknologi

- **Backend:** PHP 8.x Native OOP
- **Database:** MySQL / MariaDB via PDO Prepared Statement
- **Frontend:** HTML, CSS, Javascript
- **Server Lokal:** XAMPP / Laragon

---

## ⚙️ Cara Instalasi

### 1. Clone / Download Project

```bash
git clone https://github.com/username/elevate-me.git
```

Atau download ZIP dan extract ke folder XAMPP.

### 2. Pindahkan ke Folder XAMPP

```
C:/xampp/htdocs/elevate_me/
```

### 3. Import Database

1. Buka **phpMyAdmin** di `http://localhost/phpmyadmin`
2. Buat database baru bernama `elevate_me`
3. Klik tab **Import**
4. Pilih file `database.sql` dari root project
5. Klik **Go**

### 4. Sesuaikan Konfigurasi Database

Edit file `config/database.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'elevate_me');
define('DB_USER', 'root');      // ← sesuaikan username MySQL
define('DB_PASS', '');          // ← sesuaikan password MySQL
```

### 5. Sesuaikan BASE_URL

Edit file `config/bootstrap.php`:

```php
define('BASE_URL', '/elevate_me'); // ← sesuaikan nama subfolder
```

Jika project ada di root XAMPP (bukan subfolder), ubah jadi:
```php
define('BASE_URL', '');
```

### 6. Jalankan Aplikasi

Buka browser dan akses:
```
http://localhost/elevate_me/
```

---

## 👤 Akun Demo

Semua akun menggunakan password yang sama: **`password`**

| Role | Username | Email |
|------|----------|-------|
| Admin | `superadmin` | admin@elevateme.id |
| Organizer | `budi_org` | budi@elevateme.id |
| Organizer | `sari_org` | sari@elevateme.id |
| Attendee | `andi_user` | andi@gmail.com |
| Attendee | `dewi_user` | dewi@gmail.com |

---

## 📺 Demo Video

Link video YouTube (Unlisted): **https://www.youtube.com/watch?v=QMzxyZNCdvQ**

---

## 👨‍💻 Author

Nama   : Zainab Feizia  
NIM    : H1D024097
