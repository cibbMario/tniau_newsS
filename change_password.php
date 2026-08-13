<?php
require_once __DIR__ . '/config/config.php';
requireLogin();
$current = 'change_password';
$user = currentUser();

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $error = 'Token sesi tidak valid. Silakan refresh halaman dan coba lagi.';
    } else {
        $oldPass  = $_POST['old_password']     ?? '';
        $newPass  = $_POST['new_password']     ?? '';
        $confPass = $_POST['confirm_password'] ?? '';

        if (!$oldPass || !$newPass || !$confPass) {
            $error = 'Semua kolom wajib diisi.';
        } elseif (strlen($newPass) < 8) {
            $error = 'Password baru minimal 8 karakter.';
        } elseif ($newPass !== $confPass) {
            $error = 'Konfirmasi password tidak cocok dengan password baru.';
        } elseif ($oldPass === $newPass) {
            $error = 'Password baru tidak boleh sama dengan password lama.';
        } else {
            $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
            $stmt->execute([$user['id']]);
            $dbPass = $stmt->fetchColumn();

            if (!password_verify($oldPass, $dbPass)) {
                $error = 'Password lama yang Anda masukkan tidak sesuai.';
            } else {
                $newHash = password_hash($newPass, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                $stmt->execute([$newHash, $user['id']]);
                $success = 'Password berhasil diperbarui. Silakan gunakan password baru Anda saat login berikutnya.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ganti Password — Portal Berita TNI AU</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= time() ?>">
    <style>
        .cp-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: calc(100vh - 120px);
            padding: 40px 20px;
        }

        .cp-header {
            text-align: center;
            margin-bottom: 28px;
        }
        .cp-header-icon {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, var(--navy), #1a56db);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            box-shadow: 0 4px 16px rgba(26,86,219,0.25);
        }
        .cp-header h2 {
            font-size: 20px;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 6px;
        }
        .cp-header p {
            font-size: 13.5px;
            color: var(--text-sec);
        }

        .cp-card {
            background: var(--bg-card);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 32px;
            width: 100%;
            max-width: 480px;
            border: 1px solid var(--border-light);
        }

        .cp-field {
            margin-bottom: 18px;
        }
        .cp-field label {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12.5px;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .cp-input-wrap {
            position: relative;
        }
        .cp-field input[type="password"],
        .cp-field input[type="text"] {
            width: 100%;
            height: 42px;
            border: 1.5px solid var(--border);
            border-radius: 8px;
            padding: 0 44px 0 14px;
            font-size: 13.5px;
            color: var(--text);
            background: var(--bg-body);
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
            box-sizing: border-box;
        }
        .cp-field input:focus {
            border-color: var(--blue);
            box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
        }
        .cp-toggle-eye {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--text-sec);
            padding: 0;
            display: flex;
            align-items: center;
        }
        .cp-toggle-eye:hover {
            color: var(--text);
        }

        .cp-hint {
            font-size: 11.5px;
            color: var(--text-sec);
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .cp-divider {
            height: 1px;
            background: var(--border-light);
            margin: 22px 0;
        }

        .cp-requirements {
            background: #f0f4ff;
            border: 1px solid #c7d7fe;
            border-radius: 8px;
            padding: 12px 14px;
            margin-bottom: 20px;
        }
        .cp-requirements p {
            font-size: 12px;
            font-weight: 600;
            color: #1a56db;
            margin: 0 0 8px 0;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .cp-requirements ul {
            margin: 0;
            padding-left: 16px;
            list-style: none;
        }
        .cp-requirements ul li {
            font-size: 12px;
            color: #374151;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .cp-requirements ul li::before {
            content: '';
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background: #1a56db;
            flex-shrink: 0;
        }

        .cp-alert {
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 20px;
            font-size: 13px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        .cp-alert.error {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        .cp-alert.success {
            background: #f0fdf4;
            color: #16a34a;
            border: 1px solid #bbf7d0;
        }
        .cp-alert svg { flex-shrink: 0; margin-top: 1px; }

        .cp-btn-submit {
            width: 100%;
            height: 44px;
            background: linear-gradient(135deg, var(--navy), #1a56db);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: opacity 0.2s, transform 0.1s;
            margin-top: 4px;
        }
        .cp-btn-submit:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        .cp-btn-submit:active {
            transform: translateY(0);
        }

        .cp-back-link {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin-top: 20px;
            font-size: 13px;
            color: var(--text-sec);
            text-decoration: none;
        }
        .cp-back-link:hover {
            color: var(--text);
        }
    </style>
</head>
<body>
<div class="app-layout">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="main-content">
        <?php include __DIR__ . '/includes/topbar.php'; ?>

        <div class="page-container" style="background:var(--bg-body)">
            <div class="cp-container">

                <?php if ($error): ?>
                    <div class="cp-alert error" style="width:100%;max-width:480px">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                        </svg>
                        <?= e($error) ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="cp-alert success" style="width:100%;max-width:480px">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                        </svg>
                        <?= e($success) ?>
                    </div>
                <?php endif; ?>

                <div class="cp-header">
                    <div class="cp-header-icon">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                    </div>
                    <h2>Ganti Password</h2>
                    <p>Perbarui password akun <strong><?= e($user['username']) ?></strong> Anda</p>
                </div>

                <div class="cp-card">
                    <div class="cp-requirements">
                        <p>
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                            </svg>
                            Syarat Password Baru
                        </p>
                        <ul>
                            <li>Minimal 8 karakter</li>
                            <li>Tidak boleh sama dengan password lama</li>
                            <li>Password baru harus dimasukkan dua kali (konfirmasi)</li>
                        </ul>
                    </div>

                    <form method="POST" id="cpForm">
                        <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">

                        <div class="cp-field">
                            <label>
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                                </svg>
                                Password Saat Ini
                            </label>
                            <div class="cp-input-wrap">
                                <input type="password" name="old_password" id="oldPass" placeholder="Masukkan password saat ini" autocomplete="current-password" required>
                                <button type="button" class="cp-toggle-eye" onclick="toggleVis('oldPass', this)" aria-label="Tampilkan/sembunyikan">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                </button>
                            </div>
                        </div>

                        <div class="cp-divider"></div>

                        <div class="cp-field">
                            <label>
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                </svg>
                                Password Baru
                            </label>
                            <div class="cp-input-wrap">
                                <input type="password" name="new_password" id="newPass" placeholder="Masukkan password baru (min. 8 karakter)" autocomplete="new-password" required oninput="checkStrength(this.value)">
                                <button type="button" class="cp-toggle-eye" onclick="toggleVis('newPass', this)" aria-label="Tampilkan/sembunyikan">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                </button>
                            </div>
                            <div id="strengthBar" style="height:3px;border-radius:4px;margin-top:6px;transition:all .3s;background:#e5e7eb;overflow:hidden">
                                <div id="strengthFill" style="height:100%;width:0;transition:all .3s;border-radius:4px;"></div>
                            </div>
                            <div id="strengthLabel" class="cp-hint"></div>
                        </div>

                        <div class="cp-field">
                            <label>
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                                </svg>
                                Konfirmasi Password Baru
                            </label>
                            <div class="cp-input-wrap">
                                <input type="password" name="confirm_password" id="confPass" placeholder="Ulangi password baru" autocomplete="new-password" required oninput="checkMatch()">
                                <button type="button" class="cp-toggle-eye" onclick="toggleVis('confPass', this)" aria-label="Tampilkan/sembunyikan">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                </button>
                            </div>
                            <div id="matchHint" class="cp-hint"></div>
                        </div>

                        <button type="submit" class="cp-btn-submit" id="cpSubmitBtn">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                            Perbarui Password
                        </button>
                    </form>
                </div>

                <a href="<?= BASE_URL ?>/profile.php" class="cp-back-link">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
                    </svg>
                    Kembali ke Profil
                </a>
            </div>
        </div>
    </main>
</div>

<script>
function toggleVis(inputId, btn) {
    const input = document.getElementById(inputId);
    const isPass = input.type === 'password';
    input.type = isPass ? 'text' : 'password';
    btn.innerHTML = isPass
        ? '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>'
        : '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
}

function checkStrength(val) {
    const fill  = document.getElementById('strengthFill');
    const label = document.getElementById('strengthLabel');
    if (!val) { fill.style.width = '0'; label.innerHTML = ''; return; }

    let score = 0;
    if (val.length >= 8)  score++;
    if (val.length >= 12) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    const levels = [
        { pct: '20%', color: '#ef4444', text: 'Sangat Lemah' },
        { pct: '40%', color: '#f97316', text: 'Lemah' },
        { pct: '60%', color: '#eab308', text: 'Cukup' },
        { pct: '80%', color: '#22c55e', text: 'Kuat' },
        { pct: '100%', color: '#16a34a', text: 'Sangat Kuat' },
    ];
    const lvl = levels[Math.min(score - 1, 4)] || levels[0];
    fill.style.width = lvl.pct;
    fill.style.background = lvl.color;
    label.innerHTML = '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg> Kekuatan: <span style="color:' + lvl.color + ';font-weight:600">' + lvl.text + '</span>';
}

function checkMatch() {
    const newPass  = document.getElementById('newPass').value;
    const confPass = document.getElementById('confPass').value;
    const hint     = document.getElementById('matchHint');
    if (!confPass) { hint.innerHTML = ''; return; }
    if (newPass === confPass) {
        hint.innerHTML = '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> <span style="color:#16a34a">Password cocok</span>';
    } else {
        hint.innerHTML = '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg> <span style="color:#dc2626">Password tidak cocok</span>';
    }
}
</script>
</body>
</html>
