import * as tf from '@tensorflow/tfjs';
import { ref, Ref } from 'vue';

export interface ForecastingOptions {
  algorithm: 'lstm' | 'rnn' | 'dense';
  sequenceLength?: number;
  epochs?: number;
  learningRate?: number;
  predictionSteps?: number;
}

export interface ForecastResult {
  predictions: number[];
  confidence?: number;
  algorithm: string;
  trainingTime: number;
  mae?: number;
  rmse?: number;
}

export function useForecastingService() {
  const isTraining: Ref<boolean> = ref(false);
  const trainingProgress: Ref<number> = ref(0);
  const currentModel: Ref<tf.LayersModel | null> = ref(null);

  /**
   * Normalize data to 0-1 range
   */
  function normalizeData(data: number[]): { normalized: number[]; min: number; max: number } {
    const min = Math.min(...data);
    const max = Math.max(...data);
    const range = max - min;
    const normalized = data.map((val) => (range === 0 ? 0 : (val - min) / range));
    return { normalized, min, max };
  }

  /**
   * Denormalize data back to original range
   */
  function denormalizeData(normalized: number[], min: number, max: number): number[] {
    const range = max - min;
    return normalized.map((val) => val * range + min);
  }

  /**
   * Prepare sequences for training
   */
  function createSequences(data: number[], sequenceLength: number): { xs: number[][]; ys: number[] } {
    const xs: number[][] = [];
    const ys: number[] = [];

    for (let i = 0; i < data.length - sequenceLength; i++) {
      xs.push(data.slice(i, i + sequenceLength));
      ys.push(data[i + sequenceLength]);
    }

    return { xs, ys };
  }

  /**
   * Build LSTM model for time series forecasting
   */
  function buildLSTMModel(sequenceLength: number, learningRate: number): tf.LayersModel {
    const model = tf.sequential();

    // LSTM layers
    model.add(
      tf.layers.lstm({
        units: 50,
        returnSequences: true,
        inputShape: [sequenceLength, 1],
      })
    );

    model.add(
      tf.layers.dropout({
        rate: 0.2,
      })
    );

    model.add(
      tf.layers.lstm({
        units: 50,
        returnSequences: false,
      })
    );

    model.add(
      tf.layers.dropout({
        rate: 0.2,
      })
    );

    // Output layer
    model.add(
      tf.layers.dense({
        units: 1,
      })
    );

    model.compile({
      optimizer: tf.train.adam(learningRate),
      loss: 'meanSquaredError',
      metrics: ['mae'],
    });

    return model;
  }

  /**
   * Build Simple RNN model for time series forecasting
   */
  function buildRNNModel(sequenceLength: number, learningRate: number): tf.LayersModel {
    const model = tf.sequential();

    // RNN layers
    model.add(
      tf.layers.simpleRNN({
        units: 32,
        returnSequences: true,
        inputShape: [sequenceLength, 1],
      })
    );

    model.add(
      tf.layers.dropout({
        rate: 0.2,
      })
    );

    model.add(
      tf.layers.simpleRNN({
        units: 32,
        returnSequences: false,
      })
    );

    model.add(
      tf.layers.dropout({
        rate: 0.2,
      })
    );

    // Output layer
    model.add(
      tf.layers.dense({
        units: 1,
      })
    );

    model.compile({
      optimizer: tf.train.adam(learningRate),
      loss: 'meanSquaredError',
      metrics: ['mae'],
    });

    return model;
  }

  /**
   * Build Dense Neural Network (MLP) model for time series forecasting
   */
  function buildDenseModel(sequenceLength: number, learningRate: number): tf.LayersModel {
    const model = tf.sequential();

    // Flatten input
    model.add(
      tf.layers.flatten({
        inputShape: [sequenceLength, 1],
      })
    );

    // Dense layers
    model.add(
      tf.layers.dense({
        units: 128,
        activation: 'relu',
      })
    );

    model.add(
      tf.layers.dropout({
        rate: 0.3,
      })
    );

    model.add(
      tf.layers.dense({
        units: 64,
        activation: 'relu',
      })
    );

    model.add(
      tf.layers.dropout({
        rate: 0.3,
      })
    );

    model.add(
      tf.layers.dense({
        units: 32,
        activation: 'relu',
      })
    );

    // Output layer
    model.add(
      tf.layers.dense({
        units: 1,
      })
    );

    model.compile({
      optimizer: tf.train.adam(learningRate),
      loss: 'meanSquaredError',
      metrics: ['mae'],
    });

    return model;
  }

  /**
   * Train and forecast using the selected algorithm
   */
  async function forecast(data: number[], options: ForecastingOptions): Promise<ForecastResult> {
    const startTime = Date.now();

    // Set default options
    const sequenceLength = options.sequenceLength || 10;
    const epochs = options.epochs || 50;
    const learningRate = options.learningRate || 0.001;
    const predictionSteps = options.predictionSteps || 7;
    const algorithm = options.algorithm;

    // Validate data
    if (data.length < sequenceLength + 5) {
      throw new Error(`Need at least ${sequenceLength + 5} data points for forecasting`);
    }

    isTraining.value = true;
    trainingProgress.value = 0;

    try {
      // Normalize data
      const { normalized, min, max } = normalizeData(data);

      // Create sequences
      const { xs, ys } = createSequences(normalized, sequenceLength);

      // Convert to tensors
      const xsTensor = tf.tensor3d(
        xs.map((seq) => seq.map((val) => [val])),
        [xs.length, sequenceLength, 1]
      );
      const ysTensor = tf.tensor2d(ys, [ys.length, 1]);

      // Build model based on algorithm
      let model: tf.LayersModel;
      switch (algorithm) {
        case 'lstm':
          model = buildLSTMModel(sequenceLength, learningRate);
          break;
        case 'rnn':
          model = buildRNNModel(sequenceLength, learningRate);
          break;
        case 'dense':
          model = buildDenseModel(sequenceLength, learningRate);
          break;
        default:
          throw new Error(`Unknown algorithm: ${algorithm}`);
      }

      currentModel.value = model;

      // Train model
      const history = await model.fit(xsTensor, ysTensor, {
        epochs,
        batchSize: 32,
        validationSplit: 0.1,
        shuffle: true,
        callbacks: {
          onEpochEnd: (epoch, logs) => {
            trainingProgress.value = ((epoch + 1) / epochs) * 100;
          },
        },
      });

      // Make predictions
      const predictions: number[] = [];
      let currentSequence = normalized.slice(-sequenceLength);

      for (let i = 0; i < predictionSteps; i++) {
        const inputTensor = tf.tensor3d([currentSequence.map((val) => [val])], [
          1,
          sequenceLength,
          1,
        ]);

        const predictionTensor = model.predict(inputTensor) as tf.Tensor;
        const predictionValue = (await predictionTensor.data())[0];

        predictions.push(predictionValue);
        currentSequence.push(predictionValue);
        currentSequence.shift();

        // Clean up tensors
        inputTensor.dispose();
        predictionTensor.dispose();
      }

      // Denormalize predictions
      const denormalizedPredictions = denormalizeData(predictions, min, max);

      // Clean up tensors
      xsTensor.dispose();
      ysTensor.dispose();

      const trainingTime = Date.now() - startTime;
      const range = max - min;
      const lastLoss = (history.history.loss as number[] | undefined)?.at(-1);
      const lastMae = (history.history.mae as number[] | undefined)?.at(-1);
      const mae = lastMae !== undefined ? (range === 0 ? 0 : lastMae * range) : undefined;
      const rmse =
        lastLoss !== undefined
          ? range === 0
            ? 0
            : Math.sqrt(lastLoss) * range
          : undefined;

      return {
        predictions: denormalizedPredictions,
        algorithm,
        trainingTime,
        mae,
        rmse,
      };
    } finally {
      isTraining.value = false;
      trainingProgress.value = 0;
    }
  }

  /**
   * Clean up resources
   */
  function dispose() {
    if (currentModel.value) {
      currentModel.value.dispose();
      currentModel.value = null;
    }
  }

  /**
   * Get current forecasting algorithm from API
   */
  async function getCurrentAlgorithm(): Promise<'lstm' | 'rnn' | 'dense'> {
    try {
      const response = await fetch('/api/settings/forecasting-algorithm');
      const data = await response.json();
      return data.algorithm || 'lstm';
    } catch (error) {
      console.error('Failed to fetch forecasting algorithm:', error);
      return 'lstm'; // Default fallback
    }
  }

  return {
    isTraining,
    trainingProgress,
    forecast,
    dispose,
    getCurrentAlgorithm,
  };
}
