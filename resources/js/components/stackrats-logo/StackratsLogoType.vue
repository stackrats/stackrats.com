<script setup>
import { computed, ref, watch } from "vue";
import { Vue3Lottie } from "vue3-lottie";
import StackratsTypeDark from "../../../animations/json/stackrats-type-dark.json";
import StackratsTypeLight from '../../../animations/json/stackrats-type-light.json';

const { dark } = defineProps({
    dark: Boolean,
});

const animationData = computed(() => {
    if (dark) {
        return StackratsTypeDark;
    } else {
        return StackratsTypeLight
    }
})

const stackratsTypeAnimation = ref(null);

const forwardCompleted = ref(false);
const reverseCompleted = ref(false);

const currentDirection = ref("forward");

const play = () => {
    stackratsTypeAnimation.value.play();
};
const pause = () => {
    stackratsTypeAnimation.value.pause();
};
const stop = () => {
    stackratsTypeAnimation.value.stop();
};

const playReverseAnimation = () => {
    pause()
    currentDirection.value = "reverse"
    stackratsTypeAnimation.value.setDirection(currentDirection.value);
    play();
};

const playForwardAnimation = () => {
    pause()
    currentDirection.value = "forward"
    stackratsTypeAnimation.value.setDirection(currentDirection.value);
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
    <Vue3Lottie ref="stackratsTypeAnimation" :animationData="animationData" no-margin @click="handleClick" :loop="1"
        @on-complete="handleCompleted" />
</template>