<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import darkIconUrl from '../../images/stackrats-icon-dark.svg?url';
import lightIconUrl from '../../images/stackrats-icon-light.svg?url';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
    <img :src="darkIconUrl" :class="className" v-bind="$attrs" alt="StackRats Icon" class="hidden dark:flex" />
    <img :src="lightIconUrl" :class="className" v-bind="$attrs" alt="StackRats Icon" class="flex dark:hidden" />
</template>
