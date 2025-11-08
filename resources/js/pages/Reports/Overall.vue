<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import Card from '@/components/ui/card/Card.vue';
import Button from '@/components/ui/button/Button.vue';
import Input from '@/components/ui/input/Input.vue';
import Label from '@/components/ui/label/Label.vue';
import { ref, computed, onMounted, watch } from 'vue';

interface Props {
  samplings: any[];
  summary: {
    total_samplings: number;
    total_samples: number;
    avg_weight: number;
    total_weight_kg: number;
    min_weight: number;
    max_weight: number;
    total_investors: number;
    total_cages: number;
  };
  investors: any[];
  filters: {
    investor_id?: string;
    date_from?: string;
    date_to?: string;
    cage_no?: string;
  };
}

const props = defineProps<Props>();

const breadcrumbs = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Reports', href: '/reports' },
  { title: 'Overall Reports', href: '/reports/overall' },
];

// Filter state
const filters = ref({
  investor_id: props.filters.investor_id || '',
  date_from: props.filters.date_from || '',
  date_to: props.filters.date_to || '',
  cage_no: props.filters.cage_no || '',
});

// Apply filters
const applyFilters = () => {
  const params: Record<string, string> = {};
  Object.entries(filters.value).forEach(([key, value]) => {
    if (value) params[key] = value;
  });
  
  router.get('/reports/overall', params, {
    preserveState: true,
    preserveScroll: true,
  });
};

// Clear filters
const clearFilters = () => {
  filters.value = {
    investor_id: '',
    date_from: '',
    date_to: '',
    cage_no: '',
  };
  applyFilters();
};

// Export to Excel
const exportToExcel = () => {
  const params = new URLSearchParams();
  Object.entries(filters.value).forEach(([key, value]) => {
    if (value) params.append(key, value);
  });
  
  window.open(`/reports/export-excel?${params.toString()}`, '_blank');
};

// Print functionality
const printReport = () => {
  window.print();
};

// Computed properties for better data display
const formattedSamplings = computed(() => {
  return props.samplings.map(sampling => {
    const samples = sampling.samples || [];
    const sampleCount = samples.length;
    const totalWeight = samples.reduce((sum: number, sample: any) => sum + (sample.weight || 0), 0);
    const avgWeight = sampleCount > 0 ? totalWeight / sampleCount : 0;
    const minWeight = sampleCount > 0 ? Math.min(...samples.map((s: any) => s.weight || 0)) : 0;
    const maxWeight = sampleCount > 0 ? Math.max(...samples.map((s: any) => s.weight || 0)) : 0;

    return {
      ...sampling,
      sampleCount,
      totalWeight,
      avgWeight: Math.round(avgWeight * 100) / 100,
      minWeight,
      maxWeight,
      totalWeightKg: Math.round((totalWeight / 1000) * 100) / 100,
    };
  });
});

const summaryStats = computed(() => [
  { label: 'Total Samplings', value: props.summary.total_samplings, unit: '', color: 'text-blue-600' },
  { label: 'Total Samples', value: props.summary.total_samples, unit: 'pcs', color: 'text-green-600' },
  { label: 'Average Weight', value: Math.round(props.summary.avg_weight * 100) / 100, unit: 'g', color: 'text-purple-600' },
  { label: 'Total Weight', value: Math.round(props.summary.total_weight_kg * 100) / 100, unit: 'kg', color: 'text-orange-600' },
  { label: 'Min Weight', value: props.summary.min_weight, unit: 'g', color: 'text-red-600' },
  { label: 'Max Weight', value: props.summary.max_weight, unit: 'g', color: 'text-indigo-600' },
  { label: 'Total Investors', value: props.summary.total_investors, unit: '', color: 'text-teal-600' },
  { label: 'Total Cages', value: props.summary.total_cages, unit: '', color: 'text-pink-600' },
]);

// Watch for filter changes and auto-apply after a delay
let filterTimeout: number;
watch(filters, () => {
  clearTimeout(filterTimeout);
  filterTimeout = setTimeout(() => {
    applyFilters();
  }, 500);
}, { deep: true });
</script>

