# Quick Setup: Adding Your API Key

## Current Status

Your API is currently using: **`default-api-key`**

## Quick Steps to Add Your Own API Key

### For Windows Users:

1. **Open your `.env` file** in your project root:
   ```
   C:\Users\USER\Herd\sfm\.env
   ```

2. **Add or update this line**:
   ```
   API_KEY=your-custom-api-key-here
   ```

3. **Generate a secure key** (if you want):
   ```powershell
   -join ((65..90) + (97..122) + (48..57) | Get-Random -Count 40 | % {[char]$_})
   ```
   
   Copy the output and use it as your API_KEY

4. **Clear the cache**:
   ```bash
   php artisan config:clear
   ```

5. **Verify it worked**:
   ```bash
   php artisan tinker --execute="echo config('services.api_key');"
   ```

---

## For Postman Users:

1. **First, set your `.env` file** as described above
2. **Import the Postman collection** (if you haven't):
   - File → Import → `SFM_API.postman_collection.json`
3. **Update the `api_key` variable**:
   - Right-click collection → "Edit"
   - Go to "Variables" tab
   - Change `api_key` value to match your `.env`
4. **Test it**:
   - Send any request from the collection
   - Should return 200 OK

---

## Example .env Entry:

```bash
# API Configuration
API_KEY=prod_your_api_key_here
```

After saving, run:
```bash
php artisan config:clear
```

---

## Testing Your Key

### Quick Test (cURL):
```bash
curl "http://localhost/api/cages?key=your-custom-api-key-here"
```

### Quick Test (PowerShell):
```powershell
Invoke-WebRequest -Uri "http://localhost/api/cages?key=your-custom-api-key-here" | Select-Object -ExpandProperty Content
```

---

## Common Issues:

### Issue: "Still using default key"
**Solution**: Run `php artisan config:clear`

### Issue: "Key not found in .env"
**Solution**: Make sure `.env` is in the project root, not in a subdirectory

### Issue: "Spaces in key"
**Solution**: Remove any spaces before/after your API key value

### Issue: "Windows line endings"
**Solution**: Make sure `.env` file uses LF (Unix) or CRLF (Windows) consistently

---

## Default vs Custom:

| Type | Key Value | Use Case |
|------|-----------|----------|
| **Default** | `default-api-key` | Development, Testing |
| **Custom** | `your-key-here` | Production, Security |

---

## Need a Random Key?

Visit: https://www.random.org/strings/

Settings:
- Length: 40
- Characters: All (A-Z, a-z, 0-9)

Or use Laravel Tinker:
```bash
php artisan tinker
>>> Str::random(40)
```

Copy the output → paste in `.env` → `php artisan config:clear`

---

## Done! ✅

Your API is now using your custom key. Update Postman variables and start testing!

