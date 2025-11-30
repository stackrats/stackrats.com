<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { type BreadcrumbItem } from '@/types';
import { InvoiceStatus, type InvoiceStatusType, type RecurringFrequencyType } from '@/types/enums';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { 
    Plus, 
    FileText, 
    Mail, 
    Trash2, 
    Eye, 
    Edit,
    RotateCw,
    CheckCircle2,
    Search
} from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { useEcho } from '@laravel/echo-vue';
import Pagination from '@/components/Pagination.vue';

interface LineItem {
    description: string;
    quantity: number;
    unit_price: number;
    total: number;
}

interface InvoiceStatusModel {
    id: string;
    name: InvoiceStatusType;
    sort_order: number;
    label: string
}

interface RecurringFrequencyModel {
    id: string;
    name: RecurringFrequencyType;
    sort_order: number;
}

interface Invoice {
    id: number;
    invoice_number: string;
    recipient_name: string;
    recipient_email: string;
    recipient_address?: string;
    amount: string;
    currency: string;
    description?: string;
    line_items?: LineItem[];
    invoice_status: InvoiceStatusModel;
    issue_date: string;
    due_date: string;
    is_recurring: boolean;
    recurring_frequency?: RecurringFrequencyModel;
    next_recurring_date?: string;
    last_sent_at?: string;
    created_at: string;
    updated_at: string;
}

interface PaginatedInvoices {
    data: Invoice[];
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
}

interface Props {
    invoices: PaginatedInvoices;
    filters?: {
        search?: string;
    };
}

const props = defineProps<Props>();

const page = usePage();
const user = page.props.auth.user;

const search = ref(props.filters?.search || '');

let timeout: ReturnType<typeof setTimeout>;
watch(search, (value) => {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
        router.get('/invoices', { search: value }, {
            preserveState: true,
            replace: true,
            preserveScroll: true
        });
    }, 300);
});

useEcho(`App.Models.User.${user.id}`, 'InvoiceCreated', () => {
    router.reload();
});

const showSuccess = ref(false);
const successMessage = ref('');

watch(() => page.props.flash, (flash: any) => {
    if (flash?.success) {
        successMessage.value = flash.success;
        showSuccess.value = true;
        setTimeout(() => {
            showSuccess.value = false;
        }, 5000);
    }
}, { immediate: true });

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Invoices',
        href: '/invoices',
    },
];

const getStatusColor = (status: InvoiceStatusType) => {
    const colors: Record<InvoiceStatusType, string> = {
        [InvoiceStatus.DRAFT]: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
        [InvoiceStatus.SENT]: 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300',
        [InvoiceStatus.PAID]: 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300',
        [InvoiceStatus.OVERDUE]: 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300',
        [InvoiceStatus.CANCELLED]: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
    };
    return colors[status] || colors[InvoiceStatus.DRAFT];
};

const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    const day = date.getDate().toString().padStart(2, '0');
    const month = (date.getMonth() + 1).toString().padStart(2, '0');
    const year = date.getFullYear();
    return `${day}-${month}-${year}`;
};

const formatCurrency = (amount: string, currency: string) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currency,
    }).format(parseFloat(amount));
};

const deleteInvoice = (id: number) => {
    if (confirm('Are you sure you want to delete this invoice?')) {
        router.delete(`/invoices/${id}`);
    }
};

const sendInvoice = (id: number) => {
    if (confirm('Are you sure you want to send this invoice?')) {
        router.post(`/invoices/${id}/send`);
    }
};
</script>

