# ADSWIBE® — Backend Setup Guide
## Complete step-by-step to go from files → fully live with working emails

---

## ✅ WHAT'S ALREADY DONE (in your files)

| Feature | Status |
|---------|--------|
| Contact form backend | ✅ Built |
| Proposal form backend | ✅ Built |
| Admin to sends you email with all details | ✅ Built |
| Auto-reply to sender | ✅ Built |
| CSRF protection | ✅ Built |
| Honeypot bot detection | ✅ Built |
| Rate limiting (5/hr per IP) | ✅ Built |
| Input sanitization | ✅ Built |
| SQLite database storage | ✅ Built |
| Admin panel (/admin.php) | ✅ Built |
| Security headers (.htaccess) | ✅ Built |
| Loading/disabled button UX | ✅ Built |
| Success/error messages | ✅ Built |

---

## STEP 1 — Upload Files to Your Hosting

Upload the entire `adswibe-fixed/` folder to your hosting's `public_html/` directory.

**File structure should look like:**
```
public_html/
├── index.html
├── contact.php        ← NEW
├── request.php        ← NEW (replaced)
├── csrf.php           ← NEW
├── admin.php          ← NEW
├── composer.json      ← NEW
├── .htaccess          ← NEW
├── backend/
│   ├── .htaccess      ← Protects the folder
│   └── (submissions.db and errors.log auto-create here)
├── css/
├── js/
├── images/
└── ...all other files
```

---

## STEP 2 — Install PHPMailer (2 ways)

### WAY A — Via SSH (Recommended, takes 30 seconds)
If your host gives SSH access (cPanel → SSH Terminal, or any VPS):

```bash
cd public_html
curl -sS https://getcomposer.org/installer | php
php composer.phar install --no-dev
```

That's it. PHPMailer is now installed.

### WAY B — Manual Upload (No SSH needed)
1. Go to: https://github.com/PHPMailer/PHPMailer/releases/latest
2. Download the ZIP
3. Extract it — you want the `src/` folder contents
4. Upload these 3 files to `public_html/backend/phpmailer/`:
   - `PHPMailer.php`
   - `SMTP.php`
   - `Exception.php` (already included)

The PHP files will auto-detect them.

---

## STEP 3 — Get Brevo SMTP (Free — 300 emails/day)

Brevo is the best free option. Emails go to inbox, not spam.

1. **Sign up FREE** at: https://app.brevo.com
2. Verify your email address
3. Go to: **SMTP & API** → **SMTP** tab
4. Note your **Login** (your email) and click **Generate a new SMTP key**
5. Copy the SMTP key

Now open `contact.php` and `request.php` in a text editor:

**Find these lines (near top of each file) and replace:**
```php
define('SMTP_USER', 'YOUR_BREVO_LOGIN_EMAIL');  // ← your Brevo account email
define('SMTP_PASS', 'YOUR_BREVO_SMTP_KEY');     // ← Brevo SMTP key
```

**With your actual values:**
```php
define('SMTP_USER', 'yourname@gmail.com');       // your Brevo login email
define('SMTP_PASS', 'xsmtpib-xxxxxxxxxxxxxxxx'); // your Brevo SMTP key
```

**Do this in BOTH `contact.php` AND `request.php`.**

---

## STEP 4 — Set Your Sending Domain in Brevo

For emails to say "From: noreply@adswibe.com" (not @gmail):

1. In Brevo → **Senders & IPs** → **Domains**
2. Add `adswibe.com`
3. Brevo will show you DNS records to add

### DNS Records to Add (in your domain registrar or cPanel DNS):

**SPF Record:**
```
Type: TXT
Name: @  (or adswibe.com)
Value: v=spf1 include:sendinblue.com ~all
```

**DKIM Record:** (Brevo gives you the exact value)
```
Type: TXT
Name: mail._domainkey
Value: [copy from Brevo dashboard]
```

**DMARC Record:**
```
Type: TXT
Name: _dmarc
Value: v=DMARC1; p=quarantine; rua=mailto:adswibe@gmail.com
```

After adding DNS records, click **Authenticate** in Brevo. DNS propagation takes 1–24 hours.

---

## STEP 5 — Change Admin Panel Password

