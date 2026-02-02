<script setup lang="ts">
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import { ref, onMounted, computed, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import Button from '@/components/ui/button/Button.vue';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import Input from '@/components/ui/input/Input.vue';
import Label from '@/components/ui/label/Label.vue';
import Dialog from '@/components/ui/dialog/Dialog.vue';
import DialogContent from '@/components/ui/dialog/DialogContent.vue';
import DialogHeader from '@/components/ui/dialog/DialogHeader.vue';
import DialogTitle from '@/components/ui/dialog/DialogTitle.vue';
import DialogFooter from '@/components/ui/dialog/DialogFooter.vue';
import { useCageStore } from '@/Stores/CageStore';
import { useFeedTypeStore } from '@/Stores/FeedTypeStore';
import { useInvestorStore } from '@/Stores/InvestorStore';
import { type SharedData } from '@/types';
import Swal from 'sweetalert2';

const page = usePage<SharedData>();
const userRole = computed(() => page.props.auth?.user?.role || 'farmer');
const isInvestor = computed(() => userRole.value === 'investor');
const isAdmin = computed(() => userRole.value === 'admin');

const store = useCageStore();
const feedTypeStore = useFeedTypeStore();
const investorStore = useInvestorStore();

// Harvest anticipation from backend
interface HarvestAnticipation {
  estimated_harvest_date: string | null;
  days_until_harvest: number | null;
  current_avg_weight_g: number;
  target_weight_g: number;
  growth_rate_used_g_per_day: number;
  is_ready: boolean;
  latest_sampling_date: string | null;
}

// Get cage data from Inertia props
const props = defineProps<{
  cage: Cage;
  feedConsumptions: {
    data: FeedConsumption[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
  };
  feedingSchedule?: FeedingSchedule | null;
  harvestAnticipation?: HarvestAnticipation | null;
  errors?: any;
  flash?: any;
}>();

const cageId = props.cage.id;

const breadcrumbs = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Cages', href: '/cages' },
  { title: `View Cage #${cageId}`, href: `/cages/${cageId}/view` },
];

// Types
interface Cage {
  id: number;
  number_of_fingerlings: number;
  feed_types_id?: number;
  investor_id?: number;
  farmer_id?: number | null;
  feed_type?: {
    id: number;
    feed_type: string;
    brand: string;
  };
  investor?: {
    name: string;
  };
}

interface FeedConsumption {
  id: number;
  day_number: number;
  feed_amount: string;
  consumption_date: string;
  notes?: string;
}

interface FeedingSchedule {
  id: number;
  schedule_name?: string;
  total_daily_amount?: number;
  feeding_amount_1?: number;
  feeding_amount_2?: number;
  feeding_amount_3?: number;
  feeding_amount_4?: number;
  feeding_time_1?: string;
  feeding_time_2?: string;
  feeding_time_3?: string;
  feeding_time_4?: string;
  frequency?: string;
  notes?: string;
  feeding_times?: string[];
  feeding_amounts?: number[];
}

interface FeedType {
  id: number;
  feed_type: string;
}

interface Investor {
  id: number;
  name: string;
}

interface Farmer {
  id: number;
  name: string;
}

// Reactive data
const cage = ref<Cage>(props.cage);
const feedConsumptions = ref(props.feedConsumptions);
const feedingSchedule = ref<FeedingSchedule | null>(props.feedingSchedule || null);
const showAddDialog = ref(false);
const showEditDialog = ref(false);
const showEditCageDialog = ref(false);
const editingConsumption = ref<FeedConsumption | null>(null);
const editCage = ref<Cage | null>(null);
const loading = ref(false);
const farmersForEdit = ref<Farmer[]>([]);
const currentPage = ref(props.feedConsumptions.current_page || 1);
const perPage = ref(props.feedConsumptions.per_page || 10);

// Form data for new consumption
const newConsumption = ref({
  day_number: 1,
  feed_amount: '',
  consumption_date: new Date().toISOString().split('T')[0],
  notes: ''
});

// Form data for editing
const editConsumption = ref({
  feed_amount: '',
  consumption_date: '',
  notes: ''
});

// Computed properties
const totalFeedConsumed = computed(() => {
  return feedConsumptions.value.data.reduce((total, consumption) => {
    return total + parseFloat(consumption.feed_amount || '0');
  }, 0);
});

const averageDailyFeed = computed(() => {
  if (feedConsumptions.value.data.length === 0) return 0;
  return totalFeedConsumed.value / feedConsumptions.value.data.length;
});

const pagination = computed(() => ({
  current_page: feedConsumptions.value.current_page,
  last_page: feedConsumptions.value.last_page,
  per_page: feedConsumptions.value.per_page,
  total: feedConsumptions.value.total,
  from: feedConsumptions.value.from,
  to: feedConsumptions.value.to,
}));

// Get feeding times and amounts from schedule
const feedingTimes = computed(() => {
  if (!feedingSchedule.value) return [];
  if (feedingSchedule.value.feeding_times) {
    return feedingSchedule.value.feeding_times;
  }
  const times = [];
  if (feedingSchedule.value.feeding_time_1) times.push(formatTime(feedingSchedule.value.feeding_time_1));
  if (feedingSchedule.value.feeding_time_2) times.push(formatTime(feedingSchedule.value.feeding_time_2));
  if (feedingSchedule.value.feeding_time_3) times.push(formatTime(feedingSchedule.value.feeding_time_3));
  if (feedingSchedule.value.feeding_time_4) times.push(formatTime(feedingSchedule.value.feeding_time_4));
  return times;
});

const feedingAmounts = computed(() => {
  if (!feedingSchedule.value) return [];
  if (feedingSchedule.value.feeding_amounts) {
    return feedingSchedule.value.feeding_amounts;
  }
  return [
    feedingSchedule.value.feeding_amount_1 || 0,
    feedingSchedule.value.feeding_amount_2 || 0,
    feedingSchedule.value.feeding_amount_3 || 0,
    feedingSchedule.value.feeding_amount_4 || 0,
  ].filter(amount => amount > 0);
});

const getFrequencyLabel = (frequency: string | undefined): string => {
  const labels: Record<string, string> = {
    'daily': 'Daily',
    'twice_daily': 'Twice Daily',
    'thrice_daily': 'Thrice Daily',
    'four_times_daily': 'Four Times Daily',
  };
  return labels[frequency || ''] || frequency || 'Custom';
};

const formatTime = (time: string | undefined): string => {
  if (!time) return '';
  // Handle datetime strings (ISO format or with T)
  if (time.includes('T') || time.includes(' ')) {
    try {
      const date = new Date(time);
      if (!isNaN(date.getTime())) {
        return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: false });
      }
    } catch (e) {
      // If parsing fails, try to extract time directly
      const match = time.match(/(\d{2}):(\d{2})/);
      if (match) return match[0];
    }
  }
  // If already in H:i format, return as is
  if (/^\d{2}:\d{2}$/.test(time)) {
    return time;
  }
  return time;
};

