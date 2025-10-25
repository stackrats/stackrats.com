<script setup>
import { Vue3Lottie } from "vue3-lottie";

import { computed, ref, watch } from "vue";
import StackratsIconDark from '../../../animations/json/stackrats-icon-dark.json';
import StackratsIconLight from '../../../animations/json/stackrats-icon-light.json';

const { dark } = defineProps({
    dark: Boolean,
});

const stackratsIconAnimation = ref(null);

const animationData = computed(() => {
    if (dark) {
        return StackratsIconDark;
    } else {
        return StackratsIconLight
    }
})

const forwardCompleted = ref(false);
const reverseCompleted = ref(false);

const currentDirection = ref("forward");

const play = () => {
    stackratsIconAnimation.value.play();
};
const pause = () => {
    stackratsIconAnimation.value.pause();
};
const stop = () => {
    stackratsIconAnimation.value.stop();
};

const playReverseAnimation = () => {
    pause()
    currentDirection.value = "reverse"
    stackratsIconAnimation.value.setDirection(currentDirection.value);
    play();
};

const playForwardAnimation = () => {
    pause()
    currentDirection.value = "forward"
    stackratsIconAnimation.value.setDirection(currentDirection.value);
    play();
};


const toggleDirection = () => {
    if (currentDirection.value === "forward") {
        playReverseAnimation()
    } else {
        playForwardAnimation()
    }
};

const handleClick = () => {
    forwardCompleted.value = false
    reverseCompleted.value = false

    toggleDirection()
}

const handleCompleted = () => {
    if (currentDirection.value === 'forward') {
        forwardCompleted.value = true
        reverseCompleted.value = false
    } else {
        reverseCompleted.value = true
        forwardCompleted.value = false
    }
}

watch(reverseCompleted, (val) => {
    if (val) {
        playForwardAnimation()
    }
})
</script>

<template>
    <Vue3Lottie ref="stackratsIconAnimation" :animationData="animationData" no-margin @click="handleClick" :loop="1"
        @on-complete="handleCompleted" />
</template>