<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { type SharedData } from '@/types';
import Button from '@/components/ui/button/Button.vue';
import Input from '@/components/ui/input/Input.vue';
import Dialog from '@/components/ui/dialog/Dialog.vue';
import DialogTrigger from '@/components/ui/dialog/DialogTrigger.vue';
import DialogContent from '@/components/ui/dialog/DialogContent.vue';
import DialogHeader from '@/components/ui/dialog/DialogHeader.vue';
import DialogTitle from '@/components/ui/dialog/DialogTitle.vue';
import DialogFooter from '@/components/ui/dialog/DialogFooter.vue';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import Swal from 'sweetalert2';
import axios from 'axios';

const page = usePage<SharedData>();
const userRole = computed(() => page.props.auth?.user?.role || 'farmer');
const isAdmin = computed(() => userRole.value === 'admin');

interface User {
  id: number;
  name: string;
  email: string;
  role: 'farmer' | 'investor' | 'admin';
  is_active: boolean;
  created_at: string;
}

interface PaginatedUsers {
  data: User[];
  total?: number;
  current_page: number;
  last_page: number;
  per_page: number;
  from: number | null;
  to: number | null;
}

interface Statistics {
  total_users: number;
  active_users: number;
  inactive_users: number;
  farmers: number;
  investors: number;
  admins: number;
}

const breadcrumbs = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'User Management', href: '/users' },
];

const users = ref<PaginatedUsers>({
  data: [],
  current_page: 1,
  last_page: 1,
  per_page: 10,
  total: 0,
  from: null,
  to: null,
});
const statistics = ref<Statistics | null>(null);
const loading = ref(false);
const search = ref('');
const roleFilter = ref('');
const statusFilter = ref('');

const showCreateDialog = ref(false);
const showEditDialog = ref(false);
const showDeleteDialog = ref(false);
const deleteTargetId = ref<number|null>(null);
const editUser = ref<User | null>(null);

const newUser = ref({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  role: 'farmer' as 'farmer' | 'investor' | 'admin',
});

const pagination = computed(() => {
  return users.value ? {
    current_page: users.value.current_page,
    last_page: users.value.last_page,
    per_page: users.value.per_page,
    total: users.value.total,
    from: users.value.from,
    to: users.value.to
  } : null;
});

const getRoleBadgeClass = (role: string) => {
  switch (role) {
    case 'admin':
      return 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400';
    case 'investor':
      return 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400';
    case 'farmer':
      return 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400';
    default:
      return 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400';
  }
};

const getStatusBadgeClass = (isActive: boolean) => {
  return isActive 
    ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400'
    : 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400';
};

async function fetchUsers() {
  loading.value = true;
  try {
    const params = new URLSearchParams();
    if (search.value) params.append('search', search.value);
    if (roleFilter.value) params.append('role', roleFilter.value);
    if (statusFilter.value) params.append('status', statusFilter.value);
    params.append('page', users.value.current_page.toString());

    const response = await axios.get(`/users/list?${params.toString()}`);
    users.value = response.data.users;
  } catch (error) {
    console.error('Error fetching users:', error);
    Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to load users.' });
  } finally {
    loading.value = false;
  }
}

async function fetchStatistics() {
  try {
    const response = await axios.get('/users/statistics');
    statistics.value = response.data.statistics;
  } catch (error) {
    console.error('Error fetching statistics:', error);
  }
}

function handleSearch() {
  users.value.current_page = 1;
  fetchUsers();
}

function goToPage(page: number) {
  users.value.current_page = page;
  fetchUsers();
}

function getPageNumbers() {
  if (!pagination.value) return [];
  const current = pagination.value.current_page;
  const last = pagination.value.last_page;
  const pages = [];
  
  pages.push(1);
  
  for (let i = Math.max(2, current - 1); i <= Math.min(last - 1, current + 1); i++) {
    if (i > 1 && i < last) {
      pages.push(i);
    }
  }
  
  if (last > 1) {
    pages.push(last);
  }
  
  return [...new Set(pages)].sort((a, b) => a - b);
}

function openCreateDialog() {
  newUser.value = {
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'farmer',
  };
  showCreateDialog.value = true;
}

async function createUser() {
  try {
    await axios.post('/users', newUser.value);
    await fetchUsers();
    await fetchStatistics();
    showCreateDialog.value = false;
    Swal.fire({ icon: 'success', title: 'User created successfully!' });
  } catch (error: any) {
    const message = error?.response?.data?.message || 'Failed to create user.';
    Swal.fire({ icon: 'error', title: 'Error', text: message });
  }
}