const feedTypes = computed<FeedType[]>(() => {
  const data = feedTypeStore.feedTypes as any;
  return data?.data || [];
});

const investors = computed<Investor[]>(() => investorStore.investorsSelect as Investor[]);

// Get feeding guide amount from schedule
const getFeedingGuide = (): string => {
  if (!feedingSchedule.value) return '-';
  
  // Try to use total_daily_amount accessor first
  let total = feedingSchedule.value.total_daily_amount;
  
  // If not available, calculate from individual amounts
  if (!total || total === 0) {
    total = (parseFloat(String(feedingSchedule.value.feeding_amount_1 || 0)) +
             parseFloat(String(feedingSchedule.value.feeding_amount_2 || 0)) +
             parseFloat(String(feedingSchedule.value.feeding_amount_3 || 0)) +
             parseFloat(String(feedingSchedule.value.feeding_amount_4 || 0)));
  }
  
  return total > 0 ? `${Number(total).toFixed(2)} kg` : '-';
};

// Helper function to format date in a human-readable way
const formatDate = (dateString: string | null | undefined): string => {
  if (!dateString) return '-';
  
  try {
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return '-';
    
    // Format as "Jan 15, 2024"
    const options: Intl.DateTimeFormatOptions = {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    };
    
    return date.toLocaleDateString('en-US', options);
  } catch (error) {
    return '-';
  }
};

