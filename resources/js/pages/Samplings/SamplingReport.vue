<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import Card from '@/components/ui/card/Card.vue';
import Button from '@/components/ui/button/Button.vue';
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

// Props from Inertia
const props = defineProps<{
  sampling?: any;
  samples?: any[];
  totals?: any;
  history?: any[];
}>();

// Reactive data
const report = ref({
  date: props.sampling?.date || '22-Jan-25',
  investor: props.sampling?.investor || 'Saline Tilapia Demo cage',
  cageNo: props.sampling?.cageNo || '1',
  doc: props.sampling?.doc || '54',
  samples: props.samples || [],
  totals: props.totals || {
    totalWeight: 6752,
    totalSamples: 30,
    avgWeight: 225,
    totalStocks: 5000,
    mortality: 30,
    presentStocks: 5000,
    biomass: 1127,
    feedingRate: 3,
    dailyFeedRation: 32,
    feedConsumption: 1080,
    prevABW: 161,
    prevBiomass: 803,
    totalWtGained: 304,
    dailyWtGained: 1.1,
    fcr: 2.0,
  },
  history: props.history || [
    { date: '05-Sep-24', doc: 1, stocks: 5000, mortality: 0, present: 5000, abw: 8, wtInc: 8, doc2: 1, biomass: 40, fr: '8%', dfr: 40, feed: 17, totalGained: 0, fcr: 0 },
    { date: 'Oct 25, 2024', doc: 52, stocks: 5000, mortality: 12, present: 4988, abw: 48, wtInc: 40, doc2: 40, biomass: 239, fr: '5%', dfr: 12, feed: 330, totalGained: 199, fcr: 1.7 },
    { date: 'Nov 29, 2024', doc: 112, stocks: 5000, mortality: 30, present: 5000, abw: 161, wtInc: 41, doc2: 113, biomass: 803, fr: '4%', dfr: 32, feed: 375, totalGained: 304, fcr: 2.0 },
    { date: 'Jan 22, 2025', doc: 176, stocks: 5000, mortality: 30, present: 5000, abw: 225, wtInc: 64, doc2: 176, biomass: 1127, fr: '3%', dfr: 32, feed: 1080, totalGained: 324, fcr: 2.0 },
  ],
});

// Helper function to round to nearest tenth (1 decimal place)
const roundToTenth = (value: number | string | undefined): number => {
  if (value === undefined || value === null || value === '') return 0;
  const num = typeof value === 'string' ? parseFloat(value) : value;
  if (isNaN(num)) return 0;
  return Math.round(num * 10) / 10;
};

// Computed properties for sample data organization
const organizedSamples = computed(() => {
  if (!report.value.samples || report.value.samples.length === 0) {
    // Return mock data if no real samples
    return [
      { no: 1, weight: 258, no2: 11, weight2: 260, no3: 21, weight3: 206 },
      { no: 2, weight: 322, no2: 12, weight2: 204, no3: 22, weight3: 215 },
      { no: 3, weight: 230, no2: 13, weight2: 180, no3: 23, weight3: 231 },
      { no: 4, weight: 215, no2: 14, weight2: 172, no3: 24, weight3: 218 },
      { no: 5, weight: 215, no2: 15, weight2: 218, no3: 25, weight3: 207 },
      { no: 6, weight: 215, no2: 16, weight2: 247, no3: 26, weight3: 252 },
      { no: 7, weight: 232, no2: 17, weight2: 198, no3: 27, weight3: 261 },
      { no: 8, weight: 240, no2: 18, weight2: 200, no3: 28, weight3: 210 },
      { no: 9, weight: 260, no2: 19, weight2: 153, no3: 29, weight3: 146 },
      { no: 10, weight: 240, no2: 20, weight2: 153, no3: 30, weight3: 218 },
    ];
  }

  // Organize real samples in groups of 3
  const samples = report.value.samples;
  const organized = [];
  
  for (let i = 0; i < samples.length; i += 3) {
    const row = {
      no: samples[i]?.sample_no || '',
      weight: roundToTenth(samples[i]?.weight),
      no2: samples[i + 1]?.sample_no || '',
      weight2: roundToTenth(samples[i + 1]?.weight),
      no3: samples[i + 2]?.sample_no || '',
      weight3: roundToTenth(samples[i + 2]?.weight),
    };
    organized.push(row);
  }
  
  return organized;
});

