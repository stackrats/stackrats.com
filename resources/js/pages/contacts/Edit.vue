<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';

interface Contact {
    id: string;
    name: string;
    email: string;
    address?: string;
    phone?: string;
}

interface Props {
    contact: Contact;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Contacts',
        href: '/contacts',
    },
    {
        title: 'Edit contact',
        href: '#',
    },
];

const form = useForm({
    name: props.contact.name,
    email: props.contact.email,
    address: props.contact.address || '',
    phone: props.contact.phone || '',
});

const submit = () => {
    form.put(`/contacts/${props.contact.id}`);
};
</script>

<template>
    <Head title="Edit contact" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="mb-4">
                <h1 class="text-2xl font-bold tracking-tight">Edit contact</h1>
                <p class="text-sm text-muted-foreground">
                    Update contact details
                </p>
            </div>

            <form @submit.prevent="submit" class="space-y-6 max-w-2xl">
                <Card>
                    <div class="p-6 space-y-4">
                        <div class="space-y-2">
                            <Label for="name">Name *</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                required
                                placeholder="John Doe"
                            />
                            <InputError :message="form.errors.name" />
                        </div>

                        <div class="space-y-2">
                            <Label for="email">Email *</Label>
                            <Input
                                id="email"
                                v-model="form.email"
                                type="email"
                                required
                                placeholder="john@example.com"
                            />
                            <InputError :message="form.errors.email" />
                        </div>

                        <div class="space-y-2">
                            <Label for="phone">Phone</Label>
                            <Input
                                id="phone"
                                v-model="form.phone"
                                placeholder="+1 234 567 890"
                            />
                            <InputError :message="form.errors.phone" />
                        </div>

                        <div class="space-y-2">
                            <Label for="address">Address</Label>
                            <Input
                                id="address"
                                v-model="form.address"
                                placeholder="123 Main St, City, State, ZIP"
                            />
                            <InputError :message="form.errors.address" />
                        </div>
                    </div>
                </Card>

                <div class="flex justify-end gap-4">
                    <Button
                        type="button"
                        variant="outline"
                        @click="$inertia.visit('/contacts')"
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
