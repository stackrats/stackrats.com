<script setup>
import StackratsLogoIcon from "./StackratsLogoIcon.vue";
import StackratsLogoType from "./StackratsLogoType.vue";
</script>

<template>
    <div class="flex justify-center flex-col max-w-2xl items-center">
        <!-- Dark mode -->
        <div class="hidden dark:flex flex-col items-center justify-center w-full">
            <div class="w-1/3 flex">
                <StackratsLogoIcon dark />
            </div>
            <div class="flex w-full md:-mt-10">
                <StackratsLogoType dark />
            </div>
        </div>

        <!-- Light mode -->
        <div class="flex dark:hidden flex-col items-center justify-center w-full">
            <div class="w-1/3 flex">
                <StackratsLogoIcon />
            </div>
            <div class="flex w-full md:-mt-10">
                <StackratsLogoType />
            </div>
        </div>
    </div>
</template>
