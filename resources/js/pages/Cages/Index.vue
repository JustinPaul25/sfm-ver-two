<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { useCageStore } from '@/Stores/CageStore';
import { useFeedTypeStore } from '@/Stores/FeedTypeStore';
import { useInvestorStore } from '@/Stores/InvestorStore';
import { Head, Link } from '@inertiajs/vue3';
import Button from '@/components/ui/button/Button.vue';
import Input from '@/components/ui/input/Input.vue';
import Dialog from '@/components/ui/dialog/Dialog.vue';
import DialogTrigger from '@/components/ui/dialog/DialogTrigger.vue';
import DialogContent from '@/components/ui/dialog/DialogContent.vue';
import DialogHeader from '@/components/ui/dialog/DialogHeader.vue';
import DialogTitle from '@/components/ui/dialog/DialogTitle.vue';
import DialogFooter from '@/components/ui/dialog/DialogFooter.vue';
import Swal from 'sweetalert2';

interface Cage {
  id: number;
  number_of_fingerlings: number;
  feed_types_id: number;
  investor_id: number;
}

interface FeedType {
  id: number;
  feed_type: string;
  brand: string;
}

interface Investor {
  id: number;
  name: string;
}

interface PaginatedCages {
  data: Cage[];
  total?: number;
  [key: string]: any;
}

const breadcrumbs = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Cages', href: '/cages' },
];

const store = useCageStore() as {
  cages: PaginatedCages | null;
  [key: string]: any;
};
const feedTypeStore = useFeedTypeStore();
const investorStore = useInvestorStore();
const search = ref('');
const showCreateDialog = ref(false);
const showDeleteDialog = ref(false);
const deleteTargetId = ref<number|null>(null);
const showEditDialog = ref(false);
const editCage = ref<Cage | null>(null);

const feedTypes = computed<FeedType[]>(() => {
  const data = feedTypeStore.feedTypes as any;
  return data?.data || [];
});
const investors = computed<Investor[]>(() => investorStore.investorsSelect as Investor[]);

const cages = computed<Cage[]>(() => store.cages?.data || []);
const pagination = computed(() => {
  const data = store.cages as any;
  return data?.current_page ? {
    current_page: data.current_page,
    last_page: data.last_page,
    per_page: data.per_page,
    total: data.total,
    from: data.from,
    to: data.to
  } : null;
});

const newCage = ref<Pick<Cage, 'number_of_fingerlings' | 'feed_types_id' | 'investor_id'>>({
  number_of_fingerlings: 0,
  feed_types_id: 0,
  investor_id: 0,
});

// Weather variables and functions
const weatherIcon = ref('🌡️');
const temperature = ref('--');
const weatherDescription = ref('Loading...');
const locationName = ref('Loading...');

const getWeatherIcon = (condition: string) => {
  const icons: Record<string, string> = {
    'Clear': '☀️',
    'Clouds': '☁️',
    'Rain': '🌧️',
    'Snow': '❄️',
    'Thunderstorm': '⛈️',
    'Drizzle': '🌦️',
    'Mist': '🌫️'
  };
  return icons[condition] || '🌡️';
};

const fetchLocationName = async (latitude: number, longitude: number) => {
  try {
    // Use a CORS proxy or skip location fetching to avoid CORS issues
    // For now, we'll skip the location API call and use a default location
    locationName.value = 'Davao City, Philippines';
  } catch (error) {
    locationName.value = 'Davao City, Philippines';
  }
};

const fetchWeather = async () => {
  try {
    // Skip geolocation to avoid permission issues and use default location
    const latitude = 7.305191; // Davao City coordinates
    const longitude = 125.684569;
    
    const response = await fetch(`https://api.tomorrow.io/v4/weather/realtime?location=${latitude},${longitude}&apikey=mcJYe6rycCpzd19wEcIRQgB9ks13EgTY&units=metric`);
    
    if (!response.ok) {
      throw new Error(`Weather API responded with status: ${response.status}`);
    }
    
    const data = await response.json();
    
    if (data.data && data.data.values) {
      weatherIcon.value = getWeatherIcon(data.data.values.weatherCode);
      temperature.value = String(Math.round(data.data.values.temperature));
      weatherDescription.value = data.data.values.weatherCode;
    } else {
      throw new Error('Invalid weather data format');
    }
    
    fetchLocationName(latitude, longitude);
  } catch (error) {
    console.warn('Weather data unavailable:', error);
    // Set default values without throwing errors
    weatherIcon.value = '🌡️';
    temperature.value = '--';
    weatherDescription.value = 'Weather data unavailable';
    locationName.value = 'Davao City, Philippines';
  }
};

// Initial fetch - only once to avoid repeated API calls
fetchWeather();