function openEditDialog(user: User) {
  editUser.value = { ...user };
  showEditDialog.value = true;
}

async function updateUser() {
  if (!editUser.value) return;
  
  try {
    await axios.put(`/users/${editUser.value.id}`, {
      name: editUser.value.name,
      email: editUser.value.email,
    });
    await fetchUsers();
    showEditDialog.value = false;
    editUser.value = null;
    Swal.fire({ icon: 'success', title: 'User updated successfully!' });
  } catch (error: any) {
    const message = error?.response?.data?.message || 'Failed to update user.';
    Swal.fire({ icon: 'error', title: 'Error', text: message });
  }
}

async function updateUserRole(user: User, newRole: string) {
  try {
    await axios.put(`/users/${user.id}/role`, { role: newRole });
    await fetchUsers();
    await fetchStatistics();
    Swal.fire({ icon: 'success', title: 'User role updated successfully!' });
  } catch (error: any) {
    const message = error?.response?.data?.message || 'Failed to update user role.';
    Swal.fire({ icon: 'error', title: 'Error', text: message });
  }
}

async function toggleUserStatus(user: User) {
  try {
    await axios.post(`/users/${user.id}/toggle-status`);
    await fetchUsers();
    await fetchStatistics();
    const status = !user.is_active ? 'activated' : 'deactivated';
    Swal.fire({ icon: 'success', title: `User ${status} successfully!` });
  } catch (error: any) {
    const message = error?.response?.data?.message || 'Failed to toggle user status.';
    Swal.fire({ icon: 'error', title: 'Error', text: message });
  }
}

function confirmDelete(id: number) {
  deleteTargetId.value = id;
  showDeleteDialog.value = true;
}

async function deleteUser() {
  if (deleteTargetId.value === null) return;
  
  try {
    await axios.delete(`/users/${deleteTargetId.value}`);
    await fetchUsers();
    await fetchStatistics();
    showDeleteDialog.value = false;
    deleteTargetId.value = null;
    Swal.fire({ icon: 'success', title: 'User deleted successfully!' });
  } catch (error: any) {
    const message = error?.response?.data?.message || 'Failed to delete user.';
    Swal.fire({ icon: 'error', title: 'Error', text: message });
  }
}

onMounted(() => {
  fetchUsers();
  fetchStatistics();
});
</script>

