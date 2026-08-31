<?php
require_once __DIR__ . '/config/config.php';
requireLogin();
$current = 'profile';
$user = currentUser();

// Load full user details from DB to get the actual full_name and created_at
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user['id']]);
$userDb = $stmt->fetch();
if (!$userDb) {
    $userDb = $user;
}

$error = '';
$success = '';

// Helper for Initials
function getInitials($name) {
    $cleaned = preg_replace('/[^a-zA-Z\s]/', '', $name);
    $words = explode(" ", trim($cleaned));
    $initials = "";
    $count = 0;
    foreach ($words as $w) {
        if ($count < 2 && !empty($w)) {
            $initials .= strtoupper($w[0]);
            $count++;
        }
    }
    return $initials ?: "U";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        header("Location: " . BASE_URL . "/profile.php");
        exit;
    }

    $formType = $_POST['form_type'] ?? 'update_profile';

    if ($formType === 'add_account') {
        // Only Superuser (E) can add users
        if ($userDb['role'] !== 'E') {
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
        $fullName = trim($_POST['full_name'] ?? '');
        $newUsername = trim($_POST['username'] ?? '');
        $usernameChanged = false;

        if (!$fullName || !$newUsername) {
            $error = "Nama Tampilan dan Username wajib diisi.";
        } else {
            try {
                // Check duplicate username
                if ($newUsername !== $userDb['username']) {
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND id != ?");
                    $stmt->execute([$newUsername, $userDb['id']]);
                    if ($stmt->fetchColumn() > 0) {
                        $error = "Username '$newUsername' sudah digunakan oleh pengguna lain.";
                    } else {
                        $stmt = $pdo->prepare("UPDATE users SET username = ? WHERE id = ?");
                        $stmt->execute([$newUsername, $userDb['id']]);
                        $_SESSION['username'] = $newUsername;
                        $usernameChanged = true;
                    }
                }

                if (!$error) {
                    // Update Full Name
                    $stmt = $pdo->prepare("UPDATE users SET full_name = ? WHERE id = ?");
                    $stmt->execute([$fullName, $userDb['id']]);
                    $_SESSION['full_name'] = $fullName;

                    // Re-fetch userDb to keep it in sync
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->execute([$userDb['id']]);
                    $userDb = $stmt->fetch();

                    $success = $usernameChanged ? "Profil dan Username berhasil diperbarui." : "Profil berhasil diperbarui.";
                }
            } catch (Exception $e) {
                $error = "Gagal mengupdate profil.";
            }
        }
    }
}

// Query statistics based on role
$statsData = [];
if ($userDb['role'] === 'A') { // Reporter
    $stmtDraft = $pdo->prepare("SELECT COUNT(*) FROM news WHERE created_by = ? AND status = 'draft'");
    $stmtDraft->execute([$userDb['id']]);
    $statsData['draft'] = (int)$stmtDraft->fetchColumn();

    $stmtPending = $pdo->prepare("SELECT COUNT(*) FROM news WHERE created_by = ? AND (status LIKE 'pending%' OR status LIKE 'revision%')");
    $stmtPending->execute([$userDb['id']]);
    $statsData['pending'] = (int)$stmtPending->fetchColumn();

    $stmtPublished = $pdo->prepare("SELECT COUNT(*) FROM news WHERE created_by = ? AND status = 'published'");
    $stmtPublished->execute([$userDb['id']]);
    $statsData['published'] = (int)$stmtPublished->fetchColumn();
    
    $statsData['total'] = $statsData['draft'] + $statsData['pending'] + $statsData['published'];
} elseif (in_array($userDb['role'], ['B', 'C', 'D'])) { // Editor, Approver, Peninjau Kejelasan
    $stmtActions = $pdo->prepare("SELECT COUNT(*) FROM news_history WHERE user_id = ?");
    $stmtActions->execute([$userDb['id']]);
    $statsData['total_actions'] = (int)$stmtActions->fetchColumn();

    $stmtUnique = $pdo->prepare("SELECT COUNT(DISTINCT news_id) FROM news_history WHERE user_id = ?");
    $stmtUnique->execute([$userDb['id']]);
    $statsData['unique_news'] = (int)$stmtUnique->fetchColumn();

    $stmtComments = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE user_id = ?");
    $stmtComments->execute([$userDb['id']]);
    $statsData['comments'] = (int)$stmtComments->fetchColumn();

    if ($userDb['role'] === 'B') {
        $stmtPendingSystem = $pdo->query("SELECT COUNT(*) FROM news WHERE status = 'pending_b'");
    } elseif ($userDb['role'] === 'C') {
        $stmtPendingSystem = $pdo->query("SELECT COUNT(*) FROM news WHERE status = 'pending_c'");
    } else { // 'D'
        $stmtPendingSystem = $pdo->query("SELECT COUNT(*) FROM news WHERE status = 'pending_d'");
    }
    $statsData['pending_action'] = (int)$stmtPendingSystem->fetchColumn();
} elseif ($userDb['role'] === 'E') { // Admin
    $statsData['total_users'] = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $statsData['total_news'] = (int)$pdo->query("SELECT COUNT(*) FROM news")->fetchColumn();
    $statsData['total_comments'] = (int)$pdo->query("SELECT COUNT(*) FROM comments")->fetchColumn();
}

