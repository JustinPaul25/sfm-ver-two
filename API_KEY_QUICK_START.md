# API Key Quick Start

## The Fastest Way to Set Your API Key

### Step 1: Edit `.env` File

Add this line to your `.env` file in the project root:

```bash
API_KEY=your-secret-key-here
```

### Step 2: Clear Cache

```bash
php artisan config:clear
```

### Step 3: Done! ✅

Now update your Postman `api_key` variable to match.

---

## Want a Random Key?

**PowerShell**:
```powershell
-join ((65..90) + (97..122) + (48..57) | Get-Random -Count 40 | % {[char]$_})
```

**Laravel Tinker**:
```bash
php artisan tinker
>>> Str::random(40)
```

Copy the result → paste in `.env` → done!

---

## Update Postman

1. Import `SFM_API.postman_collection.json`
2. Click collection → Variables tab
3. Update `api_key` = your new key
4. Test any request

---

## Test It

**With cURL**:
```bash
curl "http://localhost/api/cages?key=your-new-key"
```

**In Postman**:
Just send any request from the collection!

---

## That's It! 🎉

See `SETUP_API_KEY.md` for detailed instructions and troubleshooting.

