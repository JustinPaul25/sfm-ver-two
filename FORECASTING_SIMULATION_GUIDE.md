# Forecasting Simulation Guide

This guide explains how to use the **Forecasting Simulation** page — a browser-based tool for testing time-series forecasting algorithms (the JavaScript alternative to the Jupyter notebook).

---

## Table of Contents

1. [Accessing the Simulation](#accessing-the-simulation)
2. [How It Works](#how-it-works)
3. [Step-by-Step Usage](#step-by-step-usage)
4. [Parameters Explained](#parameters-explained)
5. [Algorithms Overview](#algorithms-overview)
6. [Understanding the Results](#understanding-the-results)
7. [Tips & Best Practices](#tips--best-practices)
8. [Troubleshooting](#troubleshooting)

---

## Accessing the Simulation

- **Who can access**: Admin users only  
- **Where**: Sidebar → **Forecasting Simulation** (or go to `/forecasting/simulation`)  
- **Requirements**: Modern browser with WebGL support (Chrome, Firefox, Edge, Safari)

---

## How It Works

The simulation runs machine learning models in your browser using TensorFlow.js. It:

1. Takes your **historical data** (e.g., fish weights over time)
2. **Normalizes** it to a 0–1 range
3. Builds **sequences** of past values to predict the next value
4. **Trains** a neural network on those sequences
5. **Predicts** future values step-by-step

All processing happens in the browser — no data is sent to a server.

---

## Step-by-Step Usage

### 1. Choose the Algorithm

- **Use system-configured algorithm** (checked by default)  
  - Uses the algorithm set in System Settings  
  - Ensures consistency with production forecasting  

- **Override** (uncheck the box)  
  - Lets you pick LSTM, RNN, or Dense for experiments  

### 2. Enter Sample Data

Enter comma-separated numbers in the **Sample Data** field, for example:

```
10,12,15,18,22,25,30,35,40,45,50,55,60,65,70
```

This might represent:

- Fish weight (g) over 15 days  
- Feed consumption over weeks  
- Any numeric time series  

**Rules**:

- Comma-separated numbers only  
- At least `sequenceLength + 5` values (e.g., 12+ with sequence length 7)  
- More data usually improves predictions (30+ points recommended)  

### 3. Set Parameters

| Parameter        | Default | Range | Description                                              |
|------------------|---------|-------|----------------------------------------------------------|
| Sequence Length  | 7       | 3–20  | How many past values the model uses to predict the next |
| Training Epochs  | 50      | 10–200| Number of training iterations (more = slower, often better) |
| Prediction Steps | 7       | 1–30  | How many future values to predict                       |

### 4. Run the Forecast

Click **Run Forecast**. You’ll see:

- **Training Progress** bar during training  
- **Forecast Results** when done (algorithm used and training time)  
- **Results table** (Step vs. Predicted Value)  
- **Visual Preview** bar chart of the predictions  

### 5. Interpret the Results

- **Step**: Forecast step (1 = next, 2 = next after that, etc.)  
- **Predicted Value**: Predicted number at that step  
- **Visual Preview**: Bars scaled from min to max; taller bars = higher values  

---

## Parameters Explained

### Sequence Length

- The model uses the last N values to predict the next one.  
- Example: If sequence length = 7, it uses `[60, 62, 64, 65, 66, 67, 68]` to predict 69.  
- Shorter (3–5): Faster, good for simple patterns.  
- Longer (10–20): Better for complex or seasonal patterns.  

### Training Epochs

- One epoch = one full pass over the training data.  
- Fewer epochs (10–30): Faster, may underfit.  
- More epochs (50–100): Slower, often better accuracy.  

### Prediction Steps

- How many future values to predict in sequence.  
- Each step uses the previous predictions as input for the next.  
- Example: With 7 steps, you get predictions for days 1–7 ahead.  

---

## Algorithms Overview

| Algorithm | Best For                         | Speed    | Accuracy |
|----------|-----------------------------------|----------|----------|
| **LSTM** | Long-term trends, complex patterns | Slowest  | Highest  |
| **RNN**  | General use, balanced trade-off    | Medium   | Good     |
| **Dense**| Fast experiments, simple patterns  | Fastest  | Good     |

**Suggested choices**:

- **Long-term growth** → LSTM  
- **General forecasting** → RNN  
- **Quick tests** → Dense  

---

## Understanding the Results

### Forecast Results Section

- **Algorithm**: Which model was used.  
- **Training Time**: How long training took (seconds).  

### Results Table

- Each row is one prediction step.  
- Values are in the same units as your input (e.g., grams).  

### Visual Preview

- Bar chart of predicted values.  
- Bars use a min–max scale so differences are visible.  
- Hover over bars to see exact values.  

---

## Tips & Best Practices

1. **Data quality**  
   Use real historical data where possible; avoid random or unrealistic values.

2. **Data length**  
   Aim for at least 30 points. With fewer than 15, results can be unreliable.

3. **Epochs vs. speed**  
   Start with 30–50 epochs. Increase if results look poor; decrease if training is too slow.

4. **Compare algorithms**  
   Run the same data with LSTM, RNN, and Dense to compare speed and accuracy.

5. **Meaning of your data**  
   Interpret predictions in context (e.g., fish weight, feed usage) and check if they make sense.

---

## Troubleshooting

### "Need at least X data points"

- You need at least `sequenceLength + 5` values.  
- Increase data points or reduce sequence length.  

### "Invalid data format"

- Ensure numbers only, separated by commas.  
- No extra spaces or non-numeric characters.  

### Training seems stuck

- Training can take 10–30 seconds for LSTM.  
- Try Dense for faster runs.  

### Predictions look wrong

- Add more historical data.  
- Try a different algorithm.  
- Increase epochs (e.g., 80–100).  

### Slow or laggy

- Enable WebGL in your browser for GPU acceleration.  
- Use Dense instead of LSTM.  
- Reduce epochs or sequence length.  

---

## Related Documentation

- **FORECASTING_IMPLEMENTATION.md** — Technical implementation details  
- **System Settings** — Configure the default forecasting algorithm  
- **notebooks/forecasting_simulation.ipynb** — Python/Jupyter version of the same logic  
