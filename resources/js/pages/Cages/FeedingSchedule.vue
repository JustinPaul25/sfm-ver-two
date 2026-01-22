<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import Card from '@/components/ui/card/Card.vue';
import Button from '@/components/ui/button/Button.vue';
import Input from '@/components/ui/input/Input.vue';
import Label from '@/components/ui/label/Label.vue';
import { ref, computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';

// Props from Inertia
const props = defineProps<{
  cage?: any;
  schedules?: any[];
  activeSchedule?: any;
}>();

// Reactive data
const cage = ref(props.cage);
const schedules = ref(props.schedules || []);
const activeSchedule = ref(props.activeSchedule);
const loading = ref(false);
const showForm = ref(false);
const editingSchedule = ref(null);

// Form data
const form = ref<{
  schedule_name: string;
  feeding_time_1: string;
  feeding_time_2: string;
  feeding_time_3: string;
  feeding_time_4: string;
  feeding_amount_1: string;
  feeding_amount_2: string;
  feeding_amount_3: string;
  feeding_amount_4: string;
  frequency: string;
  notes: string;
}>({
  schedule_name: '',
  feeding_time_1: '',
  feeding_time_2: '',
  feeding_time_3: '',
  feeding_time_4: '',
  feeding_amount_1: '',
  feeding_amount_2: '',
  feeding_amount_3: '',
  feeding_amount_4: '',
  frequency: 'daily',
  notes: '',
});

const frequencyOptions = [
  { value: 'daily', label: 'Once Daily' },
  { value: 'twice_daily', label: 'Twice Daily' },
  { value: 'thrice_daily', label: 'Three Times Daily' },
  { value: 'four_times_daily', label: 'Four Times Daily' },
];

const breadcrumbs = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Cages', href: '/cages' },
  { title: 'Feeding Schedules', href: '/cages/feeding-schedules' },
  { title: cage.value?.investor?.name || 'Cage Schedule', href: '#' },
];

// Computed properties
const totalDailyAmount = computed(() => {
  const amounts = [
    parseFloat(form.value.feeding_amount_1) || 0,
    parseFloat(form.value.feeding_amount_2) || 0,
    parseFloat(form.value.feeding_amount_3) || 0,
    parseFloat(form.value.feeding_amount_4) || 0,
  ];
  return amounts.reduce((sum, amount) => sum + amount, 0).toFixed(2);
});

const isFormValid = computed(() => {
  return form.value.schedule_name.trim() !== '' && 
         (form.value.feeding_time_1 || form.value.feeding_time_2 || 
          form.value.feeding_time_3 || form.value.feeding_time_4);
});

// Methods
const resetForm = () => {
  form.value = {
    schedule_name: '',
    feeding_time_1: '',
    feeding_time_2: '',
    feeding_time_3: '',
    feeding_time_4: '',
    feeding_amount_1: '',
    feeding_amount_2: '',
    feeding_amount_3: '',
    feeding_amount_4: '',
    frequency: 'daily',
    notes: '',
  };
  editingSchedule.value = null;
};

const openForm = (schedule: any = null) => {
  if (schedule) {
    editingSchedule.value = schedule;
    form.value = {
      schedule_name: schedule.schedule_name,
      feeding_time_1: schedule.feeding_time_1 || '',
      feeding_time_2: schedule.feeding_time_2 || '',
      feeding_time_3: schedule.feeding_time_3 || '',
      feeding_time_4: schedule.feeding_time_4 || '',
      feeding_amount_1: schedule.feeding_amount_1?.toString() || '',
      feeding_amount_2: schedule.feeding_amount_2?.toString() || '',
      feeding_amount_3: schedule.feeding_amount_3?.toString() || '',
      feeding_amount_4: schedule.feeding_amount_4?.toString() || '',
      frequency: schedule.frequency,
      notes: schedule.notes || '',
    };
  } else {
    resetForm();
  }
  showForm.value = true;
};

const closeForm = () => {
  showForm.value = false;
  resetForm();
};

