<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { useInvestorStore } from '@/Stores/InvestorStore';
import { Head } from '@inertiajs/vue3';
import Button from '@/components/ui/button/Button.vue';
import Input from '@/components/ui/input/Input.vue';
import Dialog from '@/components/ui/dialog/Dialog.vue';
import DialogTrigger from '@/components/ui/dialog/DialogTrigger.vue';
import DialogContent from '@/components/ui/dialog/DialogContent.vue';
import DialogHeader from '@/components/ui/dialog/DialogHeader.vue';
import DialogTitle from '@/components/ui/dialog/DialogTitle.vue';
import DialogFooter from '@/components/ui/dialog/DialogFooter.vue';
import Swal from 'sweetalert2';

// Define Investor type
interface Investor {
  id: number;
  name: string;
  address: string;
  phone: string;
}

const breadcrumbs = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Investors', href: '/investors' },
];

const store = useInvestorStore();
const search = ref('');
const showCreateDialog = ref(false);
const showDeleteDialog = ref(false);
const deleteTargetId = ref<number|null>(null);
const showEditDialog = ref(false);
const editInvestor = ref<Investor | null>(null);

const newInvestor = ref<Pick<Investor, 'name' | 'address' | 'phone'>>({
  name: '',
  address: '',
  phone: '',
});

const investors = computed(() => {
  const data = store.investors as any;
  return data?.data || [];
});

const pagination = computed(() => {
  const data = store.investors as any;
  return data?.current_page ? {
    current_page: data.current_page,
    last_page: data.last_page,
    per_page: data.per_page,
    total: data.total,
    from: data.from,
    to: data.to
  } : null;
});

function handleSearch() {
  store.setFilters({ search: search.value, page: 1 });
  store.fetchInvestors();
}

// Pagination functions
function goToPage(page: number) {
  store.setFilters({ ...store.filters, page });
  store.fetchInvestors();
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
  newInvestor.value = { name: '', address: '', phone: '' };
  showCreateDialog.value = true;
}

async function createInvestor() {
  try {
    await store.createInvestor(newInvestor.value);
    await store.fetchInvestors();
    showCreateDialog.value = false;
    Swal.fire({ icon: 'success', title: 'Investor created successfully!' });
  } catch (error: any) {
    Swal.fire({ icon: 'error', title: 'Error', text: error?.message || 'Failed to create investor.' });
  }
}

function confirmDelete(id: number) {
  deleteTargetId.value = id;
  showDeleteDialog.value = true;
}

async function deleteInvestor() {
  if (deleteTargetId.value !== null) {
    try {
      await store.deleteInvestor(deleteTargetId.value);
      await store.fetchInvestors();
      showDeleteDialog.value = false;
      deleteTargetId.value = null;
      Swal.fire({ icon: 'success', title: 'Investor deleted successfully!' });
    } catch (error: any) {
      Swal.fire({ icon: 'error', title: 'Error', text: error?.message || 'Failed to delete investor.' });
    }
  }
}

function openEditDialog(inv: Investor) {
  editInvestor.value = { ...inv };
  showEditDialog.value = true;
}

async function updateInvestorHandler() {
  if (editInvestor.value && editInvestor.value.id) {
    try {
      await store.updateInvestor(editInvestor.value.id, {
        name: editInvestor.value.name,
        address: editInvestor.value.address,
        phone: editInvestor.value.phone,
      });
      await store.fetchInvestors();
      showEditDialog.value = false;
      editInvestor.value = null;
      Swal.fire({ icon: 'success', title: 'Investor updated successfully!' });
    } catch (error: any) {
      Swal.fire({ icon: 'error', title: 'Error', text: error?.message || 'Failed to update investor.' });
    }
  }
}

onMounted(() => {
  store.fetchInvestors();
});
</script>

<template>
  <Head title="Investors" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col gap-4 p-4">
      <div class="flex items-center justify-between gap-2">
        <div class="flex gap-2 items-center">
          <Input v-model="search" placeholder="Search investors..." @keyup.enter="handleSearch" class="w-64" />
          <Button @click="handleSearch" variant="default">Search</Button>
        </div>
        <Button @click="openCreateDialog" variant="secondary">Create Investor</Button>
      </div>
      <div class="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-white dark:bg-gray-900">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-800">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Address</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
              <th class="px-6 py-3"></th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-if="store.loading" class="animate-pulse">
              <td colspan="4" class="px-6 py-4 text-center text-gray-500">Loading...</td>
            </tr>
            <tr v-else-if="investors.length === 0">
              <td colspan="4" class="px-6 py-4 text-center text-gray-500">No investors found.</td>
            </tr>
            <tr v-else v-for="inv in investors" :key="inv.id">
              <td class="px-6 py-4 whitespace-nowrap">{{ inv.name }}</td>
              <td class="px-6 py-4 whitespace-nowrap">{{ inv.address }}</td>
              <td class="px-6 py-4 whitespace-nowrap">{{ inv.phone }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-right">
                <div class="flex gap-1">
                  <Button 
                    variant="secondary" 
                    size="sm" 
                    @click="openEditDialog(inv)"
                    title="Update"
                    class="w-8 h-8 p-0"
                  >
                    ✏️
                  </Button>
                  <Button 
                    variant="destructive" 
                    size="sm" 
                    @click="confirmDelete(inv.id)"
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

    <!-- Create Investor Dialog -->
    <Dialog v-model:open="showCreateDialog">
      <DialogTrigger as-child />
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Create Investor</DialogTitle>
        </DialogHeader>
        <form @submit.prevent="createInvestor" class="flex flex-col gap-4 mt-2">
          <Input v-model="newInvestor.name" placeholder="Name" required />
          <Input v-model="newInvestor.address" placeholder="Address" required />
          <Input v-model="newInvestor.phone" placeholder="Phone" required />
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
          <DialogTitle>Delete Investor</DialogTitle>
        </DialogHeader>
        <div class="mt-2">Are you sure you want to delete this investor?</div>
        <DialogFooter class="flex justify-end gap-2 mt-4">
          <Button type="button" variant="secondary" @click="showDeleteDialog = false">Cancel</Button>
          <Button type="button" variant="destructive" @click="deleteInvestor">Delete</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <!-- Edit Investor Dialog -->
    <Dialog v-model:open="showEditDialog" v-if="editInvestor">
      <DialogTrigger as-child />
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Edit Investor</DialogTitle>
        </DialogHeader>
        <form @submit.prevent="updateInvestorHandler" class="flex flex-col gap-4 mt-2">
          <Input v-model="editInvestor.name" placeholder="Name" required />
          <Input v-model="editInvestor.address" placeholder="Address" required />
          <Input v-model="editInvestor.phone" placeholder="Phone" required />
          <DialogFooter class="flex justify-end gap-2 mt-4">
            <Button type="button" variant="secondary" @click="showEditDialog = false">Cancel</Button>
            <Button type="submit" variant="default">Update</Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>
