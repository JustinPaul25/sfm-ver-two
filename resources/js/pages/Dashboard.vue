<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import Card from '@/components/ui/card/Card.vue';
import Button from '@/components/ui/button/Button.vue';
import Input from '@/components/ui/input/Input.vue';
import SamplingTrendsChart from '@/components/charts/SamplingTrendsChart.vue';
import { router, Link } from '@inertiajs/vue3';
import axios from 'axios';

// Props from Inertia
const props = defineProps<{
  analytics?: any;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

// Time period filter
const selectedPeriod = ref('30days');
const customStartDate = ref('');
const customEndDate = ref('');

// Investor and Cage filters
const selectedInvestorId = ref<string | null>(null);
const selectedCageId = ref<string | null>(null);
const investors = ref<any[]>([]);
const cages = ref<any[]>([]);
const investorSearch = ref('');
const cageSearch = ref('');
const loadingInvestors = ref(false);
const loadingCages = ref(false);
const showInvestorDropdown = ref(false);
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

// Summary cards data
const summaryCards = computed(() => [
    {
        title: 'Total Investors',
        value: analytics.value.summary?.total_investors || 0,
        icon: '👥',
        color: 'bg-blue-500',
        change: '+12%',
        changeType: 'positive'
    },
    {
        title: 'Total Cages',
        value: analytics.value.summary?.total_cages || 0,
        icon: '🏠',
        color: 'bg-green-500',
        change: '+5%',
        changeType: 'positive'
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

// Chart data for sampling trends
const chartData = computed(() => {
    const trends = analytics.value.sampling_trends || [];
    return {
        labels: trends.map((item: any) => new Date(item.date).toLocaleDateString()),
        datasets: [
            {
                label: 'Samplings',
                data: trends.map((item: any) => item.count),
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.4
            },
            {
                label: 'Avg Weight (g)',
                data: trends.map((item: any) => item.avg_weight),
                borderColor: 'rgb(16, 185, 129)',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                tension: 0.4,
                yAxisID: 'y1'
            }
        ]
    };
});

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
    
    if (selectedInvestorId.value) {
        params.investor_id = selectedInvestorId.value;
    }
    
    if (selectedCageId.value) {
        params.cage_no = selectedCageId.value;
    }
    
    router.get('/dashboard', params, {
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

// Fetch investors
async function fetchInvestors() {
    if (investors.value.length > 0) return; // Already loaded
    loadingInvestors.value = true;
    try {
        // @ts-ignore - route is available globally via ZiggyVue
        const response = await axios.get(route('investors.select'));
        investors.value = response.data;
    } catch (error) {
        console.error('Error fetching investors:', error);
    } finally {
        loadingInvestors.value = false;
    }
}

// Fetch cages for selected investor
async function fetchCages() {
    if (!selectedInvestorId.value) {
        cages.value = [];
        selectedCageId.value = null;
        return;
    }
    
    loadingCages.value = true;
    try {
        // @ts-ignore - route is available globally via ZiggyVue
        const response = await axios.get(route('cages.select'), {
            params: { investor_id: selectedInvestorId.value }
        });
        cages.value = response.data;
    } catch (error) {
        console.error('Error fetching cages:', error);
    } finally {
        loadingCages.value = false;
    }
}

// Filter investors based on search
const filteredInvestors = computed(() => {
    if (!investorSearch.value) {
        return investors.value;
    }
    const search = investorSearch.value.toLowerCase();
    return investors.value.filter(inv => 
        inv.name?.toLowerCase().includes(search)
    );
});

// Filter cages based on search
const filteredCages = computed(() => {
    if (!cageSearch.value) {
        return cages.value;
    }
    const search = cageSearch.value.toLowerCase();
    return cages.value.filter(cage => 
        cage.id?.toString().includes(search) ||
        cage.number_of_fingerlings?.toString().includes(search)
    );
});

// Watch for investor changes to fetch cages
watch(selectedInvestorId, () => {
    selectedCageId.value = null;
    fetchCages();
    reloadDashboard();
});

// Watch for cage changes to reload dashboard
watch(selectedCageId, () => {
    reloadDashboard();
});

// Close dropdowns when clicking outside
function handleClickOutside(event: MouseEvent) {
    const target = event.target as HTMLElement;
    if (!target.closest('.investor-dropdown-container')) {
        showInvestorDropdown.value = false;
    }
    if (!target.closest('.cage-dropdown-container')) {
        showCageDropdown.value = false;
    }
}

// Initialize on mount
onMounted(() => {
    fetchInvestors();
    
    // Get initial filters from URL params if present
    const urlParams = new URLSearchParams(window.location.search);
    const investorId = urlParams.get('investor_id');
    const cageNo = urlParams.get('cage_no');
    
    if (investorId) {
        selectedInvestorId.value = investorId;
        fetchCages().then(() => {
            if (cageNo) {
                selectedCageId.value = cageNo;
            }
        });
    }
    
    // Add click outside listener
    document.addEventListener('click', handleClickOutside);
});

// Cleanup
onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4">
            <!-- Header with period filter -->
            <div class="flex flex-col gap-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold">Dashboard Analytics</h1>
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
                            <p class="text-xs" :class="card.changeType === 'positive' ? 'text-green-600' : 'text-red-600'">
                                {{ card.change }} from last period
                            </p>
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
                        
                        <!-- Investor and Cage Filters -->
                        <div class="flex flex-col sm:flex-row gap-2">
                            <!-- Investor Select -->
                            <div class="investor-dropdown-container">
                                <div class="relative">
                                    <Input
                                        v-model="investorSearch"
                                        type="text"
                                        :placeholder="selectedInvestorId ? investors.find(i => i.id.toString() === selectedInvestorId)?.name || 'Search investor...' : 'Investor'"
                                        class="w-full sm:w-40"
                                        @focus="showInvestorDropdown = true; fetchInvestors()"
                                        @input="showInvestorDropdown = true"
                                    />
                                    <div 
                                        v-if="showInvestorDropdown && (investorSearch || filteredInvestors.length > 0)"
                                        class="absolute z-10 w-full sm:w-64 mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-md shadow-lg max-h-60 overflow-auto"
                                    >
                                        <button
                                            type="button"
                                            class="w-full text-left px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700"
                                            :class="{ 'bg-blue-50 dark:bg-blue-900/20': !selectedInvestorId }"
                                            @click="selectedInvestorId = null; investorSearch = ''; showInvestorDropdown = false; reloadDashboard()"
                                        >
                                            <span class="font-medium">All Investors (Overall)</span>
                                        </button>
                                        <div
                                            v-for="investor in filteredInvestors"
                                            :key="investor.id"
                                            class="px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer"
                                            :class="{ 'bg-blue-50 dark:bg-blue-900/20': selectedInvestorId === investor.id.toString() }"
                                            @click="selectedInvestorId = investor.id.toString(); investorSearch = investor.name; showInvestorDropdown = false; reloadDashboard()"
                                        >
                                            {{ investor.name }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Cage Select -->
                            <div class="cage-dropdown-container" v-if="selectedInvestorId">
                                <div class="relative">
                                    <Input
                                        v-model="cageSearch"
                                        type="text"
                                        :placeholder="selectedCageId ? `Cage ${selectedCageId}` : 'Cage'"
                                        class="w-full sm:w-32"
                                        :disabled="!selectedInvestorId"
                                        @focus="showCageDropdown = true"
                                        @input="showCageDropdown = true"
                                    />
                                    <div 
                                        v-if="showCageDropdown && (cageSearch || filteredCages.length > 0) && selectedInvestorId"
                                        class="absolute z-10 w-full sm:w-64 mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-md shadow-lg max-h-60 overflow-auto"
                                    >
                                        <button
                                            type="button"
                                            class="w-full text-left px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700"
                                            :class="{ 'bg-blue-50 dark:bg-blue-900/20': !selectedCageId }"
                                            @click="selectedCageId = null; cageSearch = ''; showCageDropdown = false; reloadDashboard()"
                                        >
                                            <span class="font-medium">All Cages (Investor Overall)</span>
                                        </button>
                                        <div
                                            v-for="cage in filteredCages"
                                            :key="cage.id"
                                            class="px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer"
                                            :class="{ 'bg-blue-50 dark:bg-blue-900/20': selectedCageId === cage.id.toString() }"
                                            @click="selectedCageId = cage.id.toString(); cageSearch = `Cage ${cage.id}`; showCageDropdown = false; reloadDashboard()"
                                        >
                                            Cage {{ cage.id }} ({{ cage.number_of_fingerlings }} fingerlings)
                                        </div>
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
                <!-- Top Investors -->
                <Card class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Top Performing Investors</h3>
                    <div class="space-y-3">
                        <div v-for="(investor, index) in analytics.top_investors" :key="investor.id" 
                             class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-blue-500 text-white rounded-full flex items-center justify-center text-sm font-bold">
                                    {{ index + 1 }}
                                </div>
                                <div>
                                    <p class="font-medium">{{ investor.name }}</p>
                                    <p class="text-sm text-muted-foreground">{{ investor.samplings_count }} samplings</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="font-medium">{{ formatNumber(investor.samples_sum_weight || 0) }}g</p>
                                <p class="text-sm text-muted-foreground">Total weight</p>
                            </div>
                        </div>
                        <div v-if="!analytics.top_investors?.length" class="text-center py-8 text-muted-foreground">
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

            <!-- Harvest Anticipation -->
            <Card class="p-6">
                <h3 class="text-lg font-semibold mb-4">Harvest Anticipation</h3>
                <p class="text-sm text-muted-foreground mb-4">Estimated when fish in each cage will reach target harvest weight (from latest sampling).</p>
                <div class="space-y-3">
                    <div
                        v-for="row in (analytics.cage_harvest_anticipations || [])"
                        :key="row.cage_id"
                        class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold shrink-0"
                                :class="row.is_ready ? 'bg-green-500 text-white' : 'bg-amber-500 text-white'"
                            >
                                {{ row.is_ready ? '✓' : row.days_until_harvest }}
                            </div>
                            <div>
                                <Link
                                    :href="route('cages.view', row.cage_id)"
                                    class="font-medium text-primary hover:underline"
                                >
                                    Cage {{ row.cage_id }}
                                </Link>
                                <p class="text-sm text-muted-foreground">
                                    {{ row.cage?.investor?.name }} · {{ row.cage?.number_of_fingerlings }} fingerlings
                                </p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p v-if="row.is_ready" class="font-medium text-green-600">Ready for harvest</p>
                            <p v-else class="font-medium">{{ row.days_until_harvest }} days</p>
                            <p class="text-sm text-muted-foreground">
                                {{ formatWeight(row.current_avg_weight_g) }} → {{ formatWeight(row.target_weight_g) }}g target
                            </p>
                            <p v-if="row.estimated_harvest_date" class="text-xs text-muted-foreground mt-0.5">
                                ~{{ new Date(row.estimated_harvest_date).toLocaleDateString() }}
                            </p>
                        </div>
                    </div>
                    <div v-if="!analytics.cage_harvest_anticipations?.length" class="text-center py-8 text-muted-foreground">
                        No cage sampling data yet. Add samplings with samples to see harvest estimates.
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
