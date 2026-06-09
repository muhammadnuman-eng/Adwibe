<?php
/**
 * ============================================================
 * ADSWIBE® — CONTACT FORM BACKEND
 * Production-ready | SMTP via PHPMailer | Security | Auto-reply
 * ============================================================
 */

// ══════════════════════════════════════════════════════════════
//  ▶ STEP 1: SET YOUR EMAIL
// ══════════════════════════════════════════════════════════════
define('ADMIN_EMAIL',   'adswibe@gmail.com');
define('FROM_NAME',     'Adswibe®');
define('FROM_EMAIL',    'noreply@adswibe.com');    // Must match SMTP domain
define('REPLY_TO',      'adswibe@gmail.com');
define('SITE_URL',      'https://adswibe.com');
define('PHONE_DISPLAY', '+92 331 6290097');

// ══════════════════════════════════════════════════════════════
//  ▶ STEP 2: BREVO (SENDINBLUE) SMTP — Free 300 emails/day
//  Sign up: https://app.brevo.com
//  Then: SMTP & API → SMTP tab → Generate SMTP key
// ══════════════════════════════════════════════════════════════
define('SMTP_HOST',   'smtp-relay.brevo.com');
define('SMTP_PORT',   587);
define('SMTP_USER',   'YOUR_BREVO_LOGIN_EMAIL');  // ← your Brevo account email
define('SMTP_PASS',   'YOUR_BREVO_SMTP_KEY');     // ← Brevo SMTP key (not account password)
define('SMTP_SECURE', 'tls');

// ══════════════════════════════════════════════════════════════
//  ▶ STEP 3: GOOGLE reCAPTCHA v3 (optional but recommended)
//  Get keys: https://www.google.com/recaptcha/admin/create
//  Select: reCAPTCHA v3 → add your domain → copy keys
// ══════════════════════════════════════════════════════════════
define('RECAPTCHA_SECRET',  'YOUR_RECAPTCHA_V3_SECRET'); // ← paste secret key
define('RECAPTCHA_ENABLED', false);  // Set true after adding keys

// ══════════════════════════════════════════════════════════════
//  ▶ STEP 4: STORAGE (auto-created, no config needed)
// ══════════════════════════════════════════════════════════════
define('DB_FILE',   __DIR__ . '/backend/submissions.db');
define('LOG_FILE',  __DIR__ . '/backend/errors.log');
define('RATE_LIMIT', 5); // max submissions per IP per hour

// ── Bootstrap ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); exit('Method Not Allowed');
}
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

function jsonOut($ok, $msg, $code = 200) {
    http_response_code($code);
    echo json_encode(['success' => $ok, 'message' => $msg]);
    exit;
}
function logErr($m) {
    $d = dirname(LOG_FILE);
    if (!is_dir($d)) @mkdir($d, 0755, true);
    @file_put_contents(LOG_FILE, date('[Y-m-d H:i:s] ') . $m . "\n", FILE_APPEND);
}

// ── CSRF ──────────────────────────────────────────────────────
@session_start();
$csrf = $_POST['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
    jsonOut(false, 'Security check failed. Please refresh the page and try again.', 403);
}
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// ── Honeypot ──────────────────────────────────────────────────
if (!empty($_POST['website_url'])) { jsonOut(true, 'Sent!'); }

