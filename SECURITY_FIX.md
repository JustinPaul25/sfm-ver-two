# GitGuardian Security Alert - Fix Instructions

## Alert Details
- **Date**: January 20, 2026, 04:13:51 UTC
- **Repository**: JustinPaul25/sfm-ver-two
- **Type**: Company Email Password
- **Commit**: 3030266da49cad93eba9d984749c8f756af876d1

## Immediate Actions Completed ✓

1. ✅ Verified `.env` is in `.gitignore`
2. ✅ Confirmed `config/mail.php` uses environment variables
3. ✅ `.env` was never committed to repository

## Actions Required

### 🚨 CRITICAL - Do This First
**Change the exposed password immediately** if it's a real company email password.

### Step 1: Identify the Exact Leak

Visit GitGuardian to see the exact location of the detected secret:
1. Log into GitGuardian
2. Click on the alert for "Company Email Password"
3. View the exact file and line number where it was detected

### Step 2: Remove the Secret from Current Code

Once you identify the file:
```bash
# Remove the secret from the file
# Replace with environment variable reference
```

### Step 3: Remove from Git History (if needed)

If the secret exists in git history, use BFG Repo-Cleaner or git-filter-repo:

#### Option A: Using BFG Repo-Cleaner (Recommended)
```bash
# Download BFG from https://rtyley.github.io/bfg-repo-cleaner/

# Create a file with passwords to remove
echo "your-actual-password" > passwords.txt

# Run BFG to remove the passwords from history
java -jar bfg.jar --replace-text passwords.txt

# Clean up
git reflog expire --expire=now --all
git gc --prune=now --aggressive

# Force push (⚠️ WARNING: This rewrites history)
git push origin --force --all
```

#### Option B: Using git-filter-repo
```bash
# Install git-filter-repo
pip install git-filter-repo

# Remove specific file from history
git filter-repo --path path/to/sensitive-file --invert-paths

# Force push
git push origin --force --all
```

### Step 4: Rotate All Exposed Credentials

Change these immediately:
- ✅ Email password (MAIL_PASSWORD)
- ✅ Any API keys that were exposed
- ✅ Database passwords if exposed
- ✅ Any other secrets in the same commit

### Step 5: Update Environment Variables

After rotating credentials, update your `.env` file (NOT in git):
```bash
MAIL_PASSWORD=your-new-secure-password
```

### Step 6: Verify Fix

```bash
# Check that .env is not tracked
git status | grep ".env"

# Should show nothing or only .env.example

# Verify .gitignore
cat .gitignore | grep ".env"
```

### Step 7: Resolve GitGuardian Alert

1. Go to GitGuardian dashboard
2. Mark the alert as "Resolved"
3. Confirm you've rotated the credentials
4. (Optional) Mark as "False Positive" if it wasn't a real secret

## Prevention - Setup Pre-commit Hooks

Install git-secrets to prevent future leaks:

### For PowerShell:
```powershell
# Install git-secrets (requires admin)
# Download from: https://github.com/awslabs/git-secrets

# Add to your repository
git secrets --install
git secrets --register-aws

# Add custom patterns
git secrets --add 'MAIL_PASSWORD=.*[^n][^u][^l][^l]'
git secrets --add '[a-zA-Z0-9]{20,}'
```

### Alternative: Use pre-commit framework
```bash
# Install pre-commit
pip install pre-commit

# Create .pre-commit-config.yaml (see below)
pre-commit install
```

## Best Practices Going Forward

1. ✅ **Never commit `.env` files** - Already in .gitignore
2. ✅ **Use environment variables** for all secrets - Already implemented
3. ⚠️ **Scan before committing** - Setup git hooks
4. ⚠️ **Rotate credentials regularly** - Set a schedule
5. ⚠️ **Use a secrets manager** - Consider AWS Secrets Manager or similar
6. ⚠️ **Review commits before pushing** - Check for sensitive data

## Files That Should NEVER Be Committed

- `.env`
- `.env.local`
- `.env.production`
- `auth.json` (already in .gitignore)
- Any files with real passwords, API keys, or tokens
- Private keys (*.key, *.pem)
- Credentials files

## Current Status

✅ .env properly ignored
✅ Config files use environment variables  
✅ No .env in git history
⚠️ Need to identify exact leak location from GitGuardian
⚠️ Need to rotate exposed password
⚠️ Need to clean git history if secret is there

## Support

If you need help:
1. Check GitGuardian documentation: https://docs.gitguardian.com/
2. Review Laravel security: https://laravel.com/docs/configuration#environment-configuration
3. Git history cleaning: https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/removing-sensitive-data-from-a-repository
