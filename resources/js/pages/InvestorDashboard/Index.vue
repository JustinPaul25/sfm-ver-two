<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import Card from '@/components/ui/card/Card.vue';
import Button from '@/components/ui/button/Button.vue';
import Input from '@/components/ui/input/Input.vue';
import SamplingTrendsChart from '@/components/charts/SamplingTrendsChart.vue';
import { router } from '@inertiajs/vue3';

// Props from Inertia
const props = defineProps<{
  analytics?: any;
  investor?: any;
  cages?: any[];
  farmers?: any[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Investor Dashboard',
        href: '/investor/dashboard',
    },
];

// Time period filter
const selectedPeriod = ref('30days');
const customStartDate = ref('');
const customEndDate = ref('');

// Cage filter
const selectedCageId = ref<string | null>(null);
const cageSearch = ref('');
const showCageDropdown = ref(false);

const periods = [
    { value: 'day', label: 'Today' },
    { value: 'week', label: 'This Week' },
    { value: '30days', label: 'Last 30 Days' },
    { value: 'month', label: 'This Month' },
    { value: 'custom', label: 'Custom Range' },
];

// Analytics data
const analytics = computed(() => props.analytics || {});
const investor = computed(() => props.investor || {});
const cages = computed(() => props.cages || []);
const farmers = computed(() => props.farmers || []);

// Summary cards data
const summaryCards = computed(() => [
    {
        title: 'My Cages',
        value: analytics.value.summary?.total_cages || 0,
        icon: '🏠',
        color: 'bg-blue-500',
        description: 'Total cages'
    },
    {
        title: 'My Farmers',
        value: analytics.value.summary?.total_farmers || 0,
        icon: '👨‍🌾',
        color: 'bg-green-500',
        description: 'Active farmers'
    },
    {
        title: 'Samplings This Period',
        value: analytics.value.summary?.samplings_in_period || 0,
        icon: '📊',
        color: 'bg-purple-500',
        change: analytics.value.growth_metrics?.sampling_growth > 0 ? `+${analytics.value.growth_metrics?.sampling_growth}%` : `${analytics.value.growth_metrics?.sampling_growth}%`,
        changeType: analytics.value.growth_metrics?.sampling_growth > 0 ? 'positive' : 'negative'
    },
    {
        title: 'Avg Sample Weight',
        value: `${analytics.value.weight_stats?.avg_weight || 0}g`,
        icon: '⚖️',
        color: 'bg-orange-500',
        change: analytics.value.growth_metrics?.weight_growth > 0 ? `+${analytics.value.growth_metrics?.weight_growth}%` : `${analytics.value.growth_metrics?.weight_growth}%`,
        changeType: analytics.value.growth_metrics?.weight_growth > 0 ? 'positive' : 'negative'
    }
]);

// Functions
function updatePeriod(period: string) {
    selectedPeriod.value = period;
    if (period === 'custom') {
        return; // Don't reload until dates are selected
    }
    reloadDashboard();
}

function reloadDashboard() {
    const params: any = { period: selectedPeriod.value };
    
    if (selectedPeriod.value === 'custom' && customStartDate.value && customEndDate.value) {
        params.start_date = customStartDate.value;
        params.end_date = customEndDate.value;
    }
    
    if (selectedCageId.value) {
        params.cage_no = selectedCageId.value;
    }
    
    router.get('/investor/dashboard', params, {
        preserveState: true,
        preserveScroll: true,
    });
}

function applyCustomDateRange() {
    if (customStartDate.value && customEndDate.value) {
        reloadDashboard();
    }
}

// Format numbers
function formatNumber(num: number) {
    return new Intl.NumberFormat().format(num);
}

function formatWeight(weight: number) {
    return `${weight.toFixed(1)}g`;
}

// Filter cages based on search
const filteredCages = computed(() => {
    if (!cageSearch.value) {
        return cages.value;
    }
    const search = cageSearch.value.toLowerCase();
    return cages.value.filter(cage => 
        cage.id?.toString().includes(search) ||
        cage.number_of_fingerlings?.toString().includes(search) ||
        cage.farmer_name?.toLowerCase().includes(search)
    );
});

// Watch for cage changes to reload dashboard
watch(selectedCageId, () => {
    reloadDashboard();
});

// Close dropdowns when clicking outside
function handleClickOutside(event: MouseEvent) {
    const target = event.target as HTMLElement;
    if (!target.closest('.cage-dropdown-container')) {
        showCageDropdown.value = false;
    }
}
</script>

