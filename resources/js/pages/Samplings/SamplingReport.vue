<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import Card from '@/components/ui/card/Card.vue';
import Button from '@/components/ui/button/Button.vue';
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { router } from '@inertiajs/vue3';
import FishDetectionCamera from '@/components/FishDetectionCamera.vue';
import Dialog from '@/components/ui/dialog/Dialog.vue';
import DialogContent from '@/components/ui/dialog/DialogContent.vue';
import DialogHeader from '@/components/ui/dialog/DialogHeader.vue';
import DialogTitle from '@/components/ui/dialog/DialogTitle.vue';
import DialogTrigger from '@/components/ui/dialog/DialogTrigger.vue';

// Props from Inertia
const props = defineProps<{
  sampling?: any;
  cageEntry?: any;
  samples?: any[];
  totals?: any;
  history?: any[];
}>();

// Helper function to format timestamp in a human-readable way (reuse from existing function)
const formatSamplingTimestamp = (timestamp: string | null | undefined): string => {
  return formatTimestamp(timestamp);
};

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
    feedConsumption: 0,
    prevABW: 0,
    prevBiomass: 0,
    totalWtGained: 0,
    dailyWtGained: 0,
    fcr: 0,
  },
  history: props.history || [],
});

// Fish detection state
const showDetectionDialog = ref(false);
const cameraRef = ref<InstanceType<typeof FishDetectionCamera> | null>(null);
const aiPredictions = ref<{
  length: number;
  width: number;
  weight: number;
  stage: string;
  timestamp: string;
}[]>([]);

// Helper function to round to nearest tenth (1 decimal place)
const roundToTenth = (value: number | string | undefined): number => {
  if (value === undefined || value === null || value === '') return 0;
  const num = typeof value === 'string' ? parseFloat(value) : value;
  if (isNaN(num)) return 0;
  return Math.round(num * 10) / 10;
};

// Helper function to format timestamp in a human-readable way
const formatTimestamp = (timestamp: string | null | undefined): string => {
  if (!timestamp) return '-';
  
  try {
    const date = new Date(timestamp);
    if (isNaN(date.getTime())) return '-';
    
    // Format as "Jan 15, 2024 at 2:30 PM"
    const options: Intl.DateTimeFormatOptions = {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: 'numeric',
      minute: '2-digit',
      hour12: true
    };
    
    return date.toLocaleString('en-US', options);
  } catch (error) {
    return '-';
  }
};

