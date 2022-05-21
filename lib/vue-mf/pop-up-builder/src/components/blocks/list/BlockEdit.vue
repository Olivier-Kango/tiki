<script>
export default {
    name: 'List'
}
</script>

<script setup>
import { ref, watch, computed } from 'vue'
import BlockControls from '../../utils/BlockControls.vue'
import BlockInspector from '../../utils/BlockInspector.vue'
import BlockPopup from '../../utils/BlockPopup.vue'
import CustomModal from '../../utils/CustomModal.vue'
import CustomTabs from '../../utils/CustomTabs.vue'
import { useState } from '../../hooks/useState'
import store from '../../../store'
import BlockBuilder from '../../utils/BlockBuilder.vue'

const props = defineProps({
    block: {
        type: Object,
        default() {
            return {}
        }
    }
});

const { isSelected } = useState(props.block.id)
const showPopup = ref(false)
const showInspector = ref(false)

watch(() => isSelected.value, (val, oldVal) => showPopup.value = val)

const getFirstItem = computed(() => {
    let field = props.block.attributes.field
    let data = store.getters[`module_${field}/get_${field}`]
    return data && data[0]
})

const handleEdit = (e) => showInspector.value = true
</script>

<template>
    <BlockBuilder
        :id="block.id"
        :context="getFirstItem"
        :edit="true"
    />

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

            </template>
            <template #settings>
            
            </template>
            <template #advanced>
                <BlockInspector :block="block" :context="store.getters.getAPIfields"></BlockInspector>
            </template>
        </CustomTabs>
    </CustomModal>
</template>
