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

const harvestForm = useForm({
  harvest_target_weight_grams: props.settings.harvest_target_weight_grams?.value ?? '500',
  harvest_default_growth_rate_g_per_day: props.settings.harvest_default_growth_rate_g_per_day?.value ?? '3',
});

const sampleTimestampForm = useForm({
  sample_tested_at: toDatetimeLocalValue(props.settings.sample_tested_at?.value ?? '2026-05-12 14:48:00'),
});

const isSubmittingHarvest = ref(false);
const isSubmittingSampleTimestamp = ref(false);

function toDatetimeLocalValue(value: string): string {
  const normalized = value.includes('T') ? value : value.replace(' ', 'T');
  return normalized.slice(0, 16);
}

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

async function updateSampleTimestamp() {
  isSubmittingSampleTimestamp.value = true;
  try {
    router.put(
      route('settings.system.sample-timestamp'),
      {
        sample_tested_at: sampleTimestampForm.sample_tested_at,
      },
      {
        preserveScroll: true,
        onSuccess: () => {
          Swal.fire({
            icon: 'success',
            title: 'Updated!',
            text: 'Sample timestamps updated successfully.',
            confirmButtonColor: '#3085d6',
          });
        },
        onError: (errors) => {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: errors.sample_tested_at || 'Failed to update sample timestamps.',
            confirmButtonColor: '#d33',
          });
        },
        onFinish: () => {
          isSubmittingSampleTimestamp.value = false;
        },
      }
    );
  } catch (error) {
    isSubmittingSampleTimestamp.value = false;
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

        <!-- Sample Timestamp Settings -->
        <Card>
          <CardHeader>
            <CardTitle>Sample Timestamps</CardTitle>
            <CardDescription>
              Set the Tested At date and time shown for sample measurement rows in sampling reports
            </CardDescription>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="space-y-2">
                <Label for="sample_tested_at">Sample tested at</Label>
                <input
                  id="sample_tested_at"
                  v-model="sampleTimestampForm.sample_tested_at"
                  type="datetime-local"
                  class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                />
                <p class="text-xs text-muted-foreground">Updates created and updated timestamps for existing sample rows.</p>
              </div>
            </div>
            <div class="flex justify-end">
              <Button @click="updateSampleTimestamp" :disabled="isSubmittingSampleTimestamp">
                {{ isSubmittingSampleTimestamp ? 'Applying...' : 'Apply Sample Timestamp' }}
              </Button>
            </div>
          </CardContent>
        </Card>
      </div>
    </SettingsLayout>
  </AppLayout>
</template>
