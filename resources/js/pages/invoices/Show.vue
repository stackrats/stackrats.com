<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { type BreadcrumbItem } from '@/types';
import { InvoiceStatus, type InvoiceStatusType, type RecurringFrequencyType } from '@/types/enums';
import { Head, Link, router } from '@inertiajs/vue3';
import { Mail, Edit, Trash2, Download, RotateCw, ChevronDown, CheckCircle2 } from 'lucide-vue-next';
import { useEcho } from '@laravel/echo-vue';
import { ref } from 'vue';

interface LineItem {
    description: string;
    quantity: number;
    unit_price: number;
    total?: number;
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
    label:string
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

interface Props {
    invoice: Invoice;
    statuses: InvoiceStatusModel[];
}

const props = defineProps<Props>();

const invoice = ref(props.invoice);
const showEmailSentAlert = ref(false);

// Listen for invoice email sent events
type InvoiceEmailSentPayload = {
    invoice: {
        id: number;
        invoice_number: string;
        invoice_status: InvoiceStatusModel;
        last_sent_at: string;
    };
};

useEcho<InvoiceEmailSentPayload>(
    `invoices.${invoice.value.id}`,
    'InvoiceEmailSent',
    (e) => {
        // Update the invoice status and last_sent_at
        invoice.value = {
            ...invoice.value,
            invoice_status: e.invoice.invoice_status,
            last_sent_at: e.invoice.last_sent_at,
        };
        
        // Show success alert
        showEmailSentAlert.value = true;
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            showEmailSentAlert.value = false;
        }, 5000);
    }
);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Invoices',
        href: '/invoices',
    },
    {
        title: 'View Invoice',
        href: '#',
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

const formatCurrency = (amount: string | number, currency: string) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currency,
    }).format(typeof amount === 'string' ? parseFloat(amount) : amount);
};

const deleteInvoice = (id: number) => {
    if (confirm('Are you sure you want to delete this invoice?')) {
        router.delete(`/invoices/${id}`);
    }
};

const sendInvoice = (id: number) => {
    if (confirm('Are you sure you want to send this invoice?')) {
        router.post(`/invoices/${id}/send`, {}, {
            preserveScroll: true,
        });
    }
};

const updateStatus = (invoiceId: number, statusId: string) => {
    router.patch(`/invoices/${invoiceId}/status`, {
        invoice_status_id: statusId,
    }, {
        preserveScroll: true,
        onSuccess: (page) => {
            // Update the local invoice ref with the new data from the server
            if (page.props.invoice) {
                invoice.value = page.props.invoice as Invoice;
            }
        }
    });
};

const calculateLineTotal = (item: LineItem) => {
    return item.quantity * item.unit_price;
};
</script>