<template>
    <Head title="Investor Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4">
            <!-- Header with investor info and period filter -->
            <div class="flex flex-col gap-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold">Welcome, {{ investor.name }}</h1>
                        <p class="text-muted-foreground">
                            {{ analytics.date_range?.period || 'This Month' }} 
                            ({{ analytics.date_range?.start }} to {{ analytics.date_range?.end }})
                        </p>
                    </div>
                    
                    <!-- Period Filter -->
                    <div class="flex flex-col sm:flex-row gap-2">
                        <div class="flex gap-1">
                            <Button 
                                v-for="period in periods" 
                                :key="period.value"
                                :variant="selectedPeriod === period.value ? 'default' : 'outline'"
                                size="sm"
                                @click="updatePeriod(period.value)"
                            >
                                {{ period.label }}
                            </Button>
                        </div>
                        
                        <!-- Custom Date Range -->
                        <div v-if="selectedPeriod === 'custom'" class="flex gap-2">
                            <Input 
                                v-model="customStartDate" 
                                type="date" 
                                placeholder="Start Date"
                                class="w-32"
                            />
                            <Input 
                                v-model="customEndDate" 
                                type="date" 
                                placeholder="End Date"
                                class="w-32"
                            />
                            <Button @click="applyCustomDateRange" size="sm">
                                Apply
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <Card v-for="card in summaryCards" :key="card.title" class="p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">{{ card.title }}</p>
                            <p class="text-2xl font-bold">{{ card.value }}</p>
                            <p v-if="card.change" class="text-xs" :class="card.changeType === 'positive' ? 'text-green-600' : 'text-red-600'">
                                {{ card.change }} from last period
                            </p>
                            <p v-else class="text-xs text-muted-foreground">{{ card.description }}</p>
                        </div>
                        <div class="text-3xl">{{ card.icon }}</div>
                    </div>
                </Card>
            </div>

            <!-- Charts and Analytics -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Sampling Trends Chart -->
                <Card class="p-6">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4 gap-4">
                        <h3 class="text-lg font-semibold">Sampling Trends</h3>
                        
                        <!-- Cage Filter -->
                        <div class="cage-dropdown-container">
                            <div class="relative">
                                <Input
                                    v-model="cageSearch"
                                    type="text"
                                    :placeholder="selectedCageId ? `Cage ${selectedCageId}` : 'All Cages'"
                                    class="w-full sm:w-40"
                                    @focus="showCageDropdown = true"
                                    @input="showCageDropdown = true"
                                />
                                <div 
                                    v-if="showCageDropdown && (cageSearch || filteredCages.length > 0)"
                                    class="absolute z-10 w-full sm:w-64 mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-md shadow-lg max-h-60 overflow-auto"
                                >
                                    <button
                                        type="button"
                                        class="w-full text-left px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700"
                                        :class="{ 'bg-blue-50 dark:bg-blue-900/20': !selectedCageId }"
                                        @click="selectedCageId = null; cageSearch = ''; showCageDropdown = false; reloadDashboard()"
                                    >
                                        <span class="font-medium">All Cages</span>
                                    </button>
                                    <div
                                        v-for="cage in filteredCages"
                                        :key="cage.id"
                                        class="px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer"
                                        :class="{ 'bg-blue-50 dark:bg-blue-900/20': selectedCageId === cage.id.toString() }"
                                        @click="selectedCageId = cage.id.toString(); cageSearch = `Cage ${cage.id}`; showCageDropdown = false; reloadDashboard()"
                                    >
                                        Cage {{ cage.id }} - {{ cage.farmer_name }} ({{ cage.number_of_fingerlings }} fingerlings)
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <SamplingTrendsChart :trends="analytics.sampling_trends || []" />
                </Card>

                <!-- Weight Statistics -->
                <Card class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Weight Statistics</h3>
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="text-center p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                <p class="text-2xl font-bold text-blue-600">{{ formatWeight(analytics.weight_stats?.avg_weight || 0) }}</p>
                                <p class="text-sm text-muted-foreground">Average Weight</p>
                            </div>
                            <div class="text-center p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                                <p class="text-2xl font-bold text-green-600">{{ formatWeight(analytics.weight_stats?.max_weight || 0) }}</p>
                                <p class="text-sm text-muted-foreground">Max Weight</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="text-center p-3 bg-orange-50 dark:bg-orange-900/20 rounded-lg">
                                <p class="text-2xl font-bold text-orange-600">{{ formatWeight(analytics.weight_stats?.min_weight || 0) }}</p>
                                <p class="text-sm text-muted-foreground">Min Weight</p>
                            </div>
                            <div class="text-center p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                                <p class="text-2xl font-bold text-purple-600">{{ formatNumber(analytics.weight_stats?.total_weight || 0) }}g</p>
                                <p class="text-sm text-muted-foreground">Total Weight</p>
                            </div>
                        </div>
                    </div>
                </Card>
            </div>

            <!-- Tables Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Cage Performance -->
                <Card class="p-6">
                    <h3 class="text-lg font-semibold mb-4">My Cages Performance</h3>
                    <div class="space-y-3">
                        <div v-for="cage in analytics.cage_performance" :key="cage.id" 
                             class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <div>
                                <p class="font-medium">Cage {{ cage.id }}</p>
                                <p class="text-sm text-muted-foreground">{{ cage.farmer_name || 'Unassigned' }} - {{ cage.number_of_fingerlings }} fingerlings</p>
                            </div>
                            <div class="text-right">
                                <p class="font-medium">{{ formatWeight(cage.avg_sample_weight || 0) }}</p>
                                <p class="text-sm text-muted-foreground">{{ cage.sampling_count }} samplings</p>
                            </div>
                        </div>
                        <div v-if="!analytics.cage_performance?.length" class="text-center py-8 text-muted-foreground">
                            No data available
                        </div>
                    </div>
                </Card>

                <!-- Feed Type Usage -->
                <Card class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Feed Type Usage</h3>
                    <div class="space-y-3">
                        <div v-for="feedType in analytics.feed_type_usage" :key="feedType.feed_type" 
                             class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <div>
                                <p class="font-medium">{{ feedType.feed_type }}</p>
                                <p class="text-sm text-muted-foreground">{{ feedType.brand }}</p>
                            </div>
                            <div class="text-right">
                                <p class="font-medium">{{ feedType.cage_count }}</p>
                                <p class="text-sm text-muted-foreground">cages</p>
                            </div>
                        </div>
                        <div v-if="!analytics.feed_type_usage?.length" class="text-center py-8 text-muted-foreground">
                            No data available
                        </div>
                    </div>
                </Card>
            </div>

            <!-- My Farmers Section -->
            <Card class="p-6" v-if="farmers.length > 0">
                <h3 class="text-lg font-semibold mb-4">My Farmers</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div v-for="farmer in farmers" :key="farmer.id" 
                         class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-green-500 text-white rounded-full flex items-center justify-center text-lg font-bold">
                                {{ farmer.name.charAt(0).toUpperCase() }}
                            </div>
                            <div>
                                <p class="font-medium">{{ farmer.name }}</p>
                                <p class="text-sm text-muted-foreground">{{ farmer.email }}</p>
                                <p class="text-xs text-muted-foreground">{{ farmer.cages_count }} cage(s)</p>
                            </div>
                        </div>
                    </div>
                </div>
            </Card>

            <!-- Recent Samplings -->
            <Card class="p-6">
                <h3 class="text-lg font-semibold mb-4">Recent Samplings</h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b">
                                <th class="text-left py-2 px-3">Cage</th>
                                <th class="text-left py-2 px-3">Date</th>
                                <th class="text-left py-2 px-3">DOC</th>
                                <th class="text-left py-2 px-3">Samples</th>
                                <th class="text-left py-2 px-3">Avg Weight</th>
                                <th class="text-left py-2 px-3">Mortality</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="sampling in analytics.recent_samplings" :key="sampling.id"
                                class="border-b hover:bg-gray-50 dark:hover:bg-gray-800">
                                <td class="py-2 px-3">{{ sampling.cage_no }}</td>
                                <td class="py-2 px-3">{{ sampling.date_sampling }}</td>
                                <td class="py-2 px-3">{{ sampling.doc }}</td>
                                <td class="py-2 px-3">{{ sampling.sample_count }}</td>
                                <td class="py-2 px-3">{{ formatWeight(sampling.avg_weight) }}</td>
                                <td class="py-2 px-3">{{ sampling.mortality }}</td>
                            </tr>
                        </tbody>
                    </table>
                    <div v-if="!analytics.recent_samplings?.length" class="text-center py-8 text-muted-foreground">
                        No samplings available
                    </div>
                </div>
            </Card>

            <!-- Growth Metrics -->
            <Card class="p-6">
                <h3 class="text-lg font-semibold mb-4">Growth Metrics</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="text-center p-6 bg-gradient-to-r from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 rounded-lg">
                        <div class="text-3xl mb-2">📊</div>
                        <p class="text-2xl font-bold" :class="analytics.growth_metrics?.sampling_growth > 0 ? 'text-green-600' : 'text-red-600'">
                            {{ analytics.growth_metrics?.sampling_growth > 0 ? '+' : '' }}{{ analytics.growth_metrics?.sampling_growth || 0 }}%
                        </p>
                        <p class="text-sm text-muted-foreground">Sampling Growth</p>
                        <p class="text-xs text-muted-foreground mt-1">
                            {{ analytics.growth_metrics?.current_samplings || 0 }} vs {{ analytics.growth_metrics?.previous_samplings || 0 }}
                        </p>
                    </div>
                    
                    <div class="text-center p-6 bg-gradient-to-r from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 rounded-lg">
                        <div class="text-3xl mb-2">⚖️</div>
                        <p class="text-2xl font-bold" :class="analytics.growth_metrics?.weight_growth > 0 ? 'text-green-600' : 'text-red-600'">
                            {{ analytics.growth_metrics?.weight_growth > 0 ? '+' : '' }}{{ analytics.growth_metrics?.weight_growth || 0 }}%
                        </p>
                        <p class="text-sm text-muted-foreground">Weight Growth</p>
                        <p class="text-xs text-muted-foreground mt-1">
                            {{ formatWeight(analytics.growth_metrics?.current_avg_weight || 0) }} vs {{ formatWeight(analytics.growth_metrics?.previous_avg_weight || 0) }}
                        </p>
                    </div>
                </div>
            </Card>
        </div>
    </AppLayout>
</template>
