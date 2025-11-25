<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { Plus, Pencil, Trash2, Mail, Phone, MapPin } from 'lucide-vue-next';

interface Contact {
    id: string;
    name: string;
    email: string;
    address?: string;
    phone?: string;
}

interface Props {
    contacts: Contact[];
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Contacts',
        href: '/contacts',
    },
];

const deleteContact = (id: string) => {
    if (confirm('Are you sure you want to delete this contact?')) {
        router.delete(`/contacts/${id}`);
    }
};
</script>

<template>
    <Head title="Contacts" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">Contacts</h1>
                    <p class="text-sm text-muted-foreground">
                        Manage your address book
                    </p>
                </div>
                <Button @click="$inertia.visit('/contacts/create')">
                    <Plus class="mr-2 h-4 w-4" />
                    New Contact
                </Button>
            </div>

            <div v-if="contacts.length === 0" class="flex flex-col items-center justify-center py-12 text-center">
                <div class="rounded-full bg-muted p-4 mb-4">
                    <Mail class="h-8 w-8 text-muted-foreground" />
                </div>
                <h3 class="text-lg font-semibold">No contacts yet</h3>
                <p class="text-muted-foreground mb-4">Create your first contact to get started.</p>
                <Button @click="$inertia.visit('/contacts/create')">
                    Create Contact
                </Button>
            </div>

            <div v-else class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <Card v-for="contact in contacts" :key="contact.id" class="overflow-hidden">
                    <div class="p-6">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="font-semibold text-lg">{{ contact.name }}</h3>
                                <p class="text-sm text-muted-foreground">{{ contact.email }}</p>
                            </div>
                            <div class="flex gap-2">
                                <Button variant="ghost" size="icon" @click="$inertia.visit(`/contacts/${contact.id}/edit`)">
                                    <Pencil class="h-4 w-4" />
                                </Button>
                                <Button variant="ghost" size="icon" class="text-red-600 hover:text-red-700" @click="deleteContact(contact.id)">
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>

                        <div class="space-y-2 text-sm">
                            <div v-if="contact.phone" class="flex items-center gap-2 text-muted-foreground">
                                <Phone class="h-4 w-4" />
                                <span>{{ contact.phone }}</span>
                            </div>
                            <div v-if="contact.address" class="flex items-start gap-2 text-muted-foreground">
                                <MapPin class="h-4 w-4 mt-0.5" />
                                <span>{{ contact.address }}</span>
                            </div>
                        </div>
                    </div>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
