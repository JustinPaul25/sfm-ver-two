# Historical Data Table Column Explanations

This document provides a comprehensive explanation for each column found in the "Historical Data" table, which is used in aquaculture and livestock farming to track performance metrics of a specific cage or stock over time.

---

## Table Overview

The Historical Data table displays chronological records of sampling measurements, allowing you to track the growth, feeding, and health metrics of your stock over time. Each row represents a single sampling event with all associated measurements.

---

## Column Descriptions

### 1. **Date**
- **Description**: The specific calendar date on which the sampling was conducted or the measurement was taken.
- **Format**: YYYY-MM-DD (e.g., `2025-09-06`)
- **Purpose**: Provides chronological context for all other measurements in the row.
- **Example**: `2025-09-03`, `2025-09-19`

---

### 2. **DOC (days)**
- **Description**: Stands for "Days of Culture". This column indicates the number of days elapsed since the start of the culture period for the current stock in the cage.
- **Unit**: Days
- **Purpose**: Helps track the age of the stock and compare performance at different stages of growth.
- **Calculation**: Days from initial stocking date to the sampling date.
- **Example**: `1`, `52`, `112`, `176`

---

### 3. **Total Stocks**
- **Description**: The initial or total number of fingerlings/fish (or other animals) that were stocked in the cage at the beginning of the culture cycle.
- **Unit**: Pieces (pcs)
- **Purpose**: Represents the baseline population count. This value typically remains constant unless there's a restocking event.
- **Note**: This is the original stocking number, not the current population.
- **Example**: `5000`, `1000`

---

### 4. **Mortality to date (pcs)**
- **Description**: The cumulative number of mortalities (deaths) recorded among the stock from the beginning of the culture period up to the `Date` of the current record.
- **Unit**: Pieces (pcs)
- **Purpose**: Tracks losses over time, which is crucial for calculating present stocks and understanding stock health.
- **Calculation**: Sum of all deaths recorded from start date to current sampling date.
- **Example**: `0`, `12`, `30`

---

### 5. **Present Stocks (pcs)**
- **Description**: The estimated number of living individuals remaining in the cage on the record `Date`.
- **Unit**: Pieces (pcs)
- **Purpose**: Shows the current population size, accounting for mortality.
- **Calculation**: `Present Stocks = Total Stocks - Mortality to date`
- **Example**: `5000`, `4988`, `4970`
- **Note**: This value is used in biomass calculations to account for actual living stock.

---

### 6. **ABW (grams)**
- **Description**: Stands for "Average Body Weight". This represents the average weight of an individual animal (fish, fingerling, etc.) in the cage on the record `Date`.
- **Unit**: Grams
- **Purpose**: Indicates the average size/growth of individual animals, which is a key performance indicator.
- **Calculation**: Calculated from sample measurements: `ABW = Total Weight of Samples / Number of Samples`
- **Example**: `8.0`, `48.0`, `161.0`, `225.0`
- **Note**: This is determined by sampling a representative portion of the stock and averaging their weights.

---

### 7. **Wt. Increment per day (grams)**
- **Description**: "Weight Increment per day" measures the average daily weight gain (or loss) of an individual animal in grams since the last recorded sampling date.
- **Unit**: Grams per day (grams/day)
- **Purpose**: Shows growth rate, which helps assess feeding efficiency and overall health.
- **Calculation**: `Wt. Increment = (Current ABW - Previous ABW) / Days Between Samplings`
- **Example**: `8.0`, `40.0`, `41.0`, `64.0`
- **Special Cases**:
  - **0**: Can indicate no sampling was conducted, negligible change, or it's the first record with no prior data for comparison.
  - **Negative values**: Indicate that the average weight has decreased since the last measurement, which could be due to stress, disease, insufficient feeding, or measurement errors.

---

### 8. **Biomass (kgs)**
- **Description**: The total estimated living weight of all animals in the cage on the record `Date`.
- **Unit**: Kilograms (kgs)
- **Purpose**: Represents the total productive weight of the stock, which is crucial for feeding calculations and economic planning.
- **Calculation**: `Biomass = (Present Stocks × ABW) / 1000`
  - Multiplies present stocks by average body weight, then converts from grams to kilograms by dividing by 1000.
- **Example**: `40.0`, `239.0`, `803.0`, `1127.0`
- **Note**: Uses Present Stocks (not Total Stocks) to account for mortality, giving an accurate representation of actual living biomass.

---

### 9. **Feeding Rate**
- **Description**: The percentage of the total `Biomass` that should be fed to the animals on a given day. This rate can vary based on the age of the animals, environmental conditions, and desired growth targets.
- **Unit**: Percentage (%)
- **Purpose**: Determines how much feed should be provided relative to the current biomass.
- **Calculation**: Typically a configured value (default: 3%), but can vary based on:
  - Age/stage of the stock
  - Environmental conditions
  - Growth targets
  - Feed type
- **Example**: `3%`, `4%`, `5%`, `8%`
- **Note**: This is a planned/calculated rate, not necessarily what was actually fed.

---

### 10. **Daily Feed Ration (kgs)**
- **Description**: The calculated or planned total amount of feed (in kilograms) that *should* be given to the cage on that specific day, based on the `Biomass` and `Feeding Rate`.
- **Unit**: Kilograms (kgs)
- **Purpose**: Provides a target feed amount for daily feeding operations.
- **Calculation**: `Daily Feed Ration = (Total Stocks × ABW × Feeding Rate) / 1000`
  - Note: Uses Total Stocks (not Present Stocks) for this calculation.
- **Example**: `7.64`, `8.34`, `8.47`, `32.0`
- **Note**: This is the *planned* feed amount. The actual amount consumed may differ (see "Feed Consumed").

