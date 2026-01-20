# PowerShell script to clean sensitive data from git history
# ⚠️ WARNING: This will rewrite git history. Make a backup first!
# Usage: .\clean-git-history.ps1

Write-Host "⚠️  GIT HISTORY CLEANING TOOL" -ForegroundColor Red
Write-Host "This will REWRITE your git history!" -ForegroundColor Red
Write-Host ""

$confirm = Read-Host "Have you backed up your repository? (yes/no)"
if ($confirm -ne "yes") {
    Write-Host "Please backup first, then run again." -ForegroundColor Yellow
    exit
}

Write-Host "`nChoose cleaning method:" -ForegroundColor Cyan
Write-Host "1. Remove specific file from entire history"
Write-Host "2. Replace text patterns (passwords/keys) in history"
Write-Host "3. Cancel"
Write-Host ""

$choice = Read-Host "Enter choice (1-3)"

switch ($choice) {
    "1" {
        Write-Host "`nEnter the file path to remove (e.g., .env):" -ForegroundColor Yellow
        $filePath = Read-Host
        
        Write-Host "`nThis will remove '$filePath' from all commits." -ForegroundColor Yellow
        $finalConfirm = Read-Host "Continue? (yes/no)"
        
        if ($finalConfirm -eq "yes") {
            # Check if git-filter-repo is installed
            $hasFilterRepo = Get-Command git-filter-repo -ErrorAction SilentlyContinue
            
            if ($hasFilterRepo) {
                Write-Host "Using git-filter-repo..." -ForegroundColor Cyan
                git filter-repo --path $filePath --invert-paths --force
            } else {
                Write-Host "git-filter-repo not found. Using git filter-branch..." -ForegroundColor Yellow
                git filter-branch --force --index-filter `
                    "git rm --cached --ignore-unmatch $filePath" `
                    --prune-empty --tag-name-filter cat -- --all
                
                # Cleanup
                Remove-Item .git/refs/original -Recurse -Force -ErrorAction SilentlyContinue
                git reflog expire --expire=now --all
                git gc --prune=now --aggressive
            }
            
            Write-Host "`n✅ File removed from history" -ForegroundColor Green
            Write-Host "⚠️  Now you need to force push:" -ForegroundColor Yellow
            Write-Host "   git push origin --force --all" -ForegroundColor White
        }
    }
    
    "2" {
        Write-Host "`nEnter the text/password to remove:" -ForegroundColor Yellow
        $secretText = Read-Host -AsSecureString
        $BSTR = [System.Runtime.InteropServices.Marshal]::SecureStringToBSTR($secretText)
        $plainText = [System.Runtime.InteropServices.Marshal]::PtrToStringAuto($BSTR)
        
        # Create temporary replacement file
        $replacementFile = ".git-replacements.txt"
        "$plainText==>***REMOVED***" | Out-File $replacementFile -Encoding UTF8
        
        Write-Host "`nChecking if BFG Repo-Cleaner is available..." -ForegroundColor Cyan
        
        if (Test-Path "bfg.jar") {
            Write-Host "Running BFG..." -ForegroundColor Cyan
            java -jar bfg.jar --replace-text $replacementFile
            
            # Cleanup
            git reflog expire --expire=now --all
            git gc --prune=now --aggressive
            
            Write-Host "`n✅ Passwords replaced in history" -ForegroundColor Green
            Write-Host "⚠️  Now you need to force push:" -ForegroundColor Yellow
            Write-Host "   git push origin --force --all" -ForegroundColor White
        } else {
            Write-Host "❌ BFG not found. Download from:" -ForegroundColor Red
            Write-Host "   https://rtyley.github.io/bfg-repo-cleaner/" -ForegroundColor White
        }
        
        Remove-Item $replacementFile -ErrorAction SilentlyContinue
    }
    
    "3" {
        Write-Host "Cancelled." -ForegroundColor Yellow
        exit
    }
    
    default {
        Write-Host "Invalid choice." -ForegroundColor Red
        exit
    }
}

Write-Host "`n⚠️  IMPORTANT REMINDERS:" -ForegroundColor Yellow
Write-Host "1. Inform all team members about the history rewrite" -ForegroundColor White
Write-Host "2. They will need to re-clone or reset their local repos" -ForegroundColor White
Write-Host "3. Rotate ALL exposed credentials immediately" -ForegroundColor White
Write-Host "4. Mark the GitGuardian alert as resolved" -ForegroundColor White
