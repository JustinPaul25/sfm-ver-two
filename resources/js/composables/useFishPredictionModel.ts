/**
 * Enhanced Fish Detection Composable with Machine Learning
 * 
 * This module provides fish detection and measurement prediction using TensorFlow.js.
 * It uses a regression model trained on the YOLO labels data to predict fish weight
 * from length and width measurements.
 */

import * as tf from '@tensorflow/tfjs';
import { ref, Ref } from 'vue';

interface TrainingDataPoint {
  width: number;
  height: number;
  x_center: number;
  y_center: number;
}

interface PredictionModel {
  model: tf.LayersModel | null;
  isLoaded: boolean;
}

// Singleton model instance
let predictionModel: PredictionModel = {
  model: null,
  isLoaded: false,
};

/**
 * Load and preprocess training data from yolo_labels.json
 */
export async function loadTrainingData(): Promise<TrainingDataPoint[]> {
  try {
    const response = await fetch('/models/yolo_labels.json');
    const data = await response.json();
    
    const trainingData: TrainingDataPoint[] = [];
    
    for (const [key, value] of Object.entries(data)) {
      if (Array.isArray(value) && value.length > 0) {
        const detection = value[0] as any;
        if (detection.width && detection.height) {
          trainingData.push({
            width: detection.width,
            height: detection.height,
            x_center: detection.x_center || 0.5,
            y_center: detection.y_center || 0.5,
          });
        }
      }
    }
    
    console.log(`Loaded ${trainingData.length} training samples`);
    return trainingData;
  } catch (err) {
    console.error('Failed to load training data:', err);
    return [];
  }
}

/**
 * Create and train a neural network model for weight prediction
 */
export async function createWeightPredictionModel(): Promise<tf.LayersModel> {
  // Load training data
  const trainingData = await loadTrainingData();
  
  if (trainingData.length === 0) {
    throw new Error('No training data available');
  }
  
  // Prepare training tensors
  // Input: [width, height] in normalized coordinates
  // Output: estimated weight in grams
  const inputs = trainingData.map(d => [d.width, d.height]);
  const inputTensor = tf.tensor2d(inputs);
  
  // For training, we'll use a formula-based weight as ground truth
  // Weight estimation formula for Tilapia: W = a * L^b * W^c
  const outputs = trainingData.map(d => {
    // Convert normalized dimensions to estimated real-world size
    // Assuming average image dimensions and fish size
    const estimatedLengthCm = d.height * 50; // Normalized height to cm
    const estimatedWidthCm = d.width * 30;   // Normalized width to cm
    
    // Weight formula (calibrated for Tilapia)
    const weight = 0.015 * Math.pow(estimatedLengthCm, 2.5) * Math.pow(estimatedWidthCm, 1.8);
    return [weight];
  });
  const outputTensor = tf.tensor2d(outputs);
  
  // Create neural network model
  const model = tf.sequential({
    layers: [
      tf.layers.dense({
        inputShape: [2],
        units: 16,
        activation: 'relu',
        kernelInitializer: 'heNormal',
      }),
      tf.layers.dropout({ rate: 0.2 }),
      tf.layers.dense({
        units: 32,
        activation: 'relu',
        kernelInitializer: 'heNormal',
      }),
      tf.layers.dropout({ rate: 0.2 }),
      tf.layers.dense({
        units: 16,
        activation: 'relu',
        kernelInitializer: 'heNormal',
      }),
      tf.layers.dense({
        units: 1,
        activation: 'linear', // Linear for regression
      }),
    ],
  });
  
  // Compile model
  model.compile({
    optimizer: tf.train.adam(0.001),
    loss: 'meanSquaredError',
    metrics: ['mae'],
  });
  
  console.log('Training weight prediction model...');
  
  // Train model
  await model.fit(inputTensor, outputTensor, {
    epochs: 50,
    batchSize: 32,
    validationSplit: 0.2,
    shuffle: true,
    callbacks: {
      onEpochEnd: (epoch, logs) => {
        if (epoch % 10 === 0) {
          console.log(`Epoch ${epoch}: loss = ${logs?.loss.toFixed(4)}, mae = ${logs?.mae.toFixed(4)}`);
        }
      },
    },
  });
  
  console.log('Model training complete!');
  
  // Cleanup tensors
  inputTensor.dispose();
  outputTensor.dispose();
  
  return model;
}

/**
 * Load or create the weight prediction model
 */
export async function loadWeightPredictionModel(): Promise<tf.LayersModel> {
  if (predictionModel.model && predictionModel.isLoaded) {
    return predictionModel.model;
  }
  
  try {
    // Try to load saved model
    const model = await tf.loadLayersModel('/models/weight_prediction/model.json');
    predictionModel.model = model;
    predictionModel.isLoaded = true;
    console.log('Loaded pre-trained weight prediction model');
    return model;
  } catch (err) {
    // If no saved model, create and train a new one
    console.log('Creating new weight prediction model...');
    const model = await createWeightPredictionModel();
    predictionModel.model = model;
    predictionModel.isLoaded = true;
    
    // Optionally save the model
    try {
      await model.save('localstorage://weight_prediction_model');
      console.log('Model saved to local storage');
    } catch (saveErr) {
      console.warn('Failed to save model:', saveErr);
    }
    
    return model;
  }
}

/**
 * Predict weight from normalized dimensions using the trained model
 */
export async function predictWeightFromDimensions(
  normalizedWidth: number,
  normalizedHeight: number
): Promise<number> {
  const model = await loadWeightPredictionModel();
  
  const input = tf.tensor2d([[normalizedWidth, normalizedHeight]]);
  const prediction = model.predict(input) as tf.Tensor;
  const weight = (await prediction.data())[0];
  
  // Cleanup
  input.dispose();
  prediction.dispose();
  
  return Math.max(0, weight); // Ensure non-negative
}

/**
 * Convert pixel dimensions to normalized dimensions
 */
export function pixelsToNormalized(
  pixelWidth: number,
  pixelHeight: number,
  imageWidth: number,
  imageHeight: number
): { normalizedWidth: number; normalizedHeight: number } {
  return {
    normalizedWidth: pixelWidth / imageWidth,
    normalizedHeight: pixelHeight / imageHeight,
  };
}

/**
 * Fish weight estimation using allometric formula
 * W = a * L^b (where W is weight in grams, L is length in cm)
 * For Tilapia: a ≈ 0.02, b ≈ 3.0
 */
export function estimateWeightFromLength(lengthCm: number, widthCm: number): number {
  // Enhanced formula considering both length and width
  // W = a * L^b * (W/L)^c
  const lengthWidthRatio = widthCm / lengthCm;
  
  // Tilapia-specific coefficients
  const a = 0.018;
  const b = 2.9;
  const c = 1.5;
  
  const weight = a * Math.pow(lengthCm, b) * Math.pow(lengthWidthRatio, c);
  
  return Math.max(0, weight);
}

/**
 * Save model to file (for downloading)
 */
export async function saveModelToFile(): Promise<void> {
  if (!predictionModel.model) {
    throw new Error('No model to save');
  }
  
  await predictionModel.model.save('downloads://weight_prediction_model');
  console.log('Model saved to downloads');
}

/**
 * Get model summary
 */
export function getModelSummary(): string {
  if (!predictionModel.model) {
    return 'No model loaded';
  }
  
  const summaryLines: string[] = [];
  predictionModel.model.layers.forEach((layer, index) => {
    summaryLines.push(
      `Layer ${index}: ${layer.name} (${layer.getClassName()}) - ` +
      `Output shape: ${layer.outputShape}`
    );
  });
  
  return summaryLines.join('\n');
}
