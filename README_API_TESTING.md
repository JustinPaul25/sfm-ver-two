# SFM API Testing Guide

## Quick Start

### Import Postman Collection

1. Open Postman
2. Click **Import** button
3. Select `SFM_API.postman_collection.json`
4. Collection will appear in your workspace

### Configure Variables

The collection uses two variables you need to set:

**In Postman:**
1. Click on the collection name "SFM API - Sampling Management"
2. Go to **Variables** tab
3. Update:
   - `base_url`: Your Laravel server URL (default: `http://localhost`)
   - `api_key`: Your API key (default: `default-api-key`)

**For Different Environments:**

| Environment | base_url | Notes |
|-------------|----------|-------|
| Local (Herd) | `http://localhost` | Default Laravel Herd |
| Local (Serve) | `http://localhost:8000` | Using `php artisan serve` |
| Staging | `https://staging.yourdomain.com` | Your staging server |
| Production | `https://yourdomain.com` | Your production server |

## Testing Endpoints

### 1. Get All Cages
**Purpose**: Verify authentication and get list of all cages

**Request**:
```
GET /api/cages?key=default-api-key
```

**Expected**: 200 OK with cages data

---

### 2. Calculate Weight from Dimensions
**Purpose**: Test weight calculation from height/width

**Request**:
```
POST /api/weight?key=default-api-key
Content-Type: application/json

{
    "height": 10,
    "width": 5,
    "sampling_id": 1
}
```

**Expected**: 200 OK with calculated weight

**Note**: Use a real `sampling_id` from your database

---

### 3. Get Next Sample to Measure
**Purpose**: Get the next unfilled sample

**Request**:
```
POST /api/sampling/calculate?key=default-api-key
Content-Type: application/json

{
    "sampling_id": 1
}
```

**Expected**: 200 OK with sample details

---

### 4. Get Sampling Details
**Purpose**: Get detailed information about a sampling

**Request**:
```
GET /api/sampling/1?key=default-api-key
```

**Expected**: 200 OK with sampling data

---

## Error Testing

The collection includes an "Errors" folder with tests for:

1. **Missing API Key** - Should return 422
2. **Invalid API Key** - Should return 422  
3. **Missing Required Fields** - Should return 422 with validation errors

## Running Tests

### Individual Request
1. Select a request from the collection
2. Click **Send**
3. Check response in the bottom panel

### Full Collection
1. Click on the collection name
2. Click **Run** button
3. Collection Runner opens
4. Select all requests or specific ones
5. Click **Run SFM API...**
6. View test results

### Automated Testing
Add test scripts in the **Tests** tab:

```javascript
// Test for successful response
pm.test("Status code is 200", function () {
    pm.response.to.have.status(200);
});

// Test for valid JSON
pm.test("Response is valid JSON", function () {
    pm.response.to.be.json;
});

// Test for message field
pm.test("Response has message", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData).to.have.property('message');
});

// Test for data field
pm.test("Response has data", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData).to.have.property('data');
});
```

## Test Data Setup

### Ensure Database is Seeded

Before testing, make sure you have test data:

```bash
# Fresh database with seeders
php artisan migrate:fresh --seed

# Or just seed if tables exist
php artisan db:seed
```

### Get Sample IDs

Find valid sampling IDs to use in your tests:

```bash
# Using Tinker
php artisan tinker
>>> App\Models\Sampling::pluck('id', 'doc')->take(10)

# Using SQL
php artisan tinker
>>> DB::table('samplings')->select('id', 'doc', 'date_sampling')->take(10)->get()
```

## Alternative Testing Methods

### Using cURL

```bash
# Get all cages
curl "http://localhost/api/cages?key=default-api-key"

# Calculate weight
curl -X POST "http://localhost/api/weight?key=default-api-key" \
  -H "Content-Type: application/json" \
  -d '{"height": 10, "width": 5, "sampling_id": 1}'

# Get next sample
curl -X POST "http://localhost/api/sampling/calculate?key=default-api-key" \
  -H "Content-Type: application/json" \
  -d '{"sampling_id": 1}'

# Get sampling details
curl "http://localhost/api/sampling/1?key=default-api-key"
```

