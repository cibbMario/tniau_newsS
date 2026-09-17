# Product Requirements Document
\[## Portal Berita TNI AU

Status: Draft final berdasarkan implementasi dan kebutuhan operasional
Tanggal: 17 September 2026
Platform: Aplikasi web berbasis PHP, MySQL, HTML, CSS, JavaScript
Target pengguna: Reporter, Editor, Petinggi/Approver, Peninjau Kejelasan, Administrator

## 1. Ringkasan produk

Portal Berita TNI AU adalah sistem internal untuk mengelola seluruh siklus publikasi berita di lingkungan TNI Angkatan Udara. Sistem ini dirancang untuk menangani proses penulisan, review, persetujuan, publikasi, pencarian, monitoring, dan analisis kinerja konten berita secara terstruktur.

Produk ini tidak hanya berfungsi sebagai media penyimpanan berita, tetapi juga sebagai sistem kerja editorial yang mengikat peran dan kewenangan tiap pengguna. Dari reporter yang menulis dan mengirim konten, hingga editor, approver yang memvalidasi, semua aktivitas diatur dalam satu alur yang jelas dan terdokumentasi.

Tujuan utama produk adalah memastikan berita yang masuk ke sistem memiliki kualitas, konsistensi, dan otoritas yang sesuai, sekaligus mempermudah pengawasan dan pelaporan secara internal.

## 2. Latar belakang dan masalah yang dipecahkan

Pada instansi seperti TNI AU, pengelolaan berita internal memiliki beberapa kebutuhan kritis:

- kebutuhan untuk menjaga validitas dan akurasi berita sebelum dipublikasikan
- kebutuhan kontrol akses berdasarkan jabatan dan unit kerja
- kebutuhan menjaga traceability dari setiap perubahan status berita
- kebutuhan monitoring terhadap volume, kualitas, dan distribusi berita per wilayah
- kebutuhan menyatukan seluruh workflow editorial dalam satu sistem yang bisa dipantau oleh pimpinan

Sebelum sistem ini, proses editorial cenderung berjalan secara manual, tidak terdokumentasi dengan baik, dan sulit dilacak jika terjadi perubahan atau revisi. Portal ini bertujuan me-modernisasi dan mengatasi masalah tersebut dengan mekanisme workflow, notifikasi, dan riwayat aktivitas yang terstruktur.

## 3. Tujuan produk

1. Mengurangi risiko publikasi berita yang belum lolos validasi.
2. Membuat workflow review berita menjadi jelas dan konsisten antar peran.
3. Menjamin setiap berita memiliki riwayat status, komentar, dan revisi yang terdokumentasi.
4. Mempermudah pengelolaan data per wilayah dan per lanud.
5. Memberikan dashboard dan laporan yang berguna untuk monitoring kinerja editorial.
6. Menyediakan sistem administrasi berita internal yang aman, terstruktur, dan mudah diawasi.

## 4. Ruang lingkup

### 4.1 Fitur utama

- autentikasi pengguna dengan role-based access control
- membuat, menyimpan, mengedit, dan mengajukan berita
- review berita oleh editor dan approver
- revisi dan pengembalian berita ke reporter
- publikasi berita ke status published
- notifikasi antar pengguna
- riwayat perubahan status berita
- komentar atau koreksi berita
- dashboard statistik
- pencarian, filter, dan daftar berita
- manajemen akun dan otorisasi
- galeri media dan dokumentasi visual
- laporan CSV dan monitoring media
- peta distribusi berita berdasarkan lanud atau wilayah

### 4.2 Batasan versi saat ini

- belum ada portal publik terbuka untuk umum
- belum ada aplikasi mobile native
- belum ada integrasi email, WhatsApp, atau media sosial otomatis
- belum ada rich text editor yang kompleks
- belum ada sistem approval berbasis approval matrix yang modular
- belum ada API publik untuk integrasi eksternal

## 5. Pengguna dan peran

### 5.1 Reporter

Reporter adalah user yang menulis dan mengirimkan berita. Mereka bertanggung jawab untuk:

- membuat berita baru
- menyimpan draft
- melengkapi metadata berita
- melampirkan gambar atau media pendukung
- mengajukan berita untuk review
- memperbaiki berita yang dikembalikan

### 5.2 Editor

Editor bertanggung jawab memvalidasi isi berita pada tahap awal. Mereka dapat:

- melihat berita yang masuk ke wilayah kerja mereka
- menyetujui atau meminta revisi
- menambahkan komentar atau catatan koreksi
- memantau status berita yang sedang dikerjakan

### 5.3 Petinggi atau Approver

Approver berperan pada tahap penilaian lanjutan sebelum publikasi. Mereka dapat:

- menilai berita yang masuk ke tahap persetujuan
- memperbaiki atau menolak konten yang belum layak
- meminta revisi bila diperlukan
- menampilkan keputusan persetujuan pada sistem

### 5.4 Peninjau Kejelasan

Peran ini menilai kejelasan, relevansi, dan kemungkinan kebutuhan koreksi lanjutan. Mereka dapat:

- memantau berita yang masuk dalam tahap klarifikasi
- memberikan komentar dan catatan evaluasi
- mengembalikan artikel untuk revisi
- memastikan artikel siap dipublikasikan dengan kualitas yang sesuai kebutuhan internal

### 5.5 Administrator

Administrator memiliki kewenangan paling luas untuk:

- mengelola akun pengguna
- mengatur role dan status aktif
- mengakses seluruh data dan log sistem
- melakukan pengelolaan data sensitif dan konfigurasi penting
- memelihara keamanan dan fleksibilitas operasional sistem

## 6. Persona utama

### Persona 1: Reporter lanud

- bekerja di unit atau lanud tertentu
- membutuhkan proses cepat untuk menulis dan mengajukan berita
- ingin tahu status berita yang dia buat
- perlu mendapat notifikasi ketika berita dikembalikan untuk revisi

### Persona 2: Editor wilayah

- memonitor berita dari wilayah kerja tertentu
- membutuhkan daftar berita yang menunggu review
- ingin melihat metadata dan media pendukung agar dapat memutuskan persetujuan
- membutuhkan catatan komentar agar revisi lebih mudah dipahami

### Persona 3: Pimpinan atau approver

- membutuhkan ringkasan berita dan indikator kualitas
- ingin melihat berita yang sudah siap publikasi
- perlu mengetahui apakah ada berita yang tertahan atau memerlukan revisi

### Persona 4: Administrator

- memastikan sistem berjalan aman dan konsisten
- mengelola pengguna dan otorisasi
- mengecek audit log dan aktivitas penting

## 7. Alur kerja utama

### 7.1 Proses pembuatan berita

1. Reporter login ke sistem.
2. Reporter membuat berita baru dengan mengisi judul, isi, metadata, kategori, sentimen, wilayah, media, dan lampiran gambar.
3. Reporter dapat memilih:
   - simpan draft
   - ajukan ke review
4. Setelah diajukan, berita masuk ke tahap pending review sesuai aturan role dan wilayah.

### 7.2 Proses review

1. Editor menerima notifikasi dan melihat berita yang masuk dalam wilayah kerja mereka.
2. Editor menilai berita dan memilih:
   - setujui ke tahap berikutnya
   - minta revisi
   - menolak atau mengembalikan ke reporter
3. Jika revisi diminta, reporter menerima notifikasi dan memperbaiki konten.
4. Setelah revisi selesai, berita diajukan kembali ke tahap review berikutnya.

### 7.3 Proses approval dan publikasi

1. Approver menilai berita pada tahap persetujuan.
2. Jika lolos, berita dapat masuk ke status published atau menuju tahap klarifikasi tambahan.
3. Jika ada masalah, approver mengembalikan berita untuk revisi.
4. Administrator dapat menjadi pengawas atau penengah jika dibutuhkan.

### 7.4 Status berita

Status yang direncanakan dan diimplementasikan secara realistis:

- draft
- pending_b
- revision_b
- pending_c
- revision_c
- pending_d
- revision_d
- published

Setiap perubahan status harus tercatat dalam riwayat berita dan dapat dilihat oleh pengguna yang berwenang.

## 8. Kebutuhan fungsional

### FR-01 Autentikasi dan akses

- Sistem harus memerlukan login sebelum mengakses halaman utama.
- Sistem harus membedakan hak akses per role.
- Pengguna harus dapat logout dengan aman.
- Password tidak boleh ditampilkan kembali dalam bentuk plaintext.
- Session pengguna harus dijaga keamanan dan dibatasi secara wajar.

### FR-02 Manajemen berita

- Reporter dapat membuat dan menyimpan berita dalam status draft.
- Reporter dapat mengedit berita milik sendiri saat status masih dalam tahap yang memungkinkan.
- Berita harus memiliki field utama seperti judul, isi, wilayah, media, sentimen, prioritas, dan gambar.
- Sistem harus mampu menampilkan status berita secara jelas.
- Sistem harus mendukung lampiran gambar utama dan gambar tambahan.

### FR-03 Proses review dan approval

- Editor, approver, peninjau, dan administrator harus memiliki akses sesuai status berita yang relevan.
- Review harus bisa menghasilkan keputusan approve atau reject.
- Keputusan revisi harus disertai catatan yang jelas.
- Setiap aksi review harus tercatat ke riwayat berita.

### FR-04 Komentar dan revisi

- Reviewer dapat menambahkan komentar pada berita.
- Komentar harus dapat terlihat oleh reporter dan peran terkait.
- Sistem harus memudahkan reporter memahami alasan revisi.

### FR-05 Notifikasi

- Sistem menghasilkan notifikasi ketika berita masuk tahap review atau dikembalikan untuk revisi.
- Notifikasi harus dapat dibaca dan ditandai sudah dibaca.
- Badge notifikasi harus menampilkan jumlah yang belum dibaca.
- Notifikasi harus terhubung ke berita atau halaman yang relevan.

### FR-06 Dashboard dan monitoring

- Dashboard menampilkan angka ringkas seperti total berita, berita terbit, pending review, dan distribusi sentimen.
- Dashboard harus membantu admin dan pimpinan memantau performa editorial.
- User D atau monitoring dashboard harus bisa melihat ringkasan berita, filter status, dan daftar berita aktif.

### FR-07 Pencarian dan filter

- Pengguna dapat mencari berita berdasarkan judul, isi, atau penulis.
- Sistem harus mendukung filter berdasarkan status, wilayah, sentimen, atau periode.
- Daftar berita harus rapi dan mudah dibaca.

### FR-08 Data dan laporan

- Sistem dapat mengekspor data berita dalam format CSV.
- User berwenang dapat melihat ringkasan laporan per kontributor atau reviewer.
- Sistem harus memfasilitasi monitoring distribusi berita berdasarkan wilayah atau lanud.

### FR-09 Manajemen pengguna

- Administrator dapat membuat, mengubah, dan nonaktifkan akun.
- Sistem menyimpan role, lanud, dan status aktif pengguna.
- Sistem bisa memproses reset password atau perubahan status akun.

### FR-10 Audit dan histori

- Sistem mencatat perubahan status, komentar, dan aktivitas penting.
- Admin harus bisa menelusuri log aktivitas dalam periode tertentu.

## 9. Kebutuhan non-fungsional

### NFR-01 Keamanan

- semua query SQL menggunakan prepared statement
- input pengguna harus divalidasi dan disanitasi
- output harus aman saat ditampilkan di halaman
- akses halaman harus dibatasi sesuai role
- file upload harus dibatasi berdasarkan tipe dan ukuran yang aman
- script administratif yang sensitif harus dibatasi dari lingkungan produksi

### NFR-02 Kinerja

- halaman utama, dashboard, dan daftar berita harus cepat dimuat dalam kondisi data yang wajar
- query filter dan pencarian harus dioptimalkan
- sistem harus menangani data dalam jumlah yang cukup besar tanpa menurunkan performa signifikan

### NFR-03 Ketersediaan dan stabilitas

- sistem harus tetap berjalan meskipun terjadi beberapa error input yang umum
- error yang terjadi harus ditampilkan secara jelas dan tidak menimbulkan blank page
- data atau perubahan penting harus tercatat dengan baik

### NFR-04 Usability

- antarmuka harus mudah dipahami oleh pengguna internal
- status berita harus jelas dan mudah dibedakan
- desain harus dapat diakses pada desktop dan tablet
- navigasi harus konsisten pada sidebar dan topbar

## 10. Entity data utama

- users: data akun, role, lanud, dan status aktif
- news: data berita utama, status, kategori, sentimen, wilayah, dan metadata
- news_history: riwayat perubahan status dan aktivitas editorial
- comments: komentar dan catatan revisi
- notifications: notifikasi pengguna
- news_images: gambar pendukung berita
- media_monitoring: data pemantauan media atau sumber berita eksternal
- audit_logs: log administrasi dan aktivitas keamanan

## 11. Kriteria penerimaan utama

1. Reporter dapat membuat berita, menyimpannya sebagai draft, dan mengajukannya ke review.
2. Editor dapat melihat berita yang berhak ditinjau berdasarkan wilayah kerja mereka.
3. Reviewer dapat menyetujui atau meminta revisi dengan catatan yang jelas.
4. Reporter menerima notifikasi ketika berita dikembalikan untuk revisi.
5. Status berita berubah sesuai alur workflow yang benar dan terdokumentasi.
6. Administrator dapat melihat log aktivitas dan mengelola akun dengan benar.
7. Pengguna tanpa hak akses tidak dapat membuka halaman yang tidak sesuai role.
8. Halaman dashboard menampilkan indikator penting dengan data yang benar.
9. Generic user tidak dapat melihat data pesaing atau data yang tidak relevan.
10. Data berita dapat diekspor dan ditampilkan dengan format yang konsisten.

## 12. Metrik keberhasilan

- rata-rata waktu dari reporter mengajukan berita sampai publikasi
- jumlah berita yang masuk review per bulan
- jumlah revisi per tahap workflow
- persentase berita yang dipublikasikan tanpa revisi besar
- jumlah notifikasi yang dibaca oleh pengguna
- jumlah akses tidak sah yang terblokir
- tingkat akurasi data wilayah dan lanud pada dashboard

## 13. Risiko dan kendala

1. Perbedaan nama wilayah atau lanud dapat memengaruhi notifikasi dan filter data.
2. Workflow review dapat tidak konsisten jika role dan status tidak terdokumentasi dengan jelas.
3. Data yang tidak lengkap dapat menurunkan kualitas dashboard dan analisis.
4. Pengelolaan file gambar perlu dijaga agar tidak mengganggu performa server.
5. Script migrasi atau setup harus dibatasi agar tidak bisa diakses dari lingkungan produksi.

## 14. Prioritas pengembangan

### Prioritas tinggi

- menyelaraskan role, status, dan aturan access control dengan kebutuhan operasional
- memperkuat workflow review dari reporter sampai publikasi
- memperbaiki notifikasi dan riwayat status agar konsisten
- memproteksi file setup atau script admin sensitif

### Prioritas menengah

- memperbaiki filter wilayah, lanud, dan dashboard monitoring
- menambah pagination dan optimasi query untuk data besar
- menambah laporan kontributor dan reviewer
- memperbaiki validasi input untuk form berita

### Prioritas rendah

- integrasi dengan email atau channel komunikasi lain
- portal publik terpisah untuk membaca berita yang sudah dipublikasikan
- ekspor PDF dan laporan visual yang lebih lengkap
- rich text editor yang lebih canggih

## 15. Kesimpulan

Portal Berita TNI AU adalah produk yang berorientasi pada kebutuhan internal organisasi, dengan fokus pada workflow editorial yang aman, terukur, dan terdokumentasi. Produk ini bukan sekadar CMS berita biasa, tetapi sistem kerja yang menggabungkan fungsi editorial, otorisasi, monitoring, dan pelaporan dalam satu ekosistem.

Dengan pendekatan yang realistis, produk ini dapat berkembang menjadi sistem yang kokoh untuk kebutuhan publikasi, audit, dan analisis informasi di lingkungan TNI AU. Untuk versi berikutnya, fokus pengembangan sebaiknya diarahkan pada konsistensi workflow, keamanan akses, dan pengalaman kerja para pengguna inti.
