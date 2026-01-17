/**
 * Fish Detection Utilities
 * 
 * Helper functions for fish detection, measurement conversion, and validation
 */

/**
 * Validate camera permissions
 */
export async function checkCameraPermissions(): Promise<boolean> {
  try {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      console.error('Camera API not supported');
      return false;
    }
    
    const stream = await navigator.mediaDevices.getUserMedia({ video: true });
    stream.getTracks().forEach(track => track.stop());
    return true;
  } catch (err) {
    console.error('Camera permission denied:', err);
    return false;
  }
}

/**
 * Get available cameras
 */
export async function getAvailableCameras(): Promise<MediaDeviceInfo[]> {
  try {
    const devices = await navigator.mediaDevices.enumerateDevices();
    return devices.filter(device => device.kind === 'videoinput');
  } catch (err) {
    console.error('Failed to enumerate devices:', err);
    return [];
  }
}

/**
 * Convert centimeters to inches
 */
export function cmToInches(cm: number): number {
  return cm / 2.54;
}

/**
 * Convert inches to centimeters
 */
export function inchesToCm(inches: number): number {
  return inches * 2.54;
}

/**
 * Calculate fish growth stage from width
 */
export function calculateGrowthStage(widthCm: number): 'Starter' | 'Grower' | 'Finisher' {
  const widthInches = cmToInches(widthCm);
  
  if (widthInches <= 3.0) {
    return 'Starter';
  } else if (widthInches <= 6.0) {
    return 'Grower';
  } else {
    return 'Finisher';
  }
}

/**
 * Validate detection measurements
 */
export function validateMeasurements(
  lengthCm: number,
  widthCm: number,
  weight: number
): { valid: boolean; errors: string[] } {
  const errors: string[] = [];
  
  // Check for reasonable fish dimensions (Tilapia)
  if (lengthCm < 1 || lengthCm > 50) {
    errors.push('Length out of reasonable range (1-50 cm)');
  }
  
  if (widthCm < 0.5 || widthCm > 20) {
    errors.push('Width out of reasonable range (0.5-20 cm)');
  }
  
  if (weight < 1 || weight > 2000) {
    errors.push('Weight out of reasonable range (1-2000 g)');
  }
  
  // Check proportions
  if (widthCm > lengthCm) {
    errors.push('Width should not exceed length');
  }
  
  const lengthWidthRatio = lengthCm / widthCm;
  if (lengthWidthRatio < 1.5 || lengthWidthRatio > 5) {
    errors.push('Length-to-width ratio seems unusual');
  }
  
  return {
    valid: errors.length === 0,
    errors,
  };
}

/**
 * Calculate detection confidence score
 */
export function calculateConfidenceScore(
  boundingBoxConfidence: number,
  measurementValidation: { valid: boolean; errors: string[] }
): number {
  let confidence = boundingBoxConfidence;
  
  // Reduce confidence for invalid measurements
  if (!measurementValidation.valid) {
    confidence *= 0.5;
  }
  
  return Math.max(0, Math.min(1, confidence));
}

/**
 * Format timestamp for display
 */
