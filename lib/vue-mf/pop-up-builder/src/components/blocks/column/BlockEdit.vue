<script>
export default {
    name: 'Column'
}
</script>
<script setup>
import { ref, watch } from 'vue'
import BlockAlignToolbar from '../../utils/BlockAlignToolbar.vue'
// import BlockToolbarColors from '../../utils/BlockToolbarColors.vue'
import BlockControls from '../../utils/BlockControls.vue'
import BlockControlsSizes from '../../utils/BlockControls/BlockControlsSizes.vue'
import BlockInspector from '../../utils/BlockInspector.vue'
import BlockPopup from '../../utils/BlockPopup.vue'
import CustomModal from '../../utils/CustomModal.vue'
import { useState } from '../../hooks/useState'

const props = defineProps({
    block: {
        type: Object,
        default() {
            return {}
        }
    }
});

const { isSelected, isHighlighted, setAttributes, setClasses, setStyles } = useState(props.block.id)

const showPopup = ref(false)
const showInspector = ref(false)

watch(() => isSelected.value, (val, oldVal) => showPopup.value = val)

const handleEdit = (e) => showInspector.value = true
</script>

<template>
    <slot />

    <BlockPopup v-if="isSelected">
        <BlockControls :block="block" @edit="handleEdit"></BlockControls>
        <BlockAlignToolbar :block="block" cssAlignSelf></BlockAlignToolbar>
        <!-- <BlockToolbarColors :block="block"></BlockToolbarColors> -->
    </BlockPopup>

    <CustomModal
        v-if="showInspector"
        :show="showInspector"
        @close="showInspector = false"
        :drag="true"
        :resize="true"
        :drag-selector="'.modal-drag'"
        title="Settings"
    >
        <BlockInspector :block="block" :context="context">
            <BlockControlsSizes :block="block" />
        </BlockInspector>
    </CustomModal>
</template>
