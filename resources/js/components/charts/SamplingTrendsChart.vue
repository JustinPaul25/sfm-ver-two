<script setup lang="ts">
import { Line } from 'vue-chartjs';
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    LineElement,
    CategoryScale,
    LinearScale,
    PointElement,
    Filler,
} from 'chart.js';
import { ref, onMounted, onUnmounted, watch, computed } from 'vue';
import type * as tfTypes from '@tensorflow/tfjs';

ChartJS.register(
    Title,
    Tooltip,
    Legend,
    LineElement,
    CategoryScale,
    LinearScale,
    PointElement,
    Filler
);

const props = defineProps<{
    trends?: Array<{
        date: string;
        count: number;
        avg_weight: number;
    }>;
}>();

const chartData = ref({
    labels: [] as string[],
    datasets: [] as any[],
});

const chartOptions = ref({
    responsive: true,
    maintainAspectRatio: false,
    interaction: {
        mode: 'index' as const,
        intersect: false,
    },
    plugins: {
        legend: {
            display: true,
            position: 'top' as const,
            labels: {
                usePointStyle: true,
                padding: 15,
                font: {
                    size: 12,
                },
                color: '#6B7280',
            },
        },
        tooltip: {
            backgroundColor: 'rgba(0, 0, 0, 0.8)',
            padding: 12,
            titleFont: {
                size: 14,
            },
            bodyFont: {
                size: 12,
            },
            borderColor: 'rgba(255, 255, 255, 0.1)',
            borderWidth: 1,
            cornerRadius: 8,
            displayColors: true,
            callbacks: {
                label: function(context: any) {
                    const label = context.dataset.label || '';
                    const value = context.parsed.y;
                    if (label.includes('Weight')) {
                        return `${label}: ${value.toFixed(1)}g`;
                    }
                    return `${label}: ${value}`;
                },
            },
        },
    },
    scales: {
        x: {
            grid: {
                display: false,
            },
            ticks: {
                maxRotation: 45,
                minRotation: 45,
                color: '#6B7280',
                font: {
                    size: 11,
                },
            },
        },
        y: {
            position: 'left' as const,
            beginAtZero: true,
            grid: {
                color: 'rgba(107, 114, 128, 0.1)',
            },
            ticks: {
                color: '#6B7280',
                font: {
                    size: 11,
                },
            },
            title: {
                display: true,
                text: 'Samplings Count',
                color: '#6B7280',
                font: {
                    size: 12,
                },
            },
        },
        y1: {
            position: 'right' as const,
            beginAtZero: true,
            grid: {
                drawOnChartArea: false,
            },
            ticks: {
                color: '#6B7280',
                font: {
                    size: 11,
                },
                callback: function(value: any) {
                    return value + 'g';
                },
            },
            title: {
                display: true,
                text: 'Weight (g)',
                color: '#6B7280',
                font: {
                    size: 12,
                },
            },
        },
    },
});

const isTraining = ref(false);
const predictionDays = ref(7); // Predict 7 days ahead
const showPredictions = ref(true);

// Load TensorFlow.js lazily
let tf: typeof tfTypes | null = null;
let model: tfTypes.LayersModel | null = null;

const loadTensorFlow = async () => {
    if (!tf) {
        tf = await import('@tensorflow/tfjs');
    }
    return tf;
};

const createModel = (tfInstance: typeof tfTypes) => {
    const newModel = tfInstance.sequential({
        layers: [
            tfInstance.layers.dense({
                inputShape: [5],
                units: 10,
                activation: 'relu',
            }),
            tfInstance.layers.dropout({ rate: 0.2 }),
            tfInstance.layers.dense({
                units: 10,
                activation: 'relu',
            }),
            tfInstance.layers.dropout({ rate: 0.2 }),
            tfInstance.layers.dense({
                units: 1,
                activation: 'linear',
            }),
        ],
    });

    newModel.compile({
        optimizer: tfInstance.train.adam(0.001),
        loss: 'meanSquaredError',
        metrics: ['mae'],
    });

    return newModel;
};

// Prepare data for training
const prepareData = (tfInstance: typeof tfTypes, data: number[]) => {
    if (data.length < 5) return null;

    const X: number[][] = [];
    const y: number[] = [];

    for (let i = 5; i < data.length; i++) {
        X.push(data.slice(i - 5, i));
        y.push(data[i]);
    }

    return {
        X: tfInstance.tensor2d(X),
        y: tfInstance.tensor2d(y, [y.length, 1]),
    };
};

// Train model for sampling counts
const trainSamplingModel = async (tfInstance: typeof tfTypes, data: number[]) => {
    const prepared = prepareData(tfInstance, data);
    if (!prepared) return null;

    model = createModel(tfInstance);
    isTraining.value = true;

    try {
        await model.fit(prepared.X, prepared.y, {
            epochs: 100,
            batchSize: Math.min(8, Math.floor(data.length / 2)),
            verbose: 0,
            callbacks: {
                onEpochEnd: (epoch: number, logs: any) => {
                    if (epoch % 20 === 0) {
                        console.log(`Epoch ${epoch}: loss = ${logs.loss.toFixed(4)}`);
                    }
                },
            },
        });

        prepared.X.dispose();
        prepared.y.dispose();

        return model;
    } catch (error) {
        console.error('Training error:', error);
        return null;
    } finally {
        isTraining.value = false;
    }
};

