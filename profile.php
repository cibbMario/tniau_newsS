<?php
require_once __DIR__ . '/config/config.php';
requireLogin();
$current = 'profile';
$user = currentUser();

// Load full user details from DB
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

    if ($formType === 'change_password') {
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
            $stmt->execute([$userDb['id']]);
            $dbPass = $stmt->fetchColumn();

            if (!password_verify($oldPass, $dbPass)) {
                $error = 'Password lama yang Anda masukkan tidak sesuai.';
            } else {
                $newHash = password_hash($newPass, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                $stmt->execute([$newHash, $userDb['id']]);
                $success = 'Password berhasil diperbarui. Gunakan password baru Anda saat login berikutnya.';
            }
        }
    } elseif ($formType === 'add_account') {
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
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
                    $stmt->execute([$newUsername]);
                    if ($stmt->fetchColumn() > 0) {
                        $error = "Username '$newUsername' sudah digunakan.";
                    } else {
                        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, full_name, role) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$newUsername, $hash, $newFullName, $newRole]);
                        $success = "Akun baru '$newUsername' berhasil ditambahkan ke sistem.";
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
                    $stmt = $pdo->prepare("UPDATE users SET full_name = ? WHERE id = ?");
                    $stmt->execute([$fullName, $userDb['id']]);
                    $_SESSION['full_name'] = $fullName;

                    // Re-fetch userDb to keep in sync
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
    } else {
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
    <title>Profil Pengguna - Portal Berita TNI AU</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= time() ?>">
    <style>
        .profile-page-container {
            max-width: 1140px;
            margin: 0 auto;
            padding: 24px 20px 48px 20px;
        }

        .profile-header-meta {
            margin-bottom: 24px;
        }

        .profile-header-meta h1 {
            font-size: 22px;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 4px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .profile-header-meta p {
            font-size: 13px;
            color: #64748b;
            margin: 0;
        }

        .profile-grid {
            display: grid;
            grid-template-columns: 320px 1fr;
            gap: 24px;
            align-items: start;
        }

        @media (max-width: 900px) {
            .profile-grid {
                grid-template-columns: 1fr;
            }
        }

        /* LEFT CARD: User Overview */
        .profile-card-left {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding-bottom: 20px;
        }

        .profile-card-banner {
            height: 96px;
            background: #121358;
            width: 100%;
            position: relative;
            border-bottom: 3px solid #C9A227;
        }

        .profile-avatar-wrapper {
            margin-top: -48px;
            position: relative;
            z-index: 2;
            margin-bottom: 12px;
        }

        .profile-avatar-circle {
            width: 88px;
            height: 88px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            font-weight: 700;
            color: #ffffff;
            border: 4px solid #ffffff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
            background: #121358;
        }

        .avatar-A { background: linear-gradient(135deg, #1d4ed8, #2563eb); }
        .avatar-B { background: linear-gradient(135deg, #047857, #10b981); }
        .avatar-C { background: linear-gradient(135deg, #b45309, #d97706); }
        .avatar-D { background: linear-gradient(135deg, #c2410c, #ea580c); }
        .avatar-E { background: linear-gradient(135deg, #0f172a, #121358); border-color: #C9A227; }

        .profile-user-name {
            font-size: 16.5px;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 2px 0;
            text-align: center;
            padding: 0 16px;
        }

        .profile-username-tag {
            font-size: 12px;
            color: #475569;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            background: #f1f5f9;
            padding: 3px 10px;
            border-radius: 14px;
            margin-bottom: 14px;
            border: 1px solid #e2e8f0;
        }

        .role-badge-solid {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 20px;
        }

        .badge-solid-A { background: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe; }
        .badge-solid-B { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .badge-solid-C { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .badge-solid-D { background: #ffedd5; color: #9a3412; border: 1px solid #fed7aa; }
        .badge-solid-E { background: #121358; color: #ffffff; border: 1px solid #C9A227; }

        .profile-info-list {
            width: 100%;
            padding: 0 20px;
            margin-bottom: 16px;
        }

        .info-list-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12.5px;
            padding: 10px 0;
            border-top: 1px solid #f1f5f9;
        }

        .info-list-item:first-child {
            border-top: none;
        }

        .info-label {
            color: #64748b;
            font-weight: 500;
        }

        .info-val {
            color: #0f172a;
            font-weight: 600;
        }

        .status-dot-active {
            width: 8px;
            height: 8px;
            background: #22c55e;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
        }

        .profile-side-actions {
            width: 100%;
            padding: 0 20px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .btn-side {
            width: 100%;
            padding: 9px 14px;
            border-radius: 8px;
            font-size: 12.5px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.15s ease;
            border: 1px solid transparent;
        }

        .btn-side-outline {
            background: #ffffff;
            color: #334155;
            border-color: #cbd5e1;
        }

        .btn-side-outline:hover {
            background: #f8fafc;
            color: #0f172a;
            border-color: #94a3b8;
        }

        .btn-side-navy {
            background: #121358;
            color: #ffffff;
        }

        .btn-side-navy:hover {
            background: #1c1d6b;
        }

        /* RIGHT SECTION: Content Cards */
        .profile-right-stack {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .content-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .content-card-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-bottom: 14px;
            border-bottom: 1px solid #f1f5f9;
            margin-bottom: 20px;
        }

        .content-card-title-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .content-card-title-left svg {
            color: #2563eb;
        }

        .content-card-title h2 {
            font-size: 15px;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
        }

        /* FORM STYLING UX */
        .form-grid-2 {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }

        @media (max-width: 640px) {
            .form-grid-2 {
                grid-template-columns: 1fr;
            }
        }

        .form-group-custom {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 16px;
        }

        .form-group-custom label {
            font-size: 12.5px;
            font-weight: 600;
            color: #334155;
        }

        .form-control-custom {
            width: 100%;
            height: 42px;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            padding: 0 14px;
            font-size: 13.5px;
            color: #0f172a;
            background: #ffffff;
            outline: none;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .form-control-custom:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        .form-control-custom[readonly] {
            background: #f8fafc;
            color: #64748b;
            cursor: not-allowed;
            border-color: #e2e8f0;
        }

        .form-help-text {
            font-size: 11.5px;
            color: #64748b;
            line-height: 1.4;
        }

        .role-info-callout {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 12px;
            color: #1e40af;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 4px;
        }

        .form-actions-row {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 8px;
            padding-top: 16px;
            border-top: 1px solid #f1f5f9;
        }

        .btn-submit-navy {
            background: #121358;
            color: #ffffff;
            font-weight: 600;
            font-size: 13px;
            padding: 10px 22px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background 0.15s ease;
        }

        .btn-submit-navy:hover {
            background: #1c1d6b;
        }

        /* METRIC STATS GRID */
        .metric-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }

        @media (max-width: 640px) {
            .metric-grid {
                grid-template-columns: 1fr;
            }
        }

        .metric-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 18px;
            display: flex;
            align-items: center;
            gap: 14px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
        }

        .metric-icon-box {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .metric-icon-blue { background: #eff6ff; color: #2563eb; }
        .metric-icon-green { background: #f0fdf4; color: #16a34a; }
        .metric-icon-amber { background: #fffbeb; color: #d97706; }
        .metric-icon-gold { background: #fefce8; color: #a16207; }

        .metric-details {
            display: flex;
            flex-direction: column;
        }

        .metric-number {
            font-size: 22px;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.1;
        }

        .metric-label {
            font-size: 12px;
            color: #64748b;
            font-weight: 500;
            margin-top: 3px;
        }

        /* TIMELINE ACTIVITIY */
        .timeline-clean {
            position: relative;
            padding-left: 24px;
        }

        .timeline-clean::before {
            content: '';
            position: absolute;
            left: 7px;
            top: 6px;
            bottom: 6px;
            width: 2px;
            background: #e2e8f0;
        }

        .timeline-clean-item {
            position: relative;
            margin-bottom: 20px;
        }

        .timeline-clean-item:last-child {
            margin-bottom: 0;
        }

        .timeline-node {
            position: absolute;
            left: -24px;
            top: 3px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: #ffffff;
            border: 3px solid #2563eb;
        }

        .timeline-clean-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px 16px;
        }

        .timeline-clean-title {
            font-size: 13px;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 2px;
        }

        .timeline-clean-desc {
            font-size: 12px;
            color: #475569;
            margin-bottom: 6px;
        }

        .timeline-clean-time {
            font-size: 11px;
            color: #94a3b8;
        }

        /* ALERT BANNERS UX */
        .alert-box-ux {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 13px;
        }

        .alert-error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .alert-success {
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        /* PASSWORD SECTION STYLES */
        .profile-section-tabs {
            display: flex;
            gap: 4px;
            background: #f1f5f9;
            border-radius: 10px;
            padding: 4px;
            margin-bottom: 24px;
        }

        .profile-section-tab {
            flex: 1;
            padding: 8px 12px;
            border-radius: 7px;
            border: none;
            background: transparent;
            font-size: 12.5px;
            font-weight: 600;
            color: #64748b;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            transition: all 0.18s ease;
        }

        .profile-section-tab.active {
            background: #ffffff;
            color: #0f172a;
            box-shadow: 0 1px 3px rgba(0,0,0,0.10);
        }

        .profile-section-tab:hover:not(.active) {
            background: rgba(255,255,255,0.6);
            color: #334155;
        }

        .profile-tab-pane { display: none; }
        .profile-tab-pane.active { display: block; }

        /* Password field styles */
        .pw-field {
            margin-bottom: 18px;
        }

        .pw-field label {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 700;
            color: #334155;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .pw-input-wrap {
            position: relative;
        }

        .pw-input-wrap input[type="password"],
        .pw-input-wrap input[type="text"] {
            width: 100%;
            height: 42px;
            border: 1.5px solid #cbd5e1;
            border-radius: 8px;
            padding: 0 44px 0 14px;
            font-size: 13.5px;
            color: #0f172a;
            background: #ffffff;
            outline: none;
            box-sizing: border-box;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .pw-input-wrap input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37,99,235,0.12);
        }

        .pw-toggle-eye {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #94a3b8;
            padding: 0;
            display: flex;
            align-items: center;
            transition: color 0.15s;
        }

        .pw-toggle-eye:hover { color: #334155; }

        .pw-strength-bar {
            height: 4px;
            border-radius: 4px;
            background: #e2e8f0;
            overflow: hidden;
            margin-top: 6px;
        }

        .pw-strength-fill {
            height: 100%;
            width: 0;
            border-radius: 4px;
            transition: width 0.3s, background 0.3s;
        }

        .pw-hint {
            font-size: 11.5px;
            color: #64748b;
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .pw-requirements-box {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            padding: 12px 14px;
            margin-bottom: 20px;
        }

        .pw-requirements-box strong {
            font-size: 12px;
            color: #1d4ed8;
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 8px;
        }

        .pw-requirements-box ul {
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .pw-requirements-box ul li {
            font-size: 12px;
            color: #1e3a8a;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .pw-requirements-box ul li::before {
            content: '';
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background: #3b82f6;
            flex-shrink: 0;
        }

        .pw-divider {
            height: 1px;
            background: #f1f5f9;
            margin: 18px 0;
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

        <div class="page-container" style="background:#f8fafc">
            <div class="profile-page-container">

                <div class="profile-header-meta">
                    <h1>
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                        Profil Pengguna
                    </h1>
                    <p>Kelola data profil pribadi, lihat statistik kontribusi, dan pantau catatan aktivitas Anda di Portal Berita TNI AU.</p>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert-box-ux alert-error">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                        <div><?= e($error) ?></div>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert-box-ux alert-success">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                        <div><?= e($success) ?></div>
                    </div>
                <?php endif; ?>

                <div class="profile-grid">
                    
                    <!-- LEFT COLUMN: Card Identity Overview -->
                    <div class="profile-card-left">
                        <div class="profile-card-banner"></div>
                        <div class="profile-avatar-wrapper">
                            <div class="profile-avatar-circle avatar-<?= $userDb['role'] ?>">
                                <?= e(getInitials($userDb['full_name'])) ?>
                            </div>
                        </div>
                        
                        <h2 class="profile-user-name"><?= e($userDb['full_name']) ?></h2>
                        <div class="profile-username-tag">@<?= e($userDb['username']) ?></div>
                        
                        <div class="role-badge-solid badge-solid-<?= $userDb['role'] ?>">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                            <?= e(userDisplayName($userDb['role'])) ?>
                        </div>

                        <div class="profile-info-list">
                            <div class="info-list-item">
                                <span class="info-label">Status Akun</span>
                                <span class="info-val"><span class="status-dot-active"></span>Aktif</span>
                            </div>
                            <div class="info-list-item">
                                <span class="info-label">Terdaftar Sejak</span>
                                <span class="info-val"><?= e(isset($userDb['created_at']) ? date('d M Y', strtotime($userDb['created_at'])) : '-') ?></span>
                            </div>
                            <div class="info-list-item">
                                <span class="info-label">Satuan / Wilayah</span>
                                <span class="info-val"><?= e(!empty($userDb['lanud']) ? strtoupper($userDb['lanud']) : 'MABES TNI AU') ?></span>
                            </div>
                        </div>

                        <div class="profile-side-actions">
                            <a href="<?= BASE_URL ?>/change_password.php" class="btn-side btn-side-outline">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                                Ganti Password
                            </a>
                            <a href="<?= BASE_URL ?>/index.php" class="btn-side btn-side-navy">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                                Beranda Portal
                            </a>
                        </div>
                    </div>

                    <!-- RIGHT COLUMN: Interactive Forms & Activity Stack -->
                    <div class="profile-right-stack">
                        
                        <!-- CARD 1: Edit Profile Form -->
                        <div class="content-card">
                            <div class="content-card-title">
                                <div class="content-card-title-left">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    <h2>Informasi Dasar Profil</h2>
                                </div>
                            </div>
                            
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
                                <input type="hidden" name="form_type" value="update_profile">
                                
                                <div class="form-grid-2">
                                    <div class="form-group-custom">
                                        <label for="username_field">Username</label>
                                        <input type="text" id="username_field" name="username" class="form-control-custom" value="<?= e($userDb['username']) ?>" required>
                                        <span class="form-help-text">Username unik yang digunakan saat masuk ke portal.</span>
                                    </div>

                                    <div class="form-group-custom">
                                        <label for="fullname_field">Nama Tampilan</label>
                                        <input type="text" id="fullname_field" name="full_name" class="form-control-custom" value="<?= e($userDb['full_name']) ?>" required>
                                        <span class="form-help-text">Nama lengkap yang ditampilkan di sistem & artikel.</span>
                                    </div>
                                </div>

                                <div class="form-group-custom" style="margin-top: 8px;">
                                    <label>Hak Akses Sistem</label>
                                    <input type="text" class="form-control-custom" value="<?= e(userDisplayName($userDb['role'])) ?>" readonly>
                                    <div class="role-info-callout">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                                        <span>Peran akun diatur secara terpusat oleh Administrator TNI AU.</span>
                                    </div>
                                </div>

                                <div class="form-actions-row">
                                    <button type="submit" class="btn-submit-navy">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                                        Simpan Perubahan
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- CARD 2: Contribution Statistics -->
                        <div class="content-card">
                            <div class="content-card-title">
                                <div class="content-card-title-left">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                                    <h2>Statistik Kontribusi & Sistem</h2>
                                </div>
                            </div>

                            <div class="metric-grid">
                                <?php if ($userDb['role'] === 'A'): ?>
                                    <!-- Reporter Stats -->
                                    <div class="metric-card">
                                        <div class="metric-icon-box metric-icon-blue">
                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                                        </div>
                                        <div class="metric-details">
                                            <div class="metric-number"><?= $statsData['total'] ?></div>
                                            <div class="metric-label">Total Berita Dibuat</div>
                                        </div>
                                    </div>

                                    <div class="metric-card">
                                        <div class="metric-icon-box metric-icon-green">
                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                                        </div>
                                        <div class="metric-details">
                                            <div class="metric-number"><?= $statsData['published'] ?></div>
                                            <div class="metric-label">Sudah Dipublikasi</div>
                                        </div>
                                    </div>

                                    <div class="metric-card">
                                        <div class="metric-icon-box metric-icon-amber">
                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                        </div>
                                        <div class="metric-details">
                                            <div class="metric-number"><?= $statsData['pending'] ?></div>
                                            <div class="metric-label">Pending / Revisi</div>
                                        </div>
                                    </div>
                                <?php elseif (in_array($userDb['role'], ['B', 'C', 'D'])): ?>
                                    <!-- Reviewer / Editor / Approver Stats -->
                                    <div class="metric-card">
                                        <div class="metric-icon-box metric-icon-blue">
                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                                        </div>
                                        <div class="metric-details">
                                            <div class="metric-number"><?= $statsData['total_actions'] ?></div>
                                            <div class="metric-label">Total Aksi Peninjauan</div>
                                        </div>
                                    </div>

                                    <div class="metric-card">
                                        <div class="metric-icon-box metric-icon-green">
                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                                        </div>
                                        <div class="metric-details">
                                            <div class="metric-number"><?= $statsData['unique_news'] ?></div>
                                            <div class="metric-label">Berita Berbeda Ditinjau</div>
                                        </div>
                                    </div>

                                    <div class="metric-card">
                                        <div class="metric-icon-box metric-icon-amber">
                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 16 14"></polyline></svg>
                                        </div>
                                        <div class="metric-details">
                                            <div class="metric-number"><?= $statsData['pending_action'] ?></div>
                                            <div class="metric-label">Menunggu Review Anda</div>
                                        </div>
                                    </div>
                                <?php elseif ($userDb['role'] === 'E'): ?>
                                    <!-- Administrator Stats -->
                                    <div class="metric-card">
                                        <div class="metric-icon-box metric-icon-gold">
                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                                        </div>
                                        <div class="metric-details">
                                            <div class="metric-number"><?= $statsData['total_users'] ?></div>
                                            <div class="metric-label">Total Pengguna System</div>
                                        </div>
                                    </div>

                                    <div class="metric-card">
                                        <div class="metric-icon-box metric-icon-blue">
                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                                        </div>
                                        <div class="metric-details">
                                            <div class="metric-number"><?= $statsData['total_news'] ?></div>
                                            <div class="metric-label">Total Berita Portal</div>
                                        </div>
                                    </div>

                                    <div class="metric-card">
                                        <div class="metric-icon-box metric-icon-green">
                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                                        </div>
                                        <div class="metric-details">
                                            <div class="metric-number"><?= $statsData['total_comments'] ?></div>
                                            <div class="metric-label">Komentar & Catatan</div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- CARD 3: Recent Activity -->
                        <div class="content-card">
                            <div class="content-card-title">
                                <div class="content-card-title-left">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                    <h2>Riwayat Aktivitas Terbaru</h2>
                                </div>
                            </div>

                            <?php if (empty($recentActivities)): ?>
                                <div style="text-align:center; padding: 28px 0; color: #64748b; font-size:13px;">
                                    Belum ada catatan aktivitas untuk akun Anda.
                                </div>
                            <?php else: ?>
                                <div class="timeline-clean">
                                    <?php foreach ($recentActivities as $act): ?>
                                        <div class="timeline-clean-item">
                                            <div class="timeline-node"></div>
                                            <div class="timeline-clean-card">
                                                <div class="timeline-clean-title"><?= e($act['news_title'] ?? 'Aksi Sistem / Lainnya') ?></div>
                                                <div class="timeline-clean-desc"><?= e($act['note']) ?></div>
                                                <div class="timeline-clean-time"><?= e(formatTanggal($act['created_at'])) ?> (<?= e(timeAgo($act['created_at'])) ?>)</div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- CARD 4: Superuser Add Account (Admin only) -->
                        <?php if ($userDb['role'] === 'E'): ?>
                            <div class="content-card">
                                <div class="content-card-title">
                                    <div class="content-card-title-left">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><user-plus><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="17" y1="11" x2="23" y2="11"></line></user-plus></svg>
                                        <h2>Tambah Akun Pengguna Baru (Akses Administrator)</h2>
                                    </div>
                                </div>

                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
                                    <input type="hidden" name="form_type" value="add_account">
                                    
                                    <div class="form-grid-2">
                                        <div class="form-group-custom">
                                            <label for="new_username_field">Username Baru</label>
                                            <input type="text" id="new_username_field" name="new_username" class="form-control-custom" placeholder="Masukkan username baru" required>
                                        </div>

                                        <div class="form-group-custom">
                                            <label for="new_fullname_field">Nama Tampilan</label>
                                            <input type="text" id="new_fullname_field" name="new_full_name" class="form-control-custom" placeholder="Masukkan nama lengkap" required>
                                        </div>

                                        <div class="form-group-custom">
                                            <label for="new_role_field">Hak Akses / Peran</label>
                                            <select id="new_role_field" name="new_role" class="form-control-custom" required>
                                                <option value="A">Reporter</option>
                                                <option value="B">Editor</option>
                                                <option value="C">Penyetuju (Approver)</option>
                                                <option value="D">Peninjau Kejelasan</option>
                                                <option value="E">Administrator</option>
                                            </select>
                                        </div>

                                        <div class="form-group-custom">
                                            <label for="new_password_field">Kata Sandi Akun</label>
                                            <input type="password" id="new_password_field" name="new_password" class="form-control-custom" placeholder="Masukkan kata sandi baru" required>
                                        </div>
                                    </div>

                                    <div class="form-actions-row">
                                        <button type="submit" class="btn-submit-navy">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
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
