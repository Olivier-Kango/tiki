<script>
export default {
    name: 'BlocksPatterns'
}
</script>

<script setup>
import { computed } from 'vue'
import ThumbnailContent from './thumbnail-content/ThumbnailContent.vue'
import BlockInnerBlocks from './BlockInnerBlocks.vue'
import { cloneBlockDeepBulk } from '../../store/helpers'
import store from '../../store'

const getPatterns = computed(() => store.getters.getPatterns)
const getBlocks = computed(() => store.getters.getBlocks)

const handleClick = (pattern) => {
    let cloned = cloneBlockDeepBulk(store.getters.getBlocks, pattern.innerBlocks)
    store.dispatch('addBlocks', cloned.blocks)
    cloned.innerBlocks.forEach(blockId => {
        store.dispatch('insertBlock',{ blockId: blockId, targetId: store.getters.getRootBlock.id })
    })
}
</script>

<template>
    <div class="block-pattern--container block--container container-fluid">
        <div
            v-for="pattern in getPatterns"
            :key="pattern.id"
            :id="`block-pattern--${pattern.name}`"
            class="block-pattern--item"
        >
            <ThumbnailContent v-if="pattern.type !== 'page'" :title="pattern.name" @click="handleClick(pattern)">
                <BlockInnerBlocks v-if="!edit" :ids="pattern.innerBlocks" :blocks="getBlocks" />
            </ThumbnailContent>
        </div>
    </div>
</template>

<style lang="scss" scoped>
    .block-pattern--item {
        margin-bottom: 1rem;
        margin-right: 0.5rem;
    }
</style>
