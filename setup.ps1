<#
setup.ps1 -- Automate local setup tasks for Katikamu project

This script will:
- Prompt for MySQL credentials and import `sql/school_applications.sql` if `mysql` CLI is available
- Update `config.php` with provided DB credentials
- Run `composer install` if `composer` is available
- Run `php admin_setup.php` to create the admin user
- Create `uploads/` directory with safe permissions

Run this from the project root in PowerShell (run as Administrator if needed):
    .\setup.ps1

This script makes best-effort attempts and will show manual instructions if a step cannot be completed.
#>
Set-StrictMode -Version Latest

Write-Host "Katikamu project setup — automating common tasks`n" -ForegroundColor Cyan

function PromptHidden([string]$text) {
    $secure = Read-Host -AsSecureString $text
    return [Runtime.InteropServices.Marshal]::PtrToStringAuto([Runtime.InteropServices.Marshal]::SecureStringToBSTR($secure))
}

$dbHost = Read-Host "MySQL host (default: 127.0.0.1)"; if ([string]::IsNullOrWhiteSpace($dbHost)) { $dbHost = '127.0.0.1' }
$dbName = Read-Host "Database name to create/use (default: school_applications)"; if ([string]::IsNullOrWhiteSpace($dbName)) { $dbName = 'school_applications' }
$dbUser = Read-Host "MySQL username (e.g. root)"; if ([string]::IsNullOrWhiteSpace($dbUser)) { $dbUser = 'root' }
$dbPass = PromptHidden "MySQL password (input hidden)"

Write-Host "\nUpdating config.php with provided DB settings..." -ForegroundColor Yellow
$configPath = Join-Path (Get-Location) 'config.php'
if (Test-Path $configPath) {
    (Get-Content $configPath) -replace "define\('DB_HOST', '.*'\);", "define('DB_HOST', '$dbHost');" `
        -replace "define\('DB_NAME', '.*'\);", "define('DB_NAME', '$dbName');" `
        -replace "define\('DB_USER', '.*'\);", "define('DB_USER', '$dbUser');" `
        -replace "define\('DB_PASS', '.*'\);", "define('DB_PASS', '$dbPass');" | Set-Content $configPath
    Write-Host "config.php updated." -ForegroundColor Green
} else {
    Write-Warning "config.php not found in project root — please edit it manually with DB credentials.";
}

# Attempt to import SQL using mysql CLI if available
if (Get-Command mysql -ErrorAction SilentlyContinue) {
    Write-Host "Importing SQL schema into database '$dbName'..." -ForegroundColor Yellow
    # Create DB then import
    $createCmd = "CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    & mysql -h $dbHost -u $dbUser -p$dbPass -e $createCmd
    if ($LASTEXITCODE -ne 0) { Write-Warning "Failed to create database. You may need to run this step manually." }
    $sqlFile = Join-Path (Get-Location) 'sql\school_applications.sql'
    if (Test-Path $sqlFile) {
        & mysql -h $dbHost -u $dbUser -p$dbPass $dbName < $sqlFile
        if ($LASTEXITCODE -eq 0) { Write-Host "SQL schema imported." -ForegroundColor Green } else { Write-Warning "SQL import failed. You may need to run the import manually (see README)." }
    } else {
        Write-Warning "SQL file not found: $sqlFile"
    }
} else {
    Write-Warning "MySQL client not found on PATH. Skipping automatic SQL import. Use phpMyAdmin or the MySQL CLI to import 'sql/school_applications.sql'."
}

# Run composer install if composer available
if (Get-Command composer -ErrorAction SilentlyContinue) {
    Write-Host "Running 'composer install'..." -ForegroundColor Yellow
    composer install
    if ($LASTEXITCODE -eq 0) { Write-Host "Composer install completed." -ForegroundColor Green } else { Write-Warning "Composer failed. You may need to run it manually." }
} else {
    Write-Warning "Composer not found. If you need PHPMailer, install Composer and run 'composer install' in this folder.";
}

# Run admin setup using PHP CLI
if (Get-Command php -ErrorAction SilentlyContinue) {
    Write-Host "Running admin_setup.php to create/update admin account..." -ForegroundColor Yellow
    php admin_setup.php
    if ($LASTEXITCODE -eq 0) { Write-Host "admin_setup.php executed (check output)." -ForegroundColor Green } else { Write-Warning "admin_setup.php returned a non-zero exit code. Check output for details." }
} else {
    Write-Warning "PHP CLI not found. Install PHP or use XAMPP/Docker to run admin_setup.php manually.";
}

# Prompt for SMTP settings and write to config.php
$smtpChoice = Read-Host "Do you want to configure SMTP settings now? (Y/N)"
if ($smtpChoice -match '^[Yy]') {
    $smtpHost = Read-Host "SMTP host (e.g. smtp.gmail.com)"
    $smtpPort = Read-Host "SMTP port (e.g. 587)"; if ([string]::IsNullOrWhiteSpace($smtpPort)) { $smtpPort = '587' }
    $smtpUser = Read-Host "SMTP username (e.g. smtp user/email)"
    $smtpPass = PromptHidden "SMTP password (hidden)"
    $smtpSecure = Read-Host "SMTP secure (tls/ssl/none) (default: tls)"; if ([string]::IsNullOrWhiteSpace($smtpSecure)) { $smtpSecure = 'tls' }

    if (Test-Path $configPath) {
        (Get-Content $configPath) -replace "define\('SMTP_HOST', '.*'\);", "define('SMTP_HOST', '$smtpHost');" `
            -replace "define\('SMTP_PORT', .*\);", "define('SMTP_PORT', $smtpPort);" `
            -replace "define\('SMTP_USER', '.*'\);", "define('SMTP_USER', '$smtpUser');" `
            -replace "define\('SMTP_PASS', '.*'\);", "define('SMTP_PASS', '$smtpPass');" `
            -replace "define\('SMTP_SECURE', '.*'\);", "define('SMTP_SECURE', '$smtpSecure');" | Set-Content $configPath
        Write-Host "SMTP settings written to config.php." -ForegroundColor Green
    } else {
        Write-Warning "config.php not found; cannot write SMTP settings.";
    }
}

# Create uploads directory
$uploads = Join-Path (Get-Location) 'uploads'
if (-not (Test-Path $uploads)) { New-Item -ItemType Directory -Path $uploads | Out-Null; Write-Host "Created uploads/" -ForegroundColor Green }

# Create .gitignore for uploads
$gi = Join-Path $uploads '.gitignore'
if (-not (Test-Path $gi)) {
    "*`n!.gitignore`n" | Out-File -FilePath $gi -Encoding UTF8
    Write-Host "Created uploads/.gitignore" -ForegroundColor Green
}

Write-Host "\nSetup script finished. If any steps failed, follow the messages above to resolve them." -ForegroundColor Cyan
Write-Host "Open the site at http://localhost:8000/apply.html (if you used the PHP built-in server) or http://localhost/katikamusdass-main/apply.html (if using XAMPP)." -ForegroundColor Cyan

Write-Host "If you want, run this command to quickly start PHP's built-in server now (requires php on PATH):`n    php -S localhost:8000` -ForegroundColor Yellow
