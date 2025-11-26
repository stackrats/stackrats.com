<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import DateInput from '@/components/DateInput.vue';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import InputError from '@/components/InputError.vue';
import { type BreadcrumbItem } from '@/types';
import { type RecurringFrequencyType, type InvoiceStatusType } from '@/types/enums';
import { Head, useForm } from '@inertiajs/vue3';
import { Plus, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

interface LineItem {
    description: string;
    quantity: number;
    unit_price: number;
}

interface InvoiceStatusModel {
    id: string;
    name: InvoiceStatusType;
    sort_order: number;
}

interface RecurringFrequencyModel {
    id: string;
    name: RecurringFrequencyType;
    sort_order: number;
    label: string;
}

interface Contact {
    id: string;
    name: string;
    email: string;
    address?: string;
    phone?: string;
}

interface Props {
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
        title: 'Create Invoice',
        href: '/invoices/create',
    },
];

const form = useForm({
    recipient_name: '',
    recipient_email: '',
    recipient_address: '',
    contact_id: '',
    amount: '',
    gst: 0,
    currency: 'NZD',
    description: '',
    line_items: [] as LineItem[],
    issue_date: new Date().toISOString().split('T')[0],
    due_date: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
    is_recurring: false,
    recurring_frequency_id: '',
    next_recurring_date: '',
});

