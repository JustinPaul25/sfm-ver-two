<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { type SharedData } from '@/types';
import AppLayout from '@/layouts/AppLayout.vue';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardDescription from '@/components/ui/card/CardDescription.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import Button from '@/components/ui/button/Button.vue';
import Input from '@/components/ui/input/Input.vue';
import Label from '@/components/ui/label/Label.vue';
import Separator from '@/components/ui/separator/Separator.vue';
import Dialog from '@/components/ui/dialog/Dialog.vue';
import DialogContent from '@/components/ui/dialog/DialogContent.vue';
import DialogHeader from '@/components/ui/dialog/DialogHeader.vue';
import DialogTitle from '@/components/ui/dialog/DialogTitle.vue';
import DialogFooter from '@/components/ui/dialog/DialogFooter.vue';

const page = usePage<SharedData>();
const userRole = computed(() => page.props.auth?.user?.role || 'farmer');
const isInvestor = computed(() => userRole.value === 'investor');

interface DailyBreakdown {
  date: string;
  day_name: string;
  scheduled_amount: number;
  actual_amount: number;
  variance: number;
  adherence_rate: number;
  notes: string | null;
}

interface CageReport {
  cage_id: number;
  cage_number: number;
  investor_name: string;
  feed_type: string;
  fingerlings_count: number;
  has_schedule: boolean;
  schedule_name: string;
  feeding_frequency: string | null;
  feeding_times: string[];
  total_scheduled: number;
  total_consumed: number;
  variance: number;
  adherence_rate: number;
  daily_breakdown: DailyBreakdown[];
  average_daily_consumption: number;
}

interface Summary {
  total_cages: number;
  total_feed_consumed: number;
  total_scheduled_feed: number;
  average_adherence: number;
  cages_with_schedules: number;
  active_schedules: number;
}

interface Period {
  start_date: string;
  end_date: string;
  days_count: number;
}

interface FilterOption {
  id: number;
  name?: string;
  label?: string;
}

const loading = ref(false);
const reportData = ref<CageReport[]>([]);
const summary = ref<Summary>({
  total_cages: 0,
  total_feed_consumed: 0,
  total_scheduled_feed: 0,
  average_adherence: 0,
  cages_with_schedules: 0,
  active_schedules: 0,
});
const period = ref<Period>({
  start_date: '',
  end_date: '',
  days_count: 0,
});

// Filters
const investors = ref<FilterOption[]>([]);
const cages = ref<FilterOption[]>([]);
const startDate = ref('');
const endDate = ref('');
const selectedInvestor = ref<string>('');
const selectedCage = ref<string>('');

// Expanded cages for daily breakdown
const expandedCages = ref<Set<number>>(new Set());

// Add consumption dialog
const showAddConsumptionDialog = ref(false);
const addingConsumption = ref(false);
const selectedCageForConsumption = ref<number | null>(null);
const selectedDateForConsumption = ref<string>('');
const newConsumption = ref({
  day_number: 1,
  feed_amount: '',
  consumption_date: '',
  notes: ''
});

// Set default dates to current week
const setDefaultDates = () => {
  const now = new Date();
  const startOfWeek = new Date(now);
  const day = now.getDay();
  const diff = now.getDate() - day + (day === 0 ? -6 : 1); // Monday as start
  startOfWeek.setDate(diff);
  
  const endOfWeek = new Date(startOfWeek);
  endOfWeek.setDate(startOfWeek.getDate() + 6);
  
  startDate.value = startOfWeek.toISOString().split('T')[0];
  endDate.value = endOfWeek.toISOString().split('T')[0];
};

const fetchReport = async () => {
  loading.value = true;
  try {
    const params = new URLSearchParams();
    if (startDate.value) params.append('start_date', startDate.value);
    if (endDate.value) params.append('end_date', endDate.value);
    if (selectedCage.value) params.append('cage_id', selectedCage.value);
    if (selectedInvestor.value) params.append('investor_id', selectedInvestor.value);

    const response = await fetch(route('reports.feeding.weekly') + '?' + params.toString());
    const data = await response.json();

    reportData.value = data.report_data;
    summary.value = data.summary;
    period.value = data.period;

    if (data.filters) {
      investors.value = data.filters.investors;
      cages.value = data.filters.cages;
    }
  } catch (error) {
    console.error('Error fetching report:', error);
  } finally {
    loading.value = false;
  }
};

