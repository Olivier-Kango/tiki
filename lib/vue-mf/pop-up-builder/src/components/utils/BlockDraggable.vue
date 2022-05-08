<script>
export default {
    name: 'BlockDraggable'
}
</script>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, onUpdated } from 'vue'
import interact from 'interactjs'
import { getDescendantIds } from '../../store/helpers'
import store from '../../store'

const props = defineProps({
    targetId: String,
    drag: Boolean,
    drop: Boolean,
    block: {
        type: Object,
        default: () => {}
    }
})

const targetEl = ref(null)
const toggleDrag = ref(false)
const x = ref(0)
const y = ref(0)

const transform = computed(() => `translate(${x.value}px, ${y.value}px)`)
// const getSelectedBlock = computed(() => store.getters.getSelected)

onMounted(() => {
    // console.log('mounted draggable', targetEl.value, props.block)
    // if (props.targetId) targetEl.value = document.getElementById(props.targetId)
    if (props.drag && targetEl.value) initDraggable(targetEl.value)
    if (props.drop && targetEl.value) initDropzone(targetEl.value)
})

onBeforeUnmount(() => {
    if (targetEl.value) interact(targetEl.value).unset()
})

// onUpdated(() => {
//     if (props.targetId) targetEl.value = document.getElementById(props.targetId)
//     if (props.drag && targetEl.value) initDraggable(targetEl.value)
//     if (props.drop && targetEl.value) initDropzone(targetEl.value)
// })

function initDraggable(el) {
    interact(el).draggable({
        listeners: {
            start: handleDragStart,
            move: handleDragMove,
            end: handleDragEnd
        },
    }).styleCursor(false)
}

function initDropzone(el) {
    interact(el).dropzone({
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
}

function handleDragStart(event) {
    // $emit('ondragstart', event)
    // toggleDropzone(false, props.block.id)
    // if (props.drop) interact(targetEl.value).dropzone(false)
    store.dispatch('setSelected', props.block.id)
    store.dispatch('setDragged', props.block.id)
    toggleDrag.value = true
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
    // if (props.drop) interact(targetEl.value).dropzone(true)
    toggleDrag.value = false
    store.dispatch('setDragged', null)
    // $nextTick(function () {
    //     initInteract($refs.targetEl)
    // })
    // Enable dropzone
    // toggleDropzone(true, props.block.id)
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

// function handleDropDeactivate(event) {
//     // console.log('dropDeactive', props.block)
// }

function toggleDropzone(toggle, parentId) {
    let ids = getDescendantIds(store.getters.getBlocks, parentId)
    ids.forEach(id => {
        interact(`[data-dropzone-id="${id}"]`).dropzone(toggle)
    })
}
</script>

<template>
    <div ref="targetEl">
        <slot name="default"/>
        <Teleport to="body">
            <div v-if="toggleDrag" class="draggable-container" :style="{ transform: transform }">
                <slot name="draggable"/>
            </div>
        </Teleport>
    </div>
</template>

<style lang="scss" scoped>
    .draggable-container {
        position: absolute;
        top: 0;
        left: 0;
        z-index: 1;
    }
</style>
