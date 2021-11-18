<script setup>
import { ref } from 'vue';
import { Button } from '@vue-mf/styleguide';

defineProps({
    msg: String
});

const count = ref(0);
const variants = ref(['primary', 'secondary', 'success','danger', 'warning', 'info', 'light', 'dark', 'link']);
</script>

<template>
    <h2 class="title">{{ msg }}</h2>
    <Button v-for="variant in variants" class="mr-2" :variant="variant" @click="count++">{{variant}}: {{count}}</Button>
</template>

<style lang="scss" scoped>
</style>
