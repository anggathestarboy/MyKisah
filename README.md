# My Kisah App

**My Kisah** adalah aplikasi web berbasis **PHP Native** yang memungkinkan pengguna untuk berbagi kisah/cerita secara sederhana dengan sistem autentikasi, profil pengguna, dan fitur personalisasi seperti avatar, bio, serta *last seen*.

Project ini dibuat sebagai latihan dan pengembangan aplikasi web menggunakan PHP tanpa framework.

---

## ✨ Fitur Utama

* 🔐 **Authentication**

  * Login & Logout menggunakan PHP Session
* 👤 **Profil Pengguna**

  * Edit nama
  * Bio pengguna
  * Upload avatar
  * Last seen otomatis
* 🖼️ **Upload Avatar**

  * Penyimpanan gambar ke folder `uploads/`
* 🕒 **Last Seen**

  * Tercatat otomatis saat user aktif
* 📝 **Manajemen Kisah** *(opsional / dapat dikembangkan)*

  * Menulis dan menampilkan kisah

---

## 🛠️ Teknologi yang Digunakan

* **PHP Native**
* **MySQL** (PDO)
* **HTML5**
* **CSS3**
* **JavaScript (Vanilla)**

---

## 📂 Struktur Folder

```
mykisah/
├── assets/
│   └── css, img, js
├── uploads/
│   └── avatar pengguna
├── config/
│   └── db.php
├── auth/
│   ├── login.php
│   └── logout.php
├── profile.php
├── index.php
└── README.md
```

---

## 🗄️ Struktur Database (Contoh)

### Tabel `users`

| Field      | Tipe Data    | Keterangan         |
| ---------- | ------------ | ------------------ |
| id         | INT (PK)     | ID user            |
| name       | VARCHAR(100) | Nama user          |
| username   | VARCHAR(100) | Username           |
| password   | VARCHAR(255) | Password (hash)    |
| bio        | TEXT         | Bio pengguna       |
| avatar     | VARCHAR(255) | Nama file avatar   |
| last_seen  | DATETIME     | Aktivitas terakhir |
| created_at | DATETIME     | Tanggal daftar     |

---

## ⚙️ Cara Instalasi

1. Clone repository ini

   ```bash
   git clone https://github.com/username/mykisah.git
   ```
2. Pindahkan folder ke `htdocs` (XAMPP)
3. Buat database MySQL (contoh: `mykisah`)
4. Import struktur tabel ke database
5. Atur koneksi database di `config/db.php`
6. Jalankan melalui browser

   ```
   http://localhost/mykisah
   ```

---

## 🔐 Keamanan

* Password disimpan menggunakan `password_hash()`
* Validasi file upload avatar
* Session digunakan untuk autentikasi

---

## 🚀 Rencana Pengembangan

* Sistem posting kisah
* Like & komentar
* Follow user
* Notifikasi
* Versi PWA

---
