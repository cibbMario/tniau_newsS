<?php
require_once __DIR__ . '/config/config.php';
requireLogin();
$current = 'profile';
$user = currentUser();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        header("Location: " . BASE_URL . "/profile.php");
        exit;
    }

    $formType = $_POST['form_type'] ?? 'update_profile';

    if ($formType === 'add_account') {
        // Only Superuser (E) can add users
        if ($user['role'] !== 'E') {
            $error = "Hanya Admin yang diizinkan untuk menambahkan akun baru.";
        } else {
            $newUsername = trim($_POST['new_username'] ?? '');
            $newFullName = trim($_POST['new_full_name'] ?? '');
            $newPassword = $_POST['new_password'] ?? '';
            $newRole     = $_POST['new_role'] ?? '';

            if (!$newUsername || !$newFullName || !$newPassword || !$newRole) {
                $error = "Semua bidang untuk akun baru harus diisi.";
            } else {
                try {
                    // Check duplicate username
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
                    $stmt->execute([$newUsername]);
                    if ($stmt->fetchColumn() > 0) {
                        $error = "Username '$newUsername' sudah digunakan.";
                    } else {
                        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, full_name, role) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$newUsername, $hash, $newFullName, $newRole]);
                        $success = "Akun baru '$newUsername' berhasil ditambahkan.";
                    }
                } catch (Exception $e) {
                    $error = "Gagal menambahkan akun: " . $e->getMessage();
                }
            }
        }
    } else {
        // update_profile action
        $fullName = trim($_POST['full_name'] ?? $user['full_name']);
        $oldPass  = $_POST['old_password'] ?? '';
        $newPass  = $_POST['new_password'] ?? '';
        $usernameChanged = false;

        try {
            // Update Full Name
            $stmt = $pdo->prepare("UPDATE users SET full_name = ? WHERE id = ?");
            $stmt->execute([$fullName, $user['id']]);
            $_SESSION['full_name'] = $fullName;
            $user['full_name'] = $fullName;

            // All users can update their own username
            $newUsername = trim($_POST['username'] ?? '');
            if ($newUsername && $newUsername !== $user['username']) {
                // Check duplicate
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND id != ?");
                $stmt->execute([$newUsername, $user['id']]);
                if ($stmt->fetchColumn() > 0) {
                    $error = "Username '$newUsername' sudah digunakan oleh pengguna lain.";
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET username = ? WHERE id = ?");
                    $stmt->execute([$newUsername, $user['id']]);
                    $_SESSION['username'] = $newUsername;
                    $user['username'] = $newUsername;
                    $usernameChanged = true;
                }
            }

            if (!$error) {
                if ($oldPass && $newPass) {
                    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
                    $stmt->execute([$user['id']]);
                    $dbPass = $stmt->fetchColumn();

                    if (password_verify($oldPass, $dbPass)) {
                        $newHash = password_hash($newPass, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                        $stmt->execute([$newHash, $user['id']]);
                        $success = "Profil dan Password berhasil diperbarui.";
                    } else {
                        $error = "Password lama tidak sesuai.";
                    }
                } elseif ($oldPass || $newPass) {
                    $error = "Untuk mengganti password, isi password lama dan baru.";
                } else {
                    $success = $usernameChanged ? "Username berhasil diperbarui." : "Profil berhasil diperbarui.";
                }
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
        .profile-form-row input[type="password"],
        .profile-form-row select {
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
        .profile-form-row select {
            background-color: #fff;
            cursor: pointer;
        }
        .profile-form-row input:focus:not([readonly]),
        .profile-form-row select:focus {
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
        /* Logout confirmation modal */
        .modal-overlay {
            position: fixed; inset: 0; background: rgba(0,0,0,0.45);
            display: none; align-items: center; justify-content: center; z-index: 1300;
        }
        .modal-box {
            background: #fff; border-radius: 12px; padding: 20px; width: 380px; max-width: calc(100% - 32px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.2); text-align: left;
        }
        .modal-box h3 { margin: 0 0 8px 0; font-size: 16px; color: var(--text); }
        .modal-box p { margin: 0 0 14px 0; color: var(--text-sec); font-size: 13px; }
        .modal-actions { display: flex; justify-content: flex-end; gap: 8px; }
        .modal-btn { padding: 8px 12px; border-radius: 8px; font-weight: 700; cursor: pointer; border: none; }
        .modal-btn.cancel { background: #f3f4f6; color: #111827; }
        .modal-btn.confirm { background: linear-gradient(135deg, var(--red), rgba(217,48,37,0.9)); color: #fff; }
    </style>
</head>
<body>
<div class="app-layout">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="main-content">
        <!-- TOP NAVBAR MATCHING SCREENSHOT -->
        <div class="top-navbar" style="height:56px">
            <div class="top-navbar-left">
                <button class="hamburger-btn" title="Toggle Menu">&#9776; Menu</button>
                <div class="media-tabs">
                    <span class="media-tab-item active" style="border:none">Profil Pengguna</span>
                </div>
            </div>
            <div class="top-navbar-right">
                <div class="user-dropdown-btn">
                    <?= e($user['full_name']) ?>
                </div>
            </div>
        </div>

        <div class="page-container" style="background:var(--bg-body)">
            <div class="profile-page-container">
                
                <?php if ($error): ?>
                    <div style="background:#fceae8;color:#c0392b;padding:12px 16px;border-radius:6px;margin-bottom:16px;border:1px solid rgba(192,57,43,.15);width:100%;max-width:500px">Peringatan: <?= e($error) ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div style="background:#eafaf1;color:#27ae60;padding:12px 16px;border-radius:6px;margin-bottom:16px;border:1px solid rgba(39,174,96,.15);width:100%;max-width:500px">Berhasil: <?= e($success) ?></div>
                <?php endif; ?>

                <div class="profile-header">
                    <h2>Pengaturan Akun</h2>
                    <p>Kelola informasi profil dan keamanan akun Anda</p>
                </div>

                <div class="profile-card">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
                        <input type="hidden" name="form_type" value="update_profile">
                        <div class="profile-form-row">
                            <label>Username</label>
                            <input type="text" name="username" value="<?= e($user['username']) ?>" required>
                            <span class="profile-info-text" style="color:var(--blue); font-weight:600;">(Bisa diubah)</span>
                        </div>
                        <div class="profile-form-row">
                            <label>Role</label>
                            <input type="text" value="<?= e(['A'=>'Reporter','B'=>'Editor','C'=>'Petinggi / Approver','D'=>'Approver Kejelasan','E'=>'Superuser'][ $user['role'] ] ?? 'User') ?>" readonly>
                        </div>
                        <div class="profile-form-row">
                            <label>Nama Tampilan</label>
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

                <?php if ($user['role'] === 'E'): ?>
                    <div style="height:32px;"></div>
                    
                    <div class="profile-header">
                        <h2>Tambah Akun Baru</h2>
                        <p>Daftarkan akun pengguna baru ke dalam sistem portal berita</p>
                    </div>

                    <div class="profile-card" style="margin-bottom:40px;">
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
                            <input type="hidden" name="form_type" value="add_account">
                            
                            <div class="profile-form-row">
                                <label>Username</label>
                                <input type="text" name="new_username" placeholder="Masukkan username baru" required>
                            </div>
                            
                            <div class="profile-form-row">
                                <label>Nama Tampilan</label>
                                <input type="text" name="new_full_name" placeholder="Masukkan nama lengkap / tampilan" required>
                            </div>
                            
                            <div class="profile-form-row">
                                <label>Role Akun</label>
                                <select name="new_role" required>
                                    <option value="A">Reporter</option>
                                    <option value="B">Editor</option>
                                    <option value="C">Petinggi / Approver</option>
                                    <option value="D">Approver Kejelasan</option>
                                    <option value="E">Superuser</option>
                                </select>
                            </div>
                            
                            <div class="profile-form-row">
                                <label>Password</label>
                                <input type="password" name="new_password" placeholder="Masukkan password untuk akun baru" required>
                            </div>

                            <button type="submit" class="btn-save-wide" style="background:var(--navy); color:#fff; border-color:var(--navy);">
                                ➕ Tambah Akun Baru
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>
        
</body>
</html>
