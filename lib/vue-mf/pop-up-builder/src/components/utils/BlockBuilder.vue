<script>
export default {
    name: 'BlockBuilder'
}
</script>

<script setup>
import { ref, computed } from 'vue'
import Block from './Block.vue'
import BlockControls from './BlockControls.vue'
import CustomPopup from './CustomPopup.vue'
import store from '../../store'

defineProps({
    rootNode: {
        type: Object,
        default: () => {}
    },
    edit: Boolean
});

const selected = computed(() => store.getters.getSelected)
</script>

<template>
    <div class="block-builder">
        <Block
            :node="rootNode"
            :id="'block-' + rootNode.data.id"
            :key="'block-' + rootNode.data.id"
            :edit="edit"
        />
        <CustomPopup v-if="selected" :target="'block-' + (selected.data && selected.data.id)">
            <BlockControls :node="selected"></BlockControls>
        </CustomPopup>
    </div>
</template>

<style lang="scss">

</style>