// Computed property for biomass analysis
const biomassAnalysis = computed(() => {
  const totals = report.value.totals;
  // Biomass per Fish = Avg Body Weight (it's the same as average body weight per fish)
  const biomassPerFish = totals.avgWeight || 0;
  const biomassGrowthRate = totals.prevBiomass > 0 ? ((totals.biomass - totals.prevBiomass) / totals.prevBiomass) * 100 : 0;
  
  return {
    biomassPerFish: roundToTenth(biomassPerFish).toFixed(1),
    biomassGrowthRate: biomassGrowthRate.toFixed(1),
    biomassEfficiency: totals.avgWeight > 0 ? (totals.biomass / totals.avgWeight * 1000).toFixed(2) : '0.00'
  };
});

const breadcrumbs = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Samplings', href: '/samplings' },
  { title: 'Sampling Report', href: '/samplings/report' },
];

const printReport = () => {
  window.print();
};

const exportToExcel = () => {
  // Export the specific sampling report if available
  const samplingId = props.sampling?.id;
  if (samplingId) {
    window.open(route('samplings.export-report', samplingId), '_blank');
  } else {
    // Fallback to mock report
    window.open(route('samplings.export-report'), '_blank');
  }
};
</script>

<template>
  <Head title="Sampling Report" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col gap-6 p-4 max-w-6xl mx-auto">
      <Card class="p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-4">
          <div>
            <h2 class="text-2xl font-bold mb-1">Sampling Report</h2>
            <div class="text-sm text-muted-foreground">Date: <span class="font-medium">{{ report.date }}</span></div>
            <div class="text-sm text-muted-foreground">Investor: <span class="font-medium">{{ report.investor }}</span></div>
            <div class="text-sm text-muted-foreground">Cage No: <span class="font-medium">{{ report.cageNo }}</span></div>
            <div class="text-sm text-muted-foreground">DOC: <span class="font-medium">{{ report.doc }}</span></div>
          </div>
          <div class="flex gap-2">
            <Button variant="outline" @click="printReport">🖨️ Print Report</Button>
            <Button variant="secondary" @click="exportToExcel">📊 Export to Excel</Button>
          </div>
        </div>
        <div class="overflow-x-auto rounded-xl border border-sidebar-border/70 bg-white dark:bg-gray-900 mb-6">
          <table class="min-w-full text-xs md:text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800">
              <tr>
                <th class="px-2 py-2">No.</th>
                <th class="px-2 py-2">Weight (g)</th>
                <th class="px-2 py-2">No.</th>
                <th class="px-2 py-2">Weight (g)</th>
                <th class="px-2 py-2">No.</th>
                <th class="px-2 py-2">Weight (g)</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in organizedSamples" :key="row.no">
                <td class="px-2 py-1">{{ row.no }}</td>
                <td class="px-2 py-1">{{ row.weight ? row.weight.toFixed(1) : '' }}</td>
                <td class="px-2 py-1">{{ row.no2 }}</td>
                <td class="px-2 py-1">{{ row.weight2 ? row.weight2.toFixed(1) : '' }}</td>
                <td class="px-2 py-1">{{ row.no3 }}</td>
                <td class="px-2 py-1">{{ row.weight3 ? row.weight3.toFixed(1) : '' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <h3 class="font-semibold mb-2">Summary</h3>
            <ul class="text-sm space-y-1">
              <li>Total w.t. of samples: <span class="font-medium">{{ roundToTenth(report.totals.totalWeight).toFixed(1) }} grams</span></li>
              <li>Total # of samples: <span class="font-medium">{{ report.totals.totalSamples }} pcs</span></li>
              <li>Avg. Body Weight: <span class="font-medium">{{ roundToTenth(report.totals.avgWeight).toFixed(1) }} grams</span></li>
              <li>Total Stocks: <span class="font-medium">{{ report.totals.totalStocks }} pcs</span></li>
              <li>Mortality to date: <span class="font-medium">{{ report.totals.mortality }} pcs</span></li>
              <li>Present Stocks: <span class="font-medium">{{ report.totals.presentStocks }} pcs</span></li>
              <li>Biomass: <span class="font-medium">{{ report.totals.biomass }} kgs</span></li>
              <li>Feeding Rate: <span class="font-medium">{{ report.totals.feedingRate }}%</span></li>
              <li>Daily Feed Ration: <span class="font-medium">{{ report.totals.dailyFeedRation }} kgs</span></li>
              <li>Feed Consumption: <span class="font-medium">{{ report.totals.feedConsumption }} kgs</span></li>
              <li>Previous ABW: <span class="font-medium">{{ report.totals.prevABW }} grams</span></li>
              <li>Previous biomass: <span class="font-medium">{{ report.totals.prevBiomass }} kgs</span></li>
              <li>Total Wt. gained: <span class="font-medium">{{ report.totals.totalWtGained }} kgs</span></li>
              <li>Daily weight gained: <span class="font-medium">{{ report.totals.dailyWtGained }} grams/day</span></li>
              <li>Feed Conversion Ratio: <span class="font-medium">{{ report.totals.fcr }}</span></li>
            </ul>
          </div>
          <div class="flex flex-col items-center justify-center">
            <h3 class="font-semibold mb-2">Biomass Analysis</h3>
            <div class="w-full space-y-4">
              <!-- Biomass Trend -->
              <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                <h4 class="font-medium text-blue-900 dark:text-blue-100 mb-2">Current Biomass</h4>
                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ report.totals.biomass }} kg</div>
                <div class="text-sm text-blue-700 dark:text-blue-300">
                  {{ report.totals.presentStocks }} fish × {{ roundToTenth(report.totals.avgWeight).toFixed(1) }}g avg weight
                </div>
              </div>
              
              <!-- Biomass Growth -->
              <div v-if="report.totals.prevBiomass > 0" class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
                <h4 class="font-medium text-green-900 dark:text-green-100 mb-2">Biomass Growth</h4>
                <div class="text-lg font-bold text-green-600 dark:text-green-400">
                  +{{ report.totals.totalWtGained }} kg
                </div>
                <div class="text-sm text-green-700 dark:text-green-300">
                  {{ report.totals.dailyWtGained }} kg/day average
                </div>
              </div>
              
              <!-- Biomass Efficiency -->
              <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg">
                <h4 class="font-medium text-purple-900 dark:text-purple-100 mb-2">Biomass per Fish</h4>
                <div class="text-lg font-bold text-purple-600 dark:text-purple-400">
                  {{ biomassAnalysis.biomassPerFish }}g
                </div>
                <div class="text-sm text-purple-700 dark:text-purple-300">
                  Average weight per fish
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="mt-8">
          <h3 class="font-semibold mb-2">Historical Data</h3>
          <div class="overflow-x-auto rounded-xl border border-sidebar-border/70 bg-white dark:bg-gray-900">
            <table class="min-w-full text-xs md:text-sm">
              <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                  <th class="px-2 py-2">Date</th>
                  <th class="px-2 py-2">DOC (days)</th>
                  <th class="px-2 py-2">Total Stocks</th>
                  <th class="px-2 py-2">Mortality to date (pcs)</th>
                  <th class="px-2 py-2">Present Stocks (pcs)</th>
                  <th class="px-2 py-2">ABW (grams)</th>
                  <th class="px-2 py-2">Wt. Increment per day (grams)</th>
                  <th class="px-2 py-2">Biomass (kgs)</th>
                  <th class="px-2 py-2">Feeding Rate</th>
                  <th class="px-2 py-2">Daily Feed Ration (kgs)</th>
                  <th class="px-2 py-2">Feed Consumed (kgs)</th>
                  <th class="px-2 py-2">Total Wt. gained (kgs)</th>
                  <th class="px-2 py-2">FCR</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="row in report.history" :key="row.date">
                  <td class="px-2 py-1">{{ row.date }}</td>
                  <td class="px-2 py-1">{{ row.doc }}</td>
                  <td class="px-2 py-1">{{ row.stocks }}</td>
                  <td class="px-2 py-1">{{ row.mortality }}</td>
                  <td class="px-2 py-1">{{ row.present }}</td>
                  <td class="px-2 py-1">{{ row.abw }}</td>
                  <td class="px-2 py-1">{{ row.wtInc }}</td>
                  <td class="px-2 py-1">{{ row.biomass }}</td>
                  <td class="px-2 py-1">{{ row.fr }}</td>
                  <td class="px-2 py-1">{{ row.dfr }}</td>
                  <td class="px-2 py-1">{{ row.feed }}</td>
                  <td class="px-2 py-1">{{ row.totalGained }}</td>
                  <td class="px-2 py-1">{{ row.fcr }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </Card>
    </div>
  </AppLayout>
</template>

<style scoped>
@media print {
  .card {
    box-shadow: none !important;
    border: 1px solid #000 !important;
  }
  
  table {
    border-collapse: collapse !important;
  }
  
  th, td {
    border: 1px solid #000 !important;
  }
}
</style> 