const filteredCages = computed<Cage[]>(() => {
  if (!search.value) return store.cages?.data || [];
  return (store.cages?.data || []).filter((c: Cage) =>
    String(c.number_of_fingerlings).includes(search.value.toLowerCase())
  );
});

function handleSearch() {
  store.setFilters({ search: search.value, page: 1 });
  store.fetchCages();
}

// Pagination functions
function goToPage(page: number) {
  store.setFilters({ ...store.filters, page });
  store.fetchCages();
}

function getPageNumbers() {
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
}

function openCreateDialog() {
  newCage.value = {
    number_of_fingerlings: 0,
    feed_types_id: feedTypes.value[0]?.id || 0,
    investor_id: investors.value[0]?.id || 0,
  };
  showCreateDialog.value = true;
}

async function createCage() {
  try {
    await store.createCage(newCage.value);
    await store.fetchCages();
    showCreateDialog.value = false;
    Swal.fire({ icon: 'success', title: 'Cage created successfully!' });
  } catch (error: any) {
    Swal.fire({ icon: 'error', title: 'Error', text: error?.message || 'Failed to create cage.' });
  }
}

function confirmDelete(id: number) {
  deleteTargetId.value = id;
  showDeleteDialog.value = true;
}

async function deleteCage() {
  if (deleteTargetId.value !== null) {
    try {
      await store.deleteCage(deleteTargetId.value);
      await store.fetchCages();
      showDeleteDialog.value = false;
      deleteTargetId.value = null;
      Swal.fire({ icon: 'success', title: 'Cage deleted successfully!' });
    } catch (error: any) {
      Swal.fire({ icon: 'error', title: 'Error', text: error?.message || 'Failed to delete cage.' });
    }
  }
}

function openEditDialog(c: Cage) {
  editCage.value = { ...c };
  showEditDialog.value = true;
}

async function updateCageHandler() {
  if (editCage.value && editCage.value.id) {
    try {
      await store.updateCage(editCage.value.id, {
        number_of_fingerlings: editCage.value.number_of_fingerlings,
        feed_types_id: editCage.value.feed_types_id,
        investor_id: editCage.value.investor_id,
      });
      await store.fetchCages();
      showEditDialog.value = false;
      editCage.value = null;
      Swal.fire({ icon: 'success', title: 'Cage updated successfully!' });
    } catch (error: any) {
      Swal.fire({ icon: 'error', title: 'Error', text: error?.message || 'Failed to update cage.' });
    }
  }
}

onMounted(() => {
  store.fetchCages();
  feedTypeStore.fetchFeedTypes();
  investorStore.fetchInvestorsSelect();
});
</script>

