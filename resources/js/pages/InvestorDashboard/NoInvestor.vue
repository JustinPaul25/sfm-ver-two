<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import Card from '@/components/ui/card/Card.vue';

const props = defineProps<{
  message?: string;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Investor Dashboard',
        href: '/investor/dashboard',
    },
];
</script>

<template>
    <Head title="Investor Dashboard - Not Found" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col items-center justify-center p-4">
            <Card class="p-8 max-w-md text-center">
                <div class="text-6xl mb-4">⚠️</div>
                <h2 class="text-2xl font-bold mb-4">Investor Account Not Found</h2>
                <p class="text-muted-foreground mb-6">
                    {{ message || 'You are not associated with any investor account. Please contact the administrator.' }}
                </p>
                <p class="text-sm text-muted-foreground">
                    If you believe this is an error, please contact your system administrator.
                </p>
            </Card>
        </div>
    </AppLayout>
</template>
