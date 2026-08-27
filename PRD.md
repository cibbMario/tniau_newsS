# Product Requirements Document
## Portal Berita TNI AU

**Status:** Draft berdasarkan implementasi saat ini  
**Tanggal:** 27 Agustus 2026  
**Platform:** Aplikasi web PHP, MySQL, HTML/CSS/JavaScript  
**Target pengguna:** Reporter Lanud, Editor, Petinggi/Approver, Peninjau Kejelasan, Administrator Dispenau

## 1. Ringkasan Produk

Portal Berita TNI AU adalah sistem internal untuk membuat, memeriksa, menyetujui, mempublikasikan, dan memantau berita dari jajaran TNI Angkatan Udara. Sistem menggunakan alur editorial berjenjang dan menyediakan dashboard, pencarian, notifikasi, komentar/koreksi, statistik, peta persebaran lanud, laporan, galeri media, manajemen pengguna, serta audit trail.

Aplikasi saat ini menggunakan login wajib. Halaman utama mengarahkan pengguna yang belum login ke halaman login dan pengguna yang sudah login ke dashboard.

## 2. Tujuan Produk

1. Menstandarkan proses pembuatan dan publikasi berita TNI AU.
2. Memastikan berita melewati pemeriksaan editorial dan persetujuan sesuai kewenangan.
3. Memudahkan pelacakan status, riwayat perubahan, komentar, dan koreksi berita.
4. Menyediakan ringkasan data publikasi untuk kebutuhan monitoring dan pelaporan.
5. Menghubungkan berita wilayah dengan lanud asalnya pada peta dan notifikasi.

## 3. Ruang Lingkup

### Termasuk

- Autentikasi dan manajemen sesi.
- Manajemen berita dan media pendukung.
- Workflow review dan publikasi.
- Komentar, koreksi, notifikasi, dan riwayat status.
- Dashboard, pencarian, statistik, peta lanud, dan laporan CSV.
- Galeri media dan media monitoring.
- Manajemen akun, status aktif, reset password, dan audit log.

### Tidak termasuk dalam versi saat ini

- Portal publik tanpa login.
- Aplikasi mobile native.
- Integrasi otomatis dengan media sosial atau situs berita eksternal.
- Editor teks kaya penuh seperti TinyMCE atau Quill.
- Highlight koreksi langsung pada isi berita.
- Pengiriman notifikasi melalui email, WhatsApp, atau SMS.

## 4. Peran dan Hak Akses

| Role | Nama | Tanggung jawab utama |
|---|---|---|
| A | Reporter | Membuat berita, menyimpan draft, mengedit berita miliknya, mengajukan berita, dan memperbaiki berita yang dikembalikan. |
| B | Editor | Memeriksa berita pada tahap `pending_b`, memberi komentar/koreksi, menyetujui, atau meminta revisi. Editor menerima berita sesuai penugasan lanud. |
| C | Petinggi/Approver | Memeriksa berita pada tahap `pending_c`, memberi komentar/koreksi, menyetujui, atau meminta revisi. |
| D | Peninjau Kejelasan | Memantau berita, memberi komentar/koreksi, dan memproses berita pada tahap `pending_d` atau `pending_c`. |
| E | Administrator | Mengelola pengguna, melihat seluruh data, serta dapat menggantikan proses persetujuan sesuai kebutuhan administrasi. |

## 5. Alur Bisnis Utama

### 5.1 Pembuatan dan publikasi berita

1. Reporter mengisi judul, isi, lanud/wilayah, sumber media, waktu terbit, sentimen, prioritas, klasifikasi, aktor, tag, topik, keyword, dan gambar.
2. Reporter memilih:
   - **Simpan draft:** status menjadi `draft`.
   - **Ajukan review:** status menjadi `pending_b`.
3. Editor yang ditugaskan pada lanud yang sama menerima notifikasi untuk berita `pending_b`.
4. Editor memilih:
   - **Setujui:** status menjadi `pending_c`.
   - **Minta revisi:** status menjadi `revision_b`, disertai catatan dan notifikasi kepada Reporter.
5. Petinggi memproses berita `pending_c`:
   - **Setujui:** status menjadi `published`.
   - **Minta revisi:** status menjadi `revision_c`.
6. Peninjau Kejelasan memproses berita `pending_d` atau `pending_c` sesuai kebutuhan:
   - **Setujui:** status menjadi `published`.
   - **Minta revisi:** status menjadi `revision_d`.
7. Reporter memperbaiki berita dan mengajukan ulang. Berita kembali ke tahap review sesuai status revisinya.
8. Administrator dapat menangani persetujuan pada tahap yang diizinkan sistem.

### 5.2 Status berita

