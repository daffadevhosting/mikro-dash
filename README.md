# Mikro Dash

Mikro Dash adalah panel administrasi untuk mengelola user Hotspot MikroTik. Aplikasi ini dapat digunakan untuk jaringan hotspot di kos, kantor, warung internet, atau jaringan rumahan.

Panel menyediakan pengelolaan router, profile bandwidth, paket Hotspot, user Hotspot, topup, koneksi router, dashboard statistik, dan riwayat transaksi.

## Fitur

- Login menggunakan Firebase Authentication.
- Otorisasi role `admin` dan `sales` melalui Firebase ID token.
- Pengelolaan beberapa router MikroTik dan pemilihan router aktif.
- Pembacaan user Hotspot langsung dari MikroTik.
- Pembuatan dan pengelolaan profile bandwidth.
- Pembuatan paket Hotspot dengan harga, masa aktif, batas waktu, batas quota, shared users, dan session timeout.
- Topup user dengan reset counter waktu dan quota.
- Riwayat transaksi, pencarian, filter metode pembayaran, pagination, dan ringkasan bulanan.
- Statistik dashboard dan data income dari MySQL.

## Arsitektur

```text
Browser
	|
	| Jekyll static frontend + Firebase Web SDK
	v
PHP backend API
	|-- Firebase Authentication: validasi ID token dan role
	|-- MySQL: metadata, user mapping, paket, dan transaksi
	|-- PEAR2 RouterOS: komunikasi dengan MikroTik
	v
MikroTik Hotspot
```

Frontend berada di `jekyll-fontend/`. Backend PHP berada di `backend/` dan dijalankan melalui `backend/router.php`. Firebase Web SDK dipakai oleh browser untuk login dan konfigurasi client. Service account Firebase hanya boleh dipakai di backend.

## Teknologi

- HTML, JavaScript, Bootstrap 5.3, dan Jekyll.
- PHP 8.x dengan Composer.
- MySQL atau MariaDB.
- Firebase Authentication dan Realtime Database sebagai sumber konfigurasi/legacy import.
- PEAR2 RouterOS untuk API MikroTik.
- `kreait/firebase-php` untuk validasi token dan integrasi Firebase server-side.

## Struktur Direktori

```text
backend/
	api/              Koneksi database, Firebase, dan authorization
	auth/             Login dan service account Firebase
	database/         Schema dan utilitas import Firebase
	php/              Endpoint API aplikasi
	router.php        Router PHP untuk development server
	.env              Konfigurasi lokal, jangan commit
jekyll-fontend/
	_includes/        Komponen dan script halaman
	_layouts/         Layout Jekyll
	_plugins/         Loader environment Jekyll
	assets/           CSS, JavaScript, dan gambar
	dashboard/        Halaman dashboard
	users/            Halaman user dan topup
	history/          Halaman riwayat transaksi
	settings/         Halaman konfigurasi router dan paket
run.sh              Menjalankan backend dan frontend lokal
```

## Prasyarat

- PHP 8.x dengan extension PDO MySQL, cURL, OpenSSL, JSON, dan mbstring.
- Composer.
- Ruby dan Bundler.
- MySQL atau MariaDB.
- MikroTik dengan API RouterOS aktif dan user API khusus aplikasi.
- Project Firebase dengan Firebase Authentication aktif.

## Instalasi

### 1. Install dependency backend

```bash
cd backend
composer install
```

### 2. Install dependency frontend

```bash
cd ../jekyll-fontend
bundle install
```

### 3. Konfigurasi backend

Salin template environment yang tersedia:

```bash
cd ../backend
cp sample.env .env
```

Isi `backend/.env`:

```dotenv
ROUTER_IP=192.168.88.1
ROUTER_USER=mikroapi
ROUTER_PASS=ganti-dengan-password-kuat
FIREBASE_DB=https://project-id-default-rtdb.region.firebasedatabase.app

DB_HOST=127.0.0.1
DB_PORT=3306
DB_SOCKET=
DB_DATABASE=hotspot_data
DB_USERNAME=hotspot_app
DB_PASSWORD=ganti-dengan-password-database
```

Jika beberapa router digunakan, simpan router melalui halaman Settings. Router default di MySQL akan dipakai oleh backend.

### 4. Siapkan Firebase service account

Letakkan service account backend di:

```text
backend/auth/secret/firebase-adminsdk.json
```

File ini berisi private key dan tidak boleh masuk Git, web root, atau frontend. Gunakan service account dari project Firebase yang sama dengan `FIREBASE_DB` dan Firebase Authentication.