<template>
    <Head :title="`Invoice ${invoice.invoice_number}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Email Sent Alert -->
            <Transition
                enter-active-class="transition ease-out duration-300"
                enter-from-class="opacity-0 transform translate-y-2"
                enter-to-class="opacity-100 transform translate-y-0"
                leave-active-class="transition ease-in duration-200"
                leave-from-class="opacity-100 transform translate-y-0"
                leave-to-class="opacity-0 transform translate-y-2"
            >
                <Alert v-if="showEmailSentAlert" variant="default" class="border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-950">
                    <CheckCircle2 class="text-green-600 dark:text-green-400" />
                    <AlertTitle class="text-green-800 dark:text-green-200">Email sent successfully!</AlertTitle>
                    <AlertDescription class="text-green-700 dark:text-green-300">
                        The invoice has been sent to {{ invoice.recipient_email }}.
                    </AlertDescription>
                </Alert>
            </Transition>
            
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <h1 class="text-2xl font-bold tracking-tight">
                            Invoice {{ invoice.invoice_number }}
                        </h1>
                        <Badge :class="getStatusColor(invoice.invoice_status.name)">
                            {{ invoice.invoice_status.label }}
                        </Badge>
                        <RotateCw
                            v-if="invoice.is_recurring"
                            class="h-5 w-5 text-blue-600"
                            title="Recurring invoice"
                        />
                    </div>
                    <p class="text-sm text-muted-foreground">
                        Created {{ formatDate(invoice.created_at) }}
                    </p>
                </div>
                <div class="flex gap-2">
                    <a :href="`/invoices/${invoice.id}/pdf`" target="_blank">
                        <Button variant="outline" size="sm">
                            <Download class="h-4 w-4 mr-1" />
                            PDF
                        </Button>
                    </a>
                    <Button
                        v-if="invoice.invoice_status.name !== InvoiceStatus.SENT && invoice.invoice_status.name !== InvoiceStatus.PAID"
                        variant="outline"
                        size="sm"
                        @click="sendInvoice(invoice.id)"
                    >
                        <Mail class="h-4 w-4 mr-1" />
                        Send
                    </Button>
                    <Link :href="`/invoices/${invoice.id}/edit`">
                        <Button variant="outline" size="sm">
                            <Edit class="h-4 w-4 mr-1" />
                            Edit
                        </Button>
                    </Link>
                    <Button
                        variant="outline"
                        size="sm"
                        @click="deleteInvoice(invoice.id)"
                        class="text-red-600 hover:text-red-700"
                    >
                        <Trash2 class="h-4 w-4 mr-1" />
                        Delete
                    </Button>
                </div>
            </div>

            <!-- Status Change Section -->
            <Card>
                <div class="p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-semibold mb-1">Invoice status</h3>
                            <p class="text-xs text-muted-foreground">
                                Change the status of this invoice
                            </p>
                        </div>
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button variant="outline" size="sm">
                                    <Badge :class="getStatusColor(invoice.invoice_status.name)" class="mr-2">
                                        {{ invoice.invoice_status.label }}
                                    </Badge>
                                    <ChevronDown class="h-4 w-4" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" class="w-48">
                                <DropdownMenuLabel>Change Status</DropdownMenuLabel>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem
                                    v-for="status in statuses"
                                    :key="status.id"
                                    @click="updateStatus(invoice.id, status.id)"
                                    :disabled="status.id === invoice.invoice_status.id"
                                >
                                    <Badge :class="getStatusColor(status.name)" class="mr-2">
                                        {{ status.label }}
                                    </Badge>
                                    <span v-if="status.id === invoice.invoice_status.id" class="ml-auto text-xs text-muted-foreground">
                                        Current
                                    </span>
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </div>
            </Card>

            <!-- Invoice Content -->
            <Card>
                <div class="p-8 space-y-8">
                    <!-- Recipient Info -->
                    <div class="grid md:grid-cols-2 gap-8">
                        <div>
                            <h3 class="text-sm font-semibold text-muted-foreground uppercase tracking-wide mb-3">
                                Bill To
                            </h3>
                            <div class="space-y-1">
                                <p class="font-semibold text-lg">{{ invoice.recipient_name }}</p>
                                <p class="text-sm text-muted-foreground">{{ invoice.recipient_email }}</p>
                                <p
                                    v-if="invoice.recipient_address"
                                    class="text-sm text-muted-foreground whitespace-pre-line"
                                >
                                    {{ invoice.recipient_address }}
                                </p>
                            </div>
                        </div>

                        <div class="text-right">
                            <h3 class="text-sm font-semibold text-muted-foreground uppercase tracking-wide mb-3">
                                Invoice Details
                            </h3>
                            <div class="space-y-2">
                                <div>
                                    <span class="text-sm text-muted-foreground">Issue Date:</span>
                                    <span class="ml-2 font-medium">{{ formatDate(invoice.issue_date) }}</span>
                                </div>
                                <div>
                                    <span class="text-sm text-muted-foreground">Due Date:</span>
                                    <span class="ml-2 font-medium">{{ formatDate(invoice.due_date) }}</span>
                                </div>
                                <div v-if="invoice.is_recurring && invoice.recurring_frequency">
                                    <span class="text-sm text-muted-foreground">Frequency:</span>
                                    <span class="ml-2 font-medium capitalize">{{ invoice.recurring_frequency.label }}</span>
                                </div>
                                <div v-if="invoice.next_recurring_date">
                                    <span class="text-sm text-muted-foreground">Next Invoice:</span>
                                    <span class="ml-2 font-medium">{{ formatDate(invoice.next_recurring_date) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    <div v-if="invoice.description">
                        <h3 class="text-sm font-semibold text-muted-foreground uppercase tracking-wide mb-2">
                            Description
                        </h3>
                        <p class="text-sm">{{ invoice.description }}</p>
                    </div>

                    <!-- Line Items -->
                    <div v-if="invoice.line_items && invoice.line_items.length > 0">
                        <h3 class="text-sm font-semibold text-muted-foreground uppercase tracking-wide mb-4">
                            Line Items
                        </h3>
                        <div class="border rounded-lg overflow-hidden">
                            <table class="w-full">
                                <thead class="bg-muted/50">
                                    <tr>
                                        <th class="text-left p-3 text-sm font-semibold">Description</th>
                                        <th class="text-right p-3 text-sm font-semibold">Quantity</th>
                                        <th class="text-right p-3 text-sm font-semibold">Unit Price</th>
                                        <th class="text-right p-3 text-sm font-semibold">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="(item, index) in invoice.line_items"
                                        :key="index"
                                        class="border-t"
                                    >
                                        <td class="p-3">{{ item.description }}</td>
                                        <td class="p-3 text-right">{{ item.quantity }}</td>
                                        <td class="p-3 text-right">{{ formatCurrency(item.unit_price, invoice.currency) }}</td>
                                        <td class="p-3 text-right font-medium">
                                            {{ formatCurrency(calculateLineTotal(item), invoice.currency) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Total -->
                    <div class="flex justify-end pt-4 border-t-2">
                        <div class="text-right">
                            <p class="text-sm text-muted-foreground mb-2">Total Amount</p>
                            <p class="text-3xl font-bold">{{ formatCurrency(invoice.amount, invoice.currency) }}</p>
                        </div>
                    </div>

                    <!-- Last Sent Info -->
                    <div v-if="invoice.last_sent_at" class="pt-4 border-t">
                        <p class="text-sm text-muted-foreground">
                            Last sent: {{ formatDate(invoice.last_sent_at) }}
                        </p>
                    </div>
                </div>
            </Card>
        </div>
    </AppLayout>
</template>
