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

const { isSelected, isHighlighted, cloneBlock, setAttributes, setStyles } = useState(props.id)
const items = ref(props.block.attributes.items)
const showPopup = ref(false)
const showInspector = ref(false)
const blockTemplates = ref()
const loading = ref()

const classes = computed(() => 
    props.block.classes && props.block.classes.concat([
        {'is-highlighted': isHighlighted.value}
    ])
)

watch(() => props.edit, (val, oldVal) => {
    if (!val) {
        loading.value = true
        fetch('https://fakestoreapi.com/products')
            .then(res => res.json())
            .then(json => {
                loading.value = false
                items.value = json
                blockTemplates.value = json.map(item => {
                    return cloneBlock(props.block.innerBlocks[0])
                })
                
                console.log(json)
                console.log(blockTemplates.value)
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
            <div v-if="loading" class="d-flex justify-content-center align-items-center" :style="{'height': '200px', 'font-size': '2rem'}">
                <i class="fas fa-spinner fa-spin"></i>
            </div>
            <BlockBuilder
                v-for="blockTemplate in blockTemplates"
                :blocks="blockTemplate.blocks"
                :blockIds="[blockTemplate.rootId]"
            />
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
