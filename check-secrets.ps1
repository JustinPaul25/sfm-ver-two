# PowerShell script to check for sensitive data before committing
# Run this before pushing to remote: .\check-secrets.ps1

Write-Host "Checking for sensitive data in repository..." -ForegroundColor Cyan
Write-Host ""

$hasIssues = $false

# Check if .env is accidentally staged
Write-Host "1. Checking for .env files in staging area..." -ForegroundColor Yellow
$envFiles = git ls-files | Where-Object { $_ -match '\.env$' -and $_ -notmatch '\.env\.example' }
if ($envFiles) {
    Write-Host "ERROR: .env file found in git!" -ForegroundColor Red
    $envFiles | ForEach-Object { Write-Host "  - $_" -ForegroundColor Red }
    Write-Host "To remove: git rm --cached .env" -ForegroundColor Yellow
    $hasIssues = $true
} else {
    Write-Host "PASS: No .env files in repository" -ForegroundColor Green
}

Write-Host ""

# Check for common password patterns in staged files
Write-Host "2. Checking for password patterns in staged files..." -ForegroundColor Yellow
$staged = git diff --cached --name-only

if ($staged) {
    foreach ($file in $staged) {
        if (Test-Path $file) {
            $content = Get-Content $file -Raw -ErrorAction SilentlyContinue
            if ($content) {
                # Simple pattern matching for suspicious content
                if ($content -match 'password\s*=\s*[''"][^''"]{10,}[''"]') {
                    Write-Host "WARNING: Possible password in: $file" -ForegroundColor Red
                    $hasIssues = $true
                }
                if ($content -match 'api.?key\s*=\s*[''"][^''"]{20,}[''"]') {
                    Write-Host "WARNING: Possible API key in: $file" -ForegroundColor Red
                    $hasIssues = $true
                }
            }
        }
    }
    
    if (-not $hasIssues) {
        Write-Host "PASS: No suspicious patterns detected" -ForegroundColor Green
    }
} else {
    Write-Host "PASS: No files staged" -ForegroundColor Green
}

Write-Host ""

# Check .gitignore
Write-Host "3. Verifying .gitignore contains security entries..." -ForegroundColor Yellow
if (Test-Path ".gitignore") {
    $gitignoreContent = Get-Content .gitignore -Raw
    $requiredEntries = @(".env", "*.key")
    $missing = @()

    foreach ($entry in $requiredEntries) {
        if (-not ($gitignoreContent -match [regex]::Escape($entry))) {
            $missing += $entry
        }
    }

    if ($missing.Count -gt 0) {
        Write-Host "WARNING: Missing entries in .gitignore:" -ForegroundColor Yellow
        $missing | ForEach-Object { Write-Host "  - $_" -ForegroundColor Yellow }
    } else {
        Write-Host "PASS: .gitignore properly configured" -ForegroundColor Green
    }
} else {
    Write-Host "WARNING: No .gitignore file found" -ForegroundColor Yellow
}

Write-Host ""

# Check for large files
Write-Host "4. Checking for large files..." -ForegroundColor Yellow
$largeFiles = git diff --cached --name-only | Where-Object { 
    if (Test-Path $_) {
        (Get-Item $_).Length -gt 1MB
    }
}

if ($largeFiles) {
    Write-Host "WARNING: Large files detected (>1MB):" -ForegroundColor Yellow
    $largeFiles | ForEach-Object { 
        $size = [math]::Round((Get-Item $_).Length / 1MB, 2)
        $sizeText = "{0:N2} MB" -f $size
        Write-Host "  - $_ ($sizeText)" -ForegroundColor Yellow 
    }
} else {
    Write-Host "PASS: No large files detected" -ForegroundColor Green
}

Write-Host ""

# Summary
$separator = "=" * 50
Write-Host $separator -ForegroundColor Cyan
if ($hasIssues) {
    Write-Host "FAILED: SECURITY CHECK FAILED" -ForegroundColor Red
    Write-Host "Please fix the issues above before committing." -ForegroundColor Red
    Write-Host $separator -ForegroundColor Cyan
    exit 1
} else {
    Write-Host "PASSED: SECURITY CHECK PASSED" -ForegroundColor Green
    Write-Host "Safe to commit!" -ForegroundColor Green
    Write-Host $separator -ForegroundColor Cyan
    exit 0
}
