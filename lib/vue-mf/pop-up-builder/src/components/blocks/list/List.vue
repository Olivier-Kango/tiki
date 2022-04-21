<script>
export default {
    name: 'List'
}
</script>

<script setup>
import { ref, watch, computed, onMounted } from 'vue'
import BlockAlignTextToolbar from '../../utils/BlockAlignTextToolbar.vue'
import BlockControls from '../../utils/BlockControls.vue'
import BlockInspector from '../../utils/BlockInspector.vue'
import BlockInspectorControls from '../../utils/BlockInspectorControls.vue'
import CustomPopup from '../../utils/CustomPopup.vue'
import CustomModal from '../../utils/CustomModal.vue'
import { useState } from '../../hooks/useState'
import store from '../../../store'
import BlockBuilder from '../../utils/BlockBuilder.vue'
import cloneDeep from 'lodash/cloneDeep'

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

const { isSelected, isHighlighted, isEdit, setAttributes, setStyles } = useState(props.id)
const items = ref(props.block.attributes.items)
const showPopup = ref(false)
const showInspector = ref(false)
const blocks = ref([])

const classes = computed(() => 
    props.block.classes && props.block.classes.concat([
        {'is-highlighted': isHighlighted.value}
    ])
)

watch(() => props.edit, (val, oldVal) => {
    if (!val) {
        fetch('https://fakestoreapi.com/products')
            .then(res => res.json())
            .then(json => {
                console.log(json)
                items.value = json
                blocks.value = json.map(item => {
                    // TODO: Clone block - includes innerBlocks 
                    cloneDeep(props.block.innerBlocks)
                    return []
                })
            })
    }
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
        <slot v-if="edit"/>
        <div v-if="!edit">
            <BlockBuilder v-for="blockTemplate in blockTemplates" :blocks="blockTemplate" :blockIds="block.innerBlocks" />
        </div>
        
        <CustomPopup v-if="showPopup" :target="'block-' + block.id" @destroy="handlePopupDestroy">
            <div class="d-flex shadow bg-white">
                <BlockControls :block="block" @edit="handleEdit"></BlockControls>
                <BlockAlignTextToolbar @action="handleAlignText"></BlockAlignTextToolbar>
            </div>
        </CustomPopup>
        <CustomModal v-if="showInspector" :show="showInspector" @close="showInspector = false">
            <BlockInspector>
                <BlockInspectorControls />
            </BlockInspector>
        </CustomModal>
    </div>
</template>