| Status | Arti |
|---|---|
| `draft` | Berita masih disusun dan belum masuk antrean review. |
| `pending_b` | Menunggu pemeriksaan Editor. |
| `revision_b` | Dikembalikan Editor untuk diperbaiki Reporter. |
| `pending_c` | Menunggu persetujuan Petinggi atau proses Peninjau Kejelasan. |
| `revision_c` | Dikembalikan Petinggi untuk diperbaiki Reporter. |
| `pending_d` | Menunggu pemeriksaan Peninjau Kejelasan. |
| `revision_d` | Dikembalikan Peninjau Kejelasan untuk diperbaiki Reporter. |
| `published` | Berita telah disetujui dan dipublikasikan di sistem. |

## 6. Kebutuhan Fungsional

### FR-01 Autentikasi

- Pengguna dapat login dengan username dan password.
- Password disimpan menggunakan hash.
- Sistem membatasi sesi idle dan sesi maksimum.
- Sistem memiliki perlindungan CSRF dan pembatasan percobaan login.
- Pengguna dapat logout dan mengganti password.

### FR-02 Manajemen berita

- Reporter dapat membuat, menyimpan, mengedit, mengajukan, dan mengajukan ulang berita.
- Berita mendukung gambar utama dan galeri gambar.
- Sistem menghasilkan slug dan label penulis berdasarkan lanud.
- Pengguna berwenang dapat melihat detail berita, status, metadata, dan media pendukung.
- Pengguna berwenang dapat mencetak format siaran pers.

### FR-03 Review editorial

- Editor, Petinggi, Peninjau Kejelasan, dan Administrator dapat melihat berita sesuai kewenangannya.
- Review dapat menghasilkan persetujuan atau permintaan revisi.
- Permintaan revisi wajib dapat disertai catatan.
- Setiap perubahan status dicatat ke `news_history`.
- Berita yang sudah dipublikasikan dapat diturunkan oleh peran yang berwenang.

### FR-04 Komentar dan koreksi

- Pengguna dapat memberikan komentar pada berita yang dapat diakses.
- Reviewer dapat menyimpan catatan bertipe komentar atau koreksi.
- Pemilik berita menerima notifikasi ketika pengguna lain memberikan komentar atau koreksi.

### FR-05 Notifikasi

- Sistem membuat notifikasi ketika berita masuk tahap review, disetujui, ditolak, direvisi, dipublikasikan, atau diturunkan.
- Pengguna dapat melihat daftar notifikasi dan menandai notifikasi sebagai terbaca.
- Badge menampilkan jumlah notifikasi belum terbaca.
- Editor hanya menerima dan melihat notifikasi berita yang `news.wilayah`-nya sama dengan `users.lanud`.
- Notifikasi mengarah ke detail berita terkait.

### FR-06 Dashboard dan pencarian

- Dashboard menampilkan total berita, berita terbit, berita menunggu review, pengguna, distribusi media, dan sentimen.
- Pengguna dapat melihat daftar berita berdasarkan peran dan status.
- Pengguna dapat mencari berita berdasarkan judul, isi, atau nama penulis.
- User D memiliki halaman monitoring berita dengan KPI, filter status, pencarian, dan daftar berita.

### FR-07 Statistik dan peta lanud

- Statistik menyediakan ringkasan berita, tren, aktor, dan peta sebaran.
- Peta menampilkan daftar lanud yang tersedia di sistem beserta kota, koordinat, Koopsud, dan jumlah berita.
- Lanud tanpa berita tetap ditampilkan dengan jumlah publikasi nol.
- Rekap peta menampilkan jumlah berita dan sentimen per lanud.
- Daftar lanud pada form berita dan peta harus berasal dari sumber data yang sama.

### FR-08 Laporan dan media

- Pengguna dapat menyaring dan mengunduh data berita dalam format CSV.
- Pengguna dapat melihat galeri gambar utama dan gambar pendukung.
- Pengguna dapat mencatat dan melihat media monitoring yang terkait dengan berita.

### FR-09 Manajemen pengguna dan audit

- Administrator atau Petinggi dapat membuat, mengubah, menonaktifkan, dan mereset password pengguna.
- Akun memiliki role, lanud penugasan, dan status aktif.
- Sistem mencatat aktivitas penting pada audit log.

## 7. Kebutuhan Nonfungsional

### NFR-01 Keamanan

- Semua query database menggunakan prepared statement.
- Seluruh output pengguna di-escape sebelum ditampilkan.
- Semua aksi perubahan data menggunakan validasi CSRF.
- Akses halaman dibatasi berdasarkan login dan role.
- Password tidak boleh disimpan atau ditampilkan sebagai teks biasa.
- File upload dibatasi pada tipe gambar yang diizinkan dan ukuran maksimal 5 MB.
- File setup atau migrasi yang bersifat sementara tidak boleh dibiarkan terbuka pada server produksi.

### NFR-02 Usability

