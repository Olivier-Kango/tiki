<script>
export default {
    name: 'BlockDraggable'
}
</script>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import interact from 'interactjs';
import { getDescendantIds } from '../../store/helpers'
import store from '../../store';

const props = defineProps({
    targetId: String,
    dropzone: Boolean,
    block: {
        type: Object,
        default: () => {}
    }
})

const targetEl = ref(null)
const drag = ref(false)
const x = ref(0)
const y = ref(0)

const transform = computed(() => `translate(${x.value}px, ${y.value}px)`)
// const getSelectedBlock = computed(() => store.getters.getSelected)

onMounted(() => {
    if (props.targetId) targetEl.value = document.getElementById(props.targetId)
    if (targetEl.value) initInteract(targetEl.value)
})

onBeforeUnmount(() => {
    interact(targetEl.value).unset()
})

function initInteract(el) {
    interact(el).draggable({
        listeners: {
            start: handleDragStart,
            move: handleDragMove,
            end: handleDragEnd
        },
    }).styleCursor(false)
    // if (props.dropzone) {
        interact(`[data-id="${props.block.id}"]`).dropzone({
            // only accept elements matching this CSS selector
            // accept: '.block-editor-block',
            // Require a 75% element overlap for a drop to be possible
            // overlap: 0.1,
            overlap: 'pointer',
            // listen for drop related events:
            // ondropactivate: handleDropActivate,
            ondragenter: handleDragEnter,
            ondragleave: handleDragLeave,
            ondrop: handleDrop,
            // ondropdeactivate: handleDropDeactivate
        })
    // }
}

function handleDragStart(event) {
    // $emit('ondragstart', event)
    toggleDropzone(false, props.block.id)
    store.dispatch('setSelected', props.block.id)
    store.dispatch('setDragged', props.block.id)
    drag.value = true
    x.value = event.page.x
    y.value = event.page.y
}

function handleDragMove(event) {
    // $emit('ondragmove', event)
    x.value += event.dx
    y.value += event.dy
}

function handleDragEnd(event) {
    // $emit('ondragend', event)
    drag.value = false
    store.dispatch('setDragged', null)
    // $nextTick(function () {
    //     initInteract($refs.targetEl)
    // })
    // Enable dropzone
    toggleDropzone(true, props.block.id)
}

// function handleDropActivate(event) {
//     // console.log('dropActive', props.block)
// }

function handleDragEnter(event) {
    // console.log('dragEnter', props.block)
    // console.log('setDropzoned', props.block.id)
    let selectedBlock = store.getters.getSelected
    let allowedBlocks = props.block.attributes?.allowedBlocks
    if (Array.isArray(allowedBlocks) && allowedBlocks.includes(selectedBlock.name)) {
        store.dispatch('setDropzoned', props.block.id)
    } else if (!allowedBlocks) {
        store.dispatch('setDropzoned', props.block.id)
    }
}

function handleDragLeave(event) {
    // console.log('dragLeave', props.block)
    store.dispatch('setDropzoned', null)
}

function handleDrop(event) {
    // console.log('drop', props.block)
    
    store.dispatch('setDropped')
    store.dispatch('setDropzoned', null)
}

function toggleDropzone(toggle, parentId) {
    let ids = getDescendantIds(store.getters.getBlocks, parentId)
    ids.forEach(id => {
        interact(`[data-id="${id}"]`).dropzone(toggle)
    })
}
// function handleDropDeactivate(event) {
//     // console.log('dropDeactive', props.block)
// }
</script>

<template>
    <div ref="targetEl">
        <slot name="default"/>
    </div>
    <Teleport to="body">
        <div v-if="drag" class="draggable-container" :style="{ transform: transform }">
            <slot name="draggable"/>
        </div>
    </Teleport>
</template>

<style lang="scss" scoped>
    .draggable-container {
        position: absolute;
        top: 0;
        left: 0;
        z-index: 1;
    }
</style>
