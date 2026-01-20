<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { type SharedData } from '@/types';
import Button from '@/components/ui/button/Button.vue';
import Input from '@/components/ui/input/Input.vue';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import axios from 'axios';

const page = usePage<SharedData>();

interface VerificationData {
  id: number;
  number_of_fingerlings: number;
  mortality: number;
  present_stocks: number;
  investor: string;
  feed_type: string;
  avg_weight: number | null;
  avg_length: number | null;
  avg_width: number | null;
  last_sampling_date: string | null;
}

const breadcrumbs = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Cages', href: '/cages' },
  { title: 'Per Cage Verification', href: '/cages/verification' },
];

const verificationData = ref<VerificationData[]>([]);
const loading = ref(true);
const search = ref('');

// Filter verification data based on search
const filteredData = computed(() => {
  if (!search.value.trim()) {
    return verificationData.value;
  }
  
  const searchLower = search.value.toLowerCase();
  return verificationData.value.filter(cage => 
    cage.id.toString().includes(searchLower) ||
    cage.investor.toLowerCase().includes(searchLower) ||
    cage.feed_type.toLowerCase().includes(searchLower)
  );
});

// Fetch verification data
const fetchVerificationData = async () => {
  try {
    loading.value = true;
    const response = await axios.get(route('cages.verification.data'));
    verificationData.value = response.data.verification_data || [];
  } catch (error: any) {
    console.error('Error fetching verification data:', error);
  } finally {
    loading.value = false;
  }
};

// Format number with thousands separator
const formatNumber = (num: number | null): string => {
  if (num === null || num === undefined) return 'N/A';
  return num.toLocaleString();
};

// Format decimal with 1 decimal place
const formatDecimal = (num: number | null, decimals: number = 1): string => {
  if (num === null || num === undefined) return 'N/A';
  return num.toFixed(decimals);
};

// Format date
const formatDate = (date: string | null): string => {
  if (!date) return 'No sampling';
  try {
    return new Date(date).toLocaleDateString();
  } catch {
    return date;
  }
};

onMounted(() => {
  fetchVerificationData();
});
</script>

<template>
  <Head title="Per Cage Verification" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col gap-4 p-4">
      <Card class="p-6">
        <CardHeader>
          <CardTitle class="text-2xl font-bold">Per Cage Verification</CardTitle>
          <p class="text-sm text-muted-foreground mt-1">
            View size, weight, and number of fish for each cage based on latest sampling data
          </p>
        </CardHeader>
        <CardContent>
          <div class="flex items-center justify-between gap-2 mb-4">
            <div class="flex gap-2 items-center">
              <Input 
                v-model="search" 
                placeholder="Search by cage number, investor, or feed type..." 
                class="w-96" 
              />
              <Button @click="fetchVerificationData" variant="outline">
                🔄 Refresh
              </Button>
            </div>
          </div>
          
          <div class="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-white dark:bg-gray-900">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
              <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Cage #
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Investor
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Feed Type
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Size
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Weight
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Number of Fish
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Last Sampling
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                <tr v-if="loading">
                  <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                    Loading verification data...
                  </td>
                </tr>
                <tr v-else-if="filteredData.length === 0">
                  <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                    {{ search ? 'No cages found matching your search.' : 'No cages found.' }}
                  </td>
                </tr>
                <tr v-else v-for="cage in filteredData" :key="cage.id" class="hover:bg-gray-50 dark:hover:bg-gray-800">
                  <td class="px-6 py-4 whitespace-nowrap font-medium">
                    #{{ cage.id }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    {{ cage.investor }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    {{ cage.feed_type }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex flex-col text-sm">
                      <span v-if="cage.avg_length">
                        <span class="font-medium">Length:</span> {{ formatDecimal(cage.avg_length) }} cm
                      </span>
                      <span v-else class="text-gray-400">Length: N/A</span>
                      <span v-if="cage.avg_width" class="mt-1">
                        <span class="font-medium">Width:</span> {{ formatDecimal(cage.avg_width) }} cm
                      </span>
                      <span v-else class="text-gray-400">Width: N/A</span>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div v-if="cage.avg_weight" class="font-medium">
                      {{ formatDecimal(cage.avg_weight) }} g
                    </div>
                    <span v-else class="text-gray-400">N/A</span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex flex-col text-sm">
                      <span>
                        <span class="font-medium">Total:</span> {{ formatNumber(cage.number_of_fingerlings) }}
                      </span>
                      <span v-if="cage.mortality > 0" class="text-red-600 dark:text-red-400 mt-1">
                        <span class="font-medium">Mortality:</span> {{ formatNumber(cage.mortality) }}
                      </span>
                      <span class="text-green-600 dark:text-green-400 mt-1">
                        <span class="font-medium">Present:</span> {{ formatNumber(cage.present_stocks) }}
                      </span>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                    {{ formatDate(cage.last_sampling_date) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          
          <div v-if="!loading && filteredData.length > 0" class="mt-4 text-sm text-muted-foreground">
            Showing {{ filteredData.length }} of {{ verificationData.length }} cage{{ verificationData.length !== 1 ? 's' : '' }}
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
