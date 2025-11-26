<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage, router } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useEcho } from '@laravel/echo-vue';

interface MetricProps {
    total_revenue: number;
    outstanding_amount: number;
    total_sent_count: number;
}

interface RecurringInvoice {
    id: string;
    recipient_name: string;
    amount: number;
    next_recurring_date: string;
    frequency: string;
}

defineProps<{
    metrics: MetricProps;
    recurring_invoices: RecurringInvoice[];
}>();

const page = usePage();
const user = page.props.auth.user;

useEcho(`App.Models.User.${user.id}`, 'InvoiceCreated', () => {
    router.reload();
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-NZ', {
        style: 'currency',
        currency: 'NZD',
    }).format(amount);
};
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Revenue</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ formatCurrency(metrics.total_revenue) }}</div>
                        <p class="text-xs text-muted-foreground">
                            Total paid invoices
                        </p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Outstanding</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ formatCurrency(metrics.outstanding_amount) }}</div>
                        <p class="text-xs text-muted-foreground">
                            Sent and overdue invoices
                        </p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Invoices Sent</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ metrics.total_sent_count }}</div>
                        <p class="text-xs text-muted-foreground">
                            Total invoices sent
                        </p>
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-7">
                <Card class="col-span-4">
                    <CardHeader>
                        <CardTitle>Upcoming Recurring Invoices</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-8">
                            <div v-if="recurring_invoices.length === 0" class="text-sm text-muted-foreground">
                                No upcoming recurring invoices.
                            </div>
                            <div v-for="invoice in recurring_invoices" :key="invoice.id" class="flex items-center">
                                <div class="ml-4 space-y-1">
                                    <p class="text-sm font-medium leading-none">{{ invoice.recipient_name }}</p>
                                    <p class="text-sm text-muted-foreground">
                                        {{ invoice.frequency }} - Next: {{ invoice.next_recurring_date }}
                                    </p>
                                </div>
                                <div class="ml-auto font-medium">
                                    {{ formatCurrency(invoice.amount) }}
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