const saveSchedule = async () => {
  if (!isFormValid.value) return;

  loading.value = true;
  try {
    const data = {
      cage_id: cage.value.id,
      ...form.value,
      feeding_amount_1: parseFloat(form.value.feeding_amount_1) || 0,
      feeding_amount_2: parseFloat(form.value.feeding_amount_2) || 0,
      feeding_amount_3: parseFloat(form.value.feeding_amount_3) || 0,
      feeding_amount_4: parseFloat(form.value.feeding_amount_4) || 0,
    };

    if (editingSchedule.value) {
      await axios.put(`/cages/feeding-schedules/${editingSchedule.value.id}`, data);
    } else {
      await axios.post('/cages/feeding-schedules', data);
    }

    // Reload the page to get updated data
    router.reload();
  } catch (error) {
    console.error('Error saving schedule:', error);
  } finally {
    loading.value = false;
  }
};

const deleteSchedule = async (scheduleId: number) => {
  if (!confirm('Are you sure you want to delete this schedule?')) return;

  loading.value = true;
  try {
    await axios.delete(`/cages/feeding-schedules/${scheduleId}`);
    router.reload();
  } catch (error) {
    console.error('Error deleting schedule:', error);
  } finally {
    loading.value = false;
  }
};

const activateSchedule = async (scheduleId: number) => {
  loading.value = true;
  try {
    await axios.post(`/cages/feeding-schedules/${scheduleId}/activate`);
    router.reload();
  } catch (error) {
    console.error('Error activating schedule:', error);
  } finally {
    loading.value = false;
  }
};

const formatTimePrint = (time: string) => {
  if (!time) return '–';
  const [h, m] = time.split(':').map(Number);
  const period = h >= 12 ? 'PM' : 'AM';
  const hour = h % 12 || 12;
  return `${hour}:${m.toString().padStart(2, '0')} ${period}`;
};

const printWeeklySchedule = () => {
  const s = activeSchedule.value;
  const c = cage.value;
  if (!s || !c) return;

  const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
  const dayRows = days.map(
    (day) => `
    <tr>
      <td class="day">${day}</td>
      ${(s.feeding_times || []).map((t: string, i: number) => {
        const amt = (s.feeding_amounts || [])[i];
        return `<td>${formatTimePrint(t)}</td><td>${amt != null ? Number(amt).toFixed(2) : '0.00'} kg</td>`;
      }).join('')}
      <td class="total">${Number(s.total_daily_amount || 0).toFixed(2)} kg</td>
    </tr>`
  ).join('');

  const headerCells = (s.feeding_times || []).map((_: string, i: number) => 
    `<th colspan="2">Feeding ${i + 1}</th>`
  ).join('');

  const html = `
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Weekly Feeding Schedule - Cage #${c.id}</title>
  <style>
    body { font-family: system-ui, sans-serif; padding: 24px; color: #111; max-width: 900px; margin: 0 auto; }
    h1 { font-size: 1.5rem; margin-bottom: 0.25rem; }
    .sub { color: #666; font-size: 0.9rem; margin-bottom: 1.5rem; }
    table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
    th, td { border: 1px solid #ccc; padding: 8px 10px; text-align: left; }
    th { background: #f5f5f5; font-weight: 600; }
    .day { font-weight: 500; width: 120px; }
    .total { font-weight: 600; background: #f0f9ff; }
    td:not(.day):not(.total) { text-align: center; }
  </style>
</head>
<body>
  <h1>Weekly Feeding Schedule</h1>
  <p class="sub">${c.investor?.name || 'Cage'} – Cage #${c.id} • ${c.number_of_fingerlings?.toLocaleString()} fish • ${c.feedType?.feed_type || 'N/A'}</p>
  <p class="sub"><strong>Schedule:</strong> ${s.schedule_name} • Total daily: ${Number(s.total_daily_amount || 0).toFixed(2)} kg</p>
  <table>
    <thead>
      <tr>
        <th>Day</th>
        ${headerCells}
        <th>Total (kg)</th>
      </tr>
    </thead>
    <tbody>
      ${dayRows}
    </tbody>
  </table>
  <p style="margin-top: 1.5rem; font-size: 0.85rem; color: #666;">Generated on ${new Date().toLocaleString()} • Smart Fish Morphometrics</p>
</body>
</html>`;

  const w = window.open('', '_blank');
  if (!w) return;
  w.document.write(html);
  w.document.close();
  w.focus();
  w.onload = () => {
    w.print();
    w.onafterprint = () => w.close();
  };
};

