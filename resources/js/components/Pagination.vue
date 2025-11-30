<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';

defineProps<{
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
}>();
</script>

<template>
    <div v-if="links.length > 3" class="flex flex-wrap justify-center gap-1">
        <template v-for="(link, key) in links" :key="key">
            <div v-if="link.url === null" class="flex items-center justify-center px-4 py-2 text-sm text-gray-500 border rounded-md bg-gray-50 dark:bg-gray-900 dark:border-gray-800 dark:text-gray-400 opacity-50 cursor-not-allowed">
                <span v-html="link.label"></span>
            </div>
            <Link v-else :href="link.url" class="inline-flex">
                <Button
                    :variant="link.active ? 'default' : 'outline'"
                    class="h-10 px-3 min-w-[2.5rem]"
                    :class="{ 'bg-primary text-primary-foreground hover:bg-primary/90': link.active }"
                >
                    <span v-html="link.label"></span>
                </Button>
            </Link>
        </template>
    </div>
</template>
