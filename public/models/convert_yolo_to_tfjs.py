"""
YOLO Model Converter for TensorFlow.js

This script helps convert the YOLOv8 PyTorch model (best_SMF.pt) to TensorFlow.js format
for use in the browser-based fish detection system.

Requirements:
- ultralytics
- onnx
- tf2onnx
- tensorflowjs

Installation:
pip install ultralytics onnx tf2onnx tensorflowjs

Usage:
python convert_yolo_to_tfjs.py
"""

import os
import sys
from pathlib import Path

try:
    from ultralytics import YOLO
    import tensorflowjs as tfjs
    import tensorflow as tf
except ImportError as e:
    print(f"Error: Missing required package: {e}")
    print("\nPlease install required packages:")
    print("pip install ultralytics tensorflowjs tensorflow")
    sys.exit(1)


def convert_yolo_to_tfjs(
    pt_model_path: str = "best_SMF.pt",
    output_dir: str = "public/models/yolo_tfjs",
    image_size: int = 640
):
    """
    Convert YOLOv8 PyTorch model to TensorFlow.js format.
    
    Args:
        pt_model_path: Path to the .pt model file
        output_dir: Directory to save the converted model
        image_size: Input image size for the model
    """
    
    print("=" * 60)
    print("YOLO to TensorFlow.js Converter")
    print("=" * 60)
    
    # Check if input file exists
    if not os.path.exists(pt_model_path):
        print(f"Error: Model file not found: {pt_model_path}")
        return False
    
    # Create output directory
    os.makedirs(output_dir, exist_ok=True)
    
    try:
        # Step 1: Load YOLO model
        print(f"\n[1/4] Loading YOLO model from {pt_model_path}...")
        model = YOLO(pt_model_path)
        print("✓ Model loaded successfully")
        
        # Step 2: Export to TensorFlow SavedModel format
        print(f"\n[2/4] Exporting to TensorFlow SavedModel format...")
        saved_model_path = os.path.join(output_dir, "saved_model")
        
        # Export using Ultralytics built-in export
        model.export(
            format="saved_model",
            imgsz=image_size,
            optimize=True,
        )
        print(f"✓ SavedModel exported to {saved_model_path}")
        
        # Step 3: Convert SavedModel to TensorFlow.js
        print(f"\n[3/4] Converting to TensorFlow.js format...")
        
        # Use tensorflowjs converter
        import subprocess
        
        tfjs_output = os.path.join(output_dir, "web_model")
        
        cmd = [
            "tensorflowjs_converter",
            "--input_format=tf_saved_model",
            "--output_format=tfjs_graph_model",
            "--signature_name=serving_default",
            "--saved_model_tags=serve",
            saved_model_path,
            tfjs_output
        ]
        
        result = subprocess.run(cmd, capture_output=True, text=True)
        
        if result.returncode != 0:
            print(f"Error during conversion: {result.stderr}")
            return False
        
        print(f"✓ TensorFlow.js model saved to {tfjs_output}")
        
        # Step 4: Create model metadata
        print(f"\n[4/4] Creating model metadata...")
        
        metadata = {
            "modelFormat": "graph-model",
            "generatedBy": "YOLO to TensorFlow.js Converter",
            "convertedFrom": pt_model_path,
            "imageSize": image_size,
            "classes": ["tilapia"],
            "stageThresholds": {
                "starter": 3.0,
                "grower": 6.0
            },
            "calibration": {
                "realLengthCm": 2.54,
                "realWidthCm": 30.0,
                "imageWidthPx": 1200,
                "imageLengthPx": 127.13
            }
        }
        
        import json
        
        metadata_path = os.path.join(tfjs_output, "metadata.json")
        with open(metadata_path, "w") as f:
            json.dump(metadata, f, indent=2)
        
        print(f"✓ Metadata saved to {metadata_path}")
        
        # Success!
        print("\n" + "=" * 60)
        print("✓ Conversion completed successfully!")
        print("=" * 60)
        print(f"\nModel files are ready at: {tfjs_output}")
        print("\nTo use in your application:")
        print("1. Copy the contents to your public/models/ directory")
        print("2. Update the model path in useFishDetection.ts")
        print("3. Load the model using tf.loadGraphModel()")
        
        return True
        
    except Exception as e:
        print(f"\n✗ Error during conversion: {e}")
        import traceback
        traceback.print_exc()
        return False


def create_example_usage():
    """Create an example JavaScript file showing how to use the converted model."""
    
    example_code = """
// Example: Loading and using the converted YOLO model

import * as tf from '@tensorflow/tfjs';

async function loadYOLOModel() {
  const model = await tf.loadGraphModel('/models/yolo_tfjs/web_model/model.json');
  console.log('Model loaded successfully');
  return model;
}

async function detectFish(model, imageElement) {
  // Preprocess image
  const tensor = tf.browser.fromPixels(imageElement);
  const resized = tf.image.resizeBilinear(tensor, [640, 640]);
  const normalized = resized.div(255.0);
  const batched = normalized.expandDims(0);
  
  // Run inference
  const predictions = await model.predict(batched);
  
  // Process predictions (YOLO output format)
  // predictions will contain: [boxes, scores, classes]
  
  // Cleanup
  tensor.dispose();
  resized.dispose();
  normalized.dispose();
  batched.dispose();
  
  return predictions;
}

// Usage
const model = await loadYOLOModel();
const videoElement = document.querySelector('video');
const detections = await detectFish(model, videoElement);
"""
    
    with open("example_yolo_usage.js", "w") as f:
        f.write(example_code)
    
    print("\n✓ Example usage file created: example_yolo_usage.js")


if __name__ == "__main__":
    import argparse
    
    parser = argparse.ArgumentParser(description="Convert YOLO model to TensorFlow.js")
    parser.add_argument(
        "--model",
        type=str,
        default="best_SMF.pt",
        help="Path to the YOLO .pt model file"
    )
    parser.add_argument(
        "--output",
        type=str,
        default="public/models/yolo_tfjs",
        help="Output directory for the converted model"
    )
    parser.add_argument(
        "--size",
        type=int,
        default=640,
        help="Input image size (default: 640)"
    )
    
    args = parser.parse_args()
    
    success = convert_yolo_to_tfjs(
        pt_model_path=args.model,
        output_dir=args.output,
        image_size=args.size
    )
    
    if success:
        create_example_usage()
        sys.exit(0)
    else:
        sys.exit(1)
