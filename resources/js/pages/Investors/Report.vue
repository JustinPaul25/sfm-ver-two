<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import Card from '@/components/ui/card/Card.vue';
import Button from '@/components/ui/button/Button.vue';
import { computed } from 'vue';

interface Props {
  investor: {
    id: number;
    name: string;
    address: string;
    phone: string;
  };
  summary: {
    total_cages: number;
    total_fingerlings: number;
    total_samplings: number;
    total_samples: number;
    total_weight: number;
    total_weight_kg: number;
    avg_weight: number;
    min_weight: number;
    max_weight: number;
    total_mortality: number;
    total_present_stocks: number;
    total_biomass: number;
    earliest_sampling_date: string | null;
    latest_sampling_date: string | null;
  };
  cage_stats: Array<{
    id: number;
    number_of_fingerlings: number;
    feed_type: string;
    total_samplings: number;
    total_samples: number;
    total_weight: number;
    avg_weight: number;
    total_mortality: number;
    present_stocks: number;
  }>;
  samplings_by_cage: Array<{
    cage_id: number;
    cage_fingerlings: number;
    feed_type: string;
    samplings: Array<{
      id: number;
      date_sampling: string;
      doc: number;
      mortality: number;
      sample_count: number;
      total_weight: number;
      avg_weight: number;
      min_weight: number;
      max_weight: number;
    }>;
  }>;
}

const props = defineProps<Props>();

const breadcrumbs = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Investors', href: '/investors' },
  { title: `${props.investor.name} - Report`, href: '#' },
];

// Helper function to format dates
const formatDate = (date: string | null) => {
  if (!date) return 'N/A';
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  });
};

// Helper function to round numbers
const round = (value: number, decimals: number = 2) => {
  return Math.round(value * Math.pow(10, decimals)) / Math.pow(10, decimals);
};

// Print functionality
const printReport = () => {
  window.print();
};

// Go back to investors list
const goBack = () => {
  router.visit('/investors');
};
</script>

