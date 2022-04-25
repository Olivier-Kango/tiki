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
import BlockInspectorControls from '../../utils/BlockInspectorControls.vue'
import CustomPopup from '../../utils/CustomPopup.vue'
import CustomModal from '../../utils/CustomModal.vue'
import { useState } from '../../hooks/useState'
import store from '../../../store'
import BlockBuilder from '../../utils/BlockBuilder.vue'

const props = defineProps({
    id: String,
    block: {
        type: Object,
        default() {
            return {}
        }
    },
    edit: Boolean
});

const { isSelected, isHighlighted, cloneBlockBulk, setAttributes, setStyles } = useState(props.id)
const showPopup = ref(false)
const showInspector = ref(false)

const classes = computed(() => 
    props.block.classes && props.block.classes.concat([
        {'is-highlighted': isHighlighted.value}
    ])
)

const getFirstItem = computed(() => {
    let field = props.block.attributes.field
    let data = store.getters[`module_${field}/get_${field}`]
    return data && data[0]
})

const getBlockTemplates = computed(() => {
    let field = props.block.attributes.field
    return store.getters[`module_${field}/get_${field}`]?.map(item => {
        return { ...cloneBlockBulk(props.block.innerBlocks), context: item }
    })
})

watch(() => isSelected.value, (val, oldVal) => showPopup.value = val)

const handlePopupDestroy = () => {
    // showPopup.value = false
    // store.dispatch('setSelected', null)
}
const handleAlignText = (styles) => setStyles(styles)
const handleEdit = (e) => showInspector.value = true
</script>

<template>
    <div :id="'block-' + id" :class="classes" :style="block.styles">
        <!-- <slot v-if="edit"/> -->
        <div v-if="edit">
            <BlockBuilder
                :blockIds="block.innerBlocks"
                :edit="edit"
                :context="getFirstItem"
            />
        </div>
        <div v-if="!edit">
            <div v-if="!getBlockTemplates && !edit" class="d-flex justify-content-center align-items-center" :style="{'height': '200px', 'font-size': '2rem'}">
                <i class="fas fa-spinner fa-spin"></i>
            </div>
            <div v-if="getBlockTemplates">
                <BlockBuilder
                    v-for="blockTemplate in getBlockTemplates"
                    :blocks="blockTemplate.blocks"
                    :blockIds="blockTemplate.innerBlocks"
                    :context="blockTemplate.context"
                />
            </div>
        </div>
        
        <CustomPopup v-if="showPopup" :target="'block-' + block.id" @destroy="handlePopupDestroy">
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