<template>
  <Head title="Overall Reports" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col gap-6 p-4 max-w-7xl mx-auto">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
          <h1 class="text-3xl font-bold">Overall Sampling Reports</h1>
          <p class="text-muted-foreground">Comprehensive analysis of all sampling data</p>
        </div>
        <div class="flex gap-2">
          <Button variant="outline" @click="printReport">
            🖨️ Print Report
          </Button>
          <Button @click="exportToExcel">
            📊 Export to Excel
          </Button>
        </div>
      </div>

      <!-- Filters -->
      <Card class="p-6 print:hidden">
        <h3 class="text-lg font-semibold mb-4">Filters</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <div>
            <Label for="investor">Investor</Label>
            <select 
              id="investor"
              v-model="filters.investor_id"
              class="w-full px-3 py-2 border border-input bg-background text-foreground rounded-md focus:outline-none focus:ring-2 focus:ring-ring focus:border-ring transition-colors"
            >
              <option value="">All Investors</option>
              <option v-for="investor in investors" :key="investor.id" :value="investor.id">
                {{ investor.name }}
              </option>
            </select>
          </div>
          
          <div>
            <Label for="date_from">Date From</Label>
            <Input 
              id="date_from"
              v-model="filters.date_from"
              type="date"
              placeholder="Start date"
            />
          </div>
          
          <div>
            <Label for="date_to">Date To</Label>
            <Input 
              id="date_to"
              v-model="filters.date_to"
              type="date"
              placeholder="End date"
            />
          </div>
          
          <div>
            <Label for="cage_no">Cage Number</Label>
            <Input 
              id="cage_no"
              v-model="filters.cage_no"
              placeholder="Cage number"
            />
          </div>
        </div>
        
        <div class="flex gap-2 mt-4">
          <Button @click="applyFilters">Apply Filters</Button>
          <Button variant="outline" @click="clearFilters">Clear Filters</Button>
        </div>
      </Card>

      <!-- Summary Statistics -->
      <Card class="p-6">
        <h3 class="text-lg font-semibold mb-4">Summary Statistics</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div 
            v-for="stat in summaryStats" 
            :key="stat.label"
            class="text-center p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700"
          >
            <div class="text-2xl font-bold" :class="stat.color">{{ stat.value }}</div>
            <div class="text-sm text-muted-foreground">{{ stat.label }}</div>
            <div v-if="stat.unit" class="text-xs text-muted-foreground">{{ stat.unit }}</div>
          </div>
        </div>
      </Card>

      <!-- Detailed Data Table -->
      <Card class="p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-4">
          <h3 class="text-lg font-semibold">Detailed Sampling Data</h3>
          <div class="text-sm text-muted-foreground">
            Showing {{ formattedSamplings.length }} records
          </div>
        </div>
        
        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
          <table class="min-w-full">
            <thead class="bg-gray-50 dark:bg-gray-800">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-200 dark:border-gray-700">
                  Date
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-200 dark:border-gray-700">
                  Investor
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-200 dark:border-gray-700">
                  Cage No
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-200 dark:border-gray-700">
                  DOC
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-200 dark:border-gray-700">
                  Sample Count
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-200 dark:border-gray-700">
                  Total Weight (g)
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-200 dark:border-gray-700">
                  Avg Weight (g)
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-200 dark:border-gray-700">
                  Min Weight (g)
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-200 dark:border-gray-700">
                  Max Weight (g)
                </th>
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
              <tr 
                v-for="sampling in formattedSamplings" 
                :key="sampling.id"
                class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
              >
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                  {{ sampling.date_sampling }}
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                  {{ sampling.investor?.name || 'N/A' }}
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                  {{ sampling.cage_no }}
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                  {{ sampling.doc }}
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                  {{ sampling.sampleCount }}
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                  {{ sampling.totalWeight }}
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                  {{ sampling.avgWeight }}
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                  {{ sampling.minWeight }}
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                  {{ sampling.maxWeight }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <div v-if="formattedSamplings.length === 0" class="text-center py-8 text-muted-foreground">
          No sampling data found with the current filters.
        </div>
      </Card>
    </div>
  </AppLayout>
</template>

<style scoped>
@media print {
  .print\:hidden {
    display: none !important;
  }
  
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