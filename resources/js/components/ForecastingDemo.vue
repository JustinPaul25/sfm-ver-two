<script setup lang="ts">
import { ref, onUnmounted } from 'vue';
import { useForecastingService } from '@/composables/useForecastingService';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const { isTraining, trainingProgress, forecast, dispose, getCurrentAlgorithm } =
  useForecastingService();

const selectedAlgorithm = ref<'lstm' | 'rnn' | 'dense'>('lstm');
const useSystemAlgorithm = ref(true);
const sampleData = ref<string>('10,12,15,18,22,25,30,35,40,45,50,55,60,65,70');
const sequenceLength = ref(7);
const predictionSteps = ref(7);
const epochs = ref(50);
const forecastResults = ref<number[]>([]);
const trainingTime = ref(0);
const error = ref<string>('');
const mae = ref<number | null>(null);
const rmse = ref<number | null>(null);

async function loadSystemAlgorithm() {
  try {
    const algorithm = await getCurrentAlgorithm();
    selectedAlgorithm.value = algorithm;
  } catch (err) {
    console.error('Failed to load system algorithm:', err);
  }
}

async function runForecast() {
  error.value = '';
  forecastResults.value = [];
  trainingTime.value = 0;
  mae.value = null;
  rmse.value = null;

  try {
    // Parse sample data
    const data = sampleData.value.split(',').map((val) => parseFloat(val.trim()));

    if (data.some((val) => isNaN(val))) {
      error.value = 'Invalid data format. Please enter comma-separated numbers.';
      return;
    }

    if (data.length < sequenceLength.value + 5) {
      error.value = `Need at least ${sequenceLength.value + 5} data points.`;
      return;
    }

    // Load system algorithm if needed
    if (useSystemAlgorithm.value) {
      await loadSystemAlgorithm();
    }

    // Run forecasting
    const result = await forecast(data, {
      algorithm: selectedAlgorithm.value,
      sequenceLength: sequenceLength.value,
      epochs: epochs.value,
      predictionSteps: predictionSteps.value,
    });

    forecastResults.value = result.predictions;
    trainingTime.value = result.trainingTime;
    mae.value = typeof result.mae === 'number' ? result.mae : null;
    rmse.value = typeof result.rmse === 'number' ? result.rmse : null;
  } catch (err: any) {
    error.value = err.message || 'An error occurred during forecasting.';
    console.error('Forecasting error:', err);
  }
}

// Bar height for visual preview: min-max scale so variation is visible
function getBarHeight(value: number): number {
  const results = forecastResults.value;
  if (results.length === 0) return 0;
  const min = Math.min(...results);
  const max = Math.max(...results);
  if (max === min) return 128;
  const normalized = (value - min) / (max - min);
  return Math.max(8, normalized * 128); // 128px = h-32, min 8px for visibility
}

// Load system algorithm on mount
loadSystemAlgorithm();

