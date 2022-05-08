<script>
export default {
    name: 'BlockPopup'
}
</script>

<script setup>
import { ref } from 'vue'
import CustomPopup from './CustomPopup.vue'
import store from '../../store'

const targetEl = ref(null)
</script>

<template>
    <div ref="targetEl" :class="'popper-target-' + store.getters.getSelected?.id" style="position: absolute;top: 0;left: 0;"></div>
    <CustomPopup v-if="targetEl" :target="targetEl">
        <div class="block-popup-controls">
            <slot/>
        </div>
    </CustomPopup>
</template>

<style lang="scss" scoped>
    .block-popup-controls {
        display: flex;
        background-color: white;
        border: 1px solid #000;
        padding: 5px;
    }
</style>
