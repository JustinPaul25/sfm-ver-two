<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import Card from '@/components/ui/card/Card.vue';
import Button from '@/components/ui/button/Button.vue';
import { router } from '@inertiajs/vue3';

const breadcrumbs = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Reports', href: '/reports' },
];

const reports = [
  {
    title: 'Overall Sampling Reports',
    description: 'Comprehensive reports of all sampling data with filtering and export capabilities',
    icon: '📊',
    href: '/reports/overall',
    features: ['Filter by date range', 'Filter by investor', 'Filter by cage number', 'Export to Excel', 'Print friendly']
  },
  {
    title: 'Individual Sampling Reports',
    description: 'Detailed reports for specific sampling sessions',
    icon: '📋',
    href: '/samplings/report',
    features: ['Sample details', 'Weight analysis', 'Historical data', 'Print friendly']
  }
];

const navigateToReport = (href: string) => {
  router.visit(href);
};
</script>

<template>
  <Head title="Reports" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col gap-6 p-4 max-w-6xl mx-auto">
      <div class="flex flex-col gap-2">
        <h1 class="text-3xl font-bold">Reports</h1>
        <p class="text-muted-foreground">Generate comprehensive reports for your sampling data</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <Card 
          v-for="report in reports" 
          :key="report.title"
          class="p-6 hover:shadow-lg transition-shadow cursor-pointer"
          @click="navigateToReport(report.href)"
        >
          <div class="flex items-start gap-4">
            <div class="text-4xl">{{ report.icon }}</div>
            <div class="flex-1">
              <h3 class="text-xl font-semibold mb-2">{{ report.title }}</h3>
              <p class="text-muted-foreground mb-4">{{ report.description }}</p>
              <div class="space-y-1">
                <div 
                  v-for="feature in report.features" 
                  :key="feature"
                  class="flex items-center gap-2 text-sm"
                >
                  <div class="w-1.5 h-1.5 bg-primary rounded-full"></div>
                  {{ feature }}
                </div>
              </div>
            </div>
          </div>
        </Card>
      </div>
    </div>
  </AppLayout>
</template> 