// Methods
const loadCageData = async () => {
  // Cage data is already loaded from props
  // This method is kept for future use if needed
};

// Feed consumptions are now loaded from props, no need for separate loading function

const goToPage = (page: number) => {
  if (page < 1 || (pagination.value && page > pagination.value.last_page)) {
    return;
  }
  currentPage.value = page;
  router.get(`/cages/${cageId}/view`, {
    page,
    per_page: perPage.value,
  }, {
    preserveState: true,
    preserveScroll: true,
    only: ['feedConsumptions'],
    onSuccess: () => {
      // Force update the reactive ref after successful navigation
      feedConsumptions.value = props.feedConsumptions;
    },
  });
};

const addFeedConsumption = async () => {
  loading.value = true;
  try {
    await router.post(`/cages/${cageId}/feed-consumptions`, newConsumption.value, {
      onSuccess: () => {
        showAddDialog.value = false;
        resetNewConsumption();
        // Refresh the page to get updated data
        router.reload();
      },
      onError: (errors) => {
        const errorMessage = errors.message || Object.values(errors)[0] || 'Error adding feed consumption';
        alert(errorMessage);
      },
      onFinish: () => {
        loading.value = false;
      }
    });
  } catch (error) {
    console.error('Error adding feed consumption:', error);
    alert('Error adding feed consumption');
    loading.value = false;
  }
};

const editFeedConsumption = async () => {
  if (!editingConsumption.value) return;
  
  loading.value = true;
  try {
    await router.put(`/cages/${cageId}/feed-consumptions/${editingConsumption.value.id}`, editConsumption.value, {
      onSuccess: () => {
        showEditDialog.value = false;
        editingConsumption.value = null;
        // Refresh the page to get updated data
        router.reload();
      },
      onError: (errors) => {
        const errorMessage = errors.message || Object.values(errors)[0] || 'Error updating feed consumption';
        alert(errorMessage);
      },
      onFinish: () => {
        loading.value = false;
      }
    });
  } catch (error) {
    console.error('Error updating feed consumption:', error);
    alert('Error updating feed consumption');
    loading.value = false;
  }
};

const deleteFeedConsumption = async (consumption: FeedConsumption) => {
  if (!confirm('Are you sure you want to delete this feed consumption record?')) {
    return;
  }

  try {
    await router.delete(`/cages/${cageId}/feed-consumptions/${consumption.id}`, {
      onSuccess: () => {
        // Refresh the page to get updated data
        router.reload();
      },
      onError: (errors) => {
        const errorMessage = errors.message || Object.values(errors)[0] || 'Error deleting feed consumption';
        alert(errorMessage);
      }
    });
  } catch (error) {
    console.error('Error deleting feed consumption:', error);
    alert('Error deleting feed consumption');
  }
};

const openEditDialog = (consumption: FeedConsumption) => {
  editingConsumption.value = consumption;
  
  // Convert date to YYYY-MM-DD format for date input
  let formattedDate = '';
  if (consumption.consumption_date) {
    try {
      const date = new Date(consumption.consumption_date);
      if (!isNaN(date.getTime())) {
        // Format as YYYY-MM-DD for date input
        formattedDate = date.toISOString().split('T')[0];
      }
    } catch (e) {
      console.error('Error parsing date:', e);
    }
  }
  
  editConsumption.value = {
    feed_amount: consumption.feed_amount,
    consumption_date: formattedDate,
    notes: consumption.notes || ''
  };
  showEditDialog.value = true;
};

const resetNewConsumption = () => {
  // Calculate the next day number based on existing consumptions
  const maxDay = feedConsumptions.value.data.length > 0 
    ? Math.max(...feedConsumptions.value.data.map(c => c.day_number))
    : 0;
    
  newConsumption.value = {
    day_number: maxDay + 1,
    feed_amount: '',
    consumption_date: new Date().toISOString().split('T')[0],
    notes: ''
  };
};

