import * as tf from '@tensorflow/tfjs';
import { ref, Ref } from 'vue';
import {
  predictWeightFromDimensions,
  pixelsToNormalized,
  estimateWeightFromLength,
  loadWeightPredictionModel,
} from './useFishPredictionModel';

interface BoundingBox {
  x: number;
  y: number;
  width: number;
  height: number;
  confidence: number;
}

interface DetectionResult {
  boundingBox: BoundingBox;
  length: number; // in cm
  width: number; // in cm
  weight: number; // in grams
  stage: 'Starter' | 'Grower' | 'Finisher';
}

interface CalibrationConstants {
  realLengthCm: number;
  realWidthCm: number;
  imageWidthPx: number;
  imageLengthPx: number;
}

// Calibration constants from Python implementation
const DEFAULT_CALIBRATION: CalibrationConstants = {
  realLengthCm: 2.54,
  realWidthCm: 30.0,
  imageWidthPx: 1200,
  imageLengthPx: 127.13,
};

// Stage thresholds (in inches)
const STARTER_MAX_IN = 3.0;
const GROWER_MAX_IN = 6.0;

// Training data for weight prediction (from yolo_labels.json)
interface TrainingData {
  width: number;
  height: number;
  weight?: number;
}

export function useFishDetection() {
  const isModelLoaded = ref(false);
  const isDetecting = ref(false);
  const error: Ref<string | null> = ref(null);
  const detectionResults: Ref<DetectionResult[]> = ref([]);
  
  // Load training data from yolo_labels.json
  const loadTrainingData = async (): Promise<TrainingData[]> => {
    try {
      const response = await fetch('/models/yolo_labels.json');
      const data = await response.json();
      
      // Parse the training data
      const trainingData: TrainingData[] = [];
      
      for (const [key, value] of Object.entries(data)) {
        if (Array.isArray(value) && value.length > 0) {
          const detection = value[0];
          if (detection.width && detection.height) {
            trainingData.push({
              width: detection.width,
              height: detection.height,
            });
          }
        }
      }
      
      return trainingData;
    } catch (err) {
      console.error('Failed to load training data:', err);
      return [];
    }
  };

  // Simple linear regression to predict weight from dimensions
  const predictWeight = async (lengthCm: number, widthCm: number, imageWidth: number, imageHeight: number): Promise<number> => {
    try {
      // Method 1: Use trained model with normalized dimensions
      // Estimate pixel dimensions from real-world dimensions
      const pixelWidth = (widthCm / DEFAULT_CALIBRATION.realWidthCm) * DEFAULT_CALIBRATION.imageWidthPx;
      const pixelHeight = (lengthCm / DEFAULT_CALIBRATION.realLengthCm) * DEFAULT_CALIBRATION.imageLengthPx;
      
      const { normalizedWidth, normalizedHeight } = pixelsToNormalized(
        pixelWidth,
        pixelHeight,
        imageWidth || DEFAULT_CALIBRATION.imageWidthPx,
        imageHeight || 720
      );
      
      const modelWeight = await predictWeightFromDimensions(normalizedWidth, normalizedHeight);
      
      // Method 2: Use allometric formula as fallback/validation
      const formulaWeight = estimateWeightFromLength(lengthCm, widthCm);
      
      // Average both methods for better accuracy
      const averageWeight = (modelWeight + formulaWeight) / 2;
      
      return Math.max(0, averageWeight);
    } catch (err) {
      console.error('Weight prediction error:', err);
      // Fallback to formula-based prediction
      return estimateWeightFromLength(lengthCm, widthCm);
    }
  };

  // Alternative: Use API for weight prediction
  const predictWeightFromAPI = async (
    samplingId: number,
    doc: string,
    widthIn: number,
    lengthIn: number
  ): Promise<number> => {
    try {
      // Get API key from meta tag or environment
      const apiKeyMeta = document.querySelector('meta[name="api-key"]');
      const apiKey = apiKeyMeta?.getAttribute('content') || '';
      
      const response = await fetch(`/api/weight?key=${apiKey}`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          sampling_id: samplingId,
          doc: doc,
          width: widthIn,
          height: lengthIn,
        }),
      });
      
      const data = await response.json();
      
      if (data.data && data.data.weight) {
        return data.data.weight;
      }
      
      return 0;
    } catch (err) {
      console.error('Failed to predict weight from API:', err);
      return 0;
    }
  };

  // Convert pixel dimensions to real-world dimensions
  const pixelsToRealWorld = (
    pixelWidth: number,
    pixelLength: number,
    calibration: CalibrationConstants = DEFAULT_CALIBRATION
  ): { lengthCm: number; widthCm: number } => {
    const realWorldLengthCm = (pixelLength / calibration.imageLengthPx) * calibration.realLengthCm;
    const realWorldWidthCm = (pixelWidth / calibration.imageWidthPx) * calibration.realWidthCm;
    
    return {
      lengthCm: realWorldLengthCm,
      widthCm: realWorldWidthCm,
    };
  };

  // Determine fish growth stage
  const determineStage = (widthIn: number): 'Starter' | 'Grower' | 'Finisher' => {
    if (widthIn <= STARTER_MAX_IN) {
      return 'Starter';
    } else if (widthIn <= GROWER_MAX_IN) {
      return 'Grower';
    } else {
      return 'Finisher';
    }
  };

  // Perform object detection using TensorFlow.js
  const detectFish = async (
    imageElement: HTMLImageElement | HTMLVideoElement | HTMLCanvasElement,
    calibration: CalibrationConstants = DEFAULT_CALIBRATION
  ): Promise<DetectionResult[]> => {
    isDetecting.value = true;
    error.value = null;
    
    try {
      // Resolve dimensions: for video use videoWidth/videoHeight (element.width can be 0 before ready)
      const isVideo = imageElement instanceof HTMLVideoElement;
      const imageWidth = isVideo
        ? (imageElement as HTMLVideoElement).videoWidth
        : (imageElement as HTMLImageElement).width || (imageElement as HTMLCanvasElement).width;
      const imageHeight = isVideo
        ? (imageElement as HTMLVideoElement).videoHeight
        : (imageElement as HTMLImageElement).height || (imageElement as HTMLCanvasElement).height;
      const w = imageWidth || 1280;
      const h = imageHeight || 720;

      if (w === 0 || h === 0) {
        error.value = 'Video or image not ready. Wait for the feed to load and try again.';
        return [];
      }

      // Try to load weight prediction model (optional; detection still works with formula fallback)
      try {
        await loadWeightPredictionModel();
      } catch (modelErr) {
        console.warn('Weight model not available, using formula fallback:', modelErr);
      }
      
      // Convert image to tensor (only if we have valid dimensions)
      const tensor = tf.browser.fromPixels(imageElement);
      
      // Preprocess: Resize to YOLO input size (typically 640x640)
      const resized = tf.image.resizeBilinear(tensor, [640, 640]);
      const normalized = resized.div(255.0);
      const batched = normalized.expandDims(0);
      
      // For now, we'll use a simple detection approach
      // In production, you would load the actual YOLO model weights
      // Since we don't have the .pt file converted to TensorFlow.js format,
      // we'll simulate detection using color-based or contour-based detection
      
      const detections = await performSimpleDetection(imageElement, calibration, w, h);
      
      // Cleanup tensors
      tensor.dispose();
      resized.dispose();
      normalized.dispose();
      batched.dispose();
      
      detectionResults.value = detections;
      return detections;
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Detection failed';
      console.error('Fish detection error:', err);
      return [];
    } finally {
      isDetecting.value = false;
    }
  };

  // Simple detection using canvas analysis (fallback when YOLO model is not available)
  const performSimpleDetection = async (
    imageElement: HTMLImageElement | HTMLVideoElement | HTMLCanvasElement,
    calibration: CalibrationConstants,
    imageWidth: number,
    imageHeight: number
  ): Promise<DetectionResult[]> => {
    if (imageWidth <= 0 || imageHeight <= 0) return [];
    
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');
    if (!ctx) return [];
    
    // Use passed dimensions so video/canvas size is correct (element.width can be 0 for video)
    canvas.width = imageWidth;
    canvas.height = imageHeight;
    
    // Draw image
    ctx.drawImage(imageElement, 0, 0, imageWidth, imageHeight);
    
    // Get image data
    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
    const data = imageData.data;
    
    // Simple fish detection based on color (this is a placeholder)
    // In a real implementation, you would use the actual YOLO model
    const detections: DetectionResult[] = [];
    
    // For demonstration, detect dark regions (fish bodies are usually darker)
    const threshold = 120;
    const minDarkPixels = Math.min(500, (canvas.width * canvas.height) * 0.002); // at least 0.2% of image or 500 px
    let minX = canvas.width;
    let minY = canvas.height;
    let maxX = 0;
    let maxY = 0;
    let fishPixels = 0;
    
    for (let y = 0; y < canvas.height; y++) {
      for (let x = 0; x < canvas.width; x++) {
        const i = (y * canvas.width + x) * 4;
        const r = data[i];
        const g = data[i + 1];
        const b = data[i + 2];
        const brightness = (r + g + b) / 3;
        
        if (brightness < threshold) {
          fishPixels++;
          minX = Math.min(minX, x);
          minY = Math.min(minY, y);
          maxX = Math.max(maxX, x);
          maxY = Math.max(maxY, y);
        }
      }
    }
    
    // If we found a significant dark region, create a detection
    if (fishPixels > minDarkPixels) {
      const pixelWidth = maxX - minX;
      const pixelLength = maxY - minY;
      
      const { lengthCm, widthCm } = pixelsToRealWorld(pixelWidth, pixelLength, calibration);
      
      // Convert to inches for stage determination
      const lengthIn = lengthCm / 2.54;
      const widthIn = widthCm / 2.54;
      
      const weight = await predictWeight(lengthCm, widthCm, canvas.width, canvas.height);
      const stage = determineStage(widthIn);
      
      detections.push({
        boundingBox: {
          x: minX,
          y: minY,
          width: pixelWidth,
          height: pixelLength,
          confidence: 0.85,
        },
        length: lengthCm,
        width: widthCm,
        weight: weight,
        stage: stage,
      });
    }
    
    return detections;
  };

  // Draw detections on canvas
  const drawDetections = (
    canvas: HTMLCanvasElement,
    detections: DetectionResult[]
  ): void => {
    const ctx = canvas.getContext('2d');
    if (!ctx) return;
    
    detections.forEach((detection) => {
      const { boundingBox, stage, length, width, weight } = detection;
      
      // Set color based on stage
      let color = '';
      switch (stage) {
        case 'Starter':
          color = '#00FF00'; // Green
          break;
        case 'Grower':
          color = '#FFFF00'; // Yellow
          break;
        case 'Finisher':
          color = '#FF0000'; // Red
          break;
      }
      
      // Draw bounding box
      ctx.strokeStyle = color;
      ctx.lineWidth = 2;
      ctx.strokeRect(
        boundingBox.x,
        boundingBox.y,
        boundingBox.width,
        boundingBox.height
      );
      
      // Draw label
      ctx.fillStyle = color;
      ctx.font = '16px Arial';
      const label = `Tilapia - ${stage}`;
      ctx.fillText(label, boundingBox.x, boundingBox.y - 10);
      
      // Draw measurements
      ctx.font = '12px Arial';
      ctx.fillText(
        `L: ${length.toFixed(1)}cm W: ${width.toFixed(1)}cm`,
        boundingBox.x,
        boundingBox.y + boundingBox.height + 15
      );
      ctx.fillText(
        `Weight: ${weight.toFixed(1)}g`,
        boundingBox.x,
        boundingBox.y + boundingBox.height + 30
      );
    });
  };

  return {
    isModelLoaded,
    isDetecting,
    error,
    detectionResults,
    detectFish,
    drawDetections,
    predictWeight,
    predictWeightFromAPI,
    pixelsToRealWorld,
    determineStage,
  };
}
