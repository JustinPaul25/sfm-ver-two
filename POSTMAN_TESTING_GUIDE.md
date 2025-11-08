# Postman Testing Guide for SFM API

## Quick Start

1. **Import Collection**: Import `SFM_API.postman_collection.json` into Postman
2. **Set Base URL**: Update the `base_url` variable in Postman:
   - If using Laravel Herd: `http://localhost`
   - If using Laravel Sail: `http://localhost`
   - If using `php artisan serve`: `http://localhost:8000`
   - For production: `https://your-domain.com`

3. **API Key**: The collection uses `default-api-key` by default. You can change it in the `api_key` variable if needed.

## Endpoints

### 1. Get All Cages
**Method**: `GET`  
**URL**: `/api/cages?key={{api_key}}`

**Example Request**:
```
GET http://localhost/api/cages?key=default-api-key
```

**Expected Response**:
```json
{
    "message": "Cages fetch successfully",
    "data": [
        {
            "id": 1,
            "investor_id": 1,
            "feed_types_id": 1,
            "number_of_fingerlings": 1000,
            "samplings": [...],
            "investor": {...},
            "feedType": {...}
        }
    ]
}
```

---

### 2. Calculate Weight from Dimensions
**Method**: `POST`  
**URL**: `/api/weight?key={{api_key}}`

**Request Body**:
```json
{
    "height": 10,
    "width": 5,
    "sampling_id": 1
}
```

**Notes**:
- `height` and `width` should be in **centimeters**
- `sampling_id` must be a valid sampling ID from your database
- Check available sampling IDs by running: `php get_sample_id.php`

**Expected Response**:
```json
{
    "message": "Successfully recorded fish weight",
    "data": {
        "weight_grams": 156.789,
        "sample_no": 1,
        "sampling_id": 1,
        "avg_body_weight": 150.5,
        "biomass_kg": 150.5
    }
}
```

---

### 3. Get Next Sample to Measure
**Method**: `POST`  
**URL**: `/api/sampling/calculate?key={{api_key}}`

**Request Body**:
```json
{
    "sampling_id": 1
}
```

**Expected Response**:
```json
{
    "message": "Next sample retrieved successfully",
    "data": {
        "sample_id": 1,
        "sample_no": 1,
        "sampling_id": 1,
        "weight": 0
    }
}
```

---

### 4. Get Sampling Details
**Method**: `GET`  
**URL**: `/api/sampling/{id}?key={{api_key}}`

**Example Request**:
```
GET http://localhost/api/sampling/1?key=default-api-key
```

**Expected Response**:
```json
{
    "message": "Sampling details retrieved successfully",
    "data": {
        "id": 1,
        "investor_id": 1,
        "cage_no": 4,
        "date_sampling": "2025-10-02",
        "doc": "DOC-20251002-01",
        "samples": [...]
    }
}
```

---

## Error Testing

The collection includes error test cases:

### Missing API Key
```
GET http://localhost/api/cages
```
**Expected**: `422 Unprocessable Entity`
```json
{
    "message": "Key is required or the key is invalid"
}
```

### Invalid API Key
```
GET http://localhost/api/cages?key=wrong-key
```
**Expected**: `422 Unprocessable Entity`
```json
{
    "message": "Key is required or the key is invalid"
}
```

### Missing Required Fields
```
POST http://localhost/api/weight?key=default-api-key
Body: { "height": 10 }
```
**Expected**: `422 Unprocessable Entity`
```json
{
    "errors": {
        "width": ["The width field is required."],
        "sampling_id": ["The sampling id field is required."]
    }
}
```

---

## Running Postman Tests

1. **Open Postman** and import `SFM_API.postman_collection.json`
2. **Select your environment** or use "No Environment"
3. **Update variables**:
   - `base_url`: Your Laravel server URL
   - `api_key`: Your API key (default: `default-api-key`)
4. **Run individual requests** or use the "Run Collection" feature
5. **Check responses** against expected formats

## Useful Commands

**Get a real sampling ID**:
```bash
php get_sample_id.php
```

**Get all sampling IDs**:
```bash
php artisan tinker
>>> App\Models\Sampling::pluck('id', 'doc')
```

**Test API with cURL**:
```bash
# Get all cages
curl "http://localhost/api/cages?key=default-api-key"

# Calculate weight
curl -X POST "http://localhost/api/weight?key=default-api-key" \
  -H "Content-Type: application/json" \
  -d '{"height": 10, "width": 5, "sampling_id": 1}'
```

---

## Tips

1. **Start with "Get All Cages"** to verify authentication
2. **Use real sampling IDs** from your database for testing weight calculations
3. **Check the Tests tab** in Postman to add automated assertions
4. **Use Postman's environment variables** for different environments (dev/staging/prod)
5. **Monitor Laravel logs** for detailed error messages: `tail -f storage/logs/laravel.log`

## Troubleshooting

**Connection Refused**:
- Make sure Laravel is running (`php artisan serve` or through Herd)
- Check if the port is correct (default: 8000)

**404 Not Found**:
- Verify the API routes are registered: `php artisan route:list | grep api`
- Check that `api.php` is loaded in `bootstrap/app.php`

**422 Validation Error**:
- Verify the API key is correct
- Check that all required fields are provided
- Ensure data types match (numeric for height/width)

**Empty Data**:
- Run the database seeder: `php artisan migrate:fresh --seed`
- Verify you have test data in the database

