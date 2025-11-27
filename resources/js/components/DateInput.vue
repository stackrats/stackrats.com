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
    
    return `${day}-${month}-${year}`;
});

const handleDateChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    emit('update:modelValue', target.value);
};

const openPicker = () => {
    try {
        dateInput.value?.showPicker();
    } catch (e) {
        // Fallback for browsers that don't support showPicker (though most do now)
        // or if the input is hidden/disabled in a way that prevents it.
        // In this case, since we have the input hidden but present, 
        // we might need a different strategy if showPicker fails, 
        // but for modern Chrome/Edge/Firefox/Safari it works.
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
        
        <!-- 
            Hidden date input to handle the actual date selection.
            We use opacity-0 and absolute positioning to make it "invisible" 
            but still technically there if we needed to overlay it.
            However, we are using showPicker() on it.
            To ensure showPicker works, it shouldn't be display:none.
            We place it absolutely but with 0 size or visibility hidden might block showPicker in some browsers?
            Safest is a tiny invisible input.
        -->
        <input
            ref="dateInput"
            type="date"
            :value="modelValue"
            @input="handleDateChange"
            class="absolute bottom-0 left-0 h-0 w-0 opacity-0 pointer-events-none"
            tabindex="-1"
        />
    </div>
</template>
