# How to Add a Valid API Key

The SFM API uses a simple API key authentication system. Here's how to set up your own API key:

## Method 1: Using Environment Variables (Recommended)

### Step 1: Edit your `.env` file

Add or modify the `API_KEY` variable:

```bash
API_KEY=your-secret-api-key-here
```

**Example**:
```bash
API_KEY=prod_example_key_abc123
```

### Step 2: Clear Configuration Cache

After adding the API key, clear Laravel's config cache:

```bash
php artisan config:clear
```

### Step 3: Verify the Key

Check that your API key is loaded:

```bash
php artisan tinker
>>> config('services.api_key')
```

You should see your custom API key.

---

## Method 2: Direct Configuration (Not Recommended)

You can also modify the config file directly, but this is less flexible:

**File**: `config/services.php`

```php
'api_key' => env('API_KEY', 'your-secret-api-key-here'),
```

---

## Using the API Key

### In Postman

1. Import the `SFM_API.postman_collection.json`
2. Go to collection variables
3. Update `api_key` variable to your custom key
4. All requests will use this key

### In cURL

```bash
curl "http://localhost/api/cages?key=your-secret-api-key-here"
```

### In Code

```php
// Any API request
GET /api/cages?key=your-secret-api-key-here
```

---

## Default API Key

If no `API_KEY` is set in `.env`, the system defaults to:

```
default-api-key
```

This is useful for development but **must be changed for production**.

---

## Generating a Secure API Key

### Option 1: Using Laravel Tinker

```bash
php artisan tinker
>>> Str::random(40)
```

Copy the generated string as your API key.

### Option 2: Using Online Generator

Visit: https://www.random.org/strings/

Settings:
- Length: 40
- Character set: 0-9, a-z, A-Z

### Option 3: Using Command Line (Linux/Mac)

```bash
openssl rand -hex 32
```

### Option 4: Using PowerShell (Windows)

```powershell
-join ((65..90) + (97..122) + (48..57) | Get-Random -Count 40 | % {[char]$_})
```

---

## Testing Your API Key

### Test with Postman

1. Send a GET request to: `/api/cages?key=YOUR_KEY`
2. Should return: 200 OK with cages data
3. If wrong key: 422 with error message

### Test with cURL

```bash
# Correct key
curl "http://localhost/api/cages?key=your-key"
# Should return JSON data

# Wrong key
curl "http://localhost/api/cages?key=wrong-key"
# Should return: {"message":"Key is required or the key is invalid"}
```

### Test with PHPUnit

```bash
php artisan test --filter=DocsControllerTest::test_cages_endpoint_requires_api_key
```

---

## Production Security Tips

1. **Use Strong Keys**: At least 40 characters, mix of letters, numbers, and symbols
2. **Never Commit `.env`**: Keep API keys out of version control
3. **Rotate Keys**: Change API keys periodically
4. **Use Different Keys**: Separate keys for development, staging, and production
5. **Monitor Usage**: Track which keys are being used and from where
6. **Limit Access**: Only share API keys with authorized users/applications

---

## Multiple API Keys (Future Enhancement)

Currently, the system supports **one API key**. To support multiple keys, you would need to:

1. Create an `api_keys` database table
2. Store API keys with metadata (user, permissions, expiry)
3. Update `checkKey()` method to query database
4. Add key management endpoints

Example migration:

```php
Schema::create('api_keys', function (Blueprint $table) {
    $table->id();
    $table->string('key')->unique();
    $table->string('name');
    $table->foreignId('user_id')->nullable();
    $table->timestamp('expires_at')->nullable();
    $table->boolean('active')->default(true);
    $table->timestamps();
});
```

---

## Troubleshooting

### "Key is required or the key is invalid"

**Check**:
1. API key is in `.env` file
2. Ran `php artisan config:clear`
3. Key matches exactly (case-sensitive)
4. No extra spaces in the key

### Key not working after changing

**Solution**:
```bash
php artisan config:clear
php artisan cache:clear
```

### Want to disable authentication temporarily

**Option 1**: Return always true in `checkKey()`:

```php
private function checkKey($key)
{
    return false; // Always return valid
}
```

**⚠️ WARNING**: Never do this in production!

---

## Quick Reference

| Setting | Location | Example |
|---------|----------|---------|
| Environment Variable | `.env` | `API_KEY=my-secret-key` |
| Config | `config/services.php` | `'api_key' => env('API_KEY', 'default-api-key')` |
| Usage | Query Parameter | `?key=my-secret-key` |
| Default Value | Config | `default-api-key` |

---

## Example Setup

### Development

```bash
# .env
API_KEY=dev_key_12345
```

### Staging

```bash
# .env.staging
API_KEY=staging_key_67890
```

### Production

```bash
# .env.production
API_KEY=prod_live_key_abc123xyz789def456ghi012
```

---

## Need Help?

- Check `config/services.php` for configuration
- Run `php artisan config:clear` to refresh config
- See `API_DOCUMENTATION.md` for API details
- See `POSTMAN_TESTING_GUIDE.md` for testing steps