// Cleanup on unmount
onUnmounted(() => {
  dispose();
});
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle>Time Series Forecasting Demo</CardTitle>
      <CardDescription>
        Test the forecasting algorithms with sample data
      </CardDescription>
    </CardHeader>
    <CardContent class="space-y-6">
      <!-- Algorithm Selection -->
      <div class="space-y-2">
        <div class="flex items-center space-x-2">
          <input
            type="checkbox"
            id="useSystemAlgorithm"
            v-model="useSystemAlgorithm"
            class="h-4 w-4 rounded border-gray-300"
          />
          <Label for="useSystemAlgorithm" class="text-sm font-normal">
            Use system-configured algorithm
          </Label>
        </div>

        <div v-if="!useSystemAlgorithm" class="space-y-2">
          <Label for="algorithm">Algorithm</Label>
          <select
            id="algorithm"
            v-model="selectedAlgorithm"
            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
          >
            <option value="lstm">LSTM</option>
            <option value="rnn">RNN</option>
            <option value="dense">Dense</option>
          </select>
        </div>

        <div v-else class="text-sm text-muted-foreground">
          Currently using: <strong class="uppercase">{{ selectedAlgorithm }}</strong>
        </div>
      </div>

      <!-- Sample Data -->
      <div class="space-y-2">
        <Label for="sampleData">Sample Data (comma-separated)</Label>
        <Input
          id="sampleData"
          v-model="sampleData"
          placeholder="10,12,15,18,22,25,30,35..."
          :disabled="isTraining"
        />
      </div>

      <!-- Parameters -->
      <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <div class="space-y-2">
          <Label for="sequenceLength">Sequence Length</Label>
          <Input
            id="sequenceLength"
            type="number"
            v-model.number="sequenceLength"
            min="3"
            max="20"
            :disabled="isTraining"
          />
        </div>

        <div class="space-y-2">
          <Label for="epochs">Training Epochs</Label>
          <Input
            id="epochs"
            type="number"
            v-model.number="epochs"
            min="10"
            max="200"
            :disabled="isTraining"
          />
        </div>

        <div class="space-y-2">
          <Label for="predictionSteps">Prediction Steps</Label>
          <Input
            id="predictionSteps"
            type="number"
            v-model.number="predictionSteps"
            min="1"
            max="30"
            :disabled="isTraining"
          />
        </div>
      </div>

      <!-- Run Button -->
      <Button @click="runForecast" :disabled="isTraining" class="w-full">
        {{ isTraining ? 'Training...' : 'Run Forecast' }}
      </Button>

      <!-- Training Progress -->
      <div v-if="isTraining" class="space-y-2">
        <div class="flex items-center justify-between text-sm">
          <span>Training Progress</span>
          <span>{{ Math.round(trainingProgress) }}%</span>
        </div>
        <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200">
          <div
            class="h-full bg-primary transition-all duration-300"
            :style="{ width: `${trainingProgress}%` }"
          ></div>
        </div>
      </div>

      <!-- Error Message -->
      <div v-if="error" class="rounded-md bg-destructive/15 p-3 text-sm text-destructive">
        {{ error }}
      </div>

      <!-- Results -->
      <div v-if="forecastResults.length > 0" class="space-y-4">
        <div class="rounded-lg border bg-muted/50 p-4">
          <h3 class="mb-2 font-semibold">Forecast Results</h3>
          <p class="text-sm text-muted-foreground">
            Algorithm: <strong class="uppercase">{{ selectedAlgorithm }}</strong> |
            Training Time: <strong>{{ (trainingTime / 1000).toFixed(2) }}s</strong>
          </p>
          <p v-if="mae !== null || rmse !== null" class="text-sm text-muted-foreground">
            <span v-if="mae !== null">MAE: <strong>{{ mae.toFixed(2) }}</strong></span>
            <template v-if="mae !== null && rmse !== null"> | </template>
            <span v-if="rmse !== null">RMSE: <strong>{{ rmse.toFixed(2) }}</strong></span>
          </p>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="border-b">
              <tr>
                <th class="px-4 py-2 text-left">Step</th>
                <th class="px-4 py-2 text-right">Predicted Value</th>
              </tr>
            </thead>
            <tbody class="divide-y">
              <tr v-for="(value, index) in forecastResults" :key="index">
                <td class="px-4 py-2">{{ index + 1 }}</td>
                <td class="px-4 py-2 text-right font-mono">{{ value.toFixed(2) }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Visualization -->
        <div class="rounded-lg border p-4">
          <h4 class="mb-2 text-sm font-semibold">Visual Preview</h4>
          <div class="flex h-32 items-end justify-between gap-1">
            <div
              v-for="(value, index) in forecastResults"
              :key="index"
              class="flex-1 rounded-t bg-primary/70 transition-all hover:bg-primary"
              :style="{
                height: `${getBarHeight(value)}px`,
              }"
              :title="`Step ${index + 1}: ${value.toFixed(2)}`"
            ></div>
          </div>
        </div>
      </div>
    </CardContent>
  </Card>
</template>
