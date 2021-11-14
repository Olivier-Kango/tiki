<script setup>
import { ref } from "vue";

defineProps({
    msg: String
});

const count = ref(0);
</script>

<template>
    <h1 class="title">{{ msg }}</h1>
    <button type="button" @click="count++">count is: {{ count }}</button>
</template>

<style lang="scss" scoped>
</style>
