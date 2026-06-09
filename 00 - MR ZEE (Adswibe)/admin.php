<?php
/**
 * ============================================================
 * ADSWIBE® — ADMIN SUBMISSIONS PANEL
 * Password-protected view of all form submissions
 * ============================================================
 * Access: https://yourdomain.com/admin.php
 * ⚠️  IMPORTANT: Change the password below before going live!
 * ============================================================
 */

// ── CHANGE THIS PASSWORD ──────────────────────────────────────
define('ADMIN_PASSWORD', 'Adswibe2026!Admin');   // ← Change this!
define('DB_FILE', __DIR__ . '/backend/submissions.db');

session_start();

// Simple login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if ($_POST['password'] === ADMIN_PASSWORD) {
        $_SESSION['adswibe_admin'] = true;
        header('Location: admin.php'); exit;
    } else {
        $loginError = 'Incorrect password.';
    }
}
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php'); exit;
}

$isAdmin = !empty($_SESSION['adswibe_admin']);

// Fetch data if logged in
$contacts  = [];
$proposals = [];
if ($isAdmin && file_exists(DB_FILE)) {
    try {
        $db = new PDO('sqlite:' . DB_FILE);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        // Check tables exist
        $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
        if (in_array('contact', $tables)) {
            $contacts = $db->query("SELECT * FROM contact ORDER BY ts DESC LIMIT 200")->fetchAll(PDO::FETCH_ASSOC);
        }
        if (in_array('proposals', $tables)) {
            $proposals = $db->query("SELECT * FROM proposals ORDER BY ts DESC LIMIT 200")->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch(Exception $e) {
        $dbError = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Adswibe® — Admin Panel</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',Arial,sans-serif;background:#0f172a;color:#e2e8f0;min-height:100vh}
.login-wrap{display:flex;align-items:center;justify-content:center;min-height:100vh;padding:20px}
.login-box{background:#1e293b;border-radius:16px;padding:40px;max-width:380px;width:100%;text-align:center;border:1px solid #334155;box-shadow:0 20px 60px rgba(0,0,0,.4)}
.logo{font-size:28px;font-weight:800;color:#fff;margin-bottom:4px}.logo span{color:#FF7851}
.sub{color:#64748b;font-size:13px;margin-bottom:28px}
input[type=password]{width:100%;background:#0f172a;border:1px solid #334155;color:#e2e8f0;padding:12px 16px;border-radius:8px;font-size:15px;outline:none;margin-bottom:12px;transition:border-color .2s}
input[type=password]:focus{border-color:#2563eb}
.btn{width:100%;background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#fff;border:none;padding:13px;border-radius:8px;font-weight:700;font-size:15px;cursor:pointer}
.err{background:#450a0a;color:#fca5a5;padding:10px;border-radius:6px;margin-bottom:12px;font-size:13px}
.admin-wrap{max-width:1200px;margin:0 auto;padding:24px}
.admin-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;padding-bottom:20px;border-bottom:1px solid #1e293b}
.admin-logo{font-size:22px;font-weight:800;color:#fff}.admin-logo span{color:#FF7851}
.logout{background:#1e293b;color:#94a3b8;padding:8px 16px;border-radius:6px;text-decoration:none;font-size:13px;border:1px solid #334155}
.logout:hover{color:#fff}
.stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px;margin-bottom:28px}
.stat{background:#1e293b;border-radius:12px;padding:20px;text-align:center;border:1px solid #334155}
.stat .num{font-size:32px;font-weight:800;color:#2563eb}.stat .lbl{font-size:12px;color:#64748b;margin-top:4px;text-transform:uppercase;letter-spacing:1px}
h2{font-size:18px;font-weight:700;color:#fff;margin-bottom:16px}
.section{background:#1e293b;border-radius:12px;overflow:hidden;margin-bottom:28px;border:1px solid #334155}
table{width:100%;border-collapse:collapse}
th{background:#0f172a;color:#64748b;font-size:11px;text-transform:uppercase;letter-spacing:1px;padding:12px 16px;text-align:left;font-weight:600}
td{padding:12px 16px;font-size:13px;color:#cbd5e1;border-top:1px solid #0f172a;vertical-align:top;max-width:240px;word-break:break-word}
tr:hover td{background:rgba(37,99,235,.05)}
.badge{display:inline-block;background:#2563eb;color:#fff;padding:2px 10px;border-radius:20px;font-size:11px;font-weight:600}
.ts{color:#475569;font-size:12px}
.empty{padding:32px;text-align:center;color:#475569}
.msg-preview{max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;display:inline-block}
a.reply-btn{background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#fff;padding:5px 12px;border-radius:5px;text-decoration:none;font-size:12px;font-weight:600;white-space:nowrap}
</style>
</head>
<body>

<?php if (!$isAdmin): ?>
<div class="login-wrap">
<div class="login-box">
<div class="logo">Ads<span>wibe</span>®</div>
<div class="sub">Admin Panel — Submissions</div>
<?php if (!empty($loginError)): ?>
<div class="err"><?= htmlspecialchars($loginError) ?></div>
<?php endif; ?>
<form method="post">
  <input type="password" name="password" placeholder="Enter admin password" autofocus>
  <button class="btn" type="submit">Sign In</button>
</form>
</div>
</div>

<?php else: ?>
<div class="admin-wrap">
<div class="admin-header">
  <div class="admin-logo">Ads<span>wibe</span>® <span style="font-weight:300;color:#475569;font-size:14px">Admin Panel</span></div>
  <a class="logout" href="?logout=1">Sign Out</a>
</div>

<?php if (!empty($dbError)): ?>
<div style="background:#450a0a;color:#fca5a5;padding:14px;border-radius:8px;margin-bottom:20px">DB Error: <?= htmlspecialchars($dbError) ?></div>
<?php endif; ?>

<div class="stats">
  <div class="stat"><div class="num"><?= count($contacts) ?></div><div class="lbl">Contact Messages</div></div>
  <div class="stat"><div class="num"><?= count($proposals) ?></div><div class="lbl">Proposals</div></div>
  <div class="stat"><div class="num"><?= count($contacts) + count($proposals) ?></div><div class="lbl">Total Leads</div></div>
  <div class="stat"><div class="num"><?= date('d M') ?></div><div class="lbl">Today's Date</div></div>
</div>

<h2>📬 Contact Form Submissions</h2>
<div class="section">
<?php if (empty($contacts)): ?>
<div class="empty">No contact submissions yet.</div>
<?php else: ?>
<table>
<thead><tr><th>#</th><th>Name</th><th>Email</th><th>Phone</th><th>Service</th><th>Message</th><th>Date</th><th>Action</th></tr></thead>
<tbody>
<?php foreach ($contacts as $i => $r): ?>
<tr>
  <td style="color:#475569"><?= $r['id'] ?></td>
  <td><strong><?= htmlspecialchars($r['name']) ?></strong></td>
  <td><?= htmlspecialchars($r['email']) ?></td>
  <td><?= htmlspecialchars($r['phone'] ?? '—') ?></td>
  <td><span class="badge"><?= htmlspecialchars($r['service'] ?? '—') ?></span></td>
  <td><span class="msg-preview" title="<?= htmlspecialchars($r['message'] ?? '') ?>"><?= htmlspecialchars(substr($r['message'] ?? '', 0, 60)) ?>...</span></td>
  <td class="ts"><?= htmlspecialchars(date('d M y, g:i a', strtotime($r['ts']))) ?></td>
  <td><a class="reply-btn" href="mailto:<?= htmlspecialchars($r['email']) ?>?subject=Re: Your Inquiry — Adswibe®">Reply</a></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>
</div>

<h2>🎯 Proposal Requests</h2>
<div class="section">
<?php if (empty($proposals)): ?>
<div class="empty">No proposal requests yet.</div>
<?php else: ?>
<table>
<thead><tr><th>#</th><th>Name</th><th>Email</th><th>Company</th><th>Goal</th><th>Platforms</th><th>Budget</th><th>Date</th><th>Action</th></tr></thead>
<tbody>
<?php foreach ($proposals as $r): ?>
<tr>
  <td style="color:#475569"><?= $r['id'] ?></td>
  <td><strong><?= htmlspecialchars($r['full_name']) ?></strong></td>
  <td><?= htmlspecialchars($r['email']) ?></td>
  <td><?= htmlspecialchars($r['company'] ?? '—') ?></td>
  <td><?= htmlspecialchars($r['primary_goal'] ?? '—') ?></td>
  <td style="font-size:12px"><?= htmlspecialchars($r['platforms'] ?? '—') ?></td>
  <td><strong style="color:#2563eb"><?= htmlspecialchars($r['budget'] ?? '—') ?></strong></td>
  <td class="ts"><?= htmlspecialchars(date('d M y, g:i a', strtotime($r['ts']))) ?></td>
  <td><a class="reply-btn" href="mailto:<?= htmlspecialchars($r['email']) ?>?subject=Your Free Proposal — Adswibe®">Reply</a></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>
</div>

</div>
<?php endif; ?>

</body>
</html>
