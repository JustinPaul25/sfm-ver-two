<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import Swal from 'sweetalert2';

import HeadingSmall from '@/components/HeadingSmall.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { type BreadcrumbItem } from '@/types';

import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';

interface Props {
  settings: Record<string, any>;
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
  {
    title: 'System settings',
    href: '/settings/system',
  },
];

const form = useForm({
  algorithm: props.settings.forecasting_algorithm?.value || 'lstm',
});

const harvestForm = useForm({
  harvest_target_weight_grams: props.settings.harvest_target_weight_grams?.value ?? '500',
  harvest_default_growth_rate_g_per_day: props.settings.harvest_default_growth_rate_g_per_day?.value ?? '3',
});

const algorithmDescriptions = {
  lstm: 'Long Short-Term Memory - Best for complex time series with long-term dependencies. Highest accuracy but slower training.',
  rnn: 'Simple Recurrent Neural Network - Faster training, good for simpler patterns. Balanced performance.',
  dense: 'Dense Neural Network (MLP) - Lightweight and fast, works well for shorter sequences. Quickest training.',
};

const isSubmitting = ref(false);
const isSubmittingHarvest = ref(false);

async function updateHarvestSettings() {
  isSubmittingHarvest.value = true;
  try {
    router.put(
      route('settings.system.harvest-settings'),
      {
        harvest_target_weight_grams: harvestForm.harvest_target_weight_grams,
        harvest_default_growth_rate_g_per_day: harvestForm.harvest_default_growth_rate_g_per_day,
      },
      {
        preserveScroll: true,
        onSuccess: () => {
          Swal.fire({
            icon: 'success',
            title: 'Updated!',
            text: 'Harvest settings updated successfully.',
            confirmButtonColor: '#3085d6',
          });
        },
        onError: (errors) => {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: errors.harvest_target_weight_grams || errors.harvest_default_growth_rate_g_per_day || 'Failed to update harvest settings.',
            confirmButtonColor: '#d33',
          });
        },
        onFinish: () => {
          isSubmittingHarvest.value = false;
        },
      }
    );
  } catch (error) {
    isSubmittingHarvest.value = false;
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'An unexpected error occurred.',
      confirmButtonColor: '#d33',
    });
  }
}

async function updateAlgorithm() {
  isSubmitting.value = true;

  try {
    router.put(
      route('settings.system.forecasting-algorithm'),
      {
        algorithm: form.algorithm,
      },
      {
        preserveScroll: true,
        onSuccess: () => {
          Swal.fire({
            icon: 'success',
            title: 'Updated!',
            text: 'Forecasting algorithm updated successfully.',
            confirmButtonColor: '#3085d6',
          });
        },
        onError: (errors) => {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: errors.algorithm || 'Failed to update algorithm.',
            confirmButtonColor: '#d33',
          });
        },
        onFinish: () => {
          isSubmitting.value = false;
        },
      }
    );
  } catch (error) {
    isSubmitting.value = false;
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'An unexpected error occurred.',
      confirmButtonColor: '#d33',
    });
  }
}
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbItems">
    <Head title="System settings" />

    <SettingsLayout>
      <div class="space-y-6">
        <HeadingSmall
          title="System settings"
          description="Manage system-wide configuration settings"
        />

        <!-- Forecasting Algorithm Settings -->
        <Card>
          <CardHeader>
            <CardTitle>Forecasting Algorithm</CardTitle>
            <CardDescription>
              Choose the machine learning algorithm used for forecasting across the system
            </CardDescription>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="space-y-2">
              <Label for="algorithm">Algorithm Type</Label>
              <select
                id="algorithm"
                v-model="form.algorithm"
                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
              >
                <option value="lstm">LSTM (Long Short-Term Memory)</option>
                <option value="rnn">RNN (Recurrent Neural Network)</option>
                <option value="dense">Dense Neural Network (MLP)</option>
              </select>
            </div>

            <!-- Algorithm Description -->
            <div class="rounded-md bg-muted p-4">
              <p class="text-sm text-muted-foreground">
                <strong>{{ form.algorithm.toUpperCase() }}:</strong>
                {{ algorithmDescriptions[form.algorithm as keyof typeof algorithmDescriptions] }}
              </p>
            </div>

            <!-- Algorithm Comparison Table -->
            <div class="overflow-hidden rounded-lg border">
              <table class="w-full text-sm">
                <thead class="bg-muted">
                  <tr>
                    <th class="px-4 py-3 text-left font-medium">Algorithm</th>
                    <th class="px-4 py-3 text-left font-medium">Accuracy</th>
                    <th class="px-4 py-3 text-left font-medium">Speed</th>
                    <th class="px-4 py-3 text-left font-medium">Best For</th>
                  </tr>
                </thead>
                <tbody class="divide-y">
                  <tr>
                    <td class="px-4 py-3 font-medium">LSTM</td>
                    <td class="px-4 py-3">⭐⭐⭐⭐⭐</td>
                    <td class="px-4 py-3">⭐⭐</td>
                    <td class="px-4 py-3">Long-term patterns</td>
                  </tr>
                  <tr>
                    <td class="px-4 py-3 font-medium">RNN</td>
                    <td class="px-4 py-3">⭐⭐⭐⭐</td>
                    <td class="px-4 py-3">⭐⭐⭐⭐</td>
                    <td class="px-4 py-3">Balanced use cases</td>
                  </tr>
                  <tr>
                    <td class="px-4 py-3 font-medium">Dense</td>
                    <td class="px-4 py-3">⭐⭐⭐</td>
                    <td class="px-4 py-3">⭐⭐⭐⭐⭐</td>
                    <td class="px-4 py-3">Quick predictions</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Save Button -->
            <div class="flex justify-end">
              <Button @click="updateAlgorithm" :disabled="isSubmitting">
                {{ isSubmitting ? 'Saving...' : 'Save Changes' }}
              </Button>
            </div>
          </CardContent>
        </Card>

        <!-- Harvest Anticipation Settings -->
        <Card>
          <CardHeader>
            <CardTitle>Harvest Anticipation</CardTitle>
            <CardDescription>
              Target weight and default growth rate used to estimate when fish in each cage will be ready for harvest
            </CardDescription>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="space-y-2">
                <Label for="harvest_target_weight_grams">Target harvest weight (grams)</Label>
                <input
                  id="harvest_target_weight_grams"
                  v-model="harvestForm.harvest_target_weight_grams"
                  type="number"
                  min="1"
                  max="10000"
                  step="1"
                  class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                />
                <p class="text-xs text-muted-foreground">Fish are considered ready when average weight reaches this.</p>
              </div>
              <div class="space-y-2">
                <Label for="harvest_default_growth_rate_g_per_day">Default growth rate (g/day)</Label>
                <input
                  id="harvest_default_growth_rate_g_per_day"
                  v-model="harvestForm.harvest_default_growth_rate_g_per_day"
                  type="number"
                  min="0.1"
                  max="100"
                  step="0.1"
                  class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                />
                <p class="text-xs text-muted-foreground">Used when a cage has only one sampling (no trend yet).</p>
              </div>
            </div>
            <div class="flex justify-end">
              <Button @click="updateHarvestSettings" :disabled="isSubmittingHarvest">
                {{ isSubmittingHarvest ? 'Saving...' : 'Save Harvest Settings' }}
              </Button>
            </div>
          </CardContent>
        </Card>
      </div>
    </SettingsLayout>
  </AppLayout>
</template>