- Navigasi menggunakan sidebar dan topbar yang konsisten.
- Status berita menggunakan label yang mudah dibedakan.
- Tabel harus dapat digulir secara horizontal pada layar kecil.
- Form dan dashboard harus dapat digunakan pada desktop maupun mobile.
- Ikon harus profesional dan tidak mengandalkan emoji untuk fungsi utama.

### NFR-03 Performance dan reliability

- Halaman daftar menggunakan query terfilter dan terurut.
- Sistem harus menampilkan keadaan kosong yang jelas ketika tidak ada data.
- Kegagalan upload atau query harus menghasilkan pesan yang dapat dipahami pengguna.
- Peta menggunakan marker yang stabil meskipun jumlah berita berubah.

## 8. Entitas Data Utama

- `users`: akun, role, lanud penugasan, dan status aktif.
- `news`: berita, metadata, status workflow, wilayah, sentimen, dan waktu publikasi.
- `news_history`: riwayat perubahan status dan catatan proses.
- `comments`: komentar dan koreksi berita.
- `notifications`: notifikasi pengguna terkait berita.
- `news_images`: gambar pendukung berita.
- `media_monitoring`: catatan publikasi atau pemantauan media eksternal.
- `audit_logs`: rekam aktivitas administratif dan sistem.

## 9. Kriteria Penerimaan Utama

1. Reporter dapat membuat draft dan mengajukan berita sampai menjadi `published` melalui tahap review yang sesuai.
2. Berita yang ditolak selalu memiliki status revisi, catatan, dan notifikasi kepada Reporter.
3. Editor tidak menerima notifikasi berita dari lanud yang tidak menjadi penugasannya.
4. Badge dan halaman notifikasi menampilkan data yang sama dan tidak memunculkan error ketika database telah dimigrasikan.
5. Semua lanud yang tersedia pada pilihan form memiliki marker atau koordinat pada peta.
6. User D dapat memfilter, mencari, dan membuka detail berita dari halaman monitoring.
7. Pengguna tanpa hak akses tidak dapat membuka aksi review atau halaman administratif.
8. Password tersimpan sebagai hash dan tidak pernah ditampilkan kembali.
9. Layout utama tetap terbaca pada desktop, tablet, dan mobile.
10. Perubahan status dapat dilacak melalui riwayat berita dan audit log jika aksinya bersifat administratif.

## 10. Metrik Keberhasilan

- Persentase berita yang melewati workflow tanpa kehilangan status atau riwayat.
- Waktu rata-rata dari pengajuan Reporter sampai publikasi.
- Jumlah berita yang dikembalikan untuk revisi per tahap.
- Jumlah notifikasi yang dibaca oleh penerima.
- Persentase berita wilayah yang memiliki lanud valid dan tampil pada peta.
- Jumlah kesalahan akses atau error aplikasi yang tercatat pada log.

## 11. Risiko dan Catatan Implementasi

1. Dokumentasi lama hanya menyebut alur User A, B, dan C. Implementasi terbaru sudah memiliki User D dan E, sehingga dokumentasi workflow perlu dijaga tetap sinkron.
2. Data lanud disimpan sebagai teks pada `news.wilayah` dan `users.lanud`. Perbedaan ejaan dapat menyebabkan filter notifikasi atau pemetaan gagal; pilihan lanud harus selalu menggunakan sumber data terpusat.
3. Peta menggunakan koordinat statis. Perubahan nama lanud atau penambahan lanud memerlukan pembaruan data lokasi.
4. File `migrate.php` dan `setup_users.php` memiliki fungsi administratif sensitif dan harus dibatasi atau dihapus dari deployment produksi.
5. Halaman detail berita menjadi titik utama aksi review, sedangkan halaman User D berfungsi terutama sebagai monitoring dan navigasi.

## 12. Prioritas Pengembangan Berikutnya

### Prioritas tinggi

- Menyelaraskan `ARAHAN.md` dan skema SQL dengan role D/E, kolom lanud, serta status `pending_d` dan `revision_d`.
- Memusatkan aturan otorisasi review agar setiap role hanya dapat memproses status yang sesuai.
- Menambahkan pagination pada daftar berita, notifikasi, dan audit log.
- Menghapus atau mengamankan script setup dan migrasi setelah instalasi.

### Prioritas menengah

- Menambahkan highlight koreksi langsung pada isi berita.
- Mengganti editor `contenteditable` dengan rich text editor yang tervalidasi.
- Menyediakan filter lanud pada dashboard, statistik, laporan, dan manajemen pengguna.
- Menambahkan pengujian otomatis untuk workflow status dan filter notifikasi.

### Prioritas rendah

- Menambahkan notifikasi email atau kanal komunikasi resmi.
- Menambahkan ekspor PDF dan format laporan tambahan.
- Menyediakan portal publik terpisah untuk berita berstatus `published`.
