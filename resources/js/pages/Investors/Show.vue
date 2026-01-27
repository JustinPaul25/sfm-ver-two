<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import Card from '@/components/ui/card/Card.vue';
import Button from '@/components/ui/button/Button.vue';
import { BarChart3 } from 'lucide-vue-next';

interface Cage {
  id: number;
  number_of_fingerlings: number;
  feed_type: string | null;
  farmer: { id: number; name: string } | null;
}

interface Props {
  investor: {
    id: number;
    name: string;
    address: string;
    phone: string;
  };
  cages: Cage[];
}

const props = defineProps<Props>();

const breadcrumbs = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Investors', href: '/investors' },
  { title: props.investor.name, href: '#' },
];

const viewReport = () => {
  router.visit(`/investors/${props.investor.id}/report`);
};

const goBack = () => {
  router.visit('/investors');
};
</script>

<template>
  <Head :title="`${investor.name} - Details`" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col gap-6 p-4">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold">Investor Details</h1>
        <div class="flex gap-2">
          <Button @click="viewReport" variant="default" class="gap-2">
            <BarChart3 :size="16" />
            View Report
          </Button>
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

      <!-- Cages -->
      <Card class="p-6">
        <h2 class="text-xl font-semibold mb-4">Cages ({{ cages.length }})</h2>
        <div class="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-800">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cage ID</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fingerlings</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Feed Type</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Farmer</th>
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-if="cages.length === 0">
                <td colspan="4" class="px-6 py-4 text-center text-gray-500">No cages for this investor.</td>
              </tr>
              <tr v-else v-for="cage in cages" :key="cage.id">
                <td class="px-6 py-4 whitespace-nowrap">{{ cage.id }}</td>
                <td class="px-6 py-4 whitespace-nowrap">{{ cage.number_of_fingerlings.toLocaleString() }}</td>
                <td class="px-6 py-4 whitespace-nowrap">{{ cage.feed_type || 'N/A' }}</td>
                <td class="px-6 py-4 whitespace-nowrap">{{ cage.farmer?.name ?? 'N/A' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </Card>
    </div>
  </AppLayout>
</template>