<template>
    <Head title="Invoices" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Success Alert -->
            <Transition
                enter-active-class="transition ease-out duration-300"
                enter-from-class="opacity-0 transform translate-y-2"
                enter-to-class="opacity-100 transform translate-y-0"
                leave-active-class="transition ease-in duration-200"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <Alert v-if="showSuccess" class="bg-green-50 border-green-200 dark:bg-green-950 dark:border-green-800">
                    <CheckCircle2 class="h-4 w-4 text-green-600 dark:text-green-400" />
                    <AlertDescription class="text-green-800 dark:text-green-200">
                        {{ successMessage }}
                    </AlertDescription>
                </Alert>
            </Transition>

            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">Invoices</h1>
                    <p class="text-sm text-muted-foreground">
                        Manage and track your invoices
                    </p>
                </div>
                <Link href="/invoices/create">
                    <Button>
                        <Plus class="mr-2 h-4 w-4" />
                        Create Invoice
                    </Button>
                </Link>
            </div>

            <div class="relative w-full max-w-sm">
                <Search class="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                <Input
                    v-model="search"
                    placeholder="Search invoice number or contact..."
                    class="pl-8"
                />
            </div>

            <!-- Invoices Grid -->
            <div
                v-if="invoices.data.length > 0"
                class="grid gap-6 md:grid-cols-2 lg:grid-cols-3"
            >
                <Card
                    v-for="invoice in invoices.data"
                    :key="invoice.id"
                    class="hover:shadow-lg transition-all duration-200 group overflow-hidden border-muted/60"
                >
                    <div class="p-6">
                        <!-- Header -->
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <FileText class="h-4 w-4 text-muted-foreground group-hover:text-primary transition-colors" />
                                    <span class="font-mono text-sm font-medium text-muted-foreground">
                                        {{ invoice.invoice_number }}
                                    </span>
                                </div>
                                <h3 class="font-semibold text-lg tracking-tight truncate max-w-[180px]" :title="invoice.recipient_name">
                                    {{ invoice.recipient_name }}
                                </h3>
                            </div>
                            <div class="flex items-center gap-1">
                                <Badge :class="getStatusColor(invoice.invoice_status.name)" variant="secondary" class="capitalize">
                                   {{ invoice.invoice_status.label }}
                                </Badge>
                                <RotateCw
                                    v-if="invoice.is_recurring"
                                    class="h-4 w-4 text-blue-600 ml-1"
                                    title="Recurring invoice"
                                />
                            </div>
                        </div>

                        <!-- Amount -->
                        <div class="mb-4 pb-4 border-b border-dashed">
                            <p class="text-3xl font-bold tracking-tight">
                                {{ formatCurrency(invoice.amount, invoice.currency) }}
                            </p>
                            <p class="text-sm text-muted-foreground mt-1">
                                Due {{ formatDate(invoice.due_date) }}
                            </p>
                        </div>

                        <!-- Description -->
                        <p
                            v-if="invoice.description"
                            class="text-sm text-muted-foreground line-clamp-2 h-10"
                        >
                            {{ invoice.description }}
                        </p>
                        <div v-else class="h-10 mb-6"></div>

                        <!-- Actions -->
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex gap-1">
                                <Link :href="`/invoices/${invoice.id}`">
                                    <Button variant="ghost" size="icon" class="h-8 w-8" title="View">
                                        <Eye class="h-4 w-4" />
                                    </Button>
                                </Link>
                                <Link :href="`/invoices/${invoice.id}/edit`">
                                    <Button variant="ghost" size="icon" class="h-8 w-8" title="Edit">
                                        <Edit class="h-4 w-4" />
                                    </Button>
                                </Link>
                            </div>
                            
                            <div class="flex gap-2">
                                <Button
                                    v-if="invoice.invoice_status.name !== InvoiceStatus.SENT && invoice.invoice_status.name !== InvoiceStatus.PAID"
                                    variant="outline"
                                    size="sm"
                                    @click="sendInvoice(invoice.id)"
                                    class="h-8"
                                >
                                    <Mail class="h-3.5 w-3.5 mr-1.5" />
                                    Send
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    @click="deleteInvoice(invoice.id)"
                                    class="text-muted-foreground hover:text-red-600 h-8 w-8"
                                    title="Delete"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    </div>
                </Card>
            </div>

            <!-- Empty State -->
            <div
                v-else
                class="flex flex-col items-center justify-center py-16 text-center border-2 border-dashed rounded-xl bg-muted/30"
            >
                <div
                    class="rounded-full bg-background p-4 mb-4 shadow-sm"
                >
                    <FileText class="h-8 w-8 text-muted-foreground" />
                </div>
                <h3 class="text-lg font-semibold mb-1">No invoices found</h3>
                <p class="text-sm text-muted-foreground mb-6 max-w-xs mx-auto">
                    Get started by creating your first invoice or try adjusting your search.
                </p>
                <Link href="/invoices/create">
                    <Button>
                        <Plus class="mr-2 h-4 w-4" />
                        Create Invoice
                    </Button>
                </Link>
            </div>

            <!-- Pagination -->
            <div v-if="invoices.data.length > 0" class="mt-8">
                <Pagination :links="invoices.links" />
            </div>
        </div>
    </AppLayout>
</template>
