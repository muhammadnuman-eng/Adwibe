<?php
/**
 * ============================================================
 * ADSWIBE® — PROPOSAL / QUOTE REQUEST FORM BACKEND
 * Production-ready | SMTP via PHPMailer | Security | Auto-reply
 * ============================================================
 */

// ── Same config as contact.php — edit once here ──────────────
define('ADMIN_EMAIL',   'adswibe@gmail.com');
define('FROM_NAME',     'Adswibe®');
define('FROM_EMAIL',    'noreply@adswibe.com');
define('REPLY_TO',      'adswibe@gmail.com');
define('SITE_URL',      'https://adswibe.com');
define('PHONE_DISPLAY', '+92 331 6290097');

define('SMTP_HOST',   'smtp-relay.brevo.com');
define('SMTP_PORT',   587);
define('SMTP_USER',   'YOUR_BREVO_LOGIN_EMAIL');
define('SMTP_PASS',   'YOUR_BREVO_SMTP_KEY');
define('SMTP_SECURE', 'tls');

define('RECAPTCHA_SECRET',  'YOUR_RECAPTCHA_V3_SECRET');
define('RECAPTCHA_ENABLED', false);

define('DB_FILE',   __DIR__ . '/backend/submissions.db');
define('LOG_FILE',  __DIR__ . '/backend/errors.log');
define('RATE_LIMIT', 5);

// ── Bootstrap ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); exit('Method Not Allowed');
}
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

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
    jsonOut(false, 'Security check failed. Please refresh and try again.', 403);
}
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// ── Honeypot ──────────────────────────────────────────────────
if (!empty($_POST['website_url'])) { jsonOut(true, 'Sent!'); }