// Fetch 5 recent activities for this user
$activityStmt = $pdo->prepare("
    SELECT h.*, n.title AS news_title 
    FROM news_history h 
    LEFT JOIN news n ON h.news_id = n.id 
    WHERE h.user_id = ? 
    ORDER BY h.created_at DESC 
    LIMIT 5
");
$activityStmt->execute([$userDb['id']]);
$recentActivities = $activityStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Pengguna Portal Berita TNI AU</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= time() ?>">
    <style>
        .profile-page-container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 24px 16px;
        }

        .profile-grid {
            display: grid;
            grid-template-columns: 320px 1fr;
            gap: 24px;
            margin-top: 16px;
            align-items: start;
        }

        @media (max-width: 900px) {
            .profile-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Profile Left Card */
        .profile-left-card {
            background: rgba(255, 255, 255, 0.88);
            border: 1px solid rgba(30, 111, 191, 0.12);
            border-radius: var(--radius);
            padding: 20px;
            box-shadow: var(--shadow-sm);
            backdrop-filter: blur(14px);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .profile-banner {
            height: 90px;
            background: linear-gradient(135deg, var(--navy) 0%, var(--navy-light) 100%);
            margin: -20px -20px 0 -20px;
            width: calc(100% + 40px);
            position: relative;
            overflow: hidden;
        }

        /* Styled airplane background element inside banner */
        .profile-banner::after {
            content: '';
            position: absolute;
            right: 10px;
            bottom: -10px;
            width: 80px;
            height: 80px;
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='rgba(255,255,255,0.08)' stroke-width='1.5'%3E%3Cpath d='M21 16v-2l-8-5V3.5c0-.83-.67-1.5-1.5-1.5S10 2.67 10 3.5V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L14 19v-5.5l7 2.5z'/%3E%3C/svg%3E") no-repeat center;
            background-size: contain;
        }

        .profile-avatar-wrap {
            margin-top: -48px;
            margin-bottom: 16px;
            position: relative;
            z-index: 2;
        }

        .profile-avatar {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            font-weight: 700;
            color: #fff;
            box-shadow: var(--shadow-sm);
            border: 4px solid var(--white);
        }

        .avatar-A { background: linear-gradient(135deg, var(--teal), var(--teal-light)); }
        .avatar-B { background: linear-gradient(135deg, #0f9b6e, #2ecc71); }
        .avatar-C { background: linear-gradient(135deg, var(--gold), var(--gold-dark)); }
        .avatar-D { background: linear-gradient(135deg, var(--yellow), #e67e22); }
        .avatar-E { background: linear-gradient(135deg, var(--navy), var(--navy-mid)); border-color: var(--gold-shine); }

        .profile-name {
            font-size: 16px;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 2px;
            text-align: center;
        }

        .profile-uname {
            font-size: 12.5px;
            color: var(--text-sec);
            margin-bottom: 14px;
            font-family: monospace;
            background: rgba(30, 111, 191, 0.06);
            padding: 2px 8px;
            border-radius: 6px;
        }

        .role-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-align: center;
            margin-bottom: 18px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-A { background: var(--teal-bg); color: var(--teal); border: 1px solid rgba(37,99,235,0.2); }
        .badge-B { background: var(--green-bg); color: var(--green); border: 1px solid rgba(15,155,110,0.2); }
        .badge-C { background: var(--gold-light); color: var(--gold-dark); border: 1px solid rgba(201,162,39,0.3); }
        .badge-D { background: var(--yellow-bg); color: var(--yellow); border: 1px solid rgba(212,133,10,0.2); }
        .badge-E { background: linear-gradient(135deg, var(--gold-light), rgba(201,162,39,0.2)); color: var(--gold-dark); border: 1px solid var(--gold); }

        .profile-meta-list {
            width: 100%;
            padding: 12px 0;
            border-top: 1px solid rgba(30,111,191,0.08);
            margin-top: 8px;
        }

        .profile-meta-item {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            padding: 6px 0;
        }

        .profile-meta-item span:first-child {
            color: var(--text-muted);
            font-weight: 500;
        }

        .profile-meta-item span:last-child {
            color: var(--text);
            font-weight: 600;
        }

        .profile-quick-actions {
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 12px;
        }

        .btn-quick {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 600;
            transition: all var(--transition-fast);
            cursor: pointer;
            border: 1px solid transparent;
            text-decoration: none;
            text-align: center;
        }

        .btn-quick.password-btn {
            background: #fff;
            border: 1px solid rgba(30, 111, 191, 0.25);
            color: var(--navy-mid);
        }

        .btn-quick.password-btn:hover {
            background: var(--teal-pale);
            border-color: var(--teal);
            color: var(--navy-light);
        }

        .btn-quick.dashboard-btn {
            background: var(--navy);
            color: #fff;
        }

        .btn-quick.dashboard-btn:hover {
            background: var(--navy-light);
        }

        /* Right cards */
        .profile-right-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .section-card {
            background: rgba(255, 255, 255, 0.88);
            border: 1px solid rgba(30, 111, 191, 0.12);
            border-radius: var(--radius);
            padding: 24px;
            box-shadow: var(--shadow-sm);
            backdrop-filter: blur(14px);
        }

        .card-header-styled {
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid rgba(30,111,191,0.08);
            padding-bottom: 12px;
            margin-bottom: 20px;
        }

        .card-header-styled svg {
            color: var(--teal);
        }

        .card-header-styled h3 {
            font-size: 14.5px;
            font-weight: 700;
            color: var(--navy);
            margin: 0;
        }



        /* Stats Grid */
        .profile-stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }

        @media (max-width: 600px) {
            .profile-stats-grid {
                grid-template-columns: 1fr;
            }
        }

        .stat-box-modern {
            background: var(--white);
            border: 1px solid rgba(30,111,191,0.08);
            border-radius: 14px;
            padding: 18px 14px;
            text-align: center;
            box-shadow: var(--shadow-sm);
            transition: all var(--transition-fast);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .stat-box-modern:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
            border-color: rgba(30,111,191,0.18);
        }

        .stat-box-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 8px;
        }

        .stat-box-icon.bg-blue { background: rgba(30,111,191,0.08); color: var(--teal); }
        .stat-box-icon.bg-green { background: rgba(15,155,110,0.08); color: var(--green); }
        .stat-box-icon.bg-yellow { background: rgba(212,133,10,0.08); color: var(--yellow); }
        .stat-box-icon.bg-gold { background: rgba(201,162,39,0.08); color: var(--gold-dark); }
        .stat-box-icon.bg-red { background: rgba(217,48,37,0.08); color: var(--red); }

        .stat-box-num {
            font-size: 22px;
            font-weight: 700;
            color: var(--navy);
            line-height: 1;
            margin-bottom: 6px;
        }

        .stat-box-label {
            font-size: 11px;
            font-weight: 500;
            color: var(--text-sec);
        }

        /* Timeline */
        .activity-timeline {
            position: relative;
            padding-left: 20px;
            border-left: 2px solid var(--teal-pale);
            margin-left: 10px;
            margin-top: 10px;
        }

        .timeline-item {
            position: relative;
            margin-bottom: 18px;
        }

        .timeline-item:last-child {
            margin-bottom: 0;
        }

        .timeline-dot {
            position: absolute;
            left: -28px;
            top: 4px;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: #fff;
            border: 3px solid var(--teal);
            box-shadow: var(--shadow-sm);
        }

        .timeline-content-box {
            background: rgba(255,255,255,0.6);
            border: 1px solid rgba(30,111,191,0.06);
            border-radius: 10px;
            padding: 10px 14px;
        }

        .timeline-title {
            font-size: 12.5px;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 3px;
        }

        .timeline-note {
            font-size: 11px;
            color: var(--text-sec);
            margin-bottom: 4px;
        }

        .timeline-time {
            font-size: 10px;
            color: var(--text-muted);
            font-style: italic;
        }


    </style>
</head>
<body>
<div class="app-layout">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="main-content">
        <!-- TOP NAVBAR -->
        <div class="top-navbar" style="height:56px">
            <div class="top-navbar-left">
                <button class="hamburger-btn" title="Toggle Menu" aria-label="Toggle menu">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                </button>
                <div class="media-tabs">
                    <span class="media-tab-item active" style="border:none">Profil Pengguna</span>
                </div>
            </div>
            <div class="top-navbar-right">
                <div class="user-dropdown-btn">
                    <?= e($userDb['full_name']) ?>
                </div>
            </div>
        </div>

        <div class="page-container" style="background:var(--bg-body)">
            <div class="profile-page-container">
                
                <?php if ($error): ?>
                    <div style="background:#fceae8;color:#c0392b;padding:12px 16px;border-radius:10px;margin-bottom:16px;border:1px solid rgba(192,57,43,.15);width:100%;">
                        <strong style="font-size:12px;">Peringatan:</strong> <span style="font-size:12.5px;"><?= e($error) ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div style="background:#eafaf1;color:#27ae60;padding:12px 16px;border-radius:10px;margin-bottom:16px;border:1px solid rgba(39,174,96,.15);width:100%;">
                        <strong style="font-size:12px;">Berhasil:</strong> <span style="font-size:12.5px;"><?= e($success) ?></span>
                    </div>
                <?php endif; ?>

                <div class="profile-grid">
                    
                    <!-- LEFT COLUMN: Profile Overview -->
                    <div class="profile-left-card">
                        <div class="profile-banner"></div>
                        <div class="profile-avatar-wrap">
                            <div class="profile-avatar avatar-<?= $userDb['role'] ?>">
                                <?= e(getInitials($userDb['full_name'])) ?>
                            </div>
                        </div>
                        <h2 class="profile-name"><?= e($userDb['full_name']) ?></h2>
                        <div class="profile-uname">@<?= e($userDb['username']) ?></div>
                        
                        <div class="role-badge badge-<?= $userDb['role'] ?>">
                            <?= e(userDisplayName($userDb['role'])) ?>
                        </div>

                        <div class="profile-meta-list">
                            <div class="profile-meta-item">
                                <span>Status Akun</span>
                                <span style="color:var(--green)">Aktif</span>
                            </div>
                            <div class="profile-meta-item">
                                <span>Anggota Sejak</span>
                                <span><?= e(isset($userDb['created_at']) ? date('d M Y', strtotime($userDb['created_at'])) : '-') ?></span>
                            </div>
                        </div>

                        <div class="profile-quick-actions">
                            <a href="<?= BASE_URL ?>/change_password.php" class="btn-quick password-btn">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                                Ganti Password
                            </a>
                            <a href="<?= BASE_URL ?>/index.php" class="btn-quick dashboard-btn">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                                Beranda Portal
                            </a>
                        </div>
                    </div>

                    <!-- RIGHT COLUMN: Edit Form, Stats, Activity -->
                    <div class="profile-right-container">
                        
                        <!-- CARD 1: Edit profile -->
                        <div class="section-card">
                            <div class="card-header-styled">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                                <h3>Informasi Dasar Profil</h3>
                            </div>
                            
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
                                <input type="hidden" name="form_type" value="update_profile">
                                
                                <div style="display: flex; flex-direction: column; gap: 16px; margin-bottom: 20px;">
                                    <div class="input-group">
                                        <label style="margin-bottom: 6px; display: block; font-size: 13px; font-weight: 600; color: var(--text-sec);">Username</label>
                                        <input type="text" name="username" class="form-input" value="<?= e($userDb['username']) ?>" style="width: 100%; height: 40px; border-radius: 6px; border: 1px solid #ced4da; padding: 0 12px; font-size: 14px;" required>
                                    </div>
                                    <div class="input-group">
                                        <label style="margin-bottom: 6px; display: block; font-size: 13px; font-weight: 600; color: var(--text-sec);">Nama Tampilan</label>
                                        <input type="text" name="full_name" class="form-input" value="<?= e($userDb['full_name']) ?>" style="width: 100%; height: 40px; border-radius: 6px; border: 1px solid #ced4da; padding: 0 12px; font-size: 14px;" required>
                                    </div>
                                    <div class="input-group">
                                        <label style="margin-bottom: 6px; display: block; font-size: 13px; font-weight: 600; color: var(--text-sec);">Hak Akses</label>
                                        <input type="text" class="form-input" value="<?= e(userDisplayName($userDb['role'])) ?>" style="width: 100%; height: 40px; border-radius: 6px; border: 1px solid #ced4da; padding: 0 12px; font-size: 14px; background: #f8f9fa; color: #6c757d;" readonly>
                                        <div style="font-size: 11px; color: var(--text-muted); font-style: italic; margin-top: 6px;">
                                            * Peran akun diatur secara terpusat oleh Administrator.
                                        </div>
                                    </div>
                                </div>

                                <div style="display: flex; justify-content: flex-end;">
                                    <button type="submit" class="btn btn-primary" style="padding: 10px 24px; font-size: 14px;">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px;"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                                        Simpan Perubahan
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- CARD 2: Contribution Stats -->
                        <div class="section-card">
                            <div class="card-header-styled">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                                <h3>Statistik Kontribusi & Sistem</h3>
                            </div>

                            <div class="profile-stats-grid">
                                <?php if ($userDb['role'] === 'A'): ?>
                                    <!-- Reporter Stats -->
                                    <div class="stat-box-modern">
                                        <div class="stat-box-icon bg-blue">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                                        </div>
                                        <div class="stat-box-num"><?= $statsData['total'] ?></div>
                                        <div class="stat-box-label">Total Berita Dibuat</div>
                                    </div>
                                    <div class="stat-box-modern">
                                        <div class="stat-box-icon bg-green">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                                        </div>
                                        <div class="stat-box-num"><?= $statsData['published'] ?></div>
                                        <div class="stat-box-label">Berita Dipublikasikan</div>
                                    </div>
                                    <div class="stat-box-modern">
                                        <div class="stat-box-icon bg-yellow">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                        </div>
                                        <div class="stat-box-num"><?= $statsData['pending'] ?></div>
                                        <div class="stat-box-label">Berita Pending / Revisi</div>
                                    </div>
                                <?php elseif (in_array($userDb['role'], ['B', 'C', 'D'])): ?>
                                    <!-- Reviewer / Editor / Approver Stats -->
                                    <div class="stat-box-modern">
                                        <div class="stat-box-icon bg-blue">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                                        </div>
                                        <div class="stat-box-num"><?= $statsData['total_actions'] ?></div>
                                        <div class="stat-box-label">Total Aksi Peninjauan</div>
                                    </div>
                                    <div class="stat-box-modern">
                                        <div class="stat-box-icon bg-green">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                                        </div>
                                        <div class="stat-box-num"><?= $statsData['unique_news'] ?></div>
                                        <div class="stat-box-label">Berita Berbeda Ditinjau</div>
                                    </div>
                                    <div class="stat-box-modern">
                                        <div class="stat-box-icon bg-yellow">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                        </div>
                                        <div class="stat-box-num"><?= $statsData['pending_action'] ?></div>
                                        <div class="stat-box-label">Menunggu Review Anda</div>
                                    </div>
                                <?php elseif ($userDb['role'] === 'E'): ?>
                                    <!-- Administrator Stats -->
                                    <div class="stat-box-modern">
                                        <div class="stat-box-icon bg-gold">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                                        </div>
                                        <div class="stat-box-num"><?= $statsData['total_users'] ?></div>
                                        <div class="stat-box-label">Total Pengguna Sistem</div>
                                    </div>
                                    <div class="stat-box-modern">
                                        <div class="stat-box-icon bg-blue">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                                        </div>
                                        <div class="stat-box-num"><?= $statsData['total_news'] ?></div>
                                        <div class="stat-box-label">Total Berita Portal</div>
                                    </div>
                                    <div class="stat-box-modern">
                                        <div class="stat-box-icon bg-green">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                                        </div>
                                        <div class="stat-box-num"><?= $statsData['total_comments'] ?></div>
                                        <div class="stat-box-label">Total Komentar & Koreksi</div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- CARD 3: Recent Activity -->
                        <div class="section-card">
                            <div class="card-header-styled">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                <h3>Riwayat Aktivitas Terbaru</h3>
                            </div>

                            <?php if (empty($recentActivities)): ?>
                                <div style="text-align:center; padding: 24px 0; color: var(--text-sec); font-size:13px;">
                                    Belum ada catatan aktivitas untuk akun Anda.
                                </div>
                            <?php else: ?>
                                <div class="activity-timeline">
                                    <?php foreach ($recentActivities as $act): ?>
                                        <div class="timeline-item">
                                            <div class="timeline-dot"></div>
                                            <div class="timeline-content-box">
                                                <div class="timeline-title"><?= e($act['news_title'] ?? 'Aksi Sistem / Lainnya') ?></div>
                                                <div class="timeline-note"><?= e($act['note']) ?></div>
                                                <div class="timeline-time"><?= e(formatTanggal($act['created_at'])) ?></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- CARD 4: Admin Add Account (Superuser only) -->
                        <?php if ($userDb['role'] === 'E'): ?>
                            <div class="section-card" style="margin-bottom: 24px;">
                                <div class="card-header-styled">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                                    <h3>Tambah Akun Baru (Akses Administrator)</h3>
                                </div>

                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
                                    <input type="hidden" name="form_type" value="add_account">
                                    
                                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-bottom: 20px;">
                                        <div class="input-group">
                                            <label style="margin-bottom: 6px; display: block; font-size: 13px; font-weight: 600; color: var(--text-sec);">Username Baru</label>
                                            <input type="text" name="new_username" class="form-input" placeholder="Masukkan username baru" style="width: 100%; height: 40px; border-radius: 6px; border: 1px solid #ced4da; padding: 0 12px; font-size: 14px;" required>
                                        </div>
                                        
                                        <div class="input-group">
                                            <label style="margin-bottom: 6px; display: block; font-size: 13px; font-weight: 600; color: var(--text-sec);">Nama Tampilan</label>
                                            <input type="text" name="new_full_name" class="form-input" placeholder="Masukkan nama lengkap" style="width: 100%; height: 40px; border-radius: 6px; border: 1px solid #ced4da; padding: 0 12px; font-size: 14px;" required>
                                        </div>
                                        
                                        <div class="input-group">
                                            <label style="margin-bottom: 6px; display: block; font-size: 13px; font-weight: 600; color: var(--text-sec);">Hak Akses</label>
                                            <select name="new_role" class="form-input" style="width: 100%; height: 40px; border-radius: 6px; border: 1px solid #ced4da; padding: 0 12px; font-size: 14px;" required>
                                                <option value="A">Reporter</option>
                                                <option value="B">Editor</option>
                                                <option value="C">Penyetuju (Approver)</option>
                                                <option value="D">Peninjau Kejelasan</option>
                                                <option value="E">Administrator</option>
                                            </select>
                                        </div>
                                        
                                        <div class="input-group">
                                            <label style="margin-bottom: 6px; display: block; font-size: 13px; font-weight: 600; color: var(--text-sec);">Kata Sandi Akun</label>
                                            <input type="password" name="new_password" class="form-input" placeholder="Masukkan password baru" style="width: 100%; height: 40px; border-radius: 6px; border: 1px solid #ced4da; padding: 0 12px; font-size: 14px;" required>
                                        </div>
                                    </div>

                                    <div style="display: flex; justify-content: flex-end;">
                                        <button type="submit" class="btn btn-primary" style="padding: 10px 24px; font-size: 14px; background: var(--teal);">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px;"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                                            Daftarkan Akun Baru
                                        </button>
                                    </div>
                                </form>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>

            </div>
        </div>
    </main>
</div>
</body>
</html>