<template>
  <Head :title="`${investor.name} - Consolidated Report`" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col gap-6 p-4 print:p-2">
      <!-- Header with Actions -->
      <div class="flex items-center justify-between print:hidden">
        <h1 class="text-2xl font-bold">Consolidated Report - {{ investor.name }}</h1>
        <div class="flex gap-2">
          <Button @click="printReport" variant="default">Print Report</Button>
          <Button @click="goBack" variant="secondary">Back to Investors</Button>
        </div>
      </div>

      <!-- Investor Information -->
      <Card class="p-6">
        <h2 class="text-xl font-semibold mb-4">Investor Information</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">Name</p>
            <p class="font-medium">{{ investor.name }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">Address</p>
            <p class="font-medium">{{ investor.address }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">Phone</p>
            <p class="font-medium">{{ investor.phone }}</p>
          </div>
        </div>
      </Card>

      <!-- Overall Summary Statistics -->
      <Card class="p-6">
        <h2 class="text-xl font-semibold mb-4">Overall Summary</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
          <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
            <p class="text-sm text-gray-600 dark:text-gray-400">Total Cages</p>
            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ summary.total_cages }}</p>
          </div>
          <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
            <p class="text-sm text-gray-600 dark:text-gray-400">Total Fingerlings</p>
            <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ summary.total_fingerlings.toLocaleString() }}</p>
          </div>
          <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg">
            <p class="text-sm text-gray-600 dark:text-gray-400">Total Samplings</p>
            <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ summary.total_samplings }}</p>
          </div>
          <div class="bg-orange-50 dark:bg-orange-900/20 p-4 rounded-lg">
            <p class="text-sm text-gray-600 dark:text-gray-400">Total Samples</p>
            <p class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ summary.total_samples }}</p>
          </div>
          <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-lg">
            <p class="text-sm text-gray-600 dark:text-gray-400">Total Mortality</p>
            <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ summary.total_mortality }}</p>
          </div>
          <div class="bg-teal-50 dark:bg-teal-900/20 p-4 rounded-lg">
            <p class="text-sm text-gray-600 dark:text-gray-400">Present Stocks</p>
            <p class="text-2xl font-bold text-teal-600 dark:text-teal-400">{{ summary.total_present_stocks.toLocaleString() }}</p>
          </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4">
          <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
            <p class="text-sm text-gray-600 dark:text-gray-400">Average Weight</p>
            <p class="text-xl font-bold">{{ round(summary.avg_weight, 2) }}g</p>
          </div>
          <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
            <p class="text-sm text-gray-600 dark:text-gray-400">Total Weight</p>
            <p class="text-xl font-bold">{{ round(summary.total_weight_kg, 2) }} kg</p>
          </div>
          <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
            <p class="text-sm text-gray-600 dark:text-gray-400">Total Biomass</p>
            <p class="text-xl font-bold">{{ round(summary.total_biomass, 2) }} kg</p>
          </div>
          <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
            <p class="text-sm text-gray-600 dark:text-gray-400">Weight Range</p>
            <p class="text-xl font-bold">{{ round(summary.min_weight, 2) }}g - {{ round(summary.max_weight, 2) }}g</p>
          </div>
        </div>

        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <p class="text-sm text-gray-600 dark:text-gray-400">Earliest Sampling Date</p>
              <p class="font-medium">{{ formatDate(summary.earliest_sampling_date) }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-600 dark:text-gray-400">Latest Sampling Date</p>
              <p class="font-medium">{{ formatDate(summary.latest_sampling_date) }}</p>
            </div>
          </div>
        </div>
      </Card>

      <!-- Per-Cage Statistics -->
      <Card class="p-6">
        <h2 class="text-xl font-semibold mb-4">Per-Cage Statistics</h2>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-800">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cage ID</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fingerlings</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Feed Type</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Samplings</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Samples</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Weight (g)</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mortality</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Present Stocks</th>
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-for="cage in cage_stats" :key="cage.id">
                <td class="px-4 py-3 whitespace-nowrap font-medium">Cage #{{ cage.id }}</td>
                <td class="px-4 py-3 whitespace-nowrap">{{ cage.number_of_fingerlings.toLocaleString() }}</td>
                <td class="px-4 py-3 whitespace-nowrap">{{ cage.feed_type }}</td>
                <td class="px-4 py-3 whitespace-nowrap">{{ cage.total_samplings }}</td>
                <td class="px-4 py-3 whitespace-nowrap">{{ cage.total_samples }}</td>
                <td class="px-4 py-3 whitespace-nowrap">{{ round(cage.avg_weight, 2) }}</td>
                <td class="px-4 py-3 whitespace-nowrap">{{ cage.total_mortality }}</td>
                <td class="px-4 py-3 whitespace-nowrap">{{ cage.present_stocks.toLocaleString() }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </Card>

      <!-- Detailed Samplings by Cage -->
      <div v-for="cageData in samplings_by_cage" :key="cageData.cage_id" class="space-y-4">
        <Card class="p-6">
          <h2 class="text-xl font-semibold mb-4">
            Cage #{{ cageData.cage_id }} - {{ cageData.feed_type }}
            <span class="text-sm font-normal text-gray-500 dark:text-gray-400">
              ({{ cageData.cage_fingerlings.toLocaleString() }} fingerlings)
            </span>
          </h2>
          
          <div v-if="cageData.samplings.length === 0" class="text-center py-8 text-gray-500">
            No samplings recorded for this cage.
          </div>
          
          <div v-else class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
              <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">DOC</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Samples</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Weight (g)</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Weight (g)</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Min Weight (g)</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Max Weight (g)</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mortality</th>
                </tr>
              </thead>
              <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                <tr v-for="sampling in cageData.samplings" :key="sampling.id">
                  <td class="px-4 py-3 whitespace-nowrap">{{ formatDate(sampling.date_sampling) }}</td>
                  <td class="px-4 py-3 whitespace-nowrap">{{ sampling.doc }}</td>
                  <td class="px-4 py-3 whitespace-nowrap">{{ sampling.sample_count }}</td>
                  <td class="px-4 py-3 whitespace-nowrap">{{ round(sampling.total_weight, 2) }}</td>
                  <td class="px-4 py-3 whitespace-nowrap">{{ round(sampling.avg_weight, 2) }}</td>
                  <td class="px-4 py-3 whitespace-nowrap">{{ round(sampling.min_weight, 2) }}</td>
                  <td class="px-4 py-3 whitespace-nowrap">{{ round(sampling.max_weight, 2) }}</td>
                  <td class="px-4 py-3 whitespace-nowrap">{{ sampling.mortality }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </Card>
      </div>

      <!-- Empty State for Cages with No Samplings -->
      <Card v-if="samplings_by_cage.length === 0" class="p-6">
        <div class="text-center py-8">
          <p class="text-gray-500 dark:text-gray-400 text-lg">No samplings recorded for any cages.</p>
        </div>
      </Card>
    </div>
  </AppLayout>
</template>

<style scoped>
@media print {
  .print\:hidden {
    display: none;
  }
  .print\:p-2 {
    padding: 0.5rem;
  }
}
</style>

