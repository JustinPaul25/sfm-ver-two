## Smart Fish Morphometrics – App Presentation

### What this app is
- **Purpose**: A web application to manage fish cage operations and sampling data, helping users track growth performance, feed usage, and investor-related metrics over time.
- **Users**: Farm owners, managers, or technicians who record samplings and monitor cage performance, plus admins who manage configuration data (investors, feed types, cages).
- **Tech stack**: Laravel (API + backend), Inertia.js, Vue 3 + TypeScript (frontend), Chart.js (`vue-chartjs`) for analytics visualizations, Tailwind-style utility classes for UI.

### Core features
- **Dashboard**
  - High-level summary cards for investors, cages, feed types, samplings, and samples.
  - **Sampling Trends chart** with AI-powered predictions of future sampling counts and average weights.
  - Weight statistics tiles (average and max weight) for the selected period.
  - Time filters (Today, This Week, Last 30 Days, This Month, Custom range).

- **Investors**
  - CRUD management of investors (name, address, phone).
  - Paginated search with filters.
  - **Soft delete** support: deleted investors are archived, and their samplings are hidden from operational views/reports.
  - Seeder populates realistic investor demo data, including an archived investor for testing.

- **Cages**
  - Management of cages linked to investors and feed types.
  - Records **number of fingerlings** and associated feed type per cage.
  - Integration with **feeding schedules** and **feed consumption** records for each cage.
  - Safe delete: deleting a cage cleans up related feed consumptions, feeding schedules, and samplings (with their samples) to keep data consistent.

- **Samplings & Samples**
  - CRUD operations for samplings, linked to investors and cages.
  - One-click generation of 30 sample weights per sampling for realistic demo/testing.
  - Automatic calculations for average body weight (ABW), biomass, and growth metrics used in reports and dashboard analytics.
  - Deleting a sampling safely removes its associated samples.

- **Reports**
  - Overall report view with filters for investor, date range, and cage.
  - Summary statistics for biomass, growth, and sample distributions.
  - Export to Excel using PhpSpreadsheet for printable/sharable reports.

### Data & analytics highlights
- **Analytics pipeline** (via `DashboardController`):
  - Aggregates counts and weight statistics over a selected date range.
  - Computes growth metrics comparing current vs previous periods (sampling count and ABW growth).
  - Produces daily sampling trends (`sampling_trends`) for charting.
  - Filters out data for soft-deleted investors so analytics reflect only active operations.

- **AI predictions for trends**:
  - `SamplingTrendsChart.vue` lazily loads TensorFlow.js.
  - Trains lightweight models on historical counts and average weights.
  - Predicts the next 7 days and overlays dashed prediction lines when enabled by the user.

### Safety & data integrity
- **Foreign key–aware deletes**:
  - Deleting a **sampling** first deletes related `samples` to avoid DB constraint errors.
  - Deleting a **cage** cascades manually through feed consumptions, feeding schedules, and samplings (including their samples).
- **Soft deletes** for investors prevent accidental loss of historical relationships while hiding archived investors from active views and analytics.

### How to demo the app
- **1. Log in using seeded accounts**
  - See `DatabaseSeeder` console output after `php artisan db:seed` for ready-made user credentials (e.g., Admin/Test/Manager).
- **2. Start at the Dashboard**
  - Show summary cards and the sampling trends chart.
  - Toggle time ranges and AI predictions to demonstrate analytics capabilities.
- **3. Manage investors, cages, and samplings**
  - Create a new investor and cage, then add a sampling and auto-generate samples.
  - Delete an investor to show soft delete behavior and how their samplings disappear from lists.
  - Delete a cage and confirm related records are cleaned up without errors.
- **4. Explore reports**
  - Open the overall report, apply filters, and export to Excel to showcase reporting and data export.

This document can be used as a quick introduction when presenting the application to stakeholders, new team members, or testers. It summarizes what the app does, how data flows, and the main scenarios to demonstrate. 