const lineItems = ref<LineItem[]>([
    { description: '', quantity: 1, unit_price: 0 },
]);

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
    const subtotal = lineItems.value.reduce((acc, item) => {
        return acc + (item.quantity * item.unit_price);
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
    form.post('/invoices');
};
</script>

<template>
    <Head title="Create Invoice" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4 lg:p-6">
            <div class="flex flex-col justify-between gap-4 border-b pb-4 sm:flex-row sm:items-end">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">Create Invoice</h1>
                    <p class="text-sm text-muted-foreground">
                        Capture who you are billing, what for, and how often.
                    </p>
                </div>

                <div class="flex flex-col items-stretch gap-2 text-right sm:flex-row sm:items-center sm:gap-4">
                    <div class="text-xs text-muted-foreground">
                        <div class="font-medium">Invoice total</div>
                        <div class="text-lg font-semibold">
                            {{ form.currency }} ${{ calculateTotal() }}
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            @click="$inertia.visit('/invoices')"
                        >
                            Cancel
                        </Button>
                        <Button
                            type="button"
                            size="sm"
                            :disabled="form.processing"
                            @click="submit"
                        >
                            {{ form.processing ? 'Creating...' : 'Create Invoice' }}
                        </Button>
                    </div>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Recipient + Invoice meta side-by-side on large screens -->
                <div class="grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(0,1.4fr)]">
                    <!-- Recipient Information -->
                    <Card>
                        <div class="p-6 space-y-5">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <h3 class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                                        Recipient
                                    </h3>
                                    <p class="text-xs text-muted-foreground">
                                        Choose a saved contact or enter details manually.
                                    </p>
                                </div>
                                <div class="w-full sm:w-60">
                                    <Label for="contact_id" class="mb-1 block text-xs font-medium">Use contact</Label>
                                    <select
                                        id="contact_id"
                                        class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors file:border-0 file:bg-transparent file:text-xs file:font-medium file:text-foreground placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50 dark:bg-input/30"
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
                                    <Label for="recipient_name">Recipient name *</Label>
                                    <Input
                                        id="recipient_name"
                                        v-model="form.recipient_name"
                                        required
                                        placeholder="John Doe"
                                    />
                                    <InputError :message="form.errors.recipient_name" />
                                </div>

                                <div class="space-y-2">
                                    <Label for="recipient_email">Recipient email *</Label>
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
                                <Label for="recipient_address">Recipient address</Label>
                                <Input
                                    id="recipient_address"
                                    v-model="form.recipient_address"
                                    placeholder="123 Main St, City, State, ZIP"
                                />
                                <InputError :message="form.errors.recipient_address" />
                            </div>
                        </div>
                    </Card>

                    <!-- Invoice meta -->
                    <Card>
                        <div class="p-6 space-y-5">
                            <div>
                                <h3 class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                                    Invoice details
                                </h3>
                                <p class="text-xs text-muted-foreground">
                                    Set the description, dates, currency and tax.
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label for="description">Invoice description</Label>
                                <Input
                                    id="description"
                                    v-model="form.description"
                                    placeholder="Brief description of the work or services"
                                />
                                <InputError :message="form.errors.description" />
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="space-y-2">
                                    <Label for="issue_date">Issue date *</Label>
                                    <DateInput
                                        id="issue_date"
                                        v-model="form.issue_date"
                                    />
                                    <InputError :message="form.errors.issue_date" />
                                </div>

                                <div class="space-y-2">
                                    <Label for="due_date">Due date *</Label>
                                    <DateInput
                                        id="due_date"
                                        v-model="form.due_date"
                                    />
                                    <InputError :message="form.errors.due_date" />
                                </div>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-[1.5fr_1fr]">
                                <div class="space-y-2">
                                    <Label for="currency">Currency *</Label>
                                    <select
                                        id="currency"
                                        v-model="form.currency"
                                        class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors file:border-0 file:bg-transparent file:text-xs file:font-medium file:text-foreground placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50 dark:bg-input/30"
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
                                    <Label for="gst">GST rate (%)</Label>
                                    <Input
                                        id="gst"
                                        v-model.number="form.gst"
                                        type="number"
                                        min="0"
                                        step="0.1"
                                        @input="calculateTotal"
                                    />
                                    <p class="text-xs text-muted-foreground">Use 0 for non-taxable invoices.</p>
                                    <InputError :message="form.errors.gst" />
                                </div>
                            </div>
                        </div>
                    </Card>
                </div>

                <!-- Line items + recurring and totals -->
                <div class="grid gap-6 xl:grid-cols-[minmax(0,2.2fr)_minmax(0,1.3fr)]">
                    <!-- Line Items -->
                    <Card>
                        <div class="p-6 space-y-4">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <h3 class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                                        Line items
                                    </h3>
                                    <p class="text-xs text-muted-foreground">
                                        Add each product or service you are billing for.
                                    </p>
                                </div>
                                <Button type="button" variant="outline" size="sm" @click="addLineItem">
                                    <Plus class="h-4 w-4 mr-1" />
                                    Add item
                                </Button>
                            </div>

                            <div class="hidden rounded-md bg-muted/40 px-4 py-2 text-xs text-muted-foreground md:grid md:grid-cols-[2fr_1fr_1fr_1fr] md:gap-4">
                                <span>Description</span>
                                <span>Qty</span>
                                <span>Unit price</span>
                                <span class="text-right">Line total</span>
                            </div>

                            <div class="space-y-3">
                                <div
                                    v-for="(item, index) in lineItems"
                                    :key="index"
                                    class="rounded-md border bg-background/40 p-3 md:grid md:grid-cols-[2fr_1fr_1fr_1fr_auto] md:items-start md:gap-4"
                                >
                                    <div class="space-y-1 md:space-y-2">
                                        <Label :for="`item_desc_${index}`" class="md:sr-only">Description</Label>
                                        <Input
                                            :id="`item_desc_${index}`"
                                            v-model="item.description"
                                            placeholder="Design work, consultation hours..."
                                            @input="calculateTotal"
                                        />
                                    </div>

                                    <div class="mt-3 space-y-1 md:mt-0 md:space-y-2">
                                        <Label :for="`item_qty_${index}`" class="text-xs text-muted-foreground md:sr-only">Qty</Label>
                                        <Input
                                            :id="`item_qty_${index}`"
                                            v-model.number="item.quantity"
                                            type="number"
                                            min="1"
                                            @input="calculateTotal"
                                        />
                                    </div>

                                    <div class="mt-3 space-y-1 md:mt-0 md:space-y-2">
                                        <Label :for="`item_price_${index}`" class="text-xs text-muted-foreground md:sr-only">Unit price</Label>
                                        <Input
                                            :id="`item_price_${index}`"
                                            v-model.number="item.unit_price"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            @input="calculateTotal"
                                        />
                                    </div>

                                    <div class="mt-3 flex items-center justify-between text-sm font-medium md:mt-0 md:justify-end">
                                        <span class="md:hidden text-xs text-muted-foreground">Line total</span>
                                        <span>{{ form.currency }} ${{ calculateLineTotal(item) }}</span>
                                    </div>

                                    <div class="mt-3 flex justify-end md:mt-0">
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            @click="removeLineItem(index)"
                                            :disabled="lineItems.length === 1"
                                            class="text-red-500 hover:text-red-600"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Card>

                    <!-- Sidebar: recurring + summary -->
                    <div class="space-y-4">
                        <!-- Recurring Options -->
                        <Card>
                            <div class="p-6 space-y-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <h3 class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                                            Recurring invoice
                                        </h3>
                                        <p class="text-xs text-muted-foreground">
                                            Turn this into an automatic repeating invoice.
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <Checkbox
                                            id="is_recurring"
                                            v-model="form.is_recurring"
                                        />
                                        <Label
                                            for="is_recurring"
                                            class="text-xs font-medium cursor-pointer"
                                        >
                                            Recurring
                                        </Label>
                                    </div>
                                </div>

                                <div v-if="form.is_recurring" class="space-y-3">
                                    <div class="space-y-2">
                                        <Label for="recurring_frequency_id">Frequency *</Label>
                                        <select
                                            id="recurring_frequency_id"
                                            v-model="form.recurring_frequency_id"
                                            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors file:border-0 file:bg-transparent file:text-xs file:font-medium file:text-foreground placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50 dark:bg-input/30"
                                            required
                                        >
                                            <option value="">Select frequency</option>
                                            <option v-for="frequency in frequencies" :key="frequency.id" :value="frequency.id">
                                                {{ frequency.label }}
                                            </option>
                                        </select>
                                        <InputError :message="form.errors.recurring_frequency_id" />
                                    </div>

                                    <div class="space-y-2">
                                        <Label for="next_recurring_date">Next recurring date</Label>
                                        <DateInput
                                            id="next_recurring_date"
                                            v-model="form.next_recurring_date"
                                        />
                                        <p class="text-xs text-muted-foreground">
                                            Leave blank to let the system calculate the next date.
                                        </p>
                                        <InputError :message="form.errors.next_recurring_date" />
                                    </div>
                                </div>
                            </div>
                        </Card>

                        <!-- Summary -->
                        <Card>
                            <div class="p-6 space-y-3">
                                <h3 class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                                    Invoice summary
                                </h3>

                                <div class="space-y-1 text-sm">
                                    <div class="flex items-center justify-between text-muted-foreground">
                                        <span>Subtotal</span>
                                        <span>{{ form.currency }} ${{ calculateSubtotal() }}</span>
                                    </div>
                                    <div class="flex items-center justify-between text-muted-foreground">
                                        <span>GST ({{ form.gst }}%)</span>
                                        <span>{{ form.currency }} ${{ calculateGst() }}</span>
                                    </div>
                                    <div class="flex items-center justify-between border-t pt-2 text-base font-semibold">
                                        <span>Total due</span>
                                        <span>{{ form.currency }} ${{ calculateTotal() }}</span>
                                    </div>
                                </div>

                                <p class="text-xs text-muted-foreground">
                                    Totals update automatically as you edit line items.
                                </p>
                            </div>
                        </Card>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