### 5. Siapkan frontend environment

Buat `jekyll-fontend/.env` berdasarkan project Firebase yang sama:

```dotenv
api_key=AIza...
auth_domain=project-id.firebaseapp.com
project_id=project-id
database_url=https://project-id-default-rtdb.region.firebasedatabase.app
storage_bucket=project-id.firebasestorage.app
sender_id=123456789
app_id=1:123456789:web:abcdef
measure_id=G-XXXXXXXXXX
localurl=http://127.0.0.1:8080
```

Jangan mencampur konfigurasi project lama dengan project aktif. `apiKey` frontend bukan pengganti private key service account.

### 6. Siapkan database

Buat database lalu jalankan schema:

```bash
mysql -u root -p -e "CREATE DATABASE hotspot_data CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u hotspot_app -p hotspot_data < backend/database/schema.sql
```

Schema utama membuat tabel `firebase_users`, `routers`, `bandwidths`, `packages`, `hotspot_users`, dan `topup_transactions`.

## Menjalankan Lokal

Dari root project:

```bash
./run.sh
```

Default URL:

- Frontend: `http://127.0.0.1:1111`
- Backend: `http://127.0.0.1:8080`

Port dan host dapat diubah:

```bash
FRONTEND_HOST=127.0.0.1 FRONTEND_PORT=1111 \
BACKEND_HOST=127.0.0.1 BACKEND_PORT=8080 ./run.sh
```

`run.sh` otomatis mencari port berikutnya jika port default sedang digunakan. Tekan `Ctrl+C` untuk menghentikan kedua service.

Untuk menjalankan service secara manual:

```bash
cd backend
php -S 127.0.0.1:8080 router.php

cd ../jekyll-fontend
bundle exec jekyll serve --host 127.0.0.1 --port 1111 --livereload false
```

## Alur Login dan Authorization

1. Browser login melalui Firebase Authentication.
2. `auth.js` mengambil Firebase ID token.
3. Request yang membutuhkan akses backend mengirim header `Authorization: Bearer <firebase-id-token>`.
4. `backend/api/authorization.php` memvalidasi token menggunakan service account.
5. UID Firebase dicocokkan ke tabel `firebase_users` untuk menentukan role.
6. Endpoint yang dilindungi menerima role `admin`, `sales`, atau keduanya sesuai kebutuhan.

Jangan mengandalkan CORS atau data dari browser untuk menentukan role. Validasi harus tetap dilakukan di backend.

## Alur Topup

Topup aktif menggunakan satu jalur:

```text
jekyll-fontend/_includes/topupModal.html
	-> backend/php/get_all_users.php
	-> backend/php/get_packages.php
	-> backend/php/topup_user.php
	-> MikroTik + MySQL
```

`topup_user.php` memvalidasi token, memastikan profile ada di MikroTik, membaca metadata paket dari MySQL, mencari user, mengaktifkan user, menerapkan profile, menerapkan batas waktu dan quota, mereset counter, lalu menyimpan transaksi sukses ke MySQL.

Endpoint lama `topup.php` dan include lama `topupUsers.html` sudah tidak digunakan dan telah dihapus. Jangan menghidupkan kembali dua jalur tersebut karena kontrak payload dan sumber konfigurasi berbeda.

## Aturan Paket MikroTik

| Pengaturan | Fungsi |
| --- | --- |
| `Session Timeout` | Membatasi durasi satu sesi login. Setelah habis, sesi diputus dan user dapat login lagi jika batas total belum habis. |
| `Time Limit` | Diterapkan sebagai `limit-uptime`, yaitu total waktu pemakaian kumulatif user. Setelah habis, user tidak dapat memakai akses sampai topup. |
| `Quota Limit` | Diterapkan sebagai `limit-bytes-total`, yaitu total volume data kumulatif user. Setelah habis, user tidak dapat memakai akses sampai topup. |

Contoh paket 12 jam dan 5 GB:

```text
Masa Aktif: 1 hari
Time Limit: 12h
Quota Limit: 5G
Session Timeout: 01:00:00
```

Artinya user dapat login dalam sesi maksimal satu jam, dengan total pemakaian maksimal 12 jam atau 5 GB, mana yang tercapai lebih dulu. Saat topup, counter waktu dan data di-reset.

`Session Timeout` saja tidak cukup untuk mencegah login ulang. Untuk mencegah akses melewati paket, isi `Time Limit`, `Quota Limit`, atau keduanya.

## Penyimpanan Data

