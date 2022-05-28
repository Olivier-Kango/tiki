<script>
export default {
    name: 'Pop-up-builder'
}
</script>
<script setup>
import { ref, watch, provide } from 'vue'
import { Button } from '@vue-mf/styleguide'
import BlockControls from '../../utils/BlockControls.vue'
import BlockControlsSizes from '../../utils/BlockControls/BlockControlsSizes.vue'
import BlockAttributesField from '../../utils/BlockAttributesField.vue'
import BlockInspector from '../../utils/BlockInspector.vue'
import BlockPopup from '../../utils/BlockPopup.vue'
import CustomModal from '../../utils/CustomModal.vue'
import CustomTabs from '../../utils/CustomTabs.vue'
import { useState } from '../../hooks/useState'

const props = defineProps({
    block: {
        type: Object,
        default() {
            return {}
        }
    },
    context: Object
})

provide('context', {
    providerBlockId: props.block.id,
    actions: [
        {
            name: 'closePopup',
            title: 'Close popup',
            description: 'Close closest popup',
            events: ['click', 'hover'],
            selectedEvents: []
        }
    ]
})
const { isSelected } = useState(props.block.id)

const showPopup = ref(false)
const showInspector = ref(false)

watch(() => isSelected.value, (val, oldVal) => showPopup.value = val)

const handleEdit = (e) => showInspector.value = true
</script>

<template>
    <div>
        <slot />
    </div>

    <BlockPopup v-if="isSelected">
        <BlockControls :block="block" @edit="handleEdit"></BlockControls>
    </BlockPopup>

    <CustomModal
        v-if="showInspector"
        :show="showInspector"
        @close="showInspector = false"
        :drag="true"
        :resize="true"
        :drag-selector="'.modal-drag'"
        :title="block.title"
        :description="block.description"
    >
        <CustomTabs>
            <template #styles>
                <BlockControlsSizes :block="block" />
            </template>
            <template #settings>
                <BlockAttributesField :block="block" field="url" title="Image url"/>
            </template>
            <template #advanced>
                <BlockInspector :block="block" :context="context"></BlockInspector>
            </template>
        </CustomTabs>
    </CustomModal>
</template>