// Predict future values
const predictFuture = async (tfInstance: typeof tfTypes, data: number[], days: number) => {
    if (!model || data.length < 5) return [];

    const predictions: number[] = [];
    let lastSequence = data.slice(-5);

    for (let i = 0; i < days; i++) {
        const input = tfInstance.tensor2d([lastSequence]);
        const prediction = model.predict(input) as tfTypes.Tensor;
        const value = await prediction.data();
        const predictedValue = Math.max(0, value[0]); // Ensure non-negative

        predictions.push(predictedValue);
        
        // Update sequence for next prediction
        lastSequence.shift();
        lastSequence.push(predictedValue);

        input.dispose();
        prediction.dispose();
    }

    return predictions;
};

// Generate future dates
const generateFutureDates = (startDate: string, days: number) => {
    const dates: string[] = [];
    const start = new Date(startDate);
    
    for (let i = 1; i <= days; i++) {
        const date = new Date(start);
        date.setDate(date.getDate() + i);
        dates.push(date.toLocaleDateString());
    }
    
    return dates;
};

// Process data and predictions
const processChartData = async () => {
    if (!props.trends || props.trends.length === 0) {
        chartData.value = {
            labels: [],
            datasets: [],
        };
        return;
    }

    const labels = props.trends.map((item) => new Date(item.date).toLocaleDateString());
    const counts = props.trends.map((item) => item.count);
    const weights = props.trends.map((item) => item.avg_weight);

    // Create actual data datasets
    const datasets: any[] = [
        {
            label: 'Samplings',
            data: counts,
            borderColor: 'rgb(59, 130, 246)',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            tension: 0.4,
            fill: true,
            pointRadius: 3,
            pointHoverRadius: 5,
        },
        {
            label: 'Avg Weight (g)',
            data: weights,
            borderColor: 'rgb(16, 185, 129)',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            tension: 0.4,
            fill: true,
            yAxisID: 'y1',
            pointRadius: 3,
            pointHoverRadius: 5,
        },
    ];

    // Add predictions if enabled and we have enough data
    if (showPredictions.value && props.trends.length >= 10) {
        isTraining.value = true;
        
        try {
            const tfInstance = await loadTensorFlow();

            // Train model and predict for sampling counts
            const samplingModel = await trainSamplingModel(tfInstance, counts);
            if (samplingModel) {
                const countPredictions = await predictFuture(tfInstance, counts, predictionDays.value);
                const futureDates = generateFutureDates(
                    props.trends[props.trends.length - 1].date,
                    predictionDays.value
                );

                // Add prediction datasets
                datasets.push({
                    label: 'Predicted Samplings',
                    data: [
                        ...Array(props.trends.length).fill(null),
                        ...countPredictions,
                    ],
                    borderColor: 'rgb(99, 102, 241)',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    borderDash: [5, 5],
                    tension: 0.4,
                    fill: false,
                    pointRadius: 2,
                    pointHoverRadius: 4,
                });

                // Update labels to include future dates
                labels.push(...futureDates);
            }

            // Train model and predict for weights
            const weightModel = await trainSamplingModel(tfInstance, weights);
            if (weightModel) {
                const weightPredictions = await predictFuture(tfInstance, weights, predictionDays.value);
                
                datasets.push({
                    label: 'Predicted Avg Weight (g)',
                    data: [
                        ...Array(props.trends.length).fill(null),
                        ...weightPredictions,
                    ],
                    borderColor: 'rgb(34, 197, 94)',
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    borderDash: [5, 5],
                    tension: 0.4,
                    fill: false,
                    yAxisID: 'y1',
                    pointRadius: 2,
                    pointHoverRadius: 4,
                });
            }
        } catch (error) {
            console.error('Prediction error:', error);
        } finally {
            isTraining.value = false;
        }
    }

    chartData.value = {
        labels,
        datasets,
    };
};

// Watch for changes in trends data
watch(
    () => props.trends,
    () => {
        processChartData();
    },
    { deep: true, immediate: true }
);

onMounted(() => {
    processChartData();
});

// Cleanup
onUnmounted(() => {
    if (model) {
        model.dispose();
        model = null;
    }
});
</script>

<template>
    <div class="relative">
        <div v-if="isTraining" class="absolute inset-0 bg-white/50 dark:bg-gray-900/50 flex items-center justify-center z-10 rounded-lg">
            <div class="text-center">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mb-2"></div>
                <p class="text-sm text-muted-foreground">Training AI model...</p>
            </div>
        </div>
        <div v-if="!trends || trends.length === 0" class="h-64 flex items-center justify-center bg-gray-50 dark:bg-gray-800 rounded-lg">
            <div class="text-center">
                <div class="text-4xl mb-2">📈</div>
                <p class="text-sm text-muted-foreground">No data available</p>
            </div>
        </div>
        <div v-else class="h-64">
            <Line :data="chartData" :options="chartOptions" />
        </div>
        <div v-if="trends && trends.length > 0" class="mt-2 flex justify-end">
            <label class="flex items-center gap-2 text-sm text-muted-foreground cursor-pointer">
                <input
                    v-model="showPredictions"
                    type="checkbox"
                    class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                    @change="processChartData"
                />
                <span>Show AI Predictions</span>
            </label>
        </div>
    </div>
</template>