// Computed properties for sample data organization
// Organize samples in a single column (1-5)
const organizedSamples = computed(() => {
  const samples = report.value.samples || [];
  // Sort samples by sample_no to ensure correct order
  const sortedSamples = [...samples].sort((a, b) => (a.sample_no || 0) - (b.sample_no || 0));
  
  return sortedSamples.map(sample => ({
    no: sample.sample_no || '',
    weight: roundToTenth(sample.weight),
    length: sample.length ? roundToTenth(sample.length) : null,
    width: sample.width ? roundToTenth(sample.width) : null,
    type: tooltipData.value.type,
    // Only show tested_at if the sample has actual weight data (has been tested)
    testedAt: sample.weight ? formatTimestamp(sample.created_at) : null,
  }));
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

const showDeleteDialog = ref(false);
const isDeleting = ref(false);

const deleteSamplingReport = async () => {
  const samplingId = props.sampling?.id;
  if (!samplingId) {
    alert('No sampling report to delete');
    return;
  }

  isDeleting.value = true;
  
  try {
    await axios.delete(route('samplings.destroy', samplingId));
    
    // Redirect to samplings list after successful deletion
    router.visit(route('samplings.index'), {
      onSuccess: () => {
        // You can show a success message here if you have a toast notification system
        console.log('Sampling report deleted successfully');
      }
    });
  } catch (error: any) {
    console.error('Error deleting sampling:', error);
    alert(error.response?.data?.message || 'Failed to delete sampling report');
    isDeleting.value = false;
    showDeleteDialog.value = false;
  }
};

// Handle detection from camera
const handleDetection = (detection: any) => {
  console.log('Fish detected:', detection);
  
  // Add to AI predictions
  aiPredictions.value.unshift({
    length: detection.length,
    width: detection.width,
    weight: detection.weight,
    stage: detection.stage,
    timestamp: detection.timestamp,
  });
  
  // Keep only last 5 predictions
  if (aiPredictions.value.length > 5) {
    aiPredictions.value.pop();
  }
  
  // Update the report samples if needed
  // You can add logic here to save the detection to the database
};

const handleDetectionError = (error: string) => {
  console.error('Detection error:', error);
  // You can show a toast notification here
};

const openDetectionCamera = () => {
  showDetectionDialog.value = true;
};

// Computed average from AI predictions
const aiAverages = computed(() => {
  if (aiPredictions.value.length === 0) {
    return null;
  }
  
  const total = aiPredictions.value.reduce(
    (acc, pred) => ({
      length: acc.length + pred.length,
      width: acc.width + pred.width,
      weight: acc.weight + pred.weight,
    }),
    { length: 0, width: 0, weight: 0 }
  );
  
  const count = aiPredictions.value.length;
  
  return {
    length: (total.length / count).toFixed(1),
    width: (total.width / count).toFixed(1),
    weight: (total.weight / count).toFixed(1),
    count,
  };
});
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
            <div v-if="props.sampling?.created_at" class="text-sm text-muted-foreground mt-2">
              <span class="font-medium">Data Entered:</span> {{ formatSamplingTimestamp(props.sampling.created_at) }}
            </div>
            <div v-if="props.sampling?.updated_at && props.sampling?.updated_at !== props.sampling?.created_at" class="text-sm text-muted-foreground">
              <span class="font-medium">Last Updated:</span> {{ formatSamplingTimestamp(props.sampling.updated_at) }}
            </div>
          </div>
        </div>
        <div class="overflow-x-auto rounded-xl border border-sidebar-border/70 bg-white dark:bg-gray-900 mb-6">
          <div class="flex gap-2 flex-wrap">
            <Button variant="outline" @click="printReport">🖨️ Print Report</Button>
            <Button variant="secondary" @click="exportToExcel">📊 Export to Excel</Button>
            <Dialog v-model:open="showDetectionDialog">
              <DialogTrigger as-child>
                <Button variant="default" @click="openDetectionCamera">🤖 AI Fish Detection</Button>
              </DialogTrigger>
              <DialogContent class="max-w-5xl max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                  <DialogTitle>AI-Powered Fish Detection</DialogTitle>
                </DialogHeader>
                <FishDetectionCamera
                  ref="cameraRef"
                  :sampling-id="props.sampling?.id"
                  :doc="report.doc"
                  :auto-detect="true"
                  @detection="handleDetection"
                  @error="handleDetectionError"
                />
              </DialogContent>
            </Dialog>
            
            <!-- Delete Button with Confirmation Dialog -->
            <Dialog v-model:open="showDeleteDialog">
              <DialogTrigger as-child>
                <Button variant="destructive" class="ml-auto">🗑️ Delete Report</Button>
              </DialogTrigger>
              <DialogContent>
                <DialogHeader>
                  <DialogTitle>Delete Sampling Report</DialogTitle>
                </DialogHeader>
                <div class="py-4">
                  <p class="text-sm text-muted-foreground mb-4">
                    Are you sure you want to delete this sampling report? This action cannot be undone.
                  </p>
                  <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-3 mb-4">
                    <p class="text-sm text-yellow-800 dark:text-yellow-200">
                      <strong>Warning:</strong> This will permanently delete:
                    </p>
                    <ul class="text-sm text-yellow-700 dark:text-yellow-300 list-disc list-inside mt-2">
                      <li>All sample data ({{ report.totals.totalSamples }} samples)</li>
                      <li>Historical records for DOC {{ report.doc }}</li>
                      <li>Report dated {{ report.date }}</li>
                    </ul>
                  </div>
                </div>
                <div class="flex justify-end gap-2">
                  <Button variant="outline" @click="showDeleteDialog = false" :disabled="isDeleting">
                    Cancel
                  </Button>
                  <Button variant="destructive" @click="deleteSamplingReport" :disabled="isDeleting">
                    {{ isDeleting ? 'Deleting...' : 'Delete Report' }}
                  </Button>
                </div>
              </DialogContent>
            </Dialog>
          </div>
        </div>
        <!-- Average Data Block -->
        <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
          <h3 class="font-semibold mb-3 text-blue-900 dark:text-blue-100">Average Data</h3>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div>
              <div class="text-sm text-blue-700 dark:text-blue-300">Weight</div>
              <div class="text-lg font-bold text-blue-900 dark:text-blue-100">{{ tooltipData.avgWeight.toFixed(1) }} g</div>
            </div>
            <div v-if="tooltipData.avgLength">
              <div class="text-sm text-blue-700 dark:text-blue-300">Length</div>
              <div class="text-lg font-bold text-blue-900 dark:text-blue-100">{{ tooltipData.avgLength.toFixed(1) }} cm</div>
            </div>
            <div v-if="tooltipData.avgWidth">
              <div class="text-sm text-blue-700 dark:text-blue-300">Width</div>
              <div class="text-lg font-bold text-blue-900 dark:text-blue-100">{{ tooltipData.avgWidth.toFixed(1) }} cm</div>
            </div>
            <div>
              <div class="text-sm text-blue-700 dark:text-blue-300">Type</div>
              <div class="text-lg font-bold text-blue-900 dark:text-blue-100">{{ tooltipData.type }}</div>
            </div>
          </div>
        </div>
        
        <!-- AI Predictions Block -->
        <div v-if="aiAverages" class="mb-6 p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg border border-purple-200 dark:border-purple-800">
          <div class="flex items-center justify-between mb-3">
            <h3 class="font-semibold text-purple-900 dark:text-purple-100">🤖 AI-Predicted Averages</h3>
            <span class="text-xs text-purple-700 dark:text-purple-300">Based on {{ aiAverages.count }} detection(s)</span>
          </div>
          <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
            <div>
              <div class="text-sm text-purple-700 dark:text-purple-300">Avg Length</div>
              <div class="text-lg font-bold text-purple-900 dark:text-purple-100">{{ aiAverages.length }} cm</div>
            </div>
            <div>
              <div class="text-sm text-purple-700 dark:text-purple-300">Avg Width</div>
              <div class="text-lg font-bold text-purple-900 dark:text-purple-100">{{ aiAverages.width }} cm</div>
            </div>
            <div>
              <div class="text-sm text-purple-700 dark:text-purple-300">Avg Weight</div>
              <div class="text-lg font-bold text-purple-900 dark:text-purple-100">{{ aiAverages.weight }} g</div>
            </div>
          </div>
        </div>
        
        <!-- Samples Table -->
        <div class="overflow-x-auto rounded-xl border border-sidebar-border/70 bg-white dark:bg-gray-900 mb-6">
          <table class="min-w-full text-xs md:text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800">
              <tr>
                <th class="px-4 py-2 text-left">No.</th>
                <th class="px-4 py-2 text-left">Weight (g)</th>
                <th class="px-4 py-2 text-left">Length (cm)</th>
                <th class="px-4 py-2 text-left">Width (cm)</th>
                <th class="px-4 py-2 text-left">Type</th>
                <th class="px-4 py-2 text-left">Tested At</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in organizedSamples" :key="row.no" class="border-b border-gray-200 dark:border-gray-700">
                <td class="px-4 py-2">{{ row.no }}</td>
                <td class="px-4 py-2">{{ row.weight ? row.weight.toFixed(1) : '' }}</td>
                <td class="px-4 py-2">{{ row.length ? row.length.toFixed(1) : '-' }}</td>
                <td class="px-4 py-2">{{ row.width ? row.width.toFixed(1) : '-' }}</td>
                <td class="px-4 py-2">{{ row.type }}</td>
                <td class="px-4 py-2">{{ row.testedAt || '-' }}</td>
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