const toggleCageExpansion = (cageId: number) => {
  if (expandedCages.value.has(cageId)) {
    expandedCages.value.delete(cageId);
  } else {
    expandedCages.value.add(cageId);
  }
};

const isCageExpanded = (cageId: number) => {
  return expandedCages.value.has(cageId);
};

const exportToExcel = () => {
  const params = new URLSearchParams();
  if (startDate.value) params.append('start_date', startDate.value);
  if (endDate.value) params.append('end_date', endDate.value);
  if (selectedCage.value) params.append('cage_id', selectedCage.value);
  if (selectedInvestor.value) params.append('investor_id', selectedInvestor.value);

  window.open(route('reports.feeding.export-weekly') + '?' + params.toString(), '_blank');
};

const printReport = () => {
  window.print();
};

const getAdherenceColor = (rate: number) => {
  if (rate >= 90) return 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400';
  if (rate >= 70) return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400';
  return 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400';
};

const getVarianceColor = (variance: number) => {
  if (variance >= 0) return 'text-green-600 dark:text-green-400';
  return 'text-red-600 dark:text-red-400';
};

const formatDate = (dateStr: string) => {
  return new Date(dateStr).toLocaleDateString('en-US', { 
    month: 'short', 
    day: 'numeric',
    year: 'numeric' 
  });
};

/** Format date as MM/DD/YYYY for print table */
const formatDatePrint = (dateStr: string) => {
  const d = new Date(dateStr);
  const m = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  const y = d.getFullYear();
  return `${m}/${day}/${y}`;
};

/** Format time for print table (e.g. "7am", "12pm"). Handles "HH:mm" (24h) and "Ham/pm". */
const formatTimeSlot = (timeStr: string) => {
  if (!timeStr) return '';
  const s = String(timeStr).trim();
  const lower = s.toLowerCase();
  if (/^\d{1,2}(:\d{2})?\s*(am|pm)$/.test(lower)) return lower;
  const m24 = s.match(/^(\d{1,2}):(\d{2})$/);
  if (m24) {
    const hour = parseInt(m24[1], 10);
    const min = m24[2];
    const h12 = hour === 0 ? 12 : hour > 12 ? hour - 12 : hour;
    const ampm = hour >= 12 ? 'pm' : 'am';
    return min === '00' ? `${h12}${ampm}` : `${h12}:${min}${ampm}`;
  }
  const match = s.match(/(\d{1,2})(?::(\d{2}))?\s*(am|pm)?/i);
  if (match) {
    const hour = parseInt(match[1], 10);
    const min = match[2] ? `:${match[2]}` : '';
    const ampm = (match[3] || (hour >= 12 ? 'pm' : 'am')).toLowerCase();
    const h = hour > 12 ? hour - 12 : hour === 0 ? 12 : hour;
    return `${h}${min}${ampm}`;
  }
  return s;
};

/** Flatten report data into rows for print table: Date | Cage # | Feed Amount | Time | Time | Time */
interface PrintTableRow {
  date: string;
  cageNumber: number;
  feedAmountKg: number;
  time1: string;
  time2: string;
  time3: string;
}

const printTableRows = computed(() => {
  const rows: PrintTableRow[] = [];
  for (const cage of reportData.value) {
    for (const day of cage.daily_breakdown) {
      const times = (cage.feeding_times || []).slice(0, 3);
      rows.push({
        date: day.date,
        cageNumber: cage.cage_number,
        feedAmountKg: day.actual_amount,
        time1: times[0] ? formatTimeSlot(times[0]) : '',
        time2: times[1] ? formatTimeSlot(times[1]) : '',
        time3: times[2] ? formatTimeSlot(times[2]) : '',
      });
    }
  }
  rows.sort((a, b) => {
    const d = a.date.localeCompare(b.date);
    return d !== 0 ? d : a.cageNumber - b.cageNumber;
  });
  return rows;
});