- **MikroTik** adalah sumber utama user Hotspot, profile, koneksi, counter, dan status online.
- **MySQL** menyimpan metadata router, bandwidth, paket, mapping user, dan transaksi topup.
- **Firebase Authentication** menyimpan identitas login dan menerbitkan ID token.
- **Firebase Realtime Database** masih digunakan untuk konfigurasi frontend dan kompatibilitas import lama, tetapi transaksi topup aktif dibaca dari MySQL.

Riwayat transaksi menggunakan alur `topup_user.php` -> `topup_transactions` -> `get_transactions.php` -> `history/index.html`.

## Endpoint Penting

| Endpoint | Fungsi |
| --- | --- |
| `api/authorization.php` | Validasi Firebase ID token dan role |
| `api/connect.php` | Koneksi MySQL, Firebase, dan router aktif |
| `php/get_all_users.php` | Membaca user Hotspot MikroTik |
| `php/get_packages.php` | Membaca profile Hotspot dan metadata paket |
| `php/topup_user.php` | Menjalankan topup user dan mencatat transaksi |
| `php/get_transactions.php` | Membaca riwayat transaksi dari MySQL |
| `php/get_income.php` | Menghitung income dari transaksi MySQL |
| `php/get_routers.php` | Membaca router terdaftar |
| `php/save_router.php` | Menyimpan router |
| `php/activate_router.php` | Menentukan router default |
| `php/save_profile.php` | Menyimpan profile bandwidth |
| `php/save_package.php` | Menyimpan paket dan profile MikroTik |

## Troubleshooting

### `Login diperlukan` atau `Akses ditolak`

- Pastikan Firebase Authentication berhasil login.
- Pastikan request mengirim header `Authorization: Bearer <token>`.
- Pastikan UID pengguna ada di `firebase_users`.
- Pastikan role database adalah `admin` atau `sales` sesuai endpoint.

### `FIREBASE_DB belum dikonfigurasi`

- Pastikan `backend/.env` ada.
- Pastikan `FIREBASE_DB` berisi URL Realtime Database yang benar.
- Pastikan service account berasal dari project yang sama.

### MikroTik tidak dapat terhubung

- Pastikan API RouterOS aktif.
- Pastikan host, port, username, dan password benar.
- Pastikan firewall MikroTik mengizinkan koneksi API dari server backend.
- Gunakan user API khusus dengan permission minimum yang diperlukan.

### Topup sukses tetapi user tetap dapat login setelah batas habis

- Pastikan paket memiliki `Time Limit`, `Quota Limit`, atau `Masa Aktif`.
- Pastikan profile user menerima `session-timeout`.
- Pastikan counter user di-reset hanya ketika topup berhasil.
- Periksa nilai `limit-uptime` dan `limit-bytes-total` di MikroTik.
- Profile lama yang masih memiliki script scheduler perlu disimpan ulang dari Settings agar script lama dibersihkan.

### Riwayat transaksi kosong

- Pastikan tabel `topup_transactions` sudah dibuat.
- Pastikan topup berhasil sampai tahap insert MySQL.
- Pastikan frontend memanggil backend melalui `localurl` yang benar.
- Periksa koneksi MySQL dan log PHP.

## Keamanan

- Jangan commit `backend/.env`, `jekyll-fontend/.env`, atau service account JSON.
- Jangan menaruh Firebase private key di frontend.
- Private key service account yang pernah terekspos harus segera dicabut dan dibuat ulang.
- Jangan memakai password MikroTik yang dibagikan untuk penggunaan produksi.
- Gunakan user MikroTik khusus API dengan permission minimum.
- Batasi CORS di production ke domain frontend resmi. `Access-Control-Allow-Origin: *` hanya sesuai untuk development terbatas.
- Nonaktifkan `display_errors` di production agar detail internal tidak dikirim ke browser.
- Gunakan HTTPS untuk frontend, backend, dan koneksi API publik.
- Jangan mencatat password router, token, atau payload sensitif ke log.

## Import Data Lama

Panduan import data Firebase lama tersedia di [`backend/database/README.md`](backend/database/README.md). Setelah import, verifikasi struktur data sebelum memakai fitur topup di production.

## Status Verifikasi

Validasi source yang digunakan:

```bash
php -l backend/php/topup_user.php
php -l backend/php/save_package.php
cd jekyll-fontend && bundle exec jekyll build
```

Pengujian terhadap router MikroTik dan database production harus dilakukan di lingkungan yang memiliki akses jaringan dan kredensial yang benar.

---
