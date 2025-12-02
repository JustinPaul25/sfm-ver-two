<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import Card from '@/components/ui/card/Card.vue';
import Button from '@/components/ui/button/Button.vue';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

// Props from Inertia
const props = defineProps<{
  sampling?: any;
  cageEntry?: any;
  samples?: any[];
  totals?: any;
  history?: any[];
}>();

// Reactive data
const report = ref({
  date: props.sampling?.date || '',
  investor: props.sampling?.investor || '',
  cageNo: props.sampling?.cageNo || '',
  doc: props.sampling?.doc || '',
  samples: props.samples || [],
  totals: props.totals || {
    totalWeight: 0,
    totalSamples: 0,
    avgWeight: 0,
    totalStocks: 0,
    mortality: 0,
    presentStocks: 0,
    biomass: 0,
    feedingRate: 0,
    dailyFeedRation: 0,
    feedConsumption: 0,
    prevABW: 0,
    prevBiomass: 0,
    totalWtGained: 0,
    dailyWtGained: 0,
    fcr: 0,
  },
  history: props.history || [],
});

// Helper function to round to nearest tenth (1 decimal place)
const roundToTenth = (value: number | string | undefined): number => {
  if (value === undefined || value === null || value === '') return 0;
  const num = typeof value === 'string' ? parseFloat(value) : value;
  if (isNaN(num)) return 0;
  return Math.round(num * 10) / 10;
};