/** For each date, how many rows share it (for rowSpan) */
const printTableDateSpans = computed(() => {
  const spans: Record<string, number> = {};
  for (const row of printTableRows.value) {
    spans[row.date] = (spans[row.date] || 0) + 1;
  }
  return spans;
});


const resetFilters = () => {
  setDefaultDates();
  selectedInvestor.value = '';
  selectedCage.value = '';
  fetchReport();
};

const openAddConsumptionDialog = (cageId: number, date: string, scheduledAmount: number) => {
  selectedCageForConsumption.value = cageId;
  selectedDateForConsumption.value = date;
  
  // Calculate day number (approximate - you may need to adjust based on your logic)
  const dateObj = new Date(date);
  const today = new Date();
  const diffTime = Math.abs(today.getTime() - dateObj.getTime());
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
  
  newConsumption.value = {
    day_number: diffDays || 1,
    feed_amount: scheduledAmount > 0 ? scheduledAmount.toString() : '',
    consumption_date: date,
    notes: ''
  };
  showAddConsumptionDialog.value = true;
};

const addConsumption = async () => {
  if (!selectedCageForConsumption.value || !newConsumption.value.feed_amount) {
    alert('Please fill in all required fields');
    return;
  }

  addingConsumption.value = true;
  try {
    await router.post(
      `/cages/${selectedCageForConsumption.value}/feed-consumptions`,
      newConsumption.value,
      {
        onSuccess: () => {
          showAddConsumptionDialog.value = false;
          fetchReport(); // Refresh the report
        },
        onError: (errors) => {
          const errorMessage = errors.message || Object.values(errors)[0] || 'Error adding feed consumption';
          alert(errorMessage);
        },
        onFinish: () => {
          addingConsumption.value = false;
        }
      }
    );
  } catch (error) {
    console.error('Error adding feed consumption:', error);
    alert('Error adding feed consumption');
    addingConsumption.value = false;
  }
};

onMounted(() => {
  setDefaultDates();
  fetchReport();
});
</script>