<template>
  <Head title="User Management" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col gap-4 p-4">
      <!-- Statistics Cards -->
      <div v-if="statistics" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-sm font-medium text-muted-foreground">Total Users</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ statistics.total_users }}</div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-sm font-medium text-muted-foreground">Active</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-green-600">{{ statistics.active_users }}</div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-sm font-medium text-muted-foreground">Inactive</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-red-600">{{ statistics.inactive_users }}</div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-sm font-medium text-muted-foreground">Farmers</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-green-600">{{ statistics.farmers }}</div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-sm font-medium text-muted-foreground">Investors</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-blue-600">{{ statistics.investors }}</div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-sm font-medium text-muted-foreground">Admins</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-red-600">{{ statistics.admins }}</div>
          </CardContent>
        </Card>
      </div>

      <!-- Filters and Actions -->
      <div class="flex items-center justify-between gap-2 flex-wrap">
        <div class="flex gap-2 items-center flex-wrap">
          <Input v-model="search" placeholder="Search users..." @keyup.enter="handleSearch" class="w-64" />
          <select v-model="roleFilter" @change="handleSearch" class="flex h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm">
            <option value="">All Roles</option>
            <option value="farmer">Farmer</option>
            <option value="investor">Investor</option>
            <option value="admin">Admin</option>
          </select>
          <select v-model="statusFilter" @change="handleSearch" class="flex h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm">
            <option value="">All Status</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
          <Button @click="handleSearch" variant="default">Search</Button>
        </div>
        <Button @click="openCreateDialog" variant="secondary">Create User</Button>
      </div>

      <!-- Users Table -->
      <div class="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-white dark:bg-gray-900">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-800">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-if="loading" class="animate-pulse">
              <td colspan="6" class="px-6 py-4 text-center text-gray-500">Loading...</td>
            </tr>
            <tr v-else-if="users.data.length === 0">
              <td colspan="6" class="px-6 py-4 text-center text-gray-500">No users found.</td>
            </tr>
            <tr v-else v-for="user in users.data" :key="user.id">
              <td class="px-6 py-4 whitespace-nowrap font-medium">{{ user.name }}</td>
              <td class="px-6 py-4 whitespace-nowrap">{{ user.email }}</td>
              <td class="px-6 py-4 whitespace-nowrap">
                <select 
                  :value="user.role" 
                  @change="(e) => updateUserRole(user, (e.target as HTMLSelectElement).value)"
                  class="px-2 py-1 text-xs rounded-full font-semibold"
                  :class="getRoleBadgeClass(user.role)"
                >
                  <option value="farmer">Farmer</option>
                  <option value="investor">Investor</option>
                  <option value="admin">Admin</option>
                </select>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span 
                  class="px-2 py-1 text-xs rounded-full font-semibold"
                  :class="getStatusBadgeClass(user.is_active)"
                >
                  {{ user.is_active ? 'Active' : 'Inactive' }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ new Date(user.created_at).toLocaleDateString() }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right">
                <div class="flex gap-1 justify-end">
                  <Button 
                    variant="outline" 
                    size="sm" 
                    @click="openEditDialog(user)" 
                    title="Edit"
                    class="w-8 h-8 p-0"
                  >
                    ✏️
                  </Button>
                  <Button 
                    :variant="user.is_active ? 'secondary' : 'default'" 
                    size="sm" 
                    @click="toggleUserStatus(user)" 
                    :title="user.is_active ? 'Deactivate' : 'Activate'"
                    class="w-8 h-8 p-0"
                  >
                    {{ user.is_active ? '🔒' : '🔓' }}
                  </Button>
                  <Button 
                    variant="destructive" 
                    size="sm" 
                    @click="confirmDelete(user.id)" 
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
      <div v-if="pagination && pagination.total > 0" class="flex items-center justify-between px-4 py-3 bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700">
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

    <!-- Create User Dialog -->
    <Dialog v-model:open="showCreateDialog">
      <DialogTrigger as-child />
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Create User</DialogTitle>
        </DialogHeader>
        <form @submit.prevent="createUser" class="flex flex-col gap-4 mt-2">
          <div class="flex flex-col gap-1">
            <label for="name" class="text-sm font-medium">Name</label>
            <Input id="name" v-model="newUser.name" type="text" placeholder="Enter name" required />
          </div>
          <div class="flex flex-col gap-1">
            <label for="email" class="text-sm font-medium">Email</label>
            <Input id="email" v-model="newUser.email" type="email" placeholder="Enter email" required />
          </div>
          <div class="flex flex-col gap-1">
            <label for="role" class="text-sm font-medium">Role</label>
            <select id="role" v-model="newUser.role" class="input w-full rounded border p-2" required>
              <option value="farmer">Farmer</option>
              <option value="investor">Investor</option>
              <option value="admin">Admin</option>
            </select>
          </div>
          <div class="flex flex-col gap-1">
            <label for="password" class="text-sm font-medium">Password</label>
            <Input id="password" v-model="newUser.password" type="password" placeholder="Enter password" required />
          </div>
          <div class="flex flex-col gap-1">
            <label for="password_confirmation" class="text-sm font-medium">Confirm Password</label>
            <Input id="password_confirmation" v-model="newUser.password_confirmation" type="password" placeholder="Confirm password" required />
          </div>
          <DialogFooter class="flex justify-end gap-2 mt-4">
            <Button type="button" variant="secondary" @click="showCreateDialog = false">Cancel</Button>
            <Button type="submit" variant="default">Create</Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>

    <!-- Edit User Dialog -->
    <Dialog v-model:open="showEditDialog" v-if="editUser">
      <DialogTrigger as-child />
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Edit User</DialogTitle>
        </DialogHeader>
        <form @submit.prevent="updateUser" class="flex flex-col gap-4 mt-2">
          <div class="flex flex-col gap-1">
            <label for="edit_name" class="text-sm font-medium">Name</label>
            <Input id="edit_name" v-model="editUser.name" type="text" placeholder="Enter name" required />
          </div>
          <div class="flex flex-col gap-1">
            <label for="edit_email" class="text-sm font-medium">Email</label>
            <Input id="edit_email" v-model="editUser.email" type="email" placeholder="Enter email" required />
          </div>
          <DialogFooter class="flex justify-end gap-2 mt-4">
            <Button type="button" variant="secondary" @click="showEditDialog = false">Cancel</Button>
            <Button type="submit" variant="default">Update</Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>

    <!-- Delete Confirmation Dialog -->
    <Dialog v-model:open="showDeleteDialog">
      <DialogTrigger as-child />
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Delete User</DialogTitle>
        </DialogHeader>
        <div class="mt-2">Are you sure you want to delete this user? This action cannot be undone.</div>
        <DialogFooter class="flex justify-end gap-2 mt-4">
          <Button type="button" variant="secondary" @click="showDeleteDialog = false">Cancel</Button>
          <Button type="button" variant="destructive" @click="deleteUser">Delete</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>