// ── Rate limiting ─────────────────────────────────────────────
$ip = trim(explode(',', ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0')))[0];
$ipH = md5($ip);
$rf  = sys_get_temp_dir() . '/adw_rate_r_' . $ipH . '.json';
$rd  = file_exists($rf) ? json_decode(file_get_contents($rf), true) : ['c' => 0, 'w' => time()];
if (time() - $rd['w'] > 3600) $rd = ['c' => 0, 'w' => time()];
if ($rd['c'] >= RATE_LIMIT) jsonOut(false, 'Too many submissions. Please wait before trying again.', 429);
$rd['c']++; file_put_contents($rf, json_encode($rd));

// ── Sanitize & Validate ───────────────────────────────────────
function clean($v) { return htmlspecialchars(strip_tags(trim($v ?? '')), ENT_QUOTES, 'UTF-8'); }

$fullName    = clean($_POST['fullName']    ?? '');
$email       = clean($_POST['email']       ?? '');
$phone       = clean($_POST['phone']       ?? '');
$companyName = clean($_POST['companyName'] ?? '');
$website     = clean($_POST['website']     ?? '');
$primaryGoal = clean($_POST['primaryGoal'] ?? '');
$challenges  = clean($_POST['challenges']  ?? '');
$platforms   = clean($_POST['platforms']   ?? '');
$budget      = clean($_POST['budget']      ?? '');
$notes       = clean($_POST['notes']       ?? '');

if (strlen($fullName) < 2)                        jsonOut(false, 'Please enter your full name.', 422);
if (!filter_var($email, FILTER_VALIDATE_EMAIL))   jsonOut(false, 'Please enter a valid email address.', 422);
if (strlen($phone) < 7)                           jsonOut(false, 'Please enter a valid phone number.', 422);
if (empty($primaryGoal))                          jsonOut(false, 'Please select your primary goal.', 422);
if (empty($budget))                               jsonOut(false, 'Please select your monthly budget.', 422);

// Validate website URL if provided
if (!empty($website) && !filter_var($website, FILTER_VALIDATE_URL) && strpos($website, '.') === false) {
    jsonOut(false, 'Please enter a valid website URL.', 422);
}

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
    $db->exec("CREATE TABLE IF NOT EXISTS proposals (id INTEGER PRIMARY KEY AUTOINCREMENT, full_name TEXT, email TEXT, phone TEXT, company TEXT, website TEXT, primary_goal TEXT, challenges TEXT, platforms TEXT, budget TEXT, notes TEXT, ip_hash TEXT, ts DATETIME DEFAULT CURRENT_TIMESTAMP)");
    $db->prepare("INSERT INTO proposals (full_name,email,phone,company,website,primary_goal,challenges,platforms,budget,notes,ip_hash) VALUES (?,?,?,?,?,?,?,?,?,?,?)")
       ->execute([$fullName,$email,$phone,$companyName,$website,$primaryGoal,$challenges,$platforms,$budget,$notes,$ipH]);
} catch(Exception $e) { logErr('DB (proposal): '.$e->getMessage()); }

// ── Format platforms for display ─────────────────────────────
$dt  = date('F j, Y \a\t g:i A') . ' PKT';
$ipD = preg_replace('/\.\d+$/', '.***', $ip);

$platformBadges = '';
if (!empty($platforms)) {
    foreach (explode(',', $platforms) as $p) {
        $p = trim($p);
        if ($p) $platformBadges .= '<span style="display:inline-block;background:#2563eb;color:#fff;padding:3px 12px;border-radius:20px;font-size:12px;font-weight:600;margin:2px 3px;">'.$p.'</span> ';
    }
}
if (!$platformBadges) $platformBadges = '—';

// ── Admin email template ──────────────────────────────────────
$adminBody = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>
*{box-sizing:border-box;margin:0;padding:0}body{font-family:\'Segoe UI\',Arial,sans-serif;background:#f0f4f8}
.wrap{max-width:620px;margin:30px auto;background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 8px 30px rgba(0,0,0,.12)}
.hdr{background:linear-gradient(135deg,#2563eb,#1e40af 60%,#FF7851);padding:32px;text-align:center}
.hdr h1{color:#fff;font-size:23px;font-weight:700;margin-bottom:4px}.hdr p{color:rgba(255,255,255,.85);font-size:13px}
.bdg{display:inline-block;background:rgba(255,255,255,.2);color:#fff;border-radius:20px;padding:4px 14px;font-size:12px;margin-top:10px;border:1px solid rgba(255,255,255,.3)}
.bdy{padding:28px}
.sec{background:#f8fafc;border-radius:10px;padding:20px;margin-bottom:14px;border-left:4px solid #2563eb}
.sec.orange{border-left-color:#FF7851}.sec h3{font-size:11px;text-transform:uppercase;letter-spacing:1.2px;color:#2563eb;margin-bottom:12px;font-weight:700}
.sec.orange h3{color:#FF7851}
.rw{display:flex;padding:8px 0;border-bottom:1px solid #e8edf2;align-items:flex-start}.rw:last-child{border-bottom:none}
.lb{width:110px;font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;flex-shrink:0;padding-top:2px}
.vl{font-size:14px;color:#1e293b;flex:1}.vl a{color:#2563eb;text-decoration:none}
.hl{background:#fef9c3;padding:3px 10px;border-radius:4px;font-weight:700;font-size:14px;color:#854d0e}
.cta{display:block;background:linear-gradient(135deg,#2563eb,#FF7851);color:#fff!important;text-align:center;padding:14px 28px;border-radius:8px;text-decoration:none!important;font-weight:700;font-size:15px;margin:18px 0 8px}
.ftr{background:#1e293b;color:#94a3b8;text-align:center;padding:20px;font-size:12px}.ftr a{color:#60a5fa;text-decoration:none}
</style></head><body>
<div class="wrap">
<div class="hdr"><h1>🎯 New Proposal Request</h1><p>A potential client wants a free proposal from Adswibe®</p><span class="bdg">🕐 '.$dt.' — <strong>'.htmlspecialchars($budget).' Budget</strong></span></div>
<div class="bdy">
<div class="sec"><h3>👤 Contact Information</h3>
<div class="rw"><span class="lb">Full Name</span><span class="vl"><strong>'.htmlspecialchars($fullName).'</strong></span></div>
<div class="rw"><span class="lb">Email</span><span class="vl"><a href="mailto:'.htmlspecialchars($email).'">'.htmlspecialchars($email).'</a></span></div>
<div class="rw"><span class="lb">Phone</span><span class="vl">'.htmlspecialchars($phone).'</span></div>
<div class="rw"><span class="lb">Company</span><span class="vl">'.htmlspecialchars($companyName ?: '—').'</span></div>
<div class="rw"><span class="lb">Website</span><span class="vl">'.($website ? '<a href="'.htmlspecialchars($website).'">'.htmlspecialchars($website).'</a>' : '—').'</span></div>
</div>
<div class="sec"><h3>🎯 Goals & Challenges</h3>
<div class="rw"><span class="lb">Primary Goal</span><span class="vl"><span class="hl">'.htmlspecialchars($primaryGoal).'</span></span></div>
<div class="rw"><span class="lb">Challenges</span><span class="vl">'.nl2br(htmlspecialchars($challenges ?: '—')).'</span></div>
</div>
<div class="sec orange"><h3>📱 Platforms & Budget</h3>
<div class="rw"><span class="lb">Platforms</span><span class="vl">'.$platformBadges.'</span></div>
<div class="rw"><span class="lb">Monthly Budget</span><span class="vl"><strong style="color:#2563eb;font-size:16px;">'.htmlspecialchars($budget).'</strong></span></div>
<div class="rw"><span class="lb">Notes</span><span class="vl">'.nl2br(htmlspecialchars($notes ?: '—')).'</span></div>
</div>
<div class="sec"><h3>🔍 Submission Info</h3>
<div class="rw"><span class="lb">Date/Time</span><span class="vl">'.$dt.'</span></div>
<div class="rw"><span class="lb">IP (partial)</span><span class="vl">'.$ipD.'</span></div>
</div>
<a class="cta" href="mailto:'.htmlspecialchars($email).'?subject=Your%20Free%20Proposal%20-%20Adswibe%C2%AE&body=Hi%20'.urlencode($fullName).',%0A%0AThank%20you%20for%20requesting%20a%20proposal!">🎯 Send Proposal to '.htmlspecialchars($fullName).'</a>
</div>
<div class="ftr"><p><a href="https://adswibe.com">Adswibe®</a> · adswibe@gmail.com · '.PHONE_DISPLAY.'</p><p style="margin-top:6px;opacity:.6">We Run Ads That Scale Your Brand</p></div>
</div></body></html>';

// ── Auto-reply template ───────────────────────────────────────
$platformList = !empty($platforms) ? implode(', ', array_filter(array_map('trim', explode(',', $platforms)))) : 'To be discussed';

$replyBody = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>
*{box-sizing:border-box;margin:0;padding:0}body{font-family:\'Segoe UI\',Arial,sans-serif;background:#f0f4f8}
.wrap{max-width:600px;margin:30px auto;background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 8px 30px rgba(0,0,0,.12)}
.hdr{background:linear-gradient(135deg,#1e293b,#2563eb 60%,#1e40af);padding:36px;text-align:center}
.brand{font-size:30px;font-weight:800;color:#fff;letter-spacing:-0.5px}.brand span{color:#FF7851}
.tl{color:rgba(255,255,255,.65);font-size:11px;letter-spacing:2px;text-transform:uppercase;margin-top:6px}
.hero{background:linear-gradient(135deg,#2563eb,#1e40af);padding:28px;text-align:center}
.hero h2{color:#fff;font-size:22px;font-weight:700;margin-bottom:6px}.hero p{color:rgba(255,255,255,.9);font-size:14px}
.bdy{padding:36px 30px}.gr{font-size:21px;font-weight:700;color:#1e293b;margin-bottom:16px}
.tx{font-size:15px;color:#475569;line-height:1.8;margin-bottom:14px}
.sum{background:#f8fafc;border-radius:12px;padding:20px;margin:22px 0;border:1px solid #e2e8f0}
.sum h3{font-size:11px;text-transform:uppercase;letter-spacing:1.2px;color:#2563eb;margin-bottom:14px;font-weight:700}
.sr{display:flex;padding:8px 0;border-bottom:1px dashed #e2e8f0}.sr:last-child{border-bottom:none}
.sl{width:110px;font-size:12px;font-weight:700;color:#94a3b8;text-transform:uppercase;flex-shrink:0}.sv{font-size:14px;color:#1e293b}
.steps{margin:22px 0}
.step{display:flex;align-items:flex-start;gap:16px;padding:14px;background:#f8fafc;border-radius:10px;margin-bottom:10px;border:1px solid #e2e8f0}
.step-num{width:36px;height:36px;background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:15px;flex-shrink:0}
.step-text h4{font-size:14px;font-weight:700;color:#1e293b;margin-bottom:3px}.step-text p{font-size:13px;color:#64748b}
.prom{background:linear-gradient(135deg,#eff6ff,#fff7ed);border-radius:12px;padding:22px;margin:22px 0;text-align:center;border:2px solid #bfdbfe}
.prom h3{font-size:17px;font-weight:700;color:#1e293b;margin-bottom:6px}.prom p{font-size:13px;color:#64748b}
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
<div class="hero"><h2>🎯 Proposal Request Received!</h2><p>We\'re already working on your custom growth strategy.</p></div>
<div class="bdy">
<div class="gr">Hi '.htmlspecialchars($fullName).'! 🚀</div>
<p class="tx">Brilliant news — your proposal request is in! The <strong>Adswibe®</strong> team is reviewing your details and we\'ll have a custom, data-backed proposal crafted specifically for your brand.</p>
<p class="tx">We take every proposal seriously. Our strategists will analyse your goals, platforms, and budget to build a plan that actually moves the needle for your business.</p>
<div class="sum"><h3>📋 Your Proposal Request Summary</h3>
<div class="sr"><span class="sl">Name</span><span class="sv"><strong>'.htmlspecialchars($fullName).'</strong></span></div>
'.($companyName ? '<div class="sr"><span class="sl">Company</span><span class="sv">'.htmlspecialchars($companyName).'</span></div>' : '').'
<div class="sr"><span class="sl">Primary Goal</span><span class="sv"><strong>'.htmlspecialchars($primaryGoal).'</strong></span></div>
<div class="sr"><span class="sl">Platforms</span><span class="sv">'.htmlspecialchars($platformList).'</span></div>
<div class="sr"><span class="sl">Monthly Budget</span><span class="sv"><strong>'.htmlspecialchars($budget).'</strong></span></div>
<div class="sr"><span class="sl">Submitted</span><span class="sv">'.$dt.'</span></div>
</div>
<div class="steps">
<div class="step"><div class="step-num">1</div><div class="step-text"><h4>We review your details</h4><p>Our strategists analyse your goals, audience, and platforms to build the right plan.</p></div></div>
<div class="step"><div class="step-num">2</div><div class="step-text"><h4>Custom proposal drafted</h4><p>A tailored, data-driven strategy for your brand — within 24 hours.</p></div></div>
<div class="step"><div class="step-num">3</div><div class="step-text"><h4>Strategy session call</h4><p>We walk you through the plan, answer questions, and align on next steps.</p></div></div>
</div>
<div class="prom"><h3>⏱️ Your proposal arrives within 24 hours</h3><p>Our team typically delivers much faster. For urgent matters, WhatsApp us directly.</p></div>
<div class="cs">
<a class="cb" href="https://adswibe.com">🌐 Explore Our Work</a><br>
<a class="wb" href="https://wa.me/923316290097?text=Hi%20Adswibe!%20I%20just%20submitted%20a%20proposal%20request.%20My%20name%20is%20'.urlencode($fullName).'.">💬 Chat on WhatsApp</a>
</div>
<div class="sig"><div class="sn">The Adswibe Strategy Team</div><div class="st">Proposals · Client Success · Lahore, Pakistan</div><div class="sb">Adswibe® — We Run Ads That Scale Your Brand 🚀</div></div>
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
        $m->addReplyTo($email, $fullName);
        $m->Subject = "🎯 New Proposal: {$fullName} — {$primaryGoal} | {$budget}";
        $m->isHTML(true); $m->Body = $adminBody;
        $m->AltBody = "New proposal from {$fullName} ({$email})\nCompany: {$companyName}\nGoal: {$primaryGoal}\nPlatforms: {$platformList}\nBudget: {$budget}\n\nChallenges: {$challenges}\nNotes: {$notes}\n\nSubmitted: {$dt}";
        $ok1 = $m->send();
    } catch(Exception $e) { logErr('Admin mail (proposal): '.(method_exists($e,'getMessage')?$e->getMessage():'')); }

    try {
        $m = makeMailer();
        $m->addAddress($email, $fullName);
        $m->addReplyTo(REPLY_TO, FROM_NAME);
        $m->Subject = "🎯 Your proposal request is confirmed, {$fullName}! — Adswibe®";
        $m->isHTML(true); $m->Body = $replyBody;
        $m->AltBody = "Hi {$fullName},\n\nThank you for requesting a proposal from Adswibe®! We're reviewing your details and will send a custom strategy within 24 hours.\n\nBest,\nThe Adswibe Strategy Team\nadswibe@gmail.com | ".PHONE_DISPLAY;
        $ok2 = $m->send();
    } catch(Exception $e) { logErr('Reply mail (proposal): '.(method_exists($e,'getMessage')?$e->getMessage():'')); }

} else {
    $h1 = "MIME-Version: 1.0\r\nContent-type: text/html; charset=UTF-8\r\nFrom: ".FROM_NAME." <".FROM_EMAIL.">\r\nReply-To: {$fullName} <{$email}>\r\n";
    $ok1 = @mail(ADMIN_EMAIL, "New Proposal: {$fullName} — {$budget}", $adminBody, $h1);
    $h2  = "MIME-Version: 1.0\r\nContent-type: text/html; charset=UTF-8\r\nFrom: ".FROM_NAME." <".FROM_EMAIL.">\r\nReply-To: ".FROM_NAME." <".REPLY_TO.">\r\n";
    $ok2 = @mail($email, "Proposal request confirmed! — Adswibe®", $replyBody, $h2);
}

if ($ok1 || $ok2) {
    jsonOut(true, "Proposal request sent! We'll have your custom strategy ready within 24 hours.");
} else {
    logErr("Both emails failed — proposal: {$fullName} <{$email}>");
    jsonOut(false, 'Submission saved but email delivery failed. Please reach us at adswibe@gmail.com or WhatsApp +92 331 6290097');
}
?>