const autoGenerateForCage = async () => {
  if (!confirm('This will replace the current schedule with an auto-generated one. Continue?')) return;
  
  loading.value = true;
  try {
    const response = await axios.post('/cages/feeding-schedules/auto-generate', {
      cage_ids: [cage.value?.id],
      overwrite_existing: true,
    });
    
    if (response.data.generated_schedules.length > 0) {
      alert('Schedule auto-generated successfully!');
      router.reload();
    } else {
      alert('Failed to generate schedule: ' + response.data.errors.join(', '));
    }
  } catch (error: any) {
    console.error('Auto-generation failed:', error);
    alert('Failed to auto-generate schedule: ' + (error.response?.data?.message || error.message));
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  // Auto-open form if no active schedule
  if (!activeSchedule.value) {
    openForm();
  }
});
</script>

<template>
  <Head :title="`Feeding Schedule - ${cage?.investor?.name || 'Cage'}`" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col gap-6 p-4 max-w-6xl mx-auto">
      <!-- Header -->
      <Card class="p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
          <div>
            <h2 class="text-2xl font-bold mb-1">Feeding Schedule</h2>
            <div class="text-sm text-muted-foreground">
              {{ cage?.investor?.name }} - Cage #{{ cage?.id }}
            </div>
            <div class="text-sm text-muted-foreground">
              {{ cage?.number_of_fingerlings?.toLocaleString() }} fish • {{ cage?.feedType?.feed_type }}
            </div>
          </div>
          <div class="flex gap-2">
            <Button variant="outline" @click="router.visit('/cages/feeding-schedules')">
              ← Back to All Schedules
            </Button>
            <Button @click="openForm()" v-if="!showForm">
              ➕ New Schedule
            </Button>
          </div>
        </div>
      </Card>

      <!-- Active Schedule -->
      <Card v-if="activeSchedule && !showForm" class="p-6">
        <div class="flex items-start justify-between mb-4">
          <div>
            <h3 class="text-xl font-semibold mb-1">{{ activeSchedule.schedule_name }}</h3>
            <span class="inline-block bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs font-medium">
              Active Schedule
            </span>
          </div>
          <div class="flex gap-2 flex-wrap">
            <Button variant="outline" @click="openForm(activeSchedule)">
              ✏️ Edit
            </Button>
            <Button variant="outline" @click="openForm()">
              ➕ New Schedule
            </Button>
            <Button variant="outline" @click="printWeeklySchedule" class="bg-amber-50 text-amber-800 hover:bg-amber-100">
              🖨️ Print Weekly Schedule
            </Button>
            <Button variant="outline" @click="autoGenerateForCage" class="bg-blue-50 text-blue-700 hover:bg-blue-100">
              🚀 Auto-Generate
            </Button>
          </div>
        </div>

        <!-- Schedule Details -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <h4 class="font-medium mb-3">Feeding Times</h4>
            <div class="space-y-2">
              <div v-for="(time, index) in activeSchedule.feeding_times" :key="index" class="flex justify-between">
                <span class="text-muted-foreground">Feeding {{ index + 1 }}:</span>
                <span class="font-mono font-medium">{{ time }}</span>
              </div>
            </div>
          </div>
          <div>
            <h4 class="font-medium mb-3">Feeding Amounts</h4>
            <div class="space-y-2">
              <div v-for="(amount, index) in activeSchedule.feeding_amounts" :key="index" class="flex justify-between">
                <span class="text-muted-foreground">Amount {{ index + 1 }}:</span>
                <span class="font-medium">{{ amount }} kg</span>
              </div>
              <div class="border-t pt-2 mt-2">
                <div class="flex justify-between font-semibold">
                  <span>Total Daily:</span>
                  <span>{{ activeSchedule.total_daily_amount }} kg</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div v-if="activeSchedule.notes" class="mt-4 p-3 bg-gray-50 rounded-lg">
          <h4 class="font-medium mb-1">Notes</h4>
          <p class="text-sm text-muted-foreground">{{ activeSchedule.notes }}</p>
        </div>
      </Card>

      <!-- Schedule Form -->
      <Card v-if="showForm" class="p-6">
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-xl font-semibold">
            {{ editingSchedule ? 'Edit Schedule' : 'Create New Schedule' }}
          </h3>
          <Button variant="outline" @click="closeForm">✕ Cancel</Button>
        </div>

        <form @submit.prevent="saveSchedule" class="space-y-6">
          <!-- Basic Info -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <Label for="schedule_name">Schedule Name</Label>
              <Input
                id="schedule_name"
                v-model="form.schedule_name"
                placeholder="e.g., Morning Schedule"
                required
              />
            </div>
            <div>
              <Label for="frequency">Frequency</Label>
              <select
                id="frequency"
                v-model="form.frequency"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option v-for="option in frequencyOptions" :key="option.value" :value="option.value">
                  {{ option.label }}
                </option>
              </select>
            </div>
          </div>

          <!-- Feeding Times and Amounts -->
          <div class="space-y-4">
            <h4 class="font-medium">Feeding Schedule</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                             <div v-for="i in 4" :key="i" class="space-y-2">
                 <Label :for="`feeding_time_${i}`">Feeding {{ i }} Time</Label>
                 <Input
                   :id="`feeding_time_${i}`"
                   :model-value="i === 1 ? form.feeding_time_1 : i === 2 ? form.feeding_time_2 : i === 3 ? form.feeding_time_3 : form.feeding_time_4"
                   @update:model-value="(val: string) => { if (i === 1) form.feeding_time_1 = val; else if (i === 2) form.feeding_time_2 = val; else if (i === 3) form.feeding_time_3 = val; else form.feeding_time_4 = val; }"
                   type="time"
                   :placeholder="`${8 + (i-1)*4}:00`"
                 />
                 <Label :for="`feeding_amount_${i}`">Amount (kg)</Label>
                 <Input
                   :id="`feeding_amount_${i}`"
                   :model-value="i === 1 ? form.feeding_amount_1 : i === 2 ? form.feeding_amount_2 : i === 3 ? form.feeding_amount_3 : form.feeding_amount_4"
                   @update:model-value="(val: string) => { if (i === 1) form.feeding_amount_1 = val; else if (i === 2) form.feeding_amount_2 = val; else if (i === 3) form.feeding_amount_3 = val; else form.feeding_amount_4 = val; }"
                   type="number"
                   step="0.01"
                   min="0"
                   placeholder="0.00"
                 />
               </div>
            </div>
          </div>

          <!-- Total Daily Amount -->
          <div class="p-4 bg-blue-50 rounded-lg">
            <div class="flex justify-between items-center">
              <span class="font-medium">Total Daily Amount:</span>
              <span class="text-xl font-bold text-blue-600">{{ totalDailyAmount }} kg</span>
            </div>
          </div>

          <!-- Notes -->
          <div>
            <Label for="notes">Notes</Label>
            <textarea
              id="notes"
              v-model="form.notes"
              rows="3"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="Any additional notes about this feeding schedule..."
            ></textarea>
          </div>

          <!-- Form Actions -->
          <div class="flex gap-2 justify-end">
            <Button type="button" variant="outline" @click="closeForm">
              Cancel
            </Button>
            <Button type="submit" :disabled="!isFormValid || loading">
              {{ loading ? 'Saving...' : (editingSchedule ? 'Update Schedule' : 'Create Schedule') }}
            </Button>
          </div>
        </form>
      </Card>

      <!-- Schedule History -->
      <Card v-if="schedules.length > 1" class="p-6">
        <h3 class="text-xl font-semibold mb-4">Schedule History</h3>
        <div class="space-y-3">
          <div
            v-for="schedule in schedules.filter(s => !s.is_active)"
            :key="schedule.id"
            class="flex items-center justify-between p-4 border rounded-lg"
          >
            <div>
              <h4 class="font-medium">{{ schedule.schedule_name }}</h4>
              <p class="text-sm text-muted-foreground">
                Created {{ new Date(schedule.created_at).toLocaleDateString() }}
              </p>
            </div>
            <div class="flex gap-2">
              <Button variant="outline" size="sm" @click="activateSchedule(schedule.id)">
                Activate
              </Button>
              <Button variant="outline" size="sm" @click="openForm(schedule)">
                Edit
              </Button>
              <Button variant="outline" size="sm" @click="deleteSchedule(schedule.id)">
                Delete
              </Button>
            </div>
          </div>
        </div>
      </Card>
    </div>
  </AppLayout>
</template> 