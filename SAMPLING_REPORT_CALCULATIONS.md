# Sampling Report Calculations Summary

This document summarizes all the calculation formulas used in the Sampling Report.

## Basic Sample Calculations

### 1. Total Weight of Samples
- **Formula:** `Total Weight = Sum of all sample weights`
- **Unit:** grams
- **Calculation:** Direct aggregation of all sample weight values
- **Example:** If 33 samples weigh 63.0g each on average → 2080.0 grams

### 2. Total Number of Samples
- **Formula:** `Total Samples = Count of sample records`
- **Unit:** pieces (pcs)
- **Calculation:** Direct count of sample data records
- **Example:** 33 pcs

### 3. Average Body Weight (ABW)
- **Formula:** `ABW = Total Weight of Samples / Total Number of Samples`
- **Unit:** grams
- **Calculation:** `avgWeight = totalWeight / totalSamples`
- **Example:** `63.0 grams = 2080.0 grams / 33 pcs`
- **Rounded to:** 1 decimal place (nearest tenth)

## Stock Calculations

### 4. Total Stocks
- **Formula:** `Total Stocks = Number of Fingerlings (from Cage data)`
- **Unit:** pieces (pcs)
- **Calculation:** Retrieved from the Cage model's `number_of_fingerlings` field
- **Example:** 1000 pcs

### 5. Mortality to Date
- **Formula:** `Mortality = Sum of mortalities recorded`
- **Unit:** pieces (pcs)
- **Calculation:** Direct value from the `mortality` field in the Sampling model
- **Example:** 100 pcs

### 6. Present Stocks
- **Formula:** `Present Stocks = Total Stocks - Mortality to Date`
- **Unit:** pieces (pcs)
- **Calculation:** `presentStocks = totalStocks - mortality`
- **Example:** `900 pcs = 1000 pcs - 100 pcs`

## Biomass Calculations

### 7. Biomass
- **Formula:** `Biomass (kgs) = (Average Body Weight × Present Stocks) / 1000`
- **Unit:** kilograms (kgs)
- **Calculation:** `biomass = (avgWeight × presentStocks) / 1000`
- **Example:** `56.73 kgs = (63.0 grams × 900 pcs) / 1000`
- **Rounded to:** 2 decimal places

**Note:** Biomass uses Present Stocks (not Total Stocks) to account for mortality.

## Feeding Calculations

### 8. Feeding Rate
- **Formula:** `Feeding Rate = Percentage value (default: 3%)`
- **Unit:** percentage (%)
- **Calculation:** Default value (can be configured per sampling)
- **Example:** 3%

### 9. Daily Feed Ration
- **Formula:** `Daily Feed Ration (kgs) = (Total Stocks × Avg Body Weight × Feeding Rate) / 1000`
- **Unit:** kilograms (kgs)
- **Calculation:** `dailyFeedRation = (totalStocks × avgWeight × (feedingRate / 100)) / 1000`
- **Step-by-step:**
  1. Convert Feeding Rate from percentage to decimal: `3% = 0.03`
  2. Multiply: `Total Stocks × Avg Body Weight × Feeding Rate (decimal)`
  3. Divide by 1000 to convert grams to kilograms
- **Example:** 
  - `(1000 pcs × 63.0 grams × 0.03) / 1000`
  - `= (1890) / 1000`
  - `= 1.89 kgs`
- **Rounded to:** 2 decimal places

**Note:** Uses Total Stocks (not Present Stocks) for Daily Feed Ration calculation.

### 10. Feed Consumption
- **Formula:** `Feed Consumption = Actual feed consumed (manual input)`
- **Unit:** kilograms (kgs)
- **Calculation:** Direct input value (currently defaults to 0)
- **Example:** 0 kgs (or actual consumption if tracked)

## Historical Comparison Calculations

### 11. Previous ABW (Average Body Weight)
- **Formula:** `Previous ABW = Average Body Weight from previous sampling`
- **Unit:** grams
- **Calculation:** Same as current ABW but calculated from the most recent previous sampling for the same investor
- **Example:** 76.92 grams

### 12. Previous Biomass
- **Formula:** `Previous Biomass = Biomass from previous sampling`
- **Unit:** kilograms (kgs)
- **Calculation:** `prevBiomass = (prevABW × prevPresentStocks) / 1000`
- **Example:** 76.92 kgs

### 13. Total Weight Gained
- **Formula:** `Total Weight Gained = Current Biomass - Previous Biomass`
- **Unit:** kilograms (kgs)
- **Calculation:** `totalWtGained = biomass - prevBiomass`
- **Example:** `-20.19 kgs = 56.73 kgs - 76.92 kgs`
- **Note:** Can be negative if biomass decreased

### 14. Daily Weight Gained
- **Formula:** `Daily Weight Gained = Total Weight Gained / Days Between Samplings`
- **Unit:** grams per day (grams/day)
- **Calculation:** 
  - `daysBetween = Date of Current Sampling - Date of Previous Sampling`
  - `dailyWtGained = (totalWtGained × 1000) / daysBetween`
  - Converts kgs to grams by multiplying by 1000
- **Example:** 
  - If 2 days between samplings: `(-20.19 kgs × 1000) / 2 days = -10.1 grams/day`
- **Rounded to:** 1 decimal place

### 15. Feed Conversion Ratio (FCR)
- **Formula:** `FCR = Total Feed Consumption / Total Weight Gained`
- **Unit:** dimensionless ratio
- **Calculation:** `fcr = feedConsumption / totalWtGained`
- **Special Cases:**
  - If `feedConsumption = 0` → `FCR = 0`
  - If `totalWtGained = 0` → `FCR = undefined` (typically shown as 0 or N/A)
  - If `totalWtGained < 0` (weight loss) and `feedConsumption > 0` → Negative FCR (indicates weight loss despite feeding)
- **Example:** 
  - With 0 kg feed consumption and -20.19 kgs weight gained → `FCR = 0 / -20.19 = 0`

## Biomass Analysis Calculations

### Biomass per Fish
- **Formula:** `Biomass per Fish = Average Body Weight`
- **Unit:** grams (g)
- **Calculation:** `biomassPerFish = avgWeight`
- **Example:** `63.0g = 63.0 grams`
- **Rounded to:** 1 decimal place
- **Note:** This is the same as Average Body Weight per fish.

## Formula Dependencies

### Calculation Order:
1. **First:** Calculate sample statistics (Total Weight, Total Samples, ABW)
2. **Second:** Calculate stock information (Total Stocks, Mortality, Present Stocks)
3. **Third:** Calculate biomass (using ABW and Present Stocks)
4. **Fourth:** Calculate feeding metrics (using Total Stocks, ABW, and Feeding Rate)
5. **Fifth:** If previous sampling exists, calculate comparison metrics (Previous ABW, Previous Biomass, Weight Gained, Daily Weight Gained)
6. **Finally:** Calculate FCR if feed consumption data is available

## Rounding Rules

- **Weights (grams):** Rounded to nearest tenth (1 decimal place)
- **Biomass (kgs):** Rounded to 2 decimal places
- **Daily Feed Ration (kgs):** Rounded to 2 decimal places
- **Daily Weight Gained (grams/day):** Rounded to 1 decimal place
- **Percentages:** Rounded to 1 decimal place

## Key Notes

1. **Biomass** uses **Present Stocks** (accounts for mortality)
2. **Daily Feed Ration** uses **Total Stocks** (based on initial stocking)
3. All calculations account for mortality when calculating Present Stocks
4. Historical comparisons only work when a previous sampling exists for the same investor
5. Some values (like Feed Consumption) are manual inputs and not calculated automatically