### Using HTTPie

```bash
# Get all cages
http GET localhost/api/cages key==default-api-key

# Calculate weight
http POST localhost/api/weight key==default-api-key height=10 width=5 sampling_id=1

# Get next sample
http POST localhost/api/sampling/calculate key==default-api-key sampling_id=1

# Get sampling details
http GET localhost/api/sampling/1 key==default-api-key
```

### Using PHPUnit Tests

```bash
# Run all API tests
php artisan test --filter=DocsControllerTest

# Run specific test
php artisan test --filter=test_cages_endpoint_returns_cages

# With verbose output
php artisan test --filter=DocsControllerTest -v
```

## Troubleshooting

### Connection Refused
**Problem**: Cannot connect to server

**Solutions**:
- Check if Laravel is running: `php artisan serve`
- Verify the port: Default is 8000
- Try: `http://localhost:8000/api/cages?key=default-api-key`
- Check Laravel Herd: Ensure site is active

### 404 Not Found
**Problem**: Endpoint not found

**Solutions**:
- Check routes: `php artisan route:list | grep api`
- Verify `routes/api.php` is loaded in `bootstrap/app.php`
- Clear cache: `php artisan route:clear`

### 422 Validation Error
**Problem**: Invalid request

**Solutions**:
- Check API key is correct: `default-api-key`
- Verify all required fields are present
- Check data types (height/width must be numeric)
- Ensure sampling_id exists in database

### Empty Response
**Problem**: No data returned

**Solutions**:
- Seed database: `php artisan migrate:fresh --seed`
- Check database has data: `php artisan tinker`
- Verify sampling_id exists

### CORS Errors
**Problem**: Cross-origin requests blocked

**Solutions**:
- Install CORS package: `composer require fruitcake/laravel-cors`
- Configure `config/cors.php` for your needs
- Or test from same origin as API

## Environment Configuration

### Change API Key

**In `.env`**:
```bash
API_KEY=your-secret-api-key-here
```

**In `config/services.php`** (already configured):
```php
'api_key' => env('API_KEY', 'default-api-key'),
```

After changing:
```bash
php artisan config:clear
```

## Postman Collection Features

### Environment Support

Create different environments in Postman:

**Development**:
```json
{
  "base_url": "http://localhost",
  "api_key": "dev-api-key"
}
```

**Production**:
```json
{
  "base_url": "https://api.yourdomain.com",
  "api_key": "prod-api-key"
}
```

### Pre-request Scripts

Add to collection **Pre-request Script**:

```javascript
// Set timestamp for testing
pm.collectionVariables.set("timestamp", new Date().toISOString());

// Log request
console.log("Request:", pm.request.url.toString());
```

### Collection Runner

Run all tests automatically:
1. Click collection → **Run**
2. Select requests to test
3. Click **Run SFM API...**
4. View results and export report

## Advanced Features

### Document Generation

Postman can generate documentation:
1. Collection → **Generate Documentation**
2. Customize and publish
3. Share with team

### Mock Servers

Create mock responses:
1. Collection → **Mock Servers**
2. Add example responses
3. Test without backend

### Team Collaboration

1. Share collection URL
2. Team members import
3. Everyone uses same tests

## Next Steps

1. **Add Tests**: Write Postman tests for all scenarios
2. **Create Environments**: Set up dev/staging/prod
3. **Automate**: Integrate with CI/CD
4. **Monitor**: Track API performance
5. **Document**: Generate API docs

## Support

- **Full API Documentation**: See `API_DOCUMENTATION.md`
- **Testing Guide**: See `POSTMAN_TESTING_GUIDE.md`
- **Laravel Docs**: https://laravel.com/docs
- **Postman Docs**: https://learning.postman.com

## Quick Reference

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/api/cages` | GET | key | Get all cages |
| `/api/weight` | POST | key | Calculate weight |
| `/api/sampling/calculate` | POST | key | Get next sample |
| `/api/sampling/{id}` | GET | key | Get sampling details |

**API Key**: `default-api-key` (set in `.env`)

