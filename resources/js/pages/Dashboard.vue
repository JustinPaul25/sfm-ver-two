<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import Card from '@/components/ui/card/Card.vue';
import Button from '@/components/ui/button/Button.vue';
import Input from '@/components/ui/input/Input.vue';
import SamplingTrendsChart from '@/components/charts/SamplingTrendsChart.vue';
import { router } from '@inertiajs/vue3';

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
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4">
            <!-- Header with period filter -->
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
                    <h3 class="text-lg font-semibold mb-4">Sampling Trends</h3>
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
