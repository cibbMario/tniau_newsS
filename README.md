# Portal Berita TNI AU

Sistem manajemen berita dengan alur review berjenjang: **User A → User B → User C**.

## 1. Instalasi

1. Salin folder `tniau-news` ke dalam server (XAMPP/Laragon: `htdocs/tniau-news`).
2. Buat database lewat phpMyAdmin, lalu import `sql/schema.sql`
   (ini akan otomatis membuat database `tniau_news` beserta semua tabel).
3. Buka `config/db.php`, sesuaikan `DB_USER` dan `DB_PASS` dengan MySQL-mu.
4. Buka `config/config.php`, sesuaikan `BASE_URL` dengan path folder di server
   (contoh: jika diakses lewat `http://localhost/tniau-news`, maka `BASE_URL` = `/tniau-news`).
5. Pastikan folder `uploads/` dapat ditulis oleh server (permission 755/775).
6. Jalankan `http://localhost/tniau-news/setup_users.php` di browser **satu kali**
   untuk membuat 3 akun contoh. Setelah muncul konfirmasi, **hapus file `setup_users.php`**.
7. Login lewat `http://localhost/tniau-news/login.php` dengan salah satu akun:

   | Username    | Password      | Role                  |
   |-------------|---------------|------------------------|
   | reporter1   | password123   | User A (Reporter)     |
   | editor1     | password123   | User B (Editor)       |
   | approver1   | password123   | User C (Petinggi)     |

   **Segera ganti password** setelah login pertama (bisa lewat phpMyAdmin dengan
   `password_hash()` baru, atau minta dibuatkan halaman ganti password menyusul).

## 2. Alur Sistem (Workflow)

```
User A buat berita
      │
      ├─ Simpan sebagai DRAFT (belum masuk antrean siapa pun)
      │
      └─ Ajukan Review ──► status: pending_b
                                │
                        User B memeriksa
                        (bisa comment / correction)
                                │
                ┌───────────────┴───────────────┐
              Oke                          Tidak Oke
                │                                │
        status: pending_c                status: revision_b
                │                          + notifikasi ke User A
        User C memeriksa                        │
        (bisa comment / correction)      User A revisi & ajukan ulang
                │                          → balik ke pending_b
        ┌───────┴───────┐
      Oke            Tidak Oke
        │                  │
  status: published   status: revision_c
  + notifikasi User A  + notifikasi ke User A
                              │
                      User A revisi & ajukan ulang
                       → balik ke pending_c (langsung ke C lagi)
```

## 3. Struktur Folder

```
tniau-news/
├── config/
│   ├── db.php          # koneksi PDO
│   └── config.php      # session, BASE_URL, load semua include
├── includes/
│   ├── auth.php        # login/logout/requireRole
│   ├── functions.php   # notifikasi, status berita, upload gambar
│   └── sidebar.php     # komponen sidebar kiri
├── sql/
│   └── schema.sql       # struktur database
├── uploads/              # gambar berita ter-upload
├── assets/css/style.css  # semua styling (sidebar, hover, form, dsb)
├── login.php / logout.php
├── dashboard.php         # ringkasan sesuai role
├── news_create.php       # User A: buat berita
├── news_edit.php         # User A: edit / revisi berita
├── news_view.php         # detail berita + komentar/koreksi + tombol Oke/Tidak Oke
├── news_list.php         # daftar berita (+ filter draft)
├── search.php            # pencarian berita
├── comment_action.php    # simpan komentar / koreksi
├── review_action.php     # inti alur approve/reject B & C
├── notifications.php     # daftar notifikasi
└── setup_users.php       # buat akun contoh (hapus setelah dipakai)
```

## 4. Hak Akses Tiap Role

| Fitur                          | User A | User B | User C |
|--------------------------------|:------:|:------:|:------:|
| Buat berita baru                | ✅     | ❌     | ❌     |
| Edit berita (saat draft/revisi) | ✅     | ❌     | ❌     |
| Simpan draft                    | ✅     | ❌     | ❌     |
| Ajukan / ajukan ulang review    | ✅     | ❌     | ❌     |
| Lihat berita yang diajukan      | ✅ (miliknya) | ✅ | ✅ |
| Comment                         | ✅     | ✅     | ✅     |
| Correction (koreksi kata)       | ❌     | ✅     | ✅     |
| Tombol Oke / Tidak Oke          | ❌     | ✅ (saat pending_b) | ✅ (saat pending_c) |
| Terima notifikasi status        | ✅     | ✅ (berita baru masuk) | ✅ (lolos dari B) |

## 5. Catatan Keamanan Minimal yang Sudah Diterapkan

- Password di-hash dengan `password_hash()` / diverifikasi dengan `password_verify()`.
- Semua query pakai **PDO prepared statement** (aman dari SQL Injection).
- Semua output di-escape dengan `htmlspecialchars()` lewat fungsi `e()`.
- Validasi tipe & ukuran file saat upload gambar (jpg/png/webp, maks 5MB).
- Proteksi halaman per-role lewat `requireRole()`.

## 6. Yang Bisa Ditambahkan Selanjutnya

- Halaman ganti password.
- Rich text editor yang lebih lengkap (mis. TinyMCE/Quill) menggantikan `contenteditable` sederhana.
- Highlight kata yang dikoreksi langsung di teks (saat ini koreksi berupa catatan di kolom komentar).
- Log/riwayat lengkap per berita (tabel `news_history` sudah tersedia, tinggal dibuat halamannya).