<template>
  <AppLayout>
    <Head title="Weekly Feeding Report" />

    <div class="flex flex-col gap-6 p-4 w-full print:p-2">
      <!-- Header -->
      <div class="flex items-center justify-between print:hidden">
        <div>
          <h1 class="text-3xl font-bold">Weekly Feeding Report</h1>
          <p class="text-muted-foreground mt-1">
            Track feeding schedules and consumption patterns
          </p>
        </div>
        <div class="flex gap-2">
          <Button variant="outline" @click="printReport">
            🖨️ Print
          </Button>
          <Button @click="exportToExcel">
            📊 Export Excel
          </Button>
        </div>
      </div>

      <!-- Filters -->
      <Card class="print:hidden">
        <CardHeader>
          <CardTitle>Filters</CardTitle>
          <CardDescription>Select date range and filters to generate the report</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
              <Label for="start-date">Start Date</Label>
              <Input
                id="start-date"
                type="date"
                v-model="startDate"
              />
            </div>
            <div>
              <Label for="end-date">End Date</Label>
              <Input
                id="end-date"
                type="date"
                v-model="endDate"
              />
            </div>
            <div>
              <Label for="investor">Investor</Label>
              <select 
                id="investor"
                v-model="selectedInvestor"
                class="w-full px-3 py-2 border border-input bg-background text-foreground rounded-md focus:outline-none focus:ring-2 focus:ring-ring focus:border-ring transition-colors"
              >
                <option value="">All Investors</option>
                <option 
                  v-for="investor in investors" 
                  :key="investor.id" 
                  :value="investor.id.toString()"
                >
                  {{ investor.name }}
                </option>
              </select>
            </div>
            <div>
              <Label for="cage">Cage</Label>
              <select 
                id="cage"
                v-model="selectedCage"
                class="w-full px-3 py-2 border border-input bg-background text-foreground rounded-md focus:outline-none focus:ring-2 focus:ring-ring focus:border-ring transition-colors"
              >
                <option value="">All Cages</option>
                <option 
                  v-for="cage in cages" 
                  :key="cage.id" 
                  :value="cage.id.toString()"
                >
                  {{ cage.label }}
                </option>
              </select>
            </div>
          </div>
          <div class="flex gap-2 mt-4">
            <Button @click="fetchReport" :disabled="loading">
              {{ loading ? 'Loading...' : 'Generate Report' }}
            </Button>
            <Button variant="outline" @click="resetFilters">
              Reset Filters
            </Button>
          </div>
        </CardContent>
      </Card>

      <!-- Summary: Report period + stats table -->
      <div v-if="reportData.length > 0">
        <div class="mb-2">
          <h2 class="text-base font-semibold mb-0.5">Report Period</h2>
          <p class="text-muted-foreground text-sm">
            {{ formatDate(period.start_date) }} - {{ formatDate(period.end_date) }}
            ({{ Math.round(period.days_count) }} days)
          </p>
        </div>

        <div class="overflow-x-auto rounded-md border border-border">
          <table class="w-full border-collapse text-sm">
            <thead>
              <tr class="bg-muted/50">
                <th class="px-4 py-2 text-left font-medium border-b border-border">Total Cages</th>
                <th class="px-4 py-2 text-left font-medium border-b border-border">With Schedules</th>
                <th class="px-4 py-2 text-left font-medium border-b border-border">Active Schedules</th>
                <th class="px-4 py-2 text-left font-medium border-b border-border">Total Scheduled</th>
                <th class="px-4 py-2 text-left font-medium border-b border-border">Total Consumed</th>
                <th class="px-4 py-2 text-left font-medium border-b border-border">Avg Adherence</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td class="px-4 py-2 border-b border-border font-semibold">{{ summary.total_cages }}</td>
                <td class="px-4 py-2 border-b border-border font-semibold text-blue-600">{{ summary.cages_with_schedules }}</td>
                <td class="px-4 py-2 border-b border-border font-semibold text-green-600">{{ summary.active_schedules }}</td>
                <td class="px-4 py-2 border-b border-border font-semibold">{{ summary.total_scheduled_feed.toFixed(2) }} kg</td>
                <td class="px-4 py-2 border-b border-border font-semibold">{{ summary.total_feed_consumed.toFixed(2) }} kg</td>
                <td class="px-4 py-2 border-b border-border font-semibold" :class="summary.average_adherence >= 90 ? 'text-green-600' : summary.average_adherence >= 70 ? 'text-yellow-600' : 'text-red-600'">
                  {{ summary.average_adherence }}%
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <Separator class="my-4" />

        <!-- Print-only: Weekly feeding table (compact) -->
        <div class="hidden print:block my-3">
          <h2 class="text-base font-bold mb-2">Weekly Feeding Report</h2>
          <div class="overflow-x-auto">
            <table class="w-full border-collapse text-xs" style="border: 1px solid #e5e7eb;">
              <thead>
                <tr style="background: #f3f4f6;">
                  <th style="border: 1px solid #e5e7eb; padding: 4px 8px; text-align: left;">Date</th>
                  <th style="border: 1px solid #e5e7eb; padding: 4px 8px; text-align: left;">Cage #</th>
                  <th style="border: 1px solid #e5e7eb; padding: 4px 8px; text-align: left;">Feed Amount</th>
                  <th style="border: 1px solid #e5e7eb; padding: 4px 8px; text-align: left;">Time</th>
                  <th style="border: 1px solid #e5e7eb; padding: 4px 8px; text-align: left;">Time</th>
                  <th style="border: 1px solid #e5e7eb; padding: 4px 8px; text-align: left;">Time</th>
                </tr>
              </thead>
              <tbody>
                <template v-for="(row, idx) in printTableRows" :key="`${row.date}-${row.cageNumber}-${idx}`">
                  <tr>
                    <td
                      v-if="idx === 0 || printTableRows[idx - 1].date !== row.date"
                      :rowspan="printTableDateSpans[row.date]"
                      style="border: 1px solid #e5e7eb; padding: 4px 8px; vertical-align: top;"
                    >
                      {{ formatDatePrint(row.date) }}
                    </td>
                    <td style="border: 1px solid #e5e7eb; padding: 4px 8px;">{{ row.cageNumber }}</td>
                    <td style="border: 1px solid #e5e7eb; padding: 4px 8px;">{{ row.feedAmountKg }}kg</td>
                    <td style="border: 1px solid #e5e7eb; padding: 4px 8px;">{{ row.time1 || '' }}</td>
                    <td style="border: 1px solid #e5e7eb; padding: 4px 8px;">{{ row.time2 || '' }}</td>
                    <td style="border: 1px solid #e5e7eb; padding: 4px 8px;">{{ row.time3 || '' }}</td>
                  </tr>
                </template>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Cage Reports (hidden when printing) -->
        <div class="space-y-4 print:hidden">
          <h2 class="text-2xl font-bold">Cage Details</h2>

          <Card v-for="cage in reportData" :key="cage.cage_id" class="overflow-hidden">
            <CardHeader class="bg-muted/50">
              <div class="flex items-start justify-between">
                <div class="flex-1">
                  <CardTitle class="flex items-center gap-2">
                    <Link
                      :href="route('cages.view', cage.cage_id)"
                      class="hover:underline text-primary focus:outline-none focus:underline"
                    >
                      Cage #{{ cage.cage_number }}
                    </Link>
                    <span v-if="!cage.has_schedule" class="px-2 py-1 text-xs rounded-md bg-secondary text-secondary-foreground">No Schedule</span>
                  </CardTitle>
                  <CardDescription class="mt-2">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2 mt-2">
                      <div><span class="font-medium">Investor:</span> {{ cage.investor_name }}</div>
                      <div><span class="font-medium">Feed Type:</span> {{ cage.feed_type }}</div>
                      <div><span class="font-medium">Fingerlings:</span> {{ cage.fingerlings_count }}</div>
                      <div><span class="font-medium">Schedule:</span> {{ cage.schedule_name }}</div>
                    </div>
                  </CardDescription>
                </div>
                <Button 
                  variant="ghost" 
                  size="sm"
                  @click="toggleCageExpansion(cage.cage_id)"
                  class="print:hidden"
                >
                  {{ isCageExpanded(cage.cage_id) ? '▼' : '▶' }}
                </Button>
              </div>
            </CardHeader>

            <CardContent class="pt-4">
              <!-- Summary Row -->
              <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-4">
                <div>
                  <div class="text-sm text-muted-foreground">Scheduled Total</div>
                  <div class="text-lg font-semibold">{{ cage.total_scheduled.toFixed(2) }} kg</div>
                </div>
                <div>
                  <div class="text-sm text-muted-foreground">Consumed Total</div>
                  <div class="text-lg font-semibold">{{ cage.total_consumed.toFixed(2) }} kg</div>
                </div>
                <div>
                  <div class="text-sm text-muted-foreground">Variance</div>
                  <div class="text-lg font-semibold" :class="getVarianceColor(cage.variance)">
                    {{ cage.variance >= 0 ? '+' : '' }}{{ cage.variance.toFixed(2) }} kg
                  </div>
                </div>
                <div>
                  <div class="text-sm text-muted-foreground">Adherence Rate</div>
                  <div class="text-lg font-semibold">
                    <span :class="['px-3 py-1 rounded-md text-sm font-medium', getAdherenceColor(cage.adherence_rate)]">
                      {{ cage.adherence_rate }}%
                    </span>
                  </div>
                </div>
                <div>
                  <div class="text-sm text-muted-foreground">Daily Average</div>
                  <div class="text-lg font-semibold">{{ cage.average_daily_consumption.toFixed(2) }} kg</div>
                </div>
              </div>

              <!-- Feeding Times -->
              <div v-if="cage.feeding_times.length > 0" class="mb-4">
                <div class="text-sm font-medium mb-1">Feeding Times:</div>
                <div class="flex gap-2 flex-wrap">
                  <span v-for="time in cage.feeding_times" :key="time" class="px-3 py-1 border border-border rounded-md text-sm">
                    🕐 {{ time }}
                  </span>
                </div>
              </div>

              <!-- Daily Breakdown (Expandable) -->
              <div v-if="isCageExpanded(cage.cage_id)" class="mt-4">
                <Separator class="mb-4" />
                <h4 class="font-semibold mb-3">Daily Breakdown</h4>
                <div class="overflow-x-auto">
                  <table class="w-full text-sm">
                    <thead class="bg-muted">
                      <tr>
                        <th class="px-4 py-2 text-left">Date</th>
                        <th class="px-4 py-2 text-left">Day</th>
                        <th class="px-4 py-2 text-right">Scheduled (kg)</th>
                        <th class="px-4 py-2 text-right">Actual (kg)</th>
                        <th class="px-4 py-2 text-right">Variance (kg)</th>
                        <th class="px-4 py-2 text-center">Adherence</th>
                        <th class="px-4 py-2 text-left">Notes</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="day in cage.daily_breakdown" :key="day.date" class="border-b">
                        <td class="px-4 py-2">{{ formatDate(day.date) }}</td>
                        <td class="px-4 py-2">{{ day.day_name }}</td>
                        <td class="px-4 py-2 text-right">{{ day.scheduled_amount.toFixed(2) }}</td>
                        <td class="px-4 py-2 text-right font-medium">
                          <div class="flex items-center justify-end gap-2">
                            <span>{{ day.actual_amount.toFixed(2) }}</span>
                            <Button
                              v-if="day.actual_amount === 0 && !isInvestor"
                              variant="ghost"
                              size="sm"
                              @click="openAddConsumptionDialog(cage.cage_id, day.date, day.scheduled_amount)"
                              class="h-6 px-2 text-xs print:hidden"
                              title="Add consumption for this day"
                            >
                              +
                            </Button>
                          </div>
                        </td>
                        <td class="px-4 py-2 text-right" :class="getVarianceColor(day.variance)">
                          {{ day.variance >= 0 ? '+' : '' }}{{ day.variance.toFixed(2) }}
                        </td>
                        <td class="px-4 py-2 text-center">
                          <span :class="['px-2 py-1 rounded text-xs font-medium inline-block', getAdherenceColor(day.adherence_rate)]">
                            {{ day.adherence_rate }}%
                          </span>
                        </td>
                        <td class="px-4 py-2 text-muted-foreground">{{ day.notes || '-' }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>

      <!-- Empty State -->
      <Card v-else-if="!loading">
        <CardContent class="py-12 text-center">
          <div class="text-muted-foreground mb-4">
            <div class="text-4xl mb-2">📊</div>
            <p class="text-lg">No data available for the selected period</p>
            <p class="text-sm mt-2">Try adjusting your filters or date range</p>
          </div>
        </CardContent>
      </Card>

      <!-- Loading State -->
      <Card v-else>
        <CardContent class="py-12 text-center">
          <div class="text-muted-foreground">
            <div class="text-4xl mb-2">⏳</div>
            <p class="text-lg">Loading report...</p>
          </div>
        </CardContent>
      </Card>

      <!-- Add Consumption Dialog -->
      <Dialog v-model:open="showAddConsumptionDialog">
        <DialogContent class="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>Add Feed Consumption</DialogTitle>
          </DialogHeader>
          <div class="space-y-4">
            <div>
              <Label for="consumption_date">Date</Label>
              <Input
                id="consumption_date"
                v-model="newConsumption.consumption_date"
                type="date"
                required
              />
            </div>
            <div>
              <Label for="day_number">Day Number</Label>
              <Input
                id="day_number"
                v-model="newConsumption.day_number"
                type="number"
                min="1"
                placeholder="Enter day number"
                required
              />
            </div>
            <div>
              <Label for="feed_amount">Actual (kg)</Label>
              <Input
                id="feed_amount"
                v-model="newConsumption.feed_amount"
                type="number"
                step="0.01"
                min="0"
                placeholder="Enter actual feed amount consumed"
                required
              />
            </div>
            <div>
              <Label for="notes">Notes (Optional)</Label>
              <Input
                id="notes"
                v-model="newConsumption.notes"
                placeholder="Enter any notes"
              />
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" @click="showAddConsumptionDialog = false">Cancel</Button>
            <Button @click="addConsumption" :disabled="addingConsumption">
              {{ addingConsumption ? 'Adding...' : 'Add Consumption' }}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  </AppLayout>
</template>

<style scoped>
@media print {
  .print\:hidden {
    display: none !important;
  }
  
  .print\:p-2 {
    padding: 0.5rem !important;
  }
}
</style>