// ── Rate limiting ─────────────────────────────────────────────
$ip = trim(explode(',', ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0')))[0];
$ipH = md5($ip);
$rf  = sys_get_temp_dir() . '/adw_rate_c_' . $ipH . '.json';
$rd  = file_exists($rf) ? json_decode(file_get_contents($rf), true) : ['c' => 0, 'w' => time()];
if (time() - $rd['w'] > 3600) $rd = ['c' => 0, 'w' => time()];
if ($rd['c'] >= RATE_LIMIT) jsonOut(false, 'Too many submissions. Please wait before trying again.', 429);
$rd['c']++; file_put_contents($rf, json_encode($rd));

// ── Sanitize & Validate ───────────────────────────────────────
function clean($v) { return htmlspecialchars(strip_tags(trim($v ?? '')), ENT_QUOTES, 'UTF-8'); }
$name    = clean($_POST['name']     ?? '');
$email   = clean($_POST['email']    ?? '');
$phone   = clean($_POST['phone']    ?? '');
$service = clean($_POST['services'] ?? '');
$message = clean($_POST['message']  ?? '');

if (strlen($name) < 2)                        jsonOut(false, 'Please enter your full name.', 422);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) jsonOut(false, 'Please enter a valid email address.', 422);
if (strlen($message) < 10)                    jsonOut(false, 'Please write a message (at least 10 characters).', 422);
if (strlen($message) > 5000)                  jsonOut(false, 'Message too long. Max 5000 characters.', 422);

// ── reCAPTCHA v3 ─────────────────────────────────────────────
if (RECAPTCHA_ENABLED) {
    $tok = $_POST['recaptcha_token'] ?? '';
    if (empty($tok)) jsonOut(false, 'reCAPTCHA verification required.', 422);
    $vr = @json_decode(file_get_contents('https://www.google.com/recaptcha/api/siteverify?' . http_build_query(['secret' => RECAPTCHA_SECRET, 'response' => $tok, 'remoteip' => $ip])), true);
    if (empty($vr['success']) || ($vr['score'] ?? 0) < 0.5) jsonOut(false, 'reCAPTCHA failed. Please try again.', 422);
}

// ── Store in SQLite DB ────────────────────────────────────────
$dbDir = dirname(DB_FILE);
if (!is_dir($dbDir)) @mkdir($dbDir, 0755, true);
try {
    $db = new PDO('sqlite:' . DB_FILE);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec("CREATE TABLE IF NOT EXISTS contact (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, email TEXT, phone TEXT, service TEXT, message TEXT, ip_hash TEXT, ts DATETIME DEFAULT CURRENT_TIMESTAMP)");
    $db->prepare("INSERT INTO contact (name,email,phone,service,message,ip_hash) VALUES (?,?,?,?,?,?)")->execute([$name,$email,$phone,$service,$message,$ipH]);
} catch(Exception $e) { logErr('DB: '.$e->getMessage()); }

// ── Email Templates ───────────────────────────────────────────
$dt  = date('F j, Y \a\t g:i A') . ' PKT';
$ipD = preg_replace('/\.\d+$/', '.***', $ip);

$adminBody = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>
*{box-sizing:border-box;margin:0;padding:0}body{font-family:\'Segoe UI\',Arial,sans-serif;background:#f0f4f8}
.wrap{max-width:600px;margin:30px auto;background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 8px 30px rgba(0,0,0,.12)}
.hdr{background:linear-gradient(135deg,#2563eb,#1e40af 60%,#FF7851);padding:32px;text-align:center}
.hdr h1{color:#fff;font-size:22px;font-weight:700;margin-bottom:4px}.hdr p{color:rgba(255,255,255,.85);font-size:13px}
.bdg{display:inline-block;background:rgba(255,255,255,.2);color:#fff;border-radius:20px;padding:4px 14px;font-size:12px;margin-top:10px;border:1px solid rgba(255,255,255,.3)}
.bdy{padding:30px}.ib{background:#f8fafc;border-radius:10px;padding:20px;margin-bottom:16px;border-left:4px solid #2563eb}
.ib h3{font-size:11px;text-transform:uppercase;letter-spacing:1.2px;color:#2563eb;margin-bottom:12px;font-weight:700}
.rw{display:flex;padding:8px 0;border-bottom:1px solid #e8edf2;align-items:flex-start}.rw:last-child{border-bottom:none}
.lb{width:105px;font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;flex-shrink:0;padding-top:2px}
.vl{font-size:14px;color:#1e293b;flex:1}.vl a{color:#2563eb;text-decoration:none}
.mb{background:#fff7ed;border-radius:10px;padding:20px;border-left:4px solid #FF7851;margin-bottom:16px}
.mb h3{font-size:11px;text-transform:uppercase;letter-spacing:1.2px;color:#FF7851;margin-bottom:10px;font-weight:700}
.mt{font-size:14px;color:#374151;line-height:1.75;white-space:pre-wrap}
.svc{display:inline-block;background:#2563eb;color:#fff;padding:4px 14px;border-radius:20px;font-size:12px;font-weight:600}
.cta{display:block;background:linear-gradient(135deg,#2563eb,#FF7851);color:#fff!important;text-align:center;padding:14px 30px;border-radius:8px;text-decoration:none!important;font-weight:700;font-size:15px;margin:20px 0 10px}
.ftr{background:#1e293b;color:#94a3b8;text-align:center;padding:20px;font-size:12px}.ftr a{color:#60a5fa;text-decoration:none}
</style></head><body>
<div class="wrap">
<div class="hdr"><h1>📬 New Contact Message</h1><p>Someone just reached out via your website contact form</p><span class="bdg">🕐 '.$dt.'</span></div>
<div class="bdy">
<div class="ib"><h3>👤 Contact Details</h3>
<div class="rw"><span class="lb">Name</span><span class="vl"><strong>'.htmlspecialchars($name).'</strong></span></div>
<div class="rw"><span class="lb">Email</span><span class="vl"><a href="mailto:'.htmlspecialchars($email).'">'.htmlspecialchars($email).'</a></span></div>
<div class="rw"><span class="lb">Phone</span><span class="vl">'.htmlspecialchars($phone ?: '—').'</span></div>
<div class="rw"><span class="lb">Service</span><span class="vl"><span class="svc">'.htmlspecialchars($service).'</span></span></div>
</div>
<div class="mb"><h3>💬 Their Message</h3><div class="mt">'.htmlspecialchars($message).'</div></div>
<div class="ib"><h3>🔍 Submission Info</h3>
<div class="rw"><span class="lb">Date/Time</span><span class="vl">'.$dt.'</span></div>
<div class="rw"><span class="lb">IP (partial)</span><span class="vl">'.$ipD.'</span></div>
<div class="rw"><span class="lb">Source Page</span><span class="vl">Contact Form — adswibe.com</span></div>
</div>
<a class="cta" href="mailto:'.htmlspecialchars($email).'?subject=Re:%20Your%20Inquiry%20-%20Adswibe%C2%AE">↩ Click to Reply to '.htmlspecialchars($name).'</a>
</div>
<div class="ftr"><p><a href="https://adswibe.com">Adswibe®</a> · adswibe@gmail.com · '.PHONE_DISPLAY.'</p><p style="margin-top:6px;opacity:.6">We Run Ads That Scale Your Brand</p></div>
</div></body></html>';

$replyBody = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>
*{box-sizing:border-box;margin:0;padding:0}body{font-family:\'Segoe UI\',Arial,sans-serif;background:#f0f4f8}
.wrap{max-width:600px;margin:30px auto;background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 8px 30px rgba(0,0,0,.12)}
.hdr{background:linear-gradient(135deg,#1e293b,#2563eb 60%,#1e40af);padding:36px;text-align:center}
.brand{font-size:30px;font-weight:800;color:#fff;letter-spacing:-0.5px}.brand span{color:#FF7851}
.tl{color:rgba(255,255,255,.65);font-size:11px;letter-spacing:2px;text-transform:uppercase;margin-top:6px}
.hero{background:linear-gradient(135deg,#2563eb,#FF7851);padding:28px;text-align:center}
.hero h2{color:#fff;font-size:22px;font-weight:700;margin-bottom:6px}.hero p{color:rgba(255,255,255,.9);font-size:14px}
.bdy{padding:36px 30px}.gr{font-size:21px;font-weight:700;color:#1e293b;margin-bottom:16px}
.tx{font-size:15px;color:#475569;line-height:1.8;margin-bottom:14px}
.sum{background:#f8fafc;border-radius:12px;padding:20px;margin:22px 0;border:1px solid #e2e8f0}
.sum h3{font-size:11px;text-transform:uppercase;letter-spacing:1.2px;color:#2563eb;margin-bottom:14px;font-weight:700}
.sr{display:flex;padding:8px 0;border-bottom:1px dashed #e2e8f0}.sr:last-child{border-bottom:none}
.sl{width:100px;font-size:12px;font-weight:700;color:#94a3b8;text-transform:uppercase;flex-shrink:0}.sv{font-size:14px;color:#1e293b}
.prom{background:linear-gradient(135deg,#eff6ff,#fff7ed);border-radius:12px;padding:22px;margin:22px 0;text-align:center;border:2px solid #bfdbfe}
.prom .ic{font-size:36px;margin-bottom:8px}.prom h3{font-size:17px;font-weight:700;color:#1e293b;margin-bottom:6px}
.prom p{font-size:13px;color:#64748b}
.cg{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin:22px 0}
.cc{background:#f8fafc;border-radius:10px;padding:16px;text-align:center;border:1px solid #e2e8f0}
.cc .ic{font-size:20px;margin-bottom:6px}.cc .lb{font-size:11px;text-transform:uppercase;letter-spacing:1px;color:#94a3b8}
.cc .vl{font-size:13px;font-weight:600;color:#1e293b;margin-top:3px}.cc .vl a{color:#2563eb;text-decoration:none}
.cs{text-align:center;margin:24px 0}
.cb{display:inline-block;background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#fff!important;padding:15px 34px;border-radius:8px;font-weight:700;font-size:15px;text-decoration:none!important;box-shadow:0 4px 15px rgba(37,99,235,.3)}
.wb{display:inline-block;background:linear-gradient(135deg,#25D366,#128C7E);color:#fff!important;padding:13px 28px;border-radius:8px;font-weight:700;font-size:14px;text-decoration:none!important;margin-top:10px}
.sig{border-top:2px solid #e2e8f0;padding-top:22px;margin-top:22px}
.sn{font-weight:700;color:#1e293b;font-size:16px}.st{color:#64748b;font-size:13px;margin-top:2px}
.sb{color:#2563eb;font-weight:700;font-size:14px;margin-top:6px}
.ftr{background:#1e293b;padding:24px;text-align:center}
.sl2 a{display:inline-block;width:34px;height:34px;background:rgba(255,255,255,.1);border-radius:50%;line-height:34px;text-align:center;color:#fff;text-decoration:none;margin:0 3px;font-size:13px}
.fc{color:#475569;font-size:12px;margin-top:14px}.fc a{color:#60a5fa;text-decoration:none}
</style></head><body>
<div class="wrap">
<div class="hdr"><div class="brand">Ads<span>wibe</span>®</div><div class="tl">Premium Social Media Marketing</div></div>
<div class="hero"><h2>✅ Message Received!</h2><p>Thank you for reaching out — we\'re excited to connect with you.</p></div>
<div class="bdy">
<div class="gr">Hi '.htmlspecialchars($name).'! 👋</div>
<p class="tx">Thank you for contacting <strong>Adswibe®</strong>! We\'ve received your message and our team will personally review your inquiry shortly.</p>
<p class="tx">We\'re passionate about helping brands scale through smart, data-driven digital marketing — and we\'re already looking forward to what we can do together.</p>
<div class="sum"><h3>📋 Your Submission</h3>
<div class="sr"><span class="sl">Name</span><span class="sv"><strong>'.htmlspecialchars($name).'</strong></span></div>
<div class="sr"><span class="sl">Email</span><span class="sv">'.htmlspecialchars($email).'</span></div>
<div class="sr"><span class="sl">Service</span><span class="sv">'.htmlspecialchars($service).'</span></div>
<div class="sr"><span class="sl">Received</span><span class="sv">'.$dt.'</span></div>
</div>
<div class="prom"><div class="ic">⏱️</div><h3>Response within 24 hours — guaranteed</h3><p>We typically reply much sooner, often within a few hours during business hours (PKT, GMT+5).</p></div>
<p class="tx">While you wait, feel free to explore our portfolio or ping us directly on WhatsApp for an instant reply.</p>
<div class="cg">
<div class="cc"><div class="ic">📧</div><div class="lb">Email Us</div><div class="vl"><a href="mailto:adswibe@gmail.com">adswibe@gmail.com</a></div></div>
<div class="cc"><div class="ic">📞</div><div class="lb">WhatsApp / Call</div><div class="vl"><a href="https://wa.me/923316290097">+92 331 6290097</a></div></div>
</div>
<div class="cs">
<a class="cb" href="https://adswibe.com">🌐 Explore Adswibe®</a><br>
<a class="wb" href="https://wa.me/923316290097?text=Hi%20Adswibe!%20I%20just%20submitted%20your%20contact%20form.">💬 Chat on WhatsApp Now</a>
</div>
<div class="sig"><div class="sn">The Adswibe Team</div><div class="st">Client Success · Lahore, Pakistan</div><div class="sb">Adswibe® — We Run Ads That Scale Your Brand 🚀</div></div>
</div>
<div class="ftr">
<div class="sl2"><a href="https://www.facebook.com/adswibe">f</a><a href="https://www.instagram.com/adswibe.pk">ig</a><a href="https://www.linkedin.com/company/adswibe">in</a><a href="https://www.tiktok.com/@adswibe.pk">tk</a></div>
<div class="fc">&copy; 2026 Adswibe Private Limited &middot; <a href="https://adswibe.com/privacy.html">Privacy</a> &middot; <a href="https://adswibe.com/terms.html">Terms</a></div>
</div>
</div></body></html>';

// ── Load PHPMailer ────────────────────────────────────────────
$pmLoaded = false;
foreach ([__DIR__.'/vendor/autoload.php', __DIR__.'/../vendor/autoload.php'] as $p) {
    if (file_exists($p)) { require_once $p; $pmLoaded = true; break; }
}
if (!$pmLoaded) {
    $pmBase = __DIR__ . '/backend/phpmailer/';
    if (file_exists($pmBase.'PHPMailer.php')) {
        require_once $pmBase.'Exception.php';
        require_once $pmBase.'PHPMailer.php';
        require_once $pmBase.'SMTP.php';
        $pmLoaded = true;
    }
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

function makeMailer() {
    $m = new PHPMailer(true);
    $m->isSMTP();
    $m->Host       = SMTP_HOST;
    $m->SMTPAuth   = true;
    $m->Username   = SMTP_USER;
    $m->Password   = SMTP_PASS;
    $m->SMTPSecure = SMTP_SECURE;
    $m->Port       = SMTP_PORT;
    $m->CharSet    = 'UTF-8';
    $m->setFrom(FROM_EMAIL, FROM_NAME);
    return $m;
}

$ok1 = $ok2 = false;

if ($pmLoaded) {
    try {
        $m = makeMailer();
        $m->addAddress(ADMIN_EMAIL, FROM_NAME);
        $m->addReplyTo($email, $name);
        $m->Subject = "📬 New Contact: {$name} — {$service}";
        $m->isHTML(true); $m->Body = $adminBody;
        $m->AltBody = "New contact from {$name} ({$email})\nService: {$service}\n\n{$message}\n\nSubmitted: {$dt}";
        $ok1 = $m->send();
    } catch(Exception $e) { logErr('Admin mail: '.(method_exists($e,'getMessage')?$e->getMessage():'')); }

    try {
        $m = makeMailer();
        $m->addAddress($email, $name);
        $m->addReplyTo(REPLY_TO, FROM_NAME);
        $m->Subject = "✅ Got your message, {$name}! — Adswibe®";
        $m->isHTML(true); $m->Body = $replyBody;
        $m->AltBody = "Hi {$name},\n\nThank you for contacting Adswibe®! We'll respond within 24 hours.\n\nBest,\nThe Adswibe Team\nadswibe@gmail.com";
        $ok2 = $m->send();
    } catch(Exception $e) { logErr('Reply mail: '.(method_exists($e,'getMessage')?$e->getMessage():'')); }

} else {
    // php mail() fallback
    $h1 = "MIME-Version: 1.0\r\nContent-type: text/html; charset=UTF-8\r\nFrom: ".FROM_NAME." <".FROM_EMAIL.">\r\nReply-To: {$name} <{$email}>\r\n";
    $ok1 = @mail(ADMIN_EMAIL, "New Contact: {$name}", $adminBody, $h1);
    $h2  = "MIME-Version: 1.0\r\nContent-type: text/html; charset=UTF-8\r\nFrom: ".FROM_NAME." <".FROM_EMAIL.">\r\nReply-To: ".FROM_NAME." <".REPLY_TO.">\r\n";
    $ok2 = @mail($email, "We received your message! — Adswibe®", $replyBody, $h2);
}

if ($ok1 || $ok2) {
    jsonOut(true, "Your message has been sent successfully! We'll respond within 24 hours.");
} else {
    logErr("Both emails failed — contact: {$name} <{$email}>");
    jsonOut(false, 'Submission saved but email delivery failed. Please email us at adswibe@gmail.com or WhatsApp +92 331 6290097');
}
?>
