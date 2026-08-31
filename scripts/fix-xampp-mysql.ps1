# Fixes XAMPP MariaDB auth_gssapi_client error and creates the Laravel database.
# Run in PowerShell as Administrator if mysql_stop/start fails.

param(
    [int]$Port = 3306
)

$MyIni = "C:\xampp\mysql\bin\my.ini"
$MySqlBin = "C:\xampp\mysql\bin"
$XamppRoot = "C:\xampp"
$Backup = "$MyIni.bak-foodbridge"
$SqlFile = Join-Path $PSScriptRoot "..\database\xampp-mysql-fix.sql"

if (-not (Test-Path $MyIni)) {
    Write-Error "XAMPP my.ini not found at $MyIni"
    exit 1
}

Write-Host "Stopping conflicting MySQL/MariaDB services..."
Get-Service -Name MariaDB -ErrorAction SilentlyContinue | Stop-Service -Force -ErrorAction SilentlyContinue
taskkill /F /IM mysqld.exe 2>$null | Out-Null
Start-Sleep -Seconds 3

if (-not (Test-Path $Backup)) {
    Copy-Item $MyIni $Backup
    Write-Host "Backed up my.ini to $Backup"
}

$content = Get-Content $MyIni -Raw
if ($content -notmatch 'skip-grant-tables') {
    $content = $content -replace '(\[mysqld\]\r?\n)', "`$1skip-grant-tables`r`n"
    Set-Content -Path $MyIni -Value $content -NoNewline
    Write-Host "Added skip-grant-tables to my.ini"
}

Write-Host "Starting MySQL on port $Port..."
Start-Process -FilePath "$MySqlBin\mysqld.exe" -ArgumentList "--defaults-file=$MyIni" -WindowStyle Hidden
Start-Sleep -Seconds 8

& "$MySqlBin\mysql.exe" -u root -P $Port -e "SOURCE $SqlFile" 2>&1
if ($LASTEXITCODE -ne 0) {
    Get-Content $SqlFile | & "$MySqlBin\mysql.exe" -u root -P $Port
}
if ($LASTEXITCODE -ne 0) {
    Write-Error "Failed to repair MySQL users. Ensure port $Port is free and try again."
    exit 1
}

Write-Host "MySQL root user repaired."

$content = Get-Content $MyIni -Raw
$content = $content -replace '\r?\nskip-grant-tables', ''
Set-Content -Path $MyIni -Value $content -NoNewline
Write-Host "Removed skip-grant-tables from my.ini"

Get-Process mysqld -ErrorAction SilentlyContinue | Stop-Process -Force -ErrorAction SilentlyContinue
Start-Sleep -Seconds 3
Start-Process -FilePath "$MySqlBin\mysqld.exe" -ArgumentList "--defaults-file=$MyIni" -WindowStyle Hidden
Start-Sleep -Seconds 8

& "$MySqlBin\mysql.exe" -u root -P $Port -e "SELECT user, host, plugin FROM mysql.user WHERE user='root';"
Write-Host "Done. Database 'asignment1' is ready on port $Port."