Open `admin.php` and find:
```php
define('ADMIN_PASSWORD', 'Adswibe2026!Admin');
```
Change it to something strong and personal.

Access your admin panel at: `https://adswibe.com/admin.php`

---

## STEP 6 — Enable reCAPTCHA (Recommended)

1. Go to: https://www.google.com/recaptcha/admin/create
2. Label: "Adswibe Website"
3. Choose: **reCAPTCHA v3**
4. Add domain: `adswibe.com`
5. Click Submit → copy both keys

**Add to your HTML files** (before `</head>` in index.html and request.html):
```html
<script src="https://www.google.com/recaptcha/api.js?render=YOUR_SITE_KEY"></script>
```

**In contact.php and request.php**, update:
```php
define('RECAPTCHA_SECRET',  'YOUR_SECRET_KEY_HERE');
define('RECAPTCHA_ENABLED', true);   // ← change false to true
```

**In index.html**, add this to the CSRF fetch block (in the contact form JS):
```javascript
grecaptcha.ready(function() {
    grecaptcha.execute('YOUR_SITE_KEY', {action: 'contact'}).then(function(token) {
        document.getElementById('recaptcha_token_contact').value = token;
    });
});
```

---

## STEP 7 — Test Everything

After uploading and configuring SMTP:

1. **Test contact form** at: https://adswibe.com (scroll to contact section)
   - Fill in your own email
   - Submit
   - Check: (a) you receive notification at adswibe@gmail.com, (b) auto-reply arrives at your test email

2. **Test proposal form** at: https://adswibe.com/request.html
   - Complete all 4 steps
   - Submit
   - Check both emails

3. **Check admin panel**: https://adswibe.com/admin.php
   - Login with your password
   - Both submissions should appear

4. **Test deliverability**: Use https://mail-tester.com
   - Send a test email and check your spam score (aim for 8+/10)

---

## STEP 8 — Set File Permissions (cPanel or SSH)

```bash
chmod 755 public_html/backend/
chmod 644 public_html/contact.php
chmod 644 public_html/request.php
chmod 644 public_html/admin.php
chmod 644 public_html/.htaccess
```

The `submissions.db` and `errors.log` files auto-create with correct permissions.

---

## ALTERNATIVE SMTP OPTIONS (if not using Brevo)

### SMTP2GO (Free: 1,000 emails/month)
- Host: `mail.smtp2go.com`
- Port: `587`
- Sign up: https://www.smtp2go.com

### Mailgun (Free: 100 emails/day)
- Host: `smtp.mailgun.org`
- Port: `587`
- Sign up: https://www.mailgun.com

### Gmail SMTP (using App Password)
- Host: `smtp.gmail.com`
- Port: `587`
- User: `adswibe@gmail.com`
- Pass: Create App Password at: https://myaccount.google.com/apppasswords
  (Requires 2FA enabled on your Google account)

---

## TROUBLESHOOTING

**Emails not arriving?**
- Check `backend/errors.log` for error messages
- Verify SMTP credentials are correct in both PHP files
- Make sure PHPMailer is installed (vendor/ folder exists)
- Test at: https://www.mail-tester.com

**Form shows "Security check failed"?**
- PHP sessions must work on your host
- Check if `csrf.php` is accessible at: https://adswibe.com/csrf.php
- Should return: `{"token":"..."}`

**Admin panel shows empty tables?**
- Submit a test form first to create the database
- Ensure the `backend/` folder is writable: `chmod 777 backend/`
- After DB is created, change back to: `chmod 755 backend/`

**404 errors on PHP files?**
- Confirm your hosting supports PHP (any shared host does)
- Check file extensions are `.php` not `.PHP`

---

## FILE SUMMARY

| File | Purpose |
|------|---------|
| `contact.php` | Contact form handler |
| `request.php` | Proposal form handler |
| `csrf.php` | CSRF token generator |
| `admin.php` | Submissions viewer (password protected) |
| `composer.json` | PHPMailer dependency |
| `.htaccess` | Security + 404 handling |
| `backend/.htaccess` | Blocks direct folder access |
| `backend/submissions.db` | SQLite database (auto-created) |
| `backend/errors.log` | Error log (auto-created) |

---

*Adswibe® Backend — Built for production. Questions? adswibe@gmail.com*