// Edit cage functions
const openEditCageDialog = () => {
  // Prevent investors from editing cages
  if (isInvestor.value) {
    return;
  }
  editCage.value = { ...cage.value };
  if (editCage.value.investor_id) {
    fetchFarmersForEdit(editCage.value.investor_id);
  }
  showEditCageDialog.value = true;
};

async function fetchFarmersForEdit(investorId: number | null) {
  if (!investorId) {
    farmersForEdit.value = [];
    return;
  }
  try {
    const response = await fetch(`/users/farmers-by-investor?investor_id=${investorId}`);
    if (response.ok) {
      farmersForEdit.value = await response.json();
    }
  } catch (error) {
    console.error('Error fetching farmers:', error);
    farmersForEdit.value = [];
  }
}

async function updateCageHandler() {
  if (editCage.value && editCage.value.id) {
    try {
      await store.updateCage(editCage.value.id, {
        number_of_fingerlings: editCage.value.number_of_fingerlings,
        feed_types_id: editCage.value.feed_types_id,
        investor_id: editCage.value.investor_id,
        farmer_id: editCage.value.farmer_id,
      });
      showEditCageDialog.value = false;
      editCage.value = null;
      Swal.fire({ icon: 'success', title: 'Cage updated successfully!' });
      // Refresh the page to get updated data
      router.reload();
    } catch (error: any) {
      Swal.fire({ icon: 'error', title: 'Error', text: error?.message || 'Failed to update cage.' });
    }
  }
}

// Pagination helper
const getPageNumbers = () => {
  if (!pagination.value) return [];
  const current = pagination.value.current_page;
  const last = pagination.value.last_page;
  const pages = [];
  
  // Always show first page
  pages.push(1);
  
  // Show pages around current page
  for (let i = Math.max(2, current - 1); i <= Math.min(last - 1, current + 1); i++) {
    if (i > 1 && i < last) {
      pages.push(i);
    }
  }
  
  // Always show last page if different from first
  if (last > 1) {
    pages.push(last);
  }
  
  return [...new Set(pages)].sort((a, b) => a - b);
};

// Watch for prop changes to update reactive data
watch(() => props.feedConsumptions, (newValue) => {
  feedConsumptions.value = newValue;
  currentPage.value = newValue.current_page || 1;
}, { deep: true });

// Lifecycle
onMounted(() => {
  // Cage data and feed consumptions are already loaded from props
  feedTypeStore.fetchFeedTypes();
  investorStore.fetchInvestorsSelect();
  currentPage.value = props.feedConsumptions.current_page || 1;
});
</script>