// Computed properties for sample data organization
// Organize samples sequentially across 3 columns
// Column 1: 1, 2, 3, 4, 5, 6, 7, 8, 9, 10
// Column 2: 11, 12, 13, 14, 15, 16, 17, 18, 19, 20
// Column 3: 21, 22, 23, 24, 25, 26, 27, 28, 29, 30
const organizedSamples = computed(() => {
  const samples = report.value.samples || [];
  // Sort samples by sample_no to ensure correct order
  const sortedSamples = [...samples].sort((a, b) => (a.sample_no || 0) - (b.sample_no || 0));
  const organized = [];
  
  // Calculate samples per column (divide total samples by 3)
  const samplesPerColumn = Math.ceil(sortedSamples.length / 3);
  
  // Distribute samples sequentially: first third in column 1, second third in column 2, last third in column 3
  for (let row = 0; row < samplesPerColumn; row++) {
    const col1Index = row;                    // Column 1: 0, 1, 2, 3, 4, 5, 6, 7, 8, 9 (samples 1-10)
    const col2Index = row + samplesPerColumn; // Column 2: 10, 11, 12, 13, 14, 15, 16, 17, 18, 19 (samples 11-20)
    const col3Index = row + samplesPerColumn * 2; // Column 3: 20, 21, 22, 23, 24, 25, 26, 27, 28, 29 (samples 21-30)
    
    const sample1 = sortedSamples[col1Index];
    const sample2 = sortedSamples[col2Index];
    const sample3 = sortedSamples[col3Index];
    
    const rowData = {
      no: sample1?.sample_no || '',
      weight: roundToTenth(sample1?.weight),
      sample1: sample1,
      no2: sample2?.sample_no || '',
      weight2: roundToTenth(sample2?.weight),
      sample2: sample2,
      no3: sample3?.sample_no || '',
      weight3: roundToTenth(sample3?.weight),
      sample3: sample3,
    };
    organized.push(rowData);
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

// Computed property for cage entry format
const cageEntryFormat = computed(() => {
  if (!props.cageEntry) return '';
  
  const parts = [
    `No. ${props.cageEntry.cageNo}`,
    `weight ${props.cageEntry.weight} kg`
  ];
  
  if (props.cageEntry.length) {
    parts.push(`length ${props.cageEntry.length} cm`);
  }
  
  if (props.cageEntry.width) {
    parts.push(`width ${props.cageEntry.width} cm`);
  }
  
  parts.push(`type: ${props.cageEntry.type || 'N/A'}`);
  
  return `(${parts.join(', ')})`;
});

// Computed property for tooltip data (average values from all samples)
const tooltipData = computed(() => {
  const samples = report.value.samples || [];
  if (samples.length === 0) {
    return {
      avgWeight: 0,
      avgLength: null,
      avgWidth: null,
      type: props.cageEntry?.type || 'N/A'
    };
  }
  
  const totalWeight = samples.reduce((sum: number, sample: any) => sum + (sample.weight || 0), 0);
  const avgWeight = samples.length > 0 ? totalWeight / samples.length : 0;
  
  const samplesWithLength = samples.filter((s: any) => s.length != null);
  const avgLength = samplesWithLength.length > 0 
    ? samplesWithLength.reduce((sum: number, sample: any) => sum + (sample.length || 0), 0) / samplesWithLength.length 
    : null;
  
  const samplesWithWidth = samples.filter((s: any) => s.width != null);
  const avgWidth = samplesWithWidth.length > 0 
    ? samplesWithWidth.reduce((sum: number, sample: any) => sum + (sample.width || 0), 0) / samplesWithWidth.length 
    : null;
  
  return {
    avgWeight: roundToTenth(avgWeight),
    avgLength: avgLength ? roundToTenth(avgLength) : null,
    avgWidth: avgWidth ? roundToTenth(avgWidth) : null,
    type: props.cageEntry?.type || 'N/A'
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
        </div>
        <div class="overflow-x-auto rounded-xl border border-sidebar-border/70 bg-white dark:bg-gray-900 mb-6">
          <div class="flex gap-2">
            <Button variant="outline" @click="printReport">🖨️ Print Report</Button>
            <Button variant="secondary" @click="exportToExcel">📊 Export to Excel</Button>
          </div>
        </div>
        <div class="overflow-x-auto rounded-xl border border-sidebar-border/70 bg-white dark:bg-gray-900 mb-6">
          <TooltipProvider>
            <table class="min-w-full text-xs md:text-sm">
              <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                  <th class="px-2 py-2">No.</th>
                  <th class="px-2 py-2">
                    <Tooltip>
                      <TooltipTrigger as-child>
                        <span class="cursor-help underline decoration-dotted">Weight (g)</span>
                      </TooltipTrigger>
                      <TooltipContent>
                        <div class="space-y-1 text-xs">
                          <div><strong>Weight:</strong> {{ tooltipData.avgWeight.toFixed(1) }} g (avg)</div>
                          <div v-if="tooltipData.avgLength"><strong>Length:</strong> {{ tooltipData.avgLength.toFixed(1) }} cm (avg)</div>
                          <div v-if="tooltipData.avgWidth"><strong>Width:</strong> {{ tooltipData.avgWidth.toFixed(1) }} cm (avg)</div>
                          <div><strong>Type:</strong> {{ tooltipData.type }}</div>
                        </div>
                      </TooltipContent>
                    </Tooltip>
                  </th>
                  <th class="px-2 py-2">No.</th>
                  <th class="px-2 py-2">
                    <Tooltip>
                      <TooltipTrigger as-child>
                        <span class="cursor-help underline decoration-dotted">Weight (g)</span>
                      </TooltipTrigger>
                      <TooltipContent>
                        <div class="space-y-1 text-xs">
                          <div><strong>Weight:</strong> {{ tooltipData.avgWeight.toFixed(1) }} g (avg)</div>
                          <div v-if="tooltipData.avgLength"><strong>Length:</strong> {{ tooltipData.avgLength.toFixed(1) }} cm (avg)</div>
                          <div v-if="tooltipData.avgWidth"><strong>Width:</strong> {{ tooltipData.avgWidth.toFixed(1) }} cm (avg)</div>
                          <div><strong>Type:</strong> {{ tooltipData.type }}</div>
                        </div>
                      </TooltipContent>
                    </Tooltip>
                  </th>
                  <th class="px-2 py-2">No.</th>
                  <th class="px-2 py-2">
                    <Tooltip>
                      <TooltipTrigger as-child>
                        <span class="cursor-help underline decoration-dotted">Weight (g)</span>
                      </TooltipTrigger>
                      <TooltipContent>
                        <div class="space-y-1 text-xs">
                          <div><strong>Weight:</strong> {{ tooltipData.avgWeight.toFixed(1) }} g (avg)</div>
                          <div v-if="tooltipData.avgLength"><strong>Length:</strong> {{ tooltipData.avgLength.toFixed(1) }} cm (avg)</div>
                          <div v-if="tooltipData.avgWidth"><strong>Width:</strong> {{ tooltipData.avgWidth.toFixed(1) }} cm (avg)</div>
                          <div><strong>Type:</strong> {{ tooltipData.type }}</div>
                        </div>
                      </TooltipContent>
                    </Tooltip>
                  </th>
                </tr>
              </thead>
            <tbody>
              <tr v-for="row in organizedSamples" :key="row.no">
                <td class="px-2 py-1">{{ row.no }}</td>
                <td class="px-2 py-1">
                  <Tooltip v-if="row.sample1">
                    <TooltipTrigger as-child>
                      <span class="cursor-help underline decoration-dotted">{{ row.weight ? row.weight.toFixed(1) : '' }}</span>
                    </TooltipTrigger>
                    <TooltipContent>
                      <div class="space-y-1 text-xs">
                        <div><strong>Weight:</strong> {{ row.weight.toFixed(1) }} g</div>
                        <div v-if="row.sample1.length"><strong>Length:</strong> {{ roundToTenth(row.sample1.length).toFixed(1) }} cm</div>
                        <div v-if="row.sample1.width"><strong>Width:</strong> {{ roundToTenth(row.sample1.width).toFixed(1) }} cm</div>
                        <div><strong>Type:</strong> {{ tooltipData.type }}</div>
                      </div>
                    </TooltipContent>
                  </Tooltip>
                  <span v-else>{{ row.weight ? row.weight.toFixed(1) : '' }}</span>
                </td>
                <td class="px-2 py-1">{{ row.no2 }}</td>
                <td class="px-2 py-1">
                  <Tooltip v-if="row.sample2">
                    <TooltipTrigger as-child>
                      <span class="cursor-help underline decoration-dotted">{{ row.weight2 ? row.weight2.toFixed(1) : '' }}</span>
                    </TooltipTrigger>
                    <TooltipContent>
                      <div class="space-y-1 text-xs">
                        <div><strong>Weight:</strong> {{ row.weight2.toFixed(1) }} g</div>
                        <div v-if="row.sample2.length"><strong>Length:</strong> {{ roundToTenth(row.sample2.length).toFixed(1) }} cm</div>
                        <div v-if="row.sample2.width"><strong>Width:</strong> {{ roundToTenth(row.sample2.width).toFixed(1) }} cm</div>
                        <div><strong>Type:</strong> {{ tooltipData.type }}</div>
                      </div>
                    </TooltipContent>
                  </Tooltip>
                  <span v-else>{{ row.weight2 ? row.weight2.toFixed(1) : '' }}</span>
                </td>
                <td class="px-2 py-1">{{ row.no3 }}</td>
                <td class="px-2 py-1">
                  <Tooltip v-if="row.sample3">
                    <TooltipTrigger as-child>
                      <span class="cursor-help underline decoration-dotted">{{ row.weight3 ? row.weight3.toFixed(1) : '' }}</span>
                    </TooltipTrigger>
                    <TooltipContent>
                      <div class="space-y-1 text-xs">
                        <div><strong>Weight:</strong> {{ row.weight3.toFixed(1) }} g</div>
                        <div v-if="row.sample3.length"><strong>Length:</strong> {{ roundToTenth(row.sample3.length).toFixed(1) }} cm</div>
                        <div v-if="row.sample3.width"><strong>Width:</strong> {{ roundToTenth(row.sample3.width).toFixed(1) }} cm</div>
                        <div><strong>Type:</strong> {{ tooltipData.type }}</div>
                      </div>
                    </TooltipContent>
                  </Tooltip>
                  <span v-else>{{ row.weight3 ? row.weight3.toFixed(1) : '' }}</span>
                </td>
              </tr>
            </tbody>
          </table>
          </TooltipProvider>
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