<script setup lang="ts">
import { Input } from '@/components/ui/input';
import { Calendar as CalendarIcon } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
    modelValue: string | null;
    class?: string;
    placeholder?: string;
    id?: string;
}>();

const emit = defineEmits(['update:modelValue']);

const dateInput = ref<HTMLInputElement | null>(null);

const formattedDate = computed(() => {
    if (!props.modelValue) return '';
    const date = new Date(props.modelValue);
    if (isNaN(date.getTime())) return props.modelValue;
    
    const day = date.getDate().toString().padStart(2, '0');
    const month = (date.getMonth() + 1).toString().padStart(2, '0');
    const year = date.getFullYear();
    const hours = date.getHours().toString().padStart(2, '0');
    const minutes = date.getMinutes().toString().padStart(2, '0');
    
    return `${day}-${month}-${year} ${hours}:${minutes}`;
});

const inputValue = computed(() => {
    if (!props.modelValue) return '';
    const date = new Date(props.modelValue);
    if (isNaN(date.getTime())) return props.modelValue;

    const year = date.getFullYear();
    const month = (date.getMonth() + 1).toString().padStart(2, '0');
    const day = date.getDate().toString().padStart(2, '0');
    const hours = date.getHours().toString().padStart(2, '0');
    const minutes = date.getMinutes().toString().padStart(2, '0');

    return `${year}-${month}-${day}T${hours}:${minutes}`;
});

const handleDateChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    emit('update:modelValue', target.value);
};

const openPicker = () => {
    try {
        dateInput.value?.showPicker();
    } catch (e) {
        console.error('Error opening date picker:', e);
    }
};
</script>

<template>
    <div class="relative">
        <Input
            :id="id"
            :model-value="formattedDate"
            readonly
            :placeholder="placeholder"
            class="cursor-pointer pr-10"
            :class="props.class"
            @click="openPicker"
            @keydown.enter.prevent="openPicker"
            @keydown.space.prevent="openPicker"
        />
        <div 
            class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-muted-foreground"
        >
            <CalendarIcon class="h-4 w-4" />
        </div>
        
        <input
            ref="dateInput"
            type="datetime-local"
            :value="inputValue"
            @input="handleDateChange"
            class="absolute bottom-0 left-0 h-0 w-0 opacity-0 pointer-events-none"
            tabindex="-1"
        />
    </div>
</template>
