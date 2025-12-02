<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { show as showInvoice } from '@/routes/invoices';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage, router, Link } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { useEcho } from '@laravel/echo-vue';
import { Bar, Doughnut } from 'vue-chartjs';
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  BarElement,
  ArcElement,
  CategoryScale,
  LinearScale
} from 'chart.js';

ChartJS.register(CategoryScale, LinearScale, BarElement, ArcElement, Title, Tooltip, Legend);

interface MetricProps {
    total_revenue: number;
    outstanding_amount: number;
    total_sent_count: number;
    revenue_by_year: { year: number; total: number }[];
    revenue_by_contact: { name: string; total: number }[];
    avg_invoice_value: number;
    overdue_rate: number;
    draft_potential: number;
    mrr: number;
    status_distribution: Record<string, number>;
}

interface RecurringInvoice {
    id: string;
    recipient_name: string;
    amount: number;
    next_recurring_date: string;
    frequency: string;
}

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

import { computed } from 'vue';

const props = defineProps<{
    metrics: MetricProps;
    recurring_invoices: RecurringInvoice[];
}>();

const yearChartData = computed(() => ({
    labels: props.metrics.revenue_by_year.map(item => item.year),
    datasets: [
        {
            label: 'Revenue',
            backgroundColor: '#f87171',
            data: props.metrics.revenue_by_year.map(item => item.total),
        },
    ],
}));

const contactChartData = computed(() => ({
    labels: props.metrics.revenue_by_contact.map(item => item.name),
    datasets: [
        {
            label: 'Revenue',
            backgroundColor: '#60a5fa',
            data: props.metrics.revenue_by_contact.map(item => item.total),
        },
    ],
}));

const statusChartData = computed(() => ({
    labels: Object.keys(props.metrics.status_distribution),
    datasets: [
        {
            backgroundColor: ['#4ade80', '#60a5fa', '#f87171', '#94a3b8', '#facc15'],
            data: Object.values(props.metrics.status_distribution),
        },
    ],
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
};

const formatPercentage = (value: number) => {
    return new Intl.NumberFormat('en-NZ', {
        style: 'percent',
        minimumFractionDigits: 1,
        maximumFractionDigits: 1,
    }).format(value);
};

const formatDateTime = (dateString: string) => {
    const date = new Date(dateString);
    const day = date.getDate().toString().padStart(2, '0');
    const month = (date.getMonth() + 1).toString().padStart(2, '0');
    const year = date.getFullYear();
    const hours = date.getHours().toString().padStart(2, '0');
    const minutes = date.getMinutes().toString().padStart(2, '0');
    return `${day}-${month}-${year} ${hours}:${minutes}`;
};
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total revenue</CardTitle>
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
                        <CardTitle class="text-sm font-medium">Invoices sent</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ metrics.total_sent_count }}</div>
                        <p class="text-xs text-muted-foreground">
                            Total invoices sent
                        </p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Avg invoice value</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ formatCurrency(metrics.avg_invoice_value) }}</div>
                        <p class="text-xs text-muted-foreground">
                            Average paid invoice amount
                        </p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Overdue rate</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ formatPercentage(metrics.overdue_rate) }}</div>
                        <p class="text-xs text-muted-foreground">
                            Percentage of sent invoices overdue
                        </p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Draft potential</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ formatCurrency(metrics.draft_potential) }}</div>
                        <p class="text-xs text-muted-foreground">
                            Value of draft invoices
                        </p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">MRR</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ formatCurrency(metrics.mrr) }}</div>
                        <p class="text-xs text-muted-foreground">
                            Monthly recurring revenue
                        </p>
                    </CardContent>
                </Card>
                <Card class="col-span-2">
                    <CardHeader>
                        <CardTitle>Upcoming recurring invoices</CardTitle>
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
                                        {{ invoice.frequency }} - Next: {{ formatDateTime(invoice.next_recurring_date) }}
                                    </p>
                                </div>
                                <div class="ml-auto font-medium">
                                    {{ formatCurrency(invoice.amount) }}
                                </div>
                                <Button variant="outline" size="sm" class="ml-4" as-child>
                                    <Link :href="showInvoice(invoice.id).url">
                                        View
                                    </Link>
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <Card>
                    <CardHeader>
                        <CardTitle>Revenue by year</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="h-[300px]">
                            <Bar :data="yearChartData" :options="chartOptions" />
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle>Top contacts by revenue</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="h-[300px]">
                            <Bar :data="contactChartData" :options="chartOptions" />
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle>Invoice status distribution</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="h-[300px]">
                            <Doughnut :data="statusChartData" :options="chartOptions" />
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