export function formatDetectionTime(timestamp: string | Date): string {
  const date = typeof timestamp === 'string' ? new Date(timestamp) : timestamp;
  
  const now = new Date();
  const diffMs = now.getTime() - date.getTime();
  const diffSecs = Math.floor(diffMs / 1000);
  const diffMins = Math.floor(diffSecs / 60);
  const diffHours = Math.floor(diffMins / 60);
  
  if (diffSecs < 60) {
    return 'Just now';
  } else if (diffMins < 60) {
    return `${diffMins} min ago`;
  } else if (diffHours < 24) {
    return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`;
  } else {
    return date.toLocaleString();
  }
}

/**
 * Calculate average from array of detections
 */
export function calculateAverageDetection(detections: Array<{
  length: number;
  width: number;
  weight: number;
  confidence: number;
}>) {
  if (detections.length === 0) {
    return null;
  }
  
  // Filter out low confidence detections
  const validDetections = detections.filter(d => d.confidence > 0.5);
  
  if (validDetections.length === 0) {
    return null;
  }
  
  const sum = validDetections.reduce(
    (acc, d) => ({
      length: acc.length + d.length,
      width: acc.width + d.width,
      weight: acc.weight + d.weight,
      confidence: acc.confidence + d.confidence,
    }),
    { length: 0, width: 0, weight: 0, confidence: 0 }
  );
  
  const count = validDetections.length;
  
  return {
    length: sum.length / count,
    width: sum.width / count,
    weight: sum.weight / count,
    confidence: sum.confidence / count,
    sampleSize: count,
  };
}

/**
 * Export detection data to CSV
 */
export function exportDetectionsToCsv(detections: Array<{
  timestamp: string;
  length: number;
  width: number;
  weight: number;
  stage: string;
  confidence: number;
}>): string {
  const headers = ['Timestamp', 'Length (cm)', 'Width (cm)', 'Weight (g)', 'Stage', 'Confidence'];
  const rows = detections.map(d => [
    d.timestamp,
    d.length.toFixed(2),
    d.width.toFixed(2),
    d.weight.toFixed(2),
    d.stage,
    (d.confidence * 100).toFixed(1) + '%',
  ]);
  
  const csv = [
    headers.join(','),
    ...rows.map(row => row.join(',')),
  ].join('\n');
  
  return csv;
}

/**
 * Download CSV file
 */
export function downloadCsv(filename: string, csvContent: string): void {
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  const url = URL.createObjectURL(blob);
  
  link.setAttribute('href', url);
  link.setAttribute('download', filename);
  link.style.visibility = 'hidden';
  
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  
  URL.revokeObjectURL(url);
}

/**
 * Create bounding box overlay for canvas
 */
export function drawBoundingBox(
  ctx: CanvasRenderingContext2D,
  x: number,
  y: number,
  width: number,
  height: number,
  color: string,
  label: string
): void {
  // Draw box
  ctx.strokeStyle = color;
  ctx.lineWidth = 3;
  ctx.strokeRect(x, y, width, height);
  
  // Draw label background
  ctx.fillStyle = color;
  const labelHeight = 25;
  ctx.fillRect(x, y - labelHeight, width, labelHeight);
  
  // Draw label text
  ctx.fillStyle = '#FFFFFF';
  ctx.font = 'bold 14px Arial';
  ctx.textAlign = 'center';
  ctx.textBaseline = 'middle';
  ctx.fillText(label, x + width / 2, y - labelHeight / 2);
}

/**
 * Get color for growth stage
 */
export function getStageColor(stage: 'Starter' | 'Grower' | 'Finisher'): string {
  const colors = {
    Starter: '#00FF00',  // Green
    Grower: '#FFFF00',   // Yellow
    Finisher: '#FF0000', // Red
  };
  
  return colors[stage];
}

/**
 * Debounce function for performance optimization
 */
export function debounce<T extends (...args: any[]) => any>(
  func: T,
  wait: number
): (...args: Parameters<T>) => void {
  let timeout: ReturnType<typeof setTimeout> | null = null;
  
  return function executedFunction(...args: Parameters<T>) {
    const later = () => {
      timeout = null;
      func(...args);
    };
    
    if (timeout !== null) {
      clearTimeout(timeout);
    }
    
    timeout = setTimeout(later, wait);
  };
}

/**
 * Check if TensorFlow.js is available
 */
export async function checkTensorFlowAvailability(): Promise<{
  available: boolean;
  backend: string | null;
  version: string | null;
}> {
  try {
    const tf = await import('@tensorflow/tfjs');
    await tf.ready();
    
    return {
      available: true,
      backend: tf.getBackend(),
      version: tf.version.tfjs,
    };
  } catch (err) {
    console.error('TensorFlow.js not available:', err);
    return {
      available: false,
      backend: null,
      version: null,
    };
  }
}
