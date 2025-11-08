# API Documentation

## Overview
This API is designed for mobile/device integration to support sampling data collection and management.

## Authentication
All endpoints require an API key to be passed as a query parameter:
```
?key=your-api-key
```

The API key can be configured in your `.env` file:
```
API_KEY=your-secret-api-key
```

## Endpoints

### 1. Get All Cages
**GET** `/api/cages`

Returns all cages with their associated samplings.

**Query Parameters:**
- `key` (required): Your API key

**Response:**
```json
{
    "message": "Cages fetch successfully",
    "data": [
        {
            "id": 1,
            "investor_id": 1,
            "feed_types_id": 1,
            "samplings": [...],
            "investor": {...},
            "feedType": {...}
        }
    ]
}
```

---

### 2. Calculate Weight from Dimensions
**POST** `/api/weight`

Calculates fish weight from height and width measurements. This is designed for devices that measure fish dimensions rather than weight directly.

**Query Parameters:**
- `key` (required): Your API key

**Request Body:**
```json
{
    "height": 10,
    "width": 5,
    "sampling_id": 1
}
```

**Response:**
```json
{
    "message": "Successfully get the fish weight",
    "data": {
        "weight": 156.789,
        "sample_no": 1,
        "abw": 150.5,
        "total_weight": 1505.00,
        "remaining_samples": 9
    }
}
```

**Errors:**
- 422: Invalid API key, missing parameters, or all samples already filled

---

### 3. Get Next Sample to Measure
**POST** `/api/sampling/calculate`

Retrieves the next unfilled sample that needs to be measured.

**Query Parameters:**
- `key` (required): Your API key

**Request Body:**
```json
{
    "sampling_id": 1
}
```

**Response:**
```json
{
    "message": "Next sample retrieved successfully",
    "data": {
        "sampling_id": 1,
        "current_sample": {
            "id": 5,
            "sample_no": 1
        },
        "progress": {
            "filled": 5,
            "remaining": 5,
            "total": 10,
            "percentage": 50.00
        },
        "sampling_info": {
            "investor": "John Doe",
            "date": "2024-01-15",
            "doc": "DOC123"
        }
    }
}
```

**Errors:**
- 422: Invalid API key, missing parameters, or sampling not found
- 422: All samples have been measured

---

### 4. Get Sampling Details
**GET** `/api/sampling/{id}`

Retrieves detailed information about a specific sampling session.

**Query Parameters:**
- `key` (required): Your API key

**URL Parameters:**
- `id`: The sampling ID

**Response:**
```json
{
    "message": "Sampling retrieved successfully",
    "data": {
        "sampling": {
            "id": 1,
            "investor_id": 1,
            "cage_no": 1,
            "date_sampling": "2024-01-15",
            "doc": "DOC123",
            "mortality": 5,
            "samples": [...]
        },
        "statistics": {
            "total_samples": 10,
            "filled_samples": 5,
            "total_weight": 1505.00,
            "average_weight": 150.5
        }
    }
}
```

**Errors:**
- 422: Invalid API key
- 404: Sampling not found

---

## Weight Calculation Formula
The weight calculation uses the following formula (from fish length measurements):
```
height_inches = (height_cm * 0.3937) * 1.9
width_inches = width_cm * 0.3937
weight_pounds = (width_inches * (height_inches^2)) / 690
weight_grams = weight_pounds * 453.592
```

---

## Error Responses
All error responses follow this format:
```json
{
    "message": "Error description",
    "errors": {
        "field": ["validation error message"]
    }
}
```

**Common Error Codes:**
- `422`: Validation error or invalid API key
- `404`: Resource not found
- `500`: Internal server error

---

## Testing
Comprehensive tests are available in `tests/Feature/Api/DocsControllerTest.php`. All endpoints are fully tested.

## Configuration
Add your API key to `.env`:
```
API_KEY=your-secret-api-key
```

The API key is also configurable via `config/services.php`.