<template>
  <Head title="Cages" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col gap-4 p-4">
      <div class="flex items-center justify-between gap-2">
        <div class="flex gap-2 items-center">
          <Input v-model="search" placeholder="Search cages..." @keyup.enter="handleSearch" class="w-64" />
          <Button @click="handleSearch" variant="default">Search</Button>
        </div>
        <Button @click="openCreateDialog" variant="secondary">Create Cage</Button>
      </div>
      <div class="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-white dark:bg-gray-900">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-800">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Number of Fingerlings</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Feed Type</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Investor</th>
              <th class="px-6 py-3"></th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-if="store.loading" class="animate-pulse">
              <td colspan="4" class="px-6 py-4 text-center text-gray-500">Loading...</td>
            </tr>
            <tr v-else-if="cages.length === 0">
              <td colspan="4" class="px-6 py-4 text-center text-gray-500">No cages found.</td>
            </tr>
            <tr v-else v-for="c in cages" :key="c?.id">
              <td class="px-6 py-4 whitespace-nowrap">{{ c?.number_of_fingerlings }}</td>
              <td class="px-6 py-4 whitespace-nowrap">
                {{ feedTypes.find(f => f.id === c?.feed_types_id)?.feed_type || c?.feed_types_id }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                {{ investors.find(i => i.id === c?.investor_id)?.name || c?.investor_id }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right">
                <div class="flex gap-1">
                  <Link :href="`/cages/${c.id}/view`">
                    <Button 
                      variant="outline" 
                      size="sm"
                      title="View"
                      class="w-8 h-8 p-0"
                    >
                      👁️
                    </Button>
                  </Link>
                  <Button 
                    variant="secondary" 
                    size="sm" 
                    @click="openEditDialog(c)" 
                    :disabled="!c?.id"
                    title="Update"
                    class="w-8 h-8 p-0"
                  >
                    ✏️
                  </Button>
                  <Button 
                    variant="destructive" 
                    size="sm" 
                    @click="confirmDelete(c?.id)" 
                    :disabled="!c?.id"
                    title="Delete"
                    class="w-8 h-8 p-0"
                  >
                    🗑️
                  </Button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="pagination" class="flex items-center justify-between px-4 py-3 bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700">
        <div class="flex items-center text-sm text-gray-700 dark:text-gray-300">
          <span>Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} results</span>
        </div>
        <div class="flex items-center space-x-2">
          <Button 
            variant="outline" 
            size="sm" 
            :disabled="pagination.current_page === 1"
            @click="goToPage(pagination.current_page - 1)"
          >
            Previous
          </Button>
          <div class="flex items-center space-x-1">
            <Button 
              v-for="page in getPageNumbers()" 
              :key="page"
              variant="outline" 
              size="sm"
              :class="page === pagination.current_page ? 'bg-primary text-primary-foreground' : ''"
              @click="goToPage(page)"
            >
              {{ page }}
            </Button>
          </div>
          <Button 
            variant="outline" 
            size="sm" 
            :disabled="pagination.current_page === pagination.last_page"
            @click="goToPage(pagination.current_page + 1)"
          >
            Next
          </Button>
        </div>
      </div>
    </div>

    <!-- Create Cage Dialog -->
    <Dialog v-model:open="showCreateDialog">
      <DialogTrigger as-child />
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Create Cage</DialogTitle>
        </DialogHeader>
        <form @submit.prevent="createCage" class="flex flex-col gap-4 mt-2">
          <div class="flex flex-col gap-1">
            <label for="investor" class="text-sm font-medium">Investor</label>
            <select id="investor" v-model="newCage.investor_id" class="input w-full rounded border p-2" required>
              <option v-for="inv in investors" :key="inv.id" :value="inv.id" class="bg-background text-foreground">{{ inv.name }}</option>
            </select>
          </div>
          <div class="flex flex-col gap-1">
            <label for="fingerlings" class="text-sm font-medium">Number of Fingerlings</label>
            <Input id="fingerlings" v-model="newCage.number_of_fingerlings" type="number" placeholder="Enter number of fingerlings" required />
          </div>
          <div class="flex flex-col gap-1">
            <label for="feedType" class="text-sm font-medium">Feed Type</label>
            <select id="feedType" v-model="newCage.feed_types_id" class="input w-full rounded border p-2" required>
              <option v-for="f in feedTypes" :key="f.id" :value="f.id" class="bg-background text-foreground">{{ f.feed_type }}</option>
            </select>
          </div>
          <div class="flex flex-col gap-1">
            <label class="text-sm font-medium">Weather Condition</label>
            <div class="flex items-center gap-2">
              <span class="text-2xl">{{ weatherIcon }}</span>
              <span class="text-lg">{{ temperature }}°C</span>
              <span class="text-sm text-gray-500">{{ weatherDescription }}</span>
            </div>
            <div class="flex items-center gap-2 mt-1">
              <span class="text-xs text-gray-400">{{ locationName }}</span>
            </div>
          </div>
          <DialogFooter class="flex justify-end gap-2 mt-4">
            <Button type="button" variant="secondary" @click="showCreateDialog = false">Cancel</Button>
            <Button type="submit" variant="default">Create</Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>

    <!-- Delete Confirmation Dialog -->
    <Dialog v-model:open="showDeleteDialog">
      <DialogTrigger as-child />
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Delete Cage</DialogTitle>
        </DialogHeader>
        <div class="mt-2">Are you sure you want to delete this cage?</div>
        <DialogFooter class="flex justify-end gap-2 mt-4">
          <Button type="button" variant="secondary" @click="showDeleteDialog = false">Cancel</Button>
          <Button type="button" variant="destructive" @click="deleteCage">Delete</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <!-- Edit Cage Dialog -->
    <Dialog v-model:open="showEditDialog" v-if="editCage">
      <DialogTrigger as-child />
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Edit Cage</DialogTitle>
        </DialogHeader>
        <form @submit.prevent="updateCageHandler" class="flex flex-col gap-4 mt-2">
          <Input v-model="editCage.number_of_fingerlings" type="number" placeholder="Number of Fingerlings" required />
          <select v-model="editCage.feed_types_id" class="input w-full rounded border p-2" required>
            <option v-for="f in feedTypes" :key="f.id" :value="f.id" class="bg-background text-foreground">{{ f.feed_type }}</option>
          </select>
          <select v-model="editCage.investor_id" class="input w-full rounded border p-2" required>
            <option v-for="inv in investors" :key="inv.id" :value="inv.id" class="bg-background text-foreground">{{ inv.name }}</option>
          </select>
          <DialogFooter class="flex justify-end gap-2 mt-4">
            <Button type="button" variant="secondary" @click="showEditDialog = false">Cancel</Button>
            <Button type="submit" variant="default">Update</Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template> 