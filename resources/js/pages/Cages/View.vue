<script setup lang="ts">
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
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

// Get cage data from Inertia props
const props = defineProps<{
  cage: Cage;
  feedConsumptions: FeedConsumption[];
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
  feed_type?: {
    name: string;
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

// Reactive data
const cage = ref<Cage>(props.cage);
const feedConsumptions = ref<FeedConsumption[]>(props.feedConsumptions);
const showAddDialog = ref(false);
const showEditDialog = ref(false);
const editingConsumption = ref<FeedConsumption | null>(null);
const loading = ref(false);

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
  return feedConsumptions.value.reduce((total, consumption) => {
    return total + parseFloat(consumption.feed_amount || '0');
  }, 0);
});

const averageDailyFeed = computed(() => {
  if (feedConsumptions.value.length === 0) return 0;
  return totalFeedConsumed.value / feedConsumptions.value.length;
});

// Methods
const loadCageData = async () => {
  // Cage data is already loaded from props
  // This method is kept for future use if needed
};

// Feed consumptions are now loaded from props, no need for separate loading function

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
  editConsumption.value = {
    feed_amount: consumption.feed_amount,
    consumption_date: consumption.consumption_date,
    notes: consumption.notes || ''
  };
  showEditDialog.value = true;
};

const resetNewConsumption = () => {
  // Calculate the next day number based on existing consumptions
  const maxDay = feedConsumptions.value.length > 0 
    ? Math.max(...feedConsumptions.value.map(c => c.day_number))
    : 0;
    
  newConsumption.value = {
    day_number: maxDay + 1,
    feed_amount: '',
    consumption_date: new Date().toISOString().split('T')[0],
    notes: ''
  };
};

// Lifecycle
onMounted(() => {
  // Cage data and feed consumptions are already loaded from props
});
</script>

<template>
  <Head :title="`View Cage #${cageId}`" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="max-w-6xl mx-auto p-6 space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <h1 class="text-3xl font-bold">Cage #{{ cageId }}</h1>
        <Link href="/cages">
          <Button variant="secondary">Back to Cages</Button>
        </Link>
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
              <p class="text-lg">{{ cage.feed_type?.name }}</p>
            </div>
            <div>
              <Label>Investor</Label>
              <p class="text-lg">{{ cage.investor?.name }}</p>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Feed Consumption Management -->
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
              <p class="text-2xl font-bold text-blue-700 dark:text-blue-300">{{ feedConsumptions.length }}</p>
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
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Notes</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                </tr>
              </thead>
              <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                <tr v-for="consumption in feedConsumptions" :key="consumption.id">
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">{{ consumption.day_number }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm">{{ consumption.consumption_date }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm">{{ consumption.feed_amount }} kg</td>
                  <td class="px-6 py-4 text-sm">{{ consumption.notes || '-' }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm">
                    <div class="flex space-x-2">
                      <Button variant="outline" size="sm" @click="openEditDialog(consumption)">Edit</Button>
                      <Button variant="destructive" size="sm" @click="deleteFeedConsumption(consumption)">Delete</Button>
                    </div>
                  </td>
                </tr>
                <tr v-if="feedConsumptions.length === 0">
                  <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                    No feed consumption records found. Add the first record to get started.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>
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
  </AppLayout>
</template> 