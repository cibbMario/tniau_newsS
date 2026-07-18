<?php
require_once __DIR__ . '/config/config.php';
requireLogin();
$current = 'profile';
$user = currentUser();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $oldPass  = $_POST['old_password'] ?? '';
    $newPass  = $_POST['new_password'] ?? '';

    if (!$fullName) {
        $error = "Nama Lengkap wajib diisi.";
    } else {
        try {
            // Update nama
            $stmt = $pdo->prepare("UPDATE users SET full_name = ? WHERE id = ?");
            $stmt->execute([$fullName, $user['id']]);
            $success = "Profil berhasil diperbarui.";
            $_SESSION['user']['full_name'] = $fullName;
            $user['full_name'] = $fullName;

            // Update password jika diisi
            if ($oldPass && $newPass) {
                // Verifikasi old pass
                $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
                $stmt->execute([$user['id']]);
                $dbPass = $stmt->fetchColumn();

                if (password_verify($oldPass, $dbPass)) {
                    $newHash = password_hash($newPass, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->execute([$newHash, $user['id']]);
                    $success = "Profil dan Password berhasil diperbarui.";
                } else {
                    $error = "Password lama tidak sesuai.";
                }
            } elseif ($oldPass || $newPass) {
                $error = "Untuk mengganti password, isi password lama dan baru.";
            }
        } catch (Exception $e) {
            $error = "Gagal mengupdate profil.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Pengguna — Portal Berita TNI AU</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= time() ?>">
    <style>
        .profile-page-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: calc(100vh - 120px);
            padding: 40px 20px;
        }
        
        .profile-header {
            text-align: center;
            margin-bottom: 32px;
        }
        .profile-header h2 {
            font-size: 20px;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 8px;
        }
        .profile-header p {
            font-size: 13.5px;
            color: var(--text-sec);
        }

        .profile-card {
            background: var(--bg-card);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 32px;
            width: 100%;
            max-width: 500px;
            border: 1px solid var(--border-light);
        }

        .profile-form-row {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
            gap: 16px;
        }
        .profile-form-row label {
            width: 120px;
            font-size: 13px;
            color: var(--text);
            font-weight: 500;
        }
        .profile-form-row input[type="text"],
        .profile-form-row input[type="password"] {
            flex: 1;
            height: 34px;
            border: 1px solid var(--border);
            border-radius: 4px;
            padding: 0 10px;
            font-size: 13px;
            color: var(--text);
            outline: none;
            transition: border-color var(--transition);
        }
        .profile-form-row input:focus:not([readonly]) {
            border-color: var(--blue);
        }
        .profile-form-row input[readonly] {
            background: #f8f9fa;
            color: var(--text-sec);
            cursor: not-allowed;
        }

        .profile-info-text {
            font-size: 11px;
            color: var(--text-sec);
            margin-left: 8px;
        }

        .profile-divider {
            height: 1px;
            background: #e2e6ea;
            margin: 24px 0;
        }

        .profile-section-title {
            font-size: 13px;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 16px;
        }

        .btn-save-wide {
            display: block;
            width: 100%;
            background: #f8f9fa;
            border: 1px solid var(--border);
            color: var(--text);
            text-align: center;
            padding: 10px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 16px;
            transition: all var(--transition);
        }
        .btn-save-wide:hover {
            background: #eef2f5;
            border-color: #d1d5da;
        }
    </style>
</head>
<body>
<div class="app-layout">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="main-content">
        <!-- TOP NAVBAR MATCHING SCREENSHOT -->
        <div class="top-navbar" style="height:56px">
            <div class="top-navbar-left">
                <button class="hamburger-btn">☰</button>
                <div class="media-tabs">
                    <span class="media-tab-item active" style="border:none">Profil Pengguna</span>
                </div>
            </div>
            <div class="top-navbar-right">
                <div class="user-dropdown-btn">
                    👤 <?= e($user['full_name']) ?> ▼
                </div>
            </div>
        </div>

        <div class="page-container" style="background:var(--bg-body)">
            <div class="profile-page-container">
                
                <?php if ($error): ?>
                    <div style="background:#fceae8;color:#c0392b;padding:12px 16px;border-radius:6px;margin-bottom:16px;border:1px solid rgba(192,57,43,.15);width:100%;max-width:500px">⚠️ <?= e($error) ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div style="background:#eafaf1;color:#27ae60;padding:12px 16px;border-radius:6px;margin-bottom:16px;border:1px solid rgba(39,174,96,.15);width:100%;max-width:500px">✅ <?= e($success) ?></div>
                <?php endif; ?>

                <div class="profile-header">
                    <h2>Pengaturan Akun</h2>
                    <p>Kelola informasi profil dan keamanan akun Anda</p>
                </div>

                <div class="profile-card">
                    <form method="POST">
                        <div class="profile-form-row">
                            <label>Username</label>
                            <input type="text" value="<?= e($user['username']) ?>" readonly>
                            <span class="profile-info-text">Username tidak dapat diubah.</span>
                        </div>
                        <div class="profile-form-row">
                            <label>Role</label>
                            <input type="text" value="<?= e(['A'=>'Reporter','B'=>'Editor','C'=>'Petinggi / Approver'][$user['role']]) ?>" readonly>
                        </div>
                        <div class="profile-form-row">
                            <label>Nama Lengkap</label>
                            <input type="text" name="full_name" value="<?= e($user['full_name']) ?>" required>
                        </div>

                        <div class="profile-divider"></div>

                        <div class="profile-section-title">Ganti Password (Opsional)</div>
                        
                        <div class="profile-form-row">
                            <label>Password Lama</label>
                            <input type="password" name="old_password" placeholder="Masukkan password saat ini">
                        </div>
                        <div class="profile-form-row">
                            <label>Password Baru</label>
                            <input type="password" name="new_password" placeholder="Masukkan password baru">
                        </div>

                        <button type="submit" class="btn-save-wide">
                            💾 Simpan Perubahan
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>
</body>
</html>
