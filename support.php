<?php
require_once __DIR__ . '/config/config.php';
requireLogin();
$current = 'support';
$user = currentUser();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kontak Support & Panduan Pengguna — Portal Berita TNI AU</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= time() ?>">
    <style>
        .support-grid {
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 24px;
            margin-top: 16px;
        }
        
        .support-card {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
            padding: 24px;
            margin-bottom: 24px;
        }

        .support-section-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--navy);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .doc-list {
            list-style: none;
            padding: 0;
            margin: 0 0 16px 0;
        }

        .doc-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 10px 0;
            border-bottom: 1px solid #f1f5f9;
            font-size: 13px;
            color: #334155;
        }
        .doc-item:last-child {
            border-bottom: none;
        }
        .doc-item .num {
            font-weight: 600;
            color: var(--teal-mid);
        }

        .faq-accordion {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .faq-item {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
            background: #f8fafc;
            transition: all 0.2s ease;
        }

        .faq-header {
            padding: 14px 16px;
            font-size: 13.5px;
            font-weight: 600;
            color: #1e293b;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            user-select: none;
            background: #ffffff;
        }

        .faq-header:hover {
            background: #f8fafc;
            color: var(--teal-mid);
        }

        .faq-header .faq-chevron {
            transition: transform 0.2s ease;
            color: #94a3b8;
            flex-shrink: 0;
        }

        .faq-item.active .faq-chevron {
            transform: rotate(180deg);
            color: var(--teal-mid);
        }

        .faq-body {
            display: none;
            padding: 14px 16px;
            font-size: 13px;
            color: #475569;
            line-height: 1.6;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
        }

        .faq-item.active .faq-body {
            display: block;
        }

        .guide-steps {
            margin: 10px 0 0 0;
            padding-left: 18px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .guide-steps li {
            margin-bottom: 2px;
        }

        .contact-box {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            padding: 24px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
        }

        .btn-whatsapp {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 12px 20px;
            background: #25D366;
            color: #ffffff;
            font-weight: 700;
            font-size: 14px;
            border-radius: 30px;
            text-decoration: none;
            box-shadow: 0 4px 14px rgba(37, 211, 102, 0.3);
            transition: all 0.2s ease;
            margin-top: 16px;
        }

        .btn-whatsapp:hover {
            background: #1ebc57;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(37, 211, 102, 0.4);
            color: #ffffff;
        }

        @media (max-width: 992px) {
            .support-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<div class="app-layout">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="top-navbar" style="height:56px">
            <div class="top-navbar-left">
                <button class="hamburger-btn" id="hamburgerBtn" title="Toggle Menu" aria-label="Toggle menu">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                </button>
                <div class="media-tabs">
                    <span class="media-tab-item active" style="border:none">Contact Support Information</span>
                </div>
            </div>
            <div class="top-navbar-right">
                <div class="topbar-user-badge">
                    <div class="topbar-avatar"><?= strtoupper(substr($user['full_name'], 0, 1)) ?></div>
                    <div class="topbar-user-info">
                        <span class="topbar-user-name"><?= e($user['full_name']) ?></span>
                        <span class="topbar-user-role"><?= e(userDisplayName($user['role'])) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="page-container" style="background:var(--bg-body)">
            <div class="support-grid">
                
                <!-- LEFT COLUMN: DOKUMEN PANDUAN & FAQ -->
                <div>
                    <!-- DOKUMEN PANDUAN -->
                    <div class="support-card">
                        <h3 class="support-section-title">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--teal-mid);"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                            Dokumen Panduan
                        </h3>
                        <ul class="doc-list">
                            <li class="doc-item">
                                <span class="num">1.</span>
                                <span>Panduan Pengguna Portal Berita TNI AU (untuk pengguna Satuan)</span>
                            </li>
                            <li class="doc-item">
                                <span class="num">2.</span>
                                <span>Materi Pelatihan Teknis Profesi Penerangan - present</span>
                            </li>
                        </ul>
                        <div style="font-size:12.5px; color:#64748b;">
                            Panduan ringkas penggunaan tersedia pada bagian FAQ di bawah.
                        </div>
                    </div>

                    <!-- FAQ -->
                    <div class="support-card">
                        <h3 class="support-section-title">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--teal-mid);"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                            FAQ
                        </h3>

                        <div class="faq-accordion">
                            <!-- Q1 -->
                            <div class="faq-item">
                                <div class="faq-header" onclick="toggleFaq(this)">
                                    <span>Sudah input username dan password, tapi tidak dapat masuk ke aplikasi?</span>
                                    <svg class="faq-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                                </div>
                                <div class="faq-body">
                                    <p>Pastikan username dan password tidak memiliki spasi di awal atau akhir. Jika tetap gagal, hubungi support.</p>
                                </div>
                            </div>

                            <!-- Q2 -->
                            <div class="faq-item">
                                <div class="faq-header" onclick="toggleFaq(this)">
                                    <span>Kenapa sudah input berita, namun tidak tampil di halaman list berita?</span>
                                    <svg class="faq-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                                </div>
                                <div class="faq-body">
                                    <p>Gunakan filter status atau pencarian pada halaman Daftar Berita. Berita draft hanya dapat dilihat oleh pembuat dan pengguna yang berwenang.</p>
                                </div>
                            </div>

                            <!-- Q3 -->
                            <div class="faq-item">
                                <div class="faq-header" onclick="toggleFaq(this)">
                                    <span>Berita yang sudah di input kemarin tidak tampil di halaman berita</span>
                                    <svg class="faq-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                                </div>
                                <div class="faq-body">
                                    <ol style="margin-left: 18px; padding: 0;">
                                        <li>Klik filter tanggal</li>
                                        <li>Ubah filter menjadi sesuai yang kita inginkan</li>
                                    </ol>
                                </div>
                            </div>

                            <!-- Q4: PANDUAN UPLOAD BERITA -->
                            <div class="faq-item active">
                                <div class="faq-header" onclick="toggleFaq(this)">
                                    <span>Bagaimana caranya input berita ?</span>
                                    <svg class="faq-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                                </div>
                                <div class="faq-body">
                                    <p style="font-weight:600; color:#1e293b; margin-bottom:8px;">Untuk input berita bisa dilakukan dengan cara :</p>
                                    <ol class="guide-steps">
                                        <li>Login aplikasi</li>
                                        <li>Login menggunakan akun yang telah diberikan Administrator.</li>
                                        <li>Buka menu <strong>Daftar Berita</strong>, lalu pilih <strong>Buat Berita Baru</strong>.</li>
                                        <li>Isi judul berita, wilayah atau satuan, sumber media, dan waktu terbit jika diperlukan.</li>
                                        <li>Upload satu gambar utama. Gambar pendukung dapat dipilih lebih dari satu.</li>
                                        <li>Tulis isi berita dengan editor, lalu lengkapi sentimen, prioritas, klasifikasi, tempat, aktor, tag, topik, dan keyword.</li>
                                        <li>Pilih <strong>Simpan sebagai Draft</strong> jika belum siap dikirim.</li>
                                        <li>Pilih <strong>Ajukan untuk Review</strong> agar berita masuk ke proses pemeriksaan Editor.</li>
                                        <li>Setelah mengirim, tunggu proses review. Hindari menekan tombol kirim berulang kali.</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT COLUMN: KONTAK SUPPORT -->
                <div>
                    <div class="contact-box">
                        <h3 class="support-section-title">Kontak (informasi support)</h3>
                        <p style="font-size:13px; color:#475569; line-height:1.6;">
                            Untuk informasi dan konsultasi penggunaan aplikasi, silakan hubungi support melalui nomor berikut:
                        </p>

                        <a href="https://wa.me/6281268687910?text=Halo%20Tim%20Support%20Portal%20Berita%20TNI%20AU" target="_blank" rel="noopener" class="btn-whatsapp">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                            Chat on WhatsApp
                        </a>


                        <div style="margin-top:18px; font-size:12px; color:#64748b; text-align:center;">
                            waktu layanan : hari dan jam kerja (08.00 s.d 17.00 WIB)
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </main>
</div>

<script>
function toggleFaq(el) {
    const parent = el.closest('.faq-item');
    parent.classList.toggle('active');
}
</script>
</body>
</html>
