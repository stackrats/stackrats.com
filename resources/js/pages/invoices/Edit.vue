<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import InputError from '@/components/InputError.vue';
import { type BreadcrumbItem } from '@/types';
import { type InvoiceStatusType, type RecurringFrequencyType } from '@/types/enums';
import { Head, useForm } from '@inertiajs/vue3';
import { Plus, Trash2 } from 'lucide-vue-next';
import { ref, onMounted } from 'vue';

interface LineItem {
    description: string;
    quantity: number;
    unit_price: number;
}

interface InvoiceStatusModel {
    id: string;
    name: InvoiceStatusType;
    sort_order: number;
    label:string;
}

interface RecurringFrequencyModel {
    id: string;
    name: RecurringFrequencyType;
    sort_order: number;
}

interface Contact {
    id: string;
    name: string;
    email: string;
    address?: string;
    phone?: string;
}

interface Invoice {
    id: number;
    invoice_number: string;
    contact_id?: string;
    recipient_name: string;
    recipient_email: string;
    recipient_address?: string;
    amount: string;
    gst: number;
    currency: string;
    description?: string;
    line_items?: LineItem[];
    invoice_status: InvoiceStatusModel;
    issue_date: string;
    due_date: string;
    is_recurring: boolean;
    recurring_frequency?: RecurringFrequencyModel;
    next_recurring_date?: string;
}

interface Props {
    invoice: Invoice;
    statuses: InvoiceStatusModel[];
    frequencies: RecurringFrequencyModel[];
    contacts: Contact[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Invoices',
        href: '/invoices',
    },
    {
        title: 'Edit invoice',
        href: '#',
    },
];

const form = useForm({
    contact_id: props.invoice.contact_id || '',
    recipient_name: props.invoice.recipient_name,
    recipient_email: props.invoice.recipient_email,
    recipient_address: props.invoice.recipient_address || '',
    amount: props.invoice.amount,
    gst: props.invoice.gst || 0,
    currency: props.invoice.currency,
    description: props.invoice.description || '',
    line_items: [] as LineItem[],
    invoice_status_id: props.invoice.invoice_status.id,
    issue_date: props.invoice.issue_date.split('T')[0],
    due_date: props.invoice.due_date.split('T')[0],
    is_recurring: props.invoice.is_recurring,
    recurring_frequency_id: props.invoice.recurring_frequency?.id || '',
    next_recurring_date: props.invoice.next_recurring_date ? props.invoice.next_recurring_date.split('T')[0] : '',
});

const lineItems = ref<LineItem[]>(
    props.invoice.line_items && props.invoice.line_items.length > 0
        ? props.invoice.line_items
        : [{ description: '', quantity: 1, unit_price: 0 }]
);

const addLineItem = () => {
    lineItems.value.push({ description: '', quantity: 1, unit_price: 0 });
};

const removeLineItem = (index: number) => {
    lineItems.value.splice(index, 1);
};

const calculateLineTotal = (item: LineItem) => {
    return (item.quantity * item.unit_price).toFixed(2);
};

const calculateTotal = () => {
    const subtotal = lineItems.value.reduce((sum, item) => {
        return sum + (item.quantity * item.unit_price);
    }, 0);
    
    const gstAmount = subtotal * (form.gst / 100);
    const total = subtotal + gstAmount;
    
    form.amount = total.toFixed(2);
    return total.toFixed(2);
};

const calculateSubtotal = () => {
    return lineItems.value.reduce((acc, item) => {
        return acc + (item.quantity * item.unit_price);
    }, 0).toFixed(2);
};

const calculateGst = () => {
    const subtotal = parseFloat(calculateSubtotal());
    return (subtotal * (form.gst / 100)).toFixed(2);
};

const onContactSelect = (event: Event) => {
    const select = event.target as HTMLSelectElement;
    const contactId = select.value;
    const contact = props.contacts.find(c => c.id === contactId);
    
    if (contact) {
        form.recipient_name = contact.name;
        form.recipient_email = contact.email;
        form.recipient_address = contact.address || '';
    }
};

const submit = () => {
    form.line_items = lineItems.value;
    calculateTotal();
    form.put(`/invoices/${props.invoice.id}`);
};

onMounted(() => {
    calculateTotal();
});
</script>

