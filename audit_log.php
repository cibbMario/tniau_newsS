<?php
require_once __DIR__ . '/config/config.php';
requireLogin();
$user = currentUser();

// Hanya Role E (Admin) atau C (Petinggi) yang dapat mengakses
if (!in_array($user['role'], ['E', 'C'])) {
    die("Akses ditolak. Halaman ini hanya untuk Administrator atau Pimpinan.");
}

$current = 'audit_log';

// Filter & Pagination
$filterUser = $_GET['user_id'] ?? '';
$filterAction = $_GET['action'] ?? '';
$filterDate = $_GET['date'] ?? '';

$sql = "SELECT a.*, u.username, u.full_name, u.role, u.lanud 
        FROM audit_logs a
        LEFT JOIN users u ON a.user_id = u.id
        WHERE 1=1";
$params = [];

if ($filterUser) {
    $sql .= " AND a.user_id = ?";
    $params[] = $filterUser;
}
if ($filterAction) {
    $sql .= " AND a.action = ?";
    $params[] = $filterAction;
}
if ($filterDate) {
    $sql .= " AND DATE(a.created_at) = ?";
    $params[] = $filterDate;
}

$sql .= " ORDER BY a.created_at DESC LIMIT 100";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Get users for dropdown
$users = $pdo->query("SELECT id, username, full_name, role FROM users ORDER BY full_name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Aktivitas & Audit Trail — Portal Berita TNI AU</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= time() ?>">
</head>
<body class="dashboard-body">
<div class="app-layout">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include __DIR__ . '/includes/topbar.php'; ?>

        <div class="page-content">
            <div class="page-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px; margin-bottom:24px;">
                <div>
                    <h1 class="page-title" style="margin:0; font-size:22px; color:#1a202c;">Audit Trail & Log Aktivitas Sistem</h1>
                    <p style="margin:4px 0 0; color:#718096; font-size:13px;">Rekam jejak seluruh aktivitas login, editorial review, dan perubahan akun sistem.</p>
                </div>
                <div>
                    <a href="<?= BASE_URL ?>/users_management.php" class="btn btn-outline">
                        &larr; Kembali ke Manajemen Pengguna
                    </a>
                </div>
            </div>

            <!-- Filter Box -->
            <div class="card" style="margin-bottom:20px; padding:16px;">
                <form method="GET" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)) 120px; gap:12px; align-items:end;">
                    <div class="form-group" style="margin:0;">
                        <label style="font-size:12px; margin-bottom:4px;">Filter Pengguna</label>
                        <select name="user_id" class="form-input">
                            <option value="">-- Semua Pengguna --</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?= $u['id'] ?>" <?= $filterUser == $u['id'] ? 'selected' : '' ?>>
                                    <?= e($u['full_name']) ?> (<?= e($u['username']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label style="font-size:12px; margin-bottom:4px;">Jenis Aksi</label>
                        <select name="action" class="form-input">
                            <option value="">-- Semua Aksi --</option>
                            <option value="LOGIN" <?= $filterAction==='LOGIN'?'selected':'' ?>>LOGIN</option>
                            <option value="LOGOUT" <?= $filterAction==='LOGOUT'?'selected':'' ?>>LOGOUT</option>
                            <option value="LOGIN_FAILED" <?= $filterAction==='LOGIN_FAILED'?'selected':'' ?>>LOGIN GAGAL</option>
                            <option value="CREATE_USER" <?= $filterAction==='CREATE_USER'?'selected':'' ?>>TAMBAH PENGGUNA</option>
                            <option value="UPDATE_USER" <?= $filterAction==='UPDATE_USER'?'selected':'' ?>>UPDATE PENGGUNA</option>
                            <option value="RESET_PASSWORD" <?= $filterAction==='RESET_PASSWORD'?'selected':'' ?>>RESET PASSWORD</option>
                            <option value="TOGGLE_USER_STATUS" <?= $filterAction==='TOGGLE_USER_STATUS'?'selected':'' ?>>STATUS PENGGUNA</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label style="font-size:12px; margin-bottom:4px;">Tanggal</label>
                        <input type="date" name="date" class="form-input" value="<?= e($filterDate) ?>">
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary" style="width:100%;">Filter Log</button>
                    </div>
                </form>
            </div>

            <!-- Table Logs -->
            <div class="card">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Waktu & Tanggal</th>
                                <th>Pengguna</th>
                                <th>Aksi</th>
                                <th>Detail Aktivitas</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="5" style="text-align:center; padding:30px; color:#a0aec0;">Belum ada catatan log aktivitas yang cocok.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $l): ?>
                                    <tr>
                                        <td style="white-space:nowrap; font-size:12px; color:#718096;">
                                            <?= date('d M Y H:i:s', strtotime($l['created_at'])) ?>
                                        </td>
                                        <td>
                                            <?php if ($l['username']): ?>
                                                <strong><?= e($l['full_name']) ?></strong>
                                                <div style="font-size:11px; color:#a0aec0;"><?= e($l['username']) ?> &bull; <?= userDisplayName($l['role']) ?></div>
                                            <?php else: ?>
                                                <em style="color:#a0aec0;">Sistem / Tamu</em>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge" style="font-size:11px; font-weight:600; background:#edf2f7; color:#2d3748;">
                                                <?= e($l['action']) ?>
                                            </span>
                                        </td>
                                        <td style="font-size:13px; color:#2d3748;">
                                            <?= e($l['details'] ?? '-') ?>
                                        </td>
                                        <td>
                                            <code style="font-size:11px; background:#f7fafc; padding:2px 6px; border-radius:4px;"><?= e($l['ip_address']) ?></code>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>
</body>
</html>
