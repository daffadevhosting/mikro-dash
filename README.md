Panel admin untuk mengelola pengguna Hotspot Mikrotik. Cocok digunakan di warung internet, kosan, kantor, atau jaringan hotspot rumahan.


![Dashboard Screenshot](/jekyll-fontend/screenshot-dashboard.png)

## 📷 Screenshot

| Login Admin | User List | Koneksi Test |
|-----------------|------------|-------------|
| ![](/jekyll-fontend/screenshot-login.png) | ![](/jekyll-fontend/screenshot-users.png) | ![](/jekyll-fontend/screenshot-koneksi.png) |

| Dashboard Mobile | User Mobile |
|-----------------|------------|
| ![](/jekyll-fontend/screenshot-dashMobile.png) | ![](/jekyll-fontend/screenshot-mobileUser.png) |

---

## 🛠️ Teknologi

- ⚡ HTML, Bootstrap 5.3
- 🧠 JavaScript + Fetch API (AJAX)
- 🐘 PHP 8.4.7 backend ringan
- 📡 Mikrotik API (PEAR2 API)

## Menjalankan Lokal

Prasyarat: PHP, Composer, Ruby/Bundler, dan konfigurasi Firebase/MikroTik di
`backend/.env` serta `jekyll-fontend/.env`.

```bash
cd backend && composer install
cd ../jekyll-fontend && bundle install
cd .. && ./run.sh
```

Buka `http://127.0.0.1:1111`. Backend berjalan di `http://127.0.0.1:8080`.
Port dapat diubah melalui `FRONTEND_PORT` dan `BACKEND_PORT`.

---