---

### 11. **Feed Consumed (kgs)**
- **Description**: The actual amount of feed that was consumed by the animals in the cage during the period between the previous sampling and the current sampling date.
- **Unit**: Kilograms (kgs)
- **Purpose**: Tracks actual feed usage, which is essential for calculating Feed Conversion Ratio (FCR) and cost analysis.
- **Calculation**: Sum of all `CageFeedConsumption` records for the cage between consecutive sampling dates.
- **Example**: `17.0`, `330.0`, `375.0`, `1080.0`
- **Special Cases**:
  - **0**: A value of '0' typically means:
    - No feed consumption records were entered for that period
    - Feed consumption data was not recorded
    - The system defaults to 0 when no data is available
  - **Note**: If `Daily Feed Ration` has a value but `Feed Consumed` is 0, it suggests feed consumption was not measured or recorded, not that no feed was given.

---

### 12. **Total Wt. gained (kgs)**
- **Description**: The total weight gained (or lost) by the entire stock in the cage since the previous sampling or measurement.
- **Unit**: Kilograms (kgs)
- **Purpose**: Shows the net growth of the entire stock, which is essential for performance evaluation and FCR calculation.
- **Calculation**: `Total Wt. gained = Current Biomass - Previous Biomass`
- **Example**: `0`, `199.0`, `304.0`, `324.0`
- **Special Cases**:
  - **0**: Can indicate:
    - No change in total biomass was detected
    - No previous sampling exists for comparison (first record)
    - The change was negligible and rounded to zero
  - **Negative values**: Mean the total biomass of the stock has decreased, which could indicate:
    - High mortality without corresponding growth
    - Weight loss due to stress or disease
    - Measurement errors

---

### 13. **FCR (Feed Conversion Ratio)**
- **Description**: Stands for "Feed Conversion Ratio". It's a measure of the efficiency with which feed is converted into biomass. A lower FCR indicates better efficiency (less feed needed per unit of weight gain).
- **Unit**: Unitless ratio
- **Purpose**: Key performance indicator for feed efficiency and cost management. Lower values are better.
- **Calculation**: `FCR = Feed Consumed (kgs) / Total Wt. gained (kgs)`
- **Example**: `0`, `1.7`, `2.0`
- **Interpretation**:
  - **FCR < 1.5**: Excellent efficiency
  - **FCR 1.5 - 2.0**: Good efficiency
  - **FCR 2.0 - 2.5**: Average efficiency
  - **FCR > 2.5**: Poor efficiency (needs attention)
- **Special Cases**:
  - **0**: An FCR of '0' typically indicates:
    - `Total Wt. gained (kgs)` is '0' or negative, making FCR undefined
    - `Feed Consumed (kgs)` is also '0', resulting in `0/0` which cannot be calculated
    - The system displays 0 as a placeholder for "not calculable" rather than an actual FCR value
  - **Negative FCR**: Can occur when weight is lost despite feeding, indicating poor conditions or health issues

---

## Calculation Dependencies

### Calculation Order:
1. **First**: Calculate sample statistics (Total Weight, Total Samples, ABW)
2. **Second**: Calculate stock information (Total Stocks, Mortality, Present Stocks)
3. **Third**: Calculate biomass (using ABW and Present Stocks)
4. **Fourth**: Calculate feeding metrics (using Total Stocks, ABW, and Feeding Rate)
5. **Fifth**: If previous sampling exists, calculate comparison metrics (Weight Gained, Daily Weight Gained)
6. **Finally**: Calculate FCR if both feed consumption and weight gained data are available

---

## Key Relationships

- **Present Stocks** = Total Stocks - Mortality to date
- **Biomass** = (Present Stocks × ABW) / 1000
- **Daily Feed Ration** = (Total Stocks × ABW × Feeding Rate) / 1000
- **Total Wt. gained** = Current Biomass - Previous Biomass
- **Wt. Increment per day** = (Current ABW - Previous ABW) / Days Between
- **FCR** = Feed Consumed / Total Wt. gained

---

## Important Notes

1. **Biomass** uses **Present Stocks** (accounts for mortality) to give accurate living weight.
2. **Daily Feed Ration** uses **Total Stocks** (based on initial stocking) for planning purposes.
3. All calculations account for mortality when calculating Present Stocks.
4. Historical comparisons only work when a previous sampling exists for comparison.
5. Some values (like Feed Consumed) require manual data entry from feed consumption records.
6. Zero values in calculated fields (Feed Consumed, Total Wt. gained, FCR) typically indicate missing data rather than actual zero values.

---

## Troubleshooting Zero Values

If you see zeros in certain columns:

- **Feed Consumed = 0**: Check if feed consumption records have been entered in the "Daily Feed Consumption" section for the cage.
- **Total Wt. gained = 0**: This is normal for the first sampling record. For subsequent records, ensure previous sampling data exists.
- **FCR = 0**: This occurs when either Feed Consumed or Total Wt. gained is 0, making the calculation impossible. Enter feed consumption data and ensure multiple samplings exist for comparison.

---

## Best Practices

1. **Regular Sampling**: Conduct samplings at consistent intervals to ensure accurate growth tracking.
2. **Record Feed Consumption**: Enter daily feed consumption records to enable FCR calculations.
3. **Monitor Trends**: Look for patterns in ABW, biomass, and FCR over time to identify issues early.
4. **Compare Periods**: Use the historical data to compare performance across different time periods.
5. **Validate Data**: Ensure mortality and sampling data are accurately recorded for reliable calculations.

---

*Last Updated: Based on current system implementation*

