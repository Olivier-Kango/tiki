<script>
export default {
    name: 'Block'
}
</script>

<script setup>
import BlockControls from './BlockControls.vue'
import CustomPopup from './CustomPopup.vue'

defineProps({
    id: String,
    node: Object,
    edit: Boolean
});
</script>

<template>
    <component
        :id="'block-' + node.data.id"
        :is="node.data.name"
        class="block-item"
        :block="node.data"
    >
        <Block
            v-for="node in node.children"
            :node="node"
            :id="'block-' + node.data.id"
            :key="'block-' + node.data.id"
            :edit="edit"
        />
        <CustomPopup v-if="edit" :target="'block-' + node.data.id">
            <BlockControls :node="node"></BlockControls>
        </CustomPopup>
    </component>
</template>
