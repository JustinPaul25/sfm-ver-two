# Forecasting Implementation Guide

## Overview

This document describes the implementation of a switchable forecasting system using TensorFlow.js with three different machine learning algorithms. The system allows admin users to configure which algorithm is used system-wide for forecasting operations.

## Features Implemented

### 1. Three Forecasting Algorithms

The system includes three time-series forecasting algorithms, each optimized for different use cases:

#### **LSTM (Long Short-Term Memory)**
- **Best for**: Complex time series with long-term dependencies
- **Accuracy**: ⭐⭐⭐⭐⭐ (Highest)
- **Speed**: ⭐⭐ (Slower training)
- **Use cases**: 
  - Long-term growth predictions
  - Complex seasonal patterns
  - Data with temporal dependencies

#### **RNN (Simple Recurrent Neural Network)**
- **Best for**: Balanced use cases with moderate complexity
- **Accuracy**: ⭐⭐⭐⭐
- **Speed**: ⭐⭐⭐⭐
- **Use cases**:
  - General purpose forecasting
  - Medium-term predictions
  - When training speed matters

#### **Dense Neural Network (Multi-layer Perceptron)**
- **Best for**: Quick predictions with shorter sequences
- **Accuracy**: ⭐⭐⭐
- **Speed**: ⭐⭐⭐⭐⭐ (Fastest)
- **Use cases**:
  - Real-time predictions
  - Simple patterns
  - When speed is critical

### 2. Admin System Settings

Admin users can configure the forecasting algorithm through:
- **Settings Page**: `/settings/system` (Admin only)
- **Navigation**: Available in the admin sidebar under "System Settings"
- **Settings Layout**: Also accessible via Settings → System tab

### 3. Database Structure

#### System Settings Table
```sql
- id (primary key)
- key (unique string)
- value (text)
- type (string: 'string', 'integer', 'boolean', 'json')
- description (text)
- timestamps
```

Default setting:
- **Key**: `forecasting_algorithm`
- **Value**: `lstm` (default)
- **Type**: `string`
- **Options**: `lstm`, `rnn`, `dense`

## Implementation Details

### Backend Files

1. **Migration**: `database/migrations/2026_01_20_015732_create_system_settings_table.php`
   - Creates system_settings table
   - Seeds default forecasting algorithm setting

2. **Model**: `app/Models/SystemSetting.php`
   - Provides `SystemSetting::get($key, $default)` helper
   - Provides `SystemSetting::set($key, $value, $type, $description)` helper
   - Handles type casting (string, integer, boolean, json)

3. **Controller**: `app/Http/Controllers/SystemSettingsController.php`
   - `index()`: Display system settings page
   - `updateForecastingAlgorithm()`: Update the algorithm
   - `getForecastingAlgorithm()`: API endpoint to get current algorithm

4. **Routes**: 
   - Web: `/settings/system` (Admin only)
   - API: `/api/settings/forecasting-algorithm` (Public, GET)

### Frontend Files

1. **Forecasting Service**: `resources/js/composables/useForecastingService.ts`
   - Complete TensorFlow.js implementation
   - All three algorithms (LSTM, RNN, Dense)
   - Training progress tracking
   - Configurable parameters:
     - `sequenceLength`: How many historical points to use (default: 10)
     - `epochs`: Training iterations (default: 50)
     - `learningRate`: Learning rate for optimizer (default: 0.001)
     - `predictionSteps`: How many future steps to predict (default: 7)

2. **Admin Settings Page**: `resources/js/pages/settings/System.vue`
   - Algorithm selection dropdown
   - Algorithm comparison table
   - Detailed descriptions
   - Save functionality with validation

3. **Demo Component**: `resources/js/components/ForecastingDemo.vue`
   - Interactive forecasting demo
   - Test with sample data
   - Configure parameters
   - Visual results display
   - Can use system algorithm or override

4. **Navigation Updates**:
   - `resources/js/components/AppSidebar.vue`: Added "System Settings" for admin
   - `resources/js/layouts/settings/Layout.vue`: Added "System" tab for admin

## Usage Examples

### 1. Using the Forecasting Service in a Component

```vue
<script setup lang="ts">
import { ref } from 'vue';
import { useForecastingService } from '@/composables/useForecastingService';

const { isTraining, trainingProgress, forecast, getCurrentAlgorithm, dispose } = useForecastingService();

// Sample historical data
const historicalData = [10, 12, 15, 18, 22, 25, 30, 35, 40, 45, 50];

async function runForecast() {
  // Get system-configured algorithm
  const algorithm = await getCurrentAlgorithm();
  
  // Run forecasting
  const result = await forecast(historicalData, {
    algorithm, // 'lstm', 'rnn', or 'dense'
    sequenceLength: 7,
    epochs: 50,
    predictionSteps: 7,
  });
  
  console.log('Predictions:', result.predictions);
  console.log('Training time:', result.trainingTime, 'ms');
}

// Clean up on unmount
onUnmounted(() => {
  dispose();
});
</script>
```

