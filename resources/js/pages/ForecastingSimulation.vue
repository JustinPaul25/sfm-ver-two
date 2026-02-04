<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import ForecastingDemo from '@/components/ForecastingDemo.vue';
import { type BreadcrumbItem } from '@/types';

const breadcrumbItems: BreadcrumbItem[] = [
  {
    title: 'Dashboard',
    href: '/dashboard',
  },
  {
    title: 'Forecasting Simulation',
    href: '/forecasting/simulation',
  },
];
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbItems">
    <Head title="Forecasting Simulation" />

    <div class="space-y-6">
      <HeadingSmall
        title="Forecasting Simulation"
        description="JavaScript-based simulation of LSTM, RNN, and Dense algorithms. Test forecasting with sample data in the browser — equivalent to the Jupyter notebook experience."
      />

      <ForecastingDemo />
    </div>
  </AppLayout>
</template>
