<script>
export default {
    name: 'List'
}
</script>

<script setup>
import { ref, watch, computed } from 'vue'
import BlockAlignTextToolbar from '../../utils/BlockAlignTextToolbar.vue'
import BlockControls from '../../utils/BlockControls.vue'
import BlockInspector from '../../utils/BlockInspector.vue'
import CustomPopup from '../../utils/CustomPopup.vue'
import CustomModal from '../../utils/CustomModal.vue'
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

const { isSelected, isHighlighted, setAttributes, setStyles } = useState(props.block.id)
const popupTarget = ref(null)
const showPopup = ref(false)
const showInspector = ref(false)

const classes = computed(() => {
    if (props.block.classes) {
        return [props.block.classes].concat([
            {'is-highlighted': isHighlighted.value}
        ])
    } else {
        return [{'is-highlighted': isHighlighted.value}]
    }
})

watch(() => isSelected.value, (val, oldVal) => showPopup.value = val)

const getFirstItem = computed(() => {
    let field = props.block.attributes.field
    let data = store.getters[`module_${field}/get_${field}`]
    return data && data[0]
})

const handleAlignText = (styles) => setStyles(styles)
const handleEdit = (e) => showInspector.value = true
</script>

<template>
    <div ref="popupTarget" :id="'block-' + id" :class="classes" :style="block.styles">
        <!-- <slot :context="getFirstItem" /> -->
        <BlockBuilder
            :id="block.id"
            :context="getFirstItem"
        />

        <CustomPopup v-if="showPopup" :target="popupTarget">
            <div class="d-flex shadow bg-white">
                <BlockControls :block="block" @edit="handleEdit"></BlockControls>
                <BlockAlignTextToolbar @action="handleAlignText"></BlockAlignTextToolbar>
            </div>
        </CustomPopup>
        <CustomModal
            v-if="showInspector"
            :show="showInspector"
            @close="showInspector = false"
            :drag="true"
            :resize="true"
            :drag-selector="'.modal-drag'"
            title="Settings"
        >
            <BlockInspector :block="block" :context="store.getters.getAPIfields"/>
        </CustomModal>
    </div>
</template>