### 2. Manually Specifying an Algorithm

```typescript
const result = await forecast(data, {
  algorithm: 'dense', // Override system setting
  sequenceLength: 5,
  epochs: 30,
  predictionSteps: 5,
});
```

### 3. Getting the System Algorithm via API

```javascript
// JavaScript/Frontend
const response = await fetch('/api/settings/forecasting-algorithm');
const { algorithm } = await response.json();
console.log('Current algorithm:', algorithm); // 'lstm', 'rnn', or 'dense'
```

```php
// PHP/Backend
use App\Models\SystemSetting;

$algorithm = SystemSetting::get('forecasting_algorithm', 'lstm');
```

### 4. Updating the Algorithm (Admin Only)

Via the UI:
1. Navigate to Settings → System
2. Select the desired algorithm from dropdown
3. Click "Save Changes"

Programmatically:
```php
use App\Models\SystemSetting;

SystemSetting::set(
    'forecasting_algorithm',
    'rnn',
    'string',
    'Algorithm used for forecasting'
);
```

## Testing the Implementation

### 1. Access System Settings
- Log in as an admin user
- Navigate to Settings → System or click "System Settings" in the sidebar
- You should see the forecasting algorithm settings page

### 2. Change Algorithm
- Select a different algorithm from the dropdown
- Click "Save Changes"
- Verify the success message appears

### 3. Test Forecasting Demo
To add the forecasting demo to any page:

```vue
<script setup lang="ts">
import ForecastingDemo from '@/components/ForecastingDemo.vue';
</script>

<template>
  <ForecastingDemo />
</template>
```

### 4. API Testing

```bash
# Get current algorithm
curl http://your-domain.com/api/settings/forecasting-algorithm

# Expected response:
# {"algorithm":"lstm"}
```

## Algorithm Selection Guide

Choose the algorithm based on your needs:

| Scenario | Recommended Algorithm | Why |
|----------|----------------------|-----|
| Long-term growth tracking | LSTM | Best at capturing long-term dependencies |
| Daily/weekly predictions | RNN | Good balance of speed and accuracy |
| Real-time dashboards | Dense | Fastest training, good for frequent updates |
| Small dataset (<50 points) | Dense | Works well with limited data |
| Large dataset (>200 points) | LSTM | Can leverage more data for better patterns |
| Complex seasonal patterns | LSTM | Better at complex temporal relationships |

## Technical Notes

### Data Requirements

- **Minimum data points**: `sequenceLength + 5` (default: 15 points)
- **Recommended**: At least 30 data points for reliable predictions
- **Data format**: Array of numbers (will be automatically normalized)

### Performance Considerations

1. **Training Time**:
   - Dense: ~1-3 seconds for 50 epochs
   - RNN: ~3-8 seconds for 50 epochs
   - LSTM: ~8-15 seconds for 50 epochs

2. **Memory Usage**:
   - All algorithms run in the browser using TensorFlow.js
   - Automatic cleanup after predictions
   - Use `dispose()` to free memory when done

3. **Browser Compatibility**:
   - Requires modern browser with WebGL support
   - Falls back to CPU if WebGL unavailable
   - Tested on Chrome, Firefox, Edge, Safari

### Model Architecture Details

**LSTM Model**:
- 2 LSTM layers (50 units each)
- Dropout layers (0.2 rate)
- Adam optimizer
- Mean Squared Error loss

**RNN Model**:
- 2 Simple RNN layers (32 units each)
- Dropout layers (0.2 rate)
- Adam optimizer
- Mean Squared Error loss

**Dense Model**:
- Flatten layer
- 3 Dense layers (128, 64, 32 units)
- Dropout layers (0.3 rate)
- ReLU activation
- Adam optimizer
- Mean Squared Error loss

## Future Enhancements

Potential improvements to consider:

1. **Model Persistence**: Save trained models for reuse
2. **Confidence Intervals**: Add prediction uncertainty metrics
3. **Auto-tuning**: Automatic hyperparameter optimization
4. **Multiple Forecasting Periods**: Support for different time horizons
5. **Model Comparison**: Side-by-side algorithm performance metrics
6. **Historical Performance**: Track algorithm accuracy over time
7. **Custom Models**: Allow users to upload custom TensorFlow models

## Troubleshooting

### Build Failed
- Make sure TensorFlow.js is installed: `npm install @tensorflow/tfjs`
- Clear cache: `npm run build` after clearing `public/build`

### Algorithm Not Changing
- Verify you're logged in as admin
- Check browser console for errors
- Verify migration ran: `php artisan migrate:status`

### Slow Predictions
- Reduce `epochs` parameter (try 20-30)
- Use Dense algorithm for faster results
- Ensure WebGL is enabled in browser

### Out of Memory Errors
- Call `dispose()` after forecasting
- Reduce batch size in training
- Use smaller sequence lengths

## Support

For issues or questions:
1. Check browser console for errors
2. Verify TensorFlow.js is loaded
3. Test with the ForecastingDemo component
4. Check system settings in database: `select * from system_settings;`
