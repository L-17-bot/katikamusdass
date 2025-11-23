# Katikamu SDA - Student Application System

This repository contains a simple PHP + MySQL student application backend and admin panel for Katikamu SDA Secondary School.

## What I added for you
- `config.php` — DB + mail configuration (update credentials)
- `sql/school_applications.sql` — DB schema
- `submit.php` — public form handler (AJAX friendly)
- `apply.html` — wired form (already patched to submit to `submit.php`)
- `emails/approval.html` and `emails/rejection.html` — templates
- `admin/` — admin panel: `login.php`, `logout.php`, `dashboard.php`, `view.php`, `approve.php`, `reject.php`, `inc_csrf.php`
- `admin_setup.php` — create/update admin user `KATIKAMUSDASS` (password `K@TIK@MUSD@SS`)
- `composer.json` — adds PHPMailer for SMTP sending (recommended)

## One-time setup (do these on your machine / hosting)

1. Put files on your web host (document root). Preferably keep `config.php` outside public web root and update includes accordingly.

2. Edit `config.php` with your MySQL credentials and SMTP settings (if using SMTP):
```php
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'school_applications');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
// Mail
define('MAIL_FROM_NAME', 'Dr Nolan - Admissions');
define('MAIL_FROM_EMAIL', 'dr.nolantheastronaut@gmail.com');
// Optional SMTP (for PHPMailer)
define('SMTP_HOST', 'smtp.example.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'smtp_user');
define('SMTP_PASS', 'smtp_pass');
define('SMTP_SECURE', 'tls');
```

3. Import the database:

PowerShell / Windows (adjust username):
```powershell
mysql -u root -p < sql\school_applications.sql
```

4. Install dependencies (PHPMailer) if you want reliable SMTP emails (recommended):
```powershell
cd C:\path\to\project
composer install
```
If you don't have Composer, install it first: https://getcomposer.org/

5. Create admin user (script will create or update admin):
```powershell
php .\admin_setup.php
```
This will create username `KATIKAMUSDASS` with the password `K@TIK@MUSD@SS`. Log in at `/admin/login.php` and immediately change the password manually via DB or request a feature to change password.

6. Ensure file uploads/folders are writable if you accept file uploads. (Current code does not store attachments to disk; you can extend `submit.php` to save uploaded files.)

7. Test the public form:
- Open `https://your-site/apply.html` and submit a test application.
- Check `https://your-site/admin/login.php`, login and verify the submission appears in the dashboard.

8. Approve/Reject
- Approve or reject an application; the system will send an email from `dr.nolantheastronaut@gmail.com` using PHPMailer (if configured) or PHP `mail()` as fallback.

## Important: Email deliverability
- Using PHP `mail()` often results in delivery to spam or blocked messages. Configure SMTP and use PHPMailer + proper SPF/DKIM records for best results.

## Security checklist (recommended)
- Use HTTPS
- Move `config.php` outside webroot
- Set strong admin password and rotate it
- Use PHPMailer with SMTP and secure credentials
- Consider adding 2FA for admin accounts
- Limit access to `/admin` by IP or HTTP auth if needed

## Troubleshooting
- If admin login fails: run `php admin_setup.php` again.
- If emails don't arrive: configure SMTP in `config.php` and run `composer install`.

If you want, I can now:
- Replace `mail()` calls with PHPMailer usage (already added auto-detection); or
- Add file-save attachments handling for uploads; or
- Add a simple admin password-change page.

Tell me which of the above you'd like next and I will implement it.