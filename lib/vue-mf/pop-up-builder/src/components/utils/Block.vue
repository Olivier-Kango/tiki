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
    block: Object,
    edit: Boolean
});
</script>

<template>
    <component
        :id="'block-' + block.data.id"
        :is="block.data.name"
        :classes="block.data.classes"
        class="block-item"
        :styles="block.data.styles"
        :text="block.data.text"
    >
        <Block
            v-for="block in block.children"
            :block="block"
            :id="'block-' + block.data.id"
            :key="'block-' + block.data.id"
            :edit="edit"
        />
        <CustomPopup v-if="edit" :target="'block-' + block.data.id">
            <BlockControls :node="block"></BlockControls>
        </CustomPopup>
    </component>
</template>

<style lang="scss">

</style>