<template>
    <Head title="Edit invoice" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="mb-4">
                <h1 class="text-2xl font-bold tracking-tight">Edit invoice</h1>
                <p class="text-sm text-muted-foreground">
                    Update invoice details
                </p>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Recipient Information -->
                <Card>
                    <div class="p-6 space-y-4">
                        <div class="flex justify-between items-center">
                            <h3 class="text-lg font-semibold">Recipient Information</h3>
                            <div class="w-64">
                                <select
                                    class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-sm transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium file:text-foreground placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50 md:text-sm dark:bg-input/30"
                                    v-model="form.contact_id"
                                    @change="onContactSelect"
                                >
                                    <option value="">Select a contact...</option>
                                    <option v-for="contact in contacts" :key="contact.id" :value="contact.id">
                                        {{ contact.name }}
                                    </option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="recipient_name">Recipient Name *</Label>
                                <Input
                                    id="recipient_name"
                                    v-model="form.recipient_name"
                                    required
                                    placeholder="John Doe"
                                />
                                <InputError :message="form.errors.recipient_name" />
                            </div>

                            <div class="space-y-2">
                                <Label for="recipient_email">Recipient Email *</Label>
                                <Input
                                    id="recipient_email"
                                    v-model="form.recipient_email"
                                    type="email"
                                    required
                                    placeholder="john@example.com"
                                />
                                <InputError :message="form.errors.recipient_email" />
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label for="recipient_address">Recipient Address</Label>
                            <Input
                                id="recipient_address"
                                v-model="form.recipient_address"
                                placeholder="123 Main St, City, State, ZIP"
                            />
                            <InputError :message="form.errors.recipient_address" />
                        </div>
                    </div>
                </Card>

                <!-- Invoice Details -->
                <Card>
                    <div class="p-6 space-y-4">
                        <h3 class="text-lg font-semibold">Invoice Details</h3>

                        <div class="space-y-2">
                            <Label for="description">Description</Label>
                            <Input
                                id="description"
                                v-model="form.description"
                                placeholder="Brief description of the invoice"
                            />
                            <InputError :message="form.errors.description" />
                        </div>

                        <div class="grid gap-4 md:grid-cols-4">
                            <div class="space-y-2">
                                <Label for="invoice_status_id">Status *</Label>
                                <select
                                    id="invoice_status_id"
                                    v-model="form.invoice_status_id"
                                    class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-sm transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium file:text-foreground placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50 md:text-sm dark:bg-input/30"
                                    required
                                >
                                    <option v-for="status in statuses" :key="status.id" :value="status.id">
                                        {{ status.label }}
                                    </option>
                                </select>
                                <InputError :message="form.errors.invoice_status_id" />
                            </div>

                            <div class="space-y-2">
                                <Label for="issue_date">Issue Date *</Label>
                                <Input
                                    id="issue_date"
                                    v-model="form.issue_date"
                                    type="date"
                                    required
                                />
                                <InputError :message="form.errors.issue_date" />
                            </div>

                            <div class="space-y-2">
                                <Label for="due_date">Due Date *</Label>
                                <Input
                                    id="due_date"
                                    v-model="form.due_date"
                                    type="date"
                                    required
                                />
                                <InputError :message="form.errors.due_date" />
                            </div>

                            <div class="space-y-2">
                                <Label for="currency">Currency *</Label>
                                <select
                                    id="currency"
                                    v-model="form.currency"
                                    class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-sm transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium file:text-foreground placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50 md:text-sm dark:bg-input/30"
                                    required
                                >
                                    <option value="NZD">NZD</option>
                                    <option value="AUD">AUD</option>
                                    <option value="USD">USD</option>
                                    <option value="EUR">EUR</option>
                                    <option value="GBP">GBP</option>
                                    <option value="CAD">CAD</option>
                                </select>
                                <InputError :message="form.errors.currency" />
                            </div>

                            <div class="space-y-2">
                                <Label for="gst">GST (%)</Label>
                                <Input
                                    id="gst"
                                    v-model.number="form.gst"
                                    type="number"
                                    min="0"
                                    step="0.1"
                                    @input="calculateTotal"
                                />
                                <InputError :message="form.errors.gst" />
                            </div>
                        </div>
                    </div>
                </Card>

                <!-- Line Items -->
                <Card>
                    <div class="p-6 space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold">Line Items</h3>
                            <Button type="button" variant="outline" size="sm" @click="addLineItem">
                                <Plus class="h-4 w-4 mr-1" />
                                Add Item
                            </Button>
                        </div>

                        <div
                            v-for="(item, index) in lineItems"
                            :key="index"
                            class="grid gap-4 md:grid-cols-[2fr_1fr_1fr_1fr_auto] items-start border-b pb-4 last:border-b-0"
                        >
                            <div class="space-y-2">
                                <Label :for="`item_desc_${index}`">Description</Label>
                                <Input
                                    :id="`item_desc_${index}`"
                                    v-model="item.description"
                                    placeholder="Item description"
                                    @input="calculateTotal"
                                />
                            </div>

                            <div class="space-y-2">
                                <Label :for="`item_qty_${index}`">Quantity</Label>
                                <Input
                                    :id="`item_qty_${index}`"
                                    v-model.number="item.quantity"
                                    type="number"
                                    min="1"
                                    @input="calculateTotal"
                                />
                            </div>

                            <div class="space-y-2">
                                <Label :for="`item_price_${index}`">Unit Price</Label>
                                <Input
                                    :id="`item_price_${index}`"
                                    v-model.number="item.unit_price"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    @input="calculateTotal"
                                />
                            </div>

                            <div class="space-y-2">
                                <Label>Total</Label>
                                <div class="h-9 flex items-center font-medium">
                                    ${{ calculateLineTotal(item) }}
                                </div>
                            </div>

                            <div class="space-y-2">
                                <Label class="invisible">Action</Label>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    @click="removeLineItem(index)"
                                    :disabled="lineItems.length === 1"
                                    class="text-red-600"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>

                        <div class="flex justify-end pt-4 border-t">
                            <div class="text-right space-y-1">
                                <div class="flex justify-between gap-8 text-sm text-muted-foreground">
                                    <span>Subtotal</span>
                                    <span>{{ form.currency }} ${{ calculateSubtotal() }}</span>
                                </div>
                                <div class="flex justify-between gap-8 text-sm text-muted-foreground">
                                    <span>GST ({{ form.gst }}%)</span>
                                    <span>{{ form.currency }} ${{ calculateGst() }}</span>
                                </div>
                                <div class="flex justify-between gap-8 font-bold text-lg pt-2 border-t">
                                    <span>Total</span>
                                    <span>{{ form.currency }} ${{ calculateTotal() }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </Card>

                <!-- Recurring Options -->
                <Card>
                    <div class="p-6 space-y-4">
                        <h3 class="text-lg font-semibold">Recurring Invoice</h3>

                        <div class="flex items-center space-x-2">
                            <Checkbox
                                id="is_recurring"
                                v-model="form.is_recurring"
                            />
                            <Label
                                for="is_recurring"
                                class="text-sm font-normal cursor-pointer"
                            >
                                Make this a recurring invoice
                            </Label>
                        </div>

                        <div v-if="form.is_recurring" class="space-y-2">
                            <Label for="recurring_frequency_id">Frequency *</Label>
                            <select
                                id="recurring_frequency_id"
                                v-model="form.recurring_frequency_id"
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-sm transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium file:text-foreground placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50 md:text-sm dark:bg-input/30"
                                required
                            >
                                <option value="">Select frequency</option>
                                <option v-for="frequency in frequencies" :key="frequency.id" :value="frequency.id">
                                    {{ frequency.name }}
                                </option>
                            </select>
                            <InputError :message="form.errors.recurring_frequency_id" />
                        </div>

                        <div v-if="form.is_recurring" class="space-y-2">
                            <Label for="next_recurring_date">Next Recurring Date</Label>
                            <Input
                                id="next_recurring_date"
                                v-model="form.next_recurring_date"
                                type="date"
                                :min="form.issue_date"
                            />
                            <p class="text-sm text-muted-foreground">
                                Leave blank to automatically calculate based on frequency.
                            </p>
                            <InputError :message="form.errors.next_recurring_date" />
                        </div>
                    </div>
                </Card>

                <!-- Actions -->
                <div class="flex justify-end gap-4">
                    <Button
                        type="button"
                        variant="outline"
                        @click="$inertia.visit('/invoices')"
                    >
                        Cancel
                    </Button>
                    <Button
                        type="submit"
                        :disabled="form.processing"
                    >
                        {{ form.processing ? 'Saving...' : 'Save Changes' }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