<template>
  <Head :title="`View Cage #${cageId}`" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="max-w-7xl mx-auto p-6 space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <h1 class="text-3xl font-bold">Cage #{{ cageId }}</h1>
        <div class="flex gap-2">
          <Button v-if="!isInvestor" @click="openEditCageDialog">Edit Cage</Button>
          <Link href="/cages">
            <Button variant="secondary">Back to Cages</Button>
          </Link>
        </div>
      </div>

      <!-- Cage Information -->
      <Card v-if="cage">
        <CardHeader>
          <CardTitle>Cage Information</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <Label>Number of Fingerlings</Label>
              <p class="text-2xl font-bold text-primary">{{ cage.number_of_fingerlings?.toLocaleString() }}</p>
            </div>
            <div>
              <Label>Feed Type</Label>
              <p class="text-lg">{{ cage.feed_type?.feed_type || 'N/A' }}</p>
            </div>
            <div>
              <Label>Investor</Label>
              <p class="text-lg">{{ cage.investor?.name }}</p>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Harvest Anticipation -->
      <Card v-if="harvestAnticipation && harvestAnticipation.latest_sampling_date">
        <CardHeader>
          <CardTitle>Harvest Anticipation</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="p-4 rounded-lg" :class="harvestAnticipation.is_ready ? 'bg-green-50 dark:bg-green-900/20' : 'bg-amber-50 dark:bg-amber-900/20'">
              <p class="text-sm text-muted-foreground">Status</p>
              <p class="text-lg font-semibold" :class="harvestAnticipation.is_ready ? 'text-green-600' : 'text-amber-600'">
                {{ harvestAnticipation.is_ready ? 'Ready for harvest' : harvestAnticipation.days_until_harvest + ' days to harvest' }}
              </p>
            </div>
            <div class="p-4 rounded-lg bg-gray-50 dark:bg-gray-800">
              <p class="text-sm text-muted-foreground">Current avg. weight</p>
              <p class="text-lg font-semibold">{{ harvestAnticipation.current_avg_weight_g }}g</p>
              <p class="text-xs text-muted-foreground">from latest sampling ({{ formatDate(harvestAnticipation.latest_sampling_date) }})</p>
            </div>
            <div class="p-4 rounded-lg bg-gray-50 dark:bg-gray-800">
              <p class="text-sm text-muted-foreground">Target harvest weight</p>
              <p class="text-lg font-semibold">{{ harvestAnticipation.target_weight_g }}g</p>
            </div>
            <div class="p-4 rounded-lg bg-gray-50 dark:bg-gray-800">
              <p class="text-sm text-muted-foreground">Estimated harvest date</p>
              <p class="text-lg font-semibold">{{ harvestAnticipation.estimated_harvest_date ? formatDate(harvestAnticipation.estimated_harvest_date) : '–' }}</p>
              <p v-if="!harvestAnticipation.is_ready && harvestAnticipation.growth_rate_used_g_per_day" class="text-xs text-muted-foreground">~{{ harvestAnticipation.growth_rate_used_g_per_day }}g/day growth</p>
            </div>
          </div>
        </CardContent>
      </Card>
      <Card v-else-if="harvestAnticipation">
        <CardHeader>
          <CardTitle>Harvest Anticipation</CardTitle>
        </CardHeader>
        <CardContent>
          <p class="text-muted-foreground">No sampling data for this cage yet. Add a sampling with samples to see estimated harvest date.</p>
        </CardContent>
      </Card>

      <!-- Main Content Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Feed Consumption Management (2 columns) -->
        <div class="lg:col-span-2">
          <Card>
        <CardHeader>
          <div class="flex items-center justify-between">
            <CardTitle>Daily Feed Consumption</CardTitle>
            <Button @click="showAddDialog = true">Add Feed Consumption</Button>
          </div>
        </CardHeader>
        <CardContent>
          <!-- Summary Stats -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
              <h3 class="text-sm font-medium text-blue-600 dark:text-blue-400">Total Days Recorded</h3>
              <p class="text-2xl font-bold text-blue-700 dark:text-blue-300">{{ pagination.total }}</p>
            </div>
            <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
              <h3 class="text-sm font-medium text-green-600 dark:text-green-400">Total Feed Consumed</h3>
              <p class="text-2xl font-bold text-green-700 dark:text-green-300">{{ totalFeedConsumed.toFixed(2) }} kg</p>
            </div>
            <div class="p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
              <h3 class="text-sm font-medium text-purple-600 dark:text-purple-400">Average Daily Feed</h3>
              <p class="text-2xl font-bold text-purple-700 dark:text-purple-300">{{ averageDailyFeed.toFixed(2) }} kg</p>
            </div>
          </div>

          <!-- Feed Consumption Table -->
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
              <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Day</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Date</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Feed Amount (kg)</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Feeding Guide</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Notes</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                </tr>
              </thead>
              <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                <tr v-for="consumption in feedConsumptions.data" :key="consumption.id">
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">{{ consumption.day_number }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm">{{ formatDate(consumption.consumption_date) }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm">{{ consumption.feed_amount }} kg</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm">{{ getFeedingGuide() }}</td>
                  <td class="px-6 py-4 text-sm">{{ consumption.notes || '-' }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm">
                    <div class="flex space-x-2">
                      <Button variant="outline" size="sm" @click="openEditDialog(consumption)">Edit</Button>
                      <Button variant="destructive" size="sm" @click="deleteFeedConsumption(consumption)">Delete</Button>
                    </div>
                  </td>
                </tr>
                <tr v-if="feedConsumptions.data.length === 0">
                  <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                    No feed consumption records found. Add the first record to get started.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div v-if="pagination.last_page > 1" class="mt-4 flex items-center justify-between">
            <div class="text-sm text-gray-700 dark:text-gray-300">
              Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} results
            </div>
            <div class="flex gap-2">
              <Button 
                variant="outline" 
                size="sm" 
                @click="goToPage(pagination.current_page - 1)"
                :disabled="pagination.current_page === 1"
              >
                Previous
              </Button>
              <div class="flex gap-1">
                <Button
                  v-for="page in getPageNumbers()"
                  :key="page"
                  variant="outline"
                  size="sm"
                  @click="goToPage(page)"
                  :class="{ 'bg-primary text-primary-foreground': page === pagination.current_page }"
                >
                  {{ page }}
                </Button>
              </div>
              <Button 
                variant="outline" 
                size="sm" 
                @click="goToPage(pagination.current_page + 1)"
                :disabled="pagination.current_page === pagination.last_page"
              >
                Next
              </Button>
            </div>
          </div>
        </CardContent>
      </Card>
        </div>

        <!-- Feeding Schedule Details (1 column) -->
        <div class="lg:col-span-1">
          <Card>
            <CardHeader>
              <CardTitle>Feeding Schedule</CardTitle>
            </CardHeader>
            <CardContent>
              <div v-if="feedingSchedule" class="space-y-6">
                <!-- Schedule Name -->
                <div v-if="feedingSchedule.schedule_name">
                  <Label class="text-sm font-medium">Schedule Name</Label>
                  <p class="text-lg font-semibold">{{ feedingSchedule.schedule_name }}</p>
                </div>

                <!-- Daily Feeding Amount -->
                <div>
                  <Label class="text-sm font-medium text-blue-600 dark:text-blue-400">Daily Feeding Amount</Label>
                  <div class="mt-2 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <p class="text-2xl font-bold text-blue-700 dark:text-blue-300">
                      {{ getFeedingGuide() }}
                    </p>
                    <p class="text-xs text-blue-600 dark:text-blue-400 mt-1">Total feed quantity per day</p>
                  </div>
                </div>

                <!-- Feeding Times and Amounts -->
                <div v-if="feedingTimes.length > 0">
                  <Label class="text-sm font-medium text-green-600 dark:text-green-400 mb-3 block">Feeding Times</Label>
                  <div class="space-y-3">
                    <div 
                      v-for="(time, index) in feedingTimes" 
                      :key="index"
                      class="p-3 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800"
                    >
                      <div class="flex items-center justify-between">
                        <div>
                          <p class="font-medium text-lg">{{ time }}</p>
                          <p class="text-xs text-gray-600 dark:text-gray-400">Feeding {{ index + 1 }}</p>
                        </div>
                        <div class="text-right">
                          <p class="font-semibold text-green-700 dark:text-green-300">
                            {{ feedingAmounts[index] ? `${Number(feedingAmounts[index]).toFixed(2)} kg` : '-' }}
                          </p>
                        </div>
                      </div>
                    </div>
                  </div>
                  <p class="text-xs text-gray-600 dark:text-gray-400 mt-2">
                    Frequency: <span class="font-medium">{{ getFrequencyLabel(feedingSchedule.frequency) }}</span>
                  </p>
                </div>

                <!-- Notes -->
                <div v-if="feedingSchedule.notes">
                  <Label class="text-sm font-medium">Notes</Label>
                  <p class="text-sm text-gray-600 dark:text-gray-400 mt-1 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    {{ feedingSchedule.notes }}
                  </p>
                </div>
              </div>
              <div v-else class="text-center py-8">
                <p class="text-gray-500 dark:text-gray-400">No active feeding schedule found</p>
                <p class="text-sm text-gray-400 dark:text-gray-500 mt-2">Create a feeding schedule to see details here</p>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>

    <!-- Add Feed Consumption Dialog -->
    <Dialog v-model:open="showAddDialog">
      <DialogContent class="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Add Feed Consumption</DialogTitle>
        </DialogHeader>
        <div class="space-y-4">
          <div>
            <Label for="day_number">Day Number</Label>
            <Input
              id="day_number"
              v-model="newConsumption.day_number"
              type="number"
              min="1"
              placeholder="Enter day number"
            />
          </div>
          <div>
            <Label for="feed_amount">Feed Amount (kg)</Label>
            <Input
              id="feed_amount"
              v-model="newConsumption.feed_amount"
              type="number"
              step="0.01"
              min="0"
              placeholder="Enter feed amount"
            />
          </div>
          <div>
            <Label for="consumption_date">Date</Label>
            <Input
              id="consumption_date"
              v-model="newConsumption.consumption_date"
              type="date"
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
          <Button variant="outline" @click="showAddDialog = false">Cancel</Button>
          <Button @click="addFeedConsumption" :disabled="loading">
            {{ loading ? 'Adding...' : 'Add Consumption' }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <!-- Edit Feed Consumption Dialog -->
    <Dialog v-model:open="showEditDialog">
      <DialogContent class="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Edit Feed Consumption - Day {{ editingConsumption?.day_number }}</DialogTitle>
        </DialogHeader>
        <div class="space-y-4">
          <div>
            <Label for="edit_feed_amount">Feed Amount (kg)</Label>
            <Input
              id="edit_feed_amount"
              v-model="editConsumption.feed_amount"
              type="number"
              step="0.01"
              min="0"
              placeholder="Enter feed amount"
            />
          </div>
          <div>
            <Label for="edit_consumption_date">Date</Label>
            <Input
              id="edit_consumption_date"
              v-model="editConsumption.consumption_date"
              type="date"
            />
          </div>
          <div>
            <Label for="edit_notes">Notes (Optional)</Label>
            <Input
              id="edit_notes"
              v-model="editConsumption.notes"
              placeholder="Enter any notes"
            />
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" @click="showEditDialog = false">Cancel</Button>
          <Button @click="editFeedConsumption" :disabled="loading">
            {{ loading ? 'Updating...' : 'Update Consumption' }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <!-- Edit Cage Dialog -->
    <Dialog v-model:open="showEditCageDialog" v-if="editCage">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Edit Cage</DialogTitle>
        </DialogHeader>
        <form @submit.prevent="updateCageHandler" class="flex flex-col gap-4 mt-2">
          <div class="flex flex-col gap-1">
            <label for="edit_investor" class="text-sm font-medium">Investor</label>
            <select 
              id="edit_investor"
              v-model="editCage.investor_id" 
              @change="fetchFarmersForEdit(editCage.investor_id)"
              class="input w-full rounded border p-2" 
              required
            >
              <option v-for="inv in investors" :key="inv.id" :value="inv.id" class="bg-background text-foreground">{{ inv.name }}</option>
            </select>
          </div>
          <div class="flex flex-col gap-1">
            <label for="edit_fingerlings" class="text-sm font-medium">Number of Fingerlings</label>
            <Input id="edit_fingerlings" v-model.number="editCage.number_of_fingerlings" type="number" placeholder="Number of Fingerlings" required />
          </div>
          <div class="flex flex-col gap-1">
            <label for="edit_feedType" class="text-sm font-medium">Feed Type</label>
            <select id="edit_feedType" v-model.number="editCage.feed_types_id" class="input w-full rounded border p-2" required>
              <option v-for="f in feedTypes" :key="f.id" :value="f.id" class="bg-background text-foreground">{{ f.feed_type }}</option>
            </select>
          </div>
          <div v-if="isAdmin" class="flex flex-col gap-1">
            <label for="edit_farmer" class="text-sm font-medium">Assign Farmer (Optional)</label>
            <select id="edit_farmer" v-model.number="editCage.farmer_id" class="input w-full rounded border p-2">
              <option :value="null">No farmer assigned</option>
              <option v-for="farmer in farmersForEdit" :key="farmer.id" :value="farmer.id" class="bg-background text-foreground">
                {{ farmer.name }}
              </option>
            </select>
            <p class="text-xs text-gray-500 mt-1">Only farmers belonging to the selected investor are shown</p>
          </div>
          <DialogFooter class="flex justify-end gap-2 mt-4">
            <Button type="button" variant="secondary" @click="showEditCageDialog = false">Cancel</Button>
            <Button type="submit" variant="default">Update</Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template> 