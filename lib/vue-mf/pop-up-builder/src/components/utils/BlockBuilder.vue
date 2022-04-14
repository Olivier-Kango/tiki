<script>
export default {
    name: 'BlockBuilder'
}
</script>

<script setup>
import { computed } from 'vue'
import Block from './Block.vue'
import store from '../../store'

defineProps({
    edit: Boolean
});

// const root = computed(() => store.getters.getTree)
const getRootBlock = computed(() => store.getters.getRootBlock)
const getBlock = computed(() => (id) => store.getters.getBlock(id))
</script>

<template>
    <div class="block-builder" :id="'block-' + getRootBlock.id">
        <TransitionGroup name="list">
            <Block
                v-for="id in getRootBlock.innerBlocks"
                :key="'block-' + id"
                :block="getBlock(id)"
                :edit="edit"
            />
        </TransitionGroup>
    </div>
</template>
