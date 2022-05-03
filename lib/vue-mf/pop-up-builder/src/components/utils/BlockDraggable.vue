<script>
export default {
    name: 'BlockDraggable'
}
</script>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import interact from 'interactjs';

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
            ondropactivate: handleDropActivate,
            ondragenter: handleDragEnter,
            ondragleave: handleDragLeave,
            ondrop: handleDrop,
            ondropdeactivate: handleDropDeactivate
        })
    // }
}

function handleDragStart(event) {
    // $emit('ondragstart', event)
    drag.value = true
    x.value = event.page.x
    y.value = event.page.y
    // interact($refs.targetEl).dropzone(false)
}
function handleDragMove(event) {
    // $emit('ondragmove', event)
    x.value += event.dx
    y.value += event.dy
}
function handleDragEnd(event) {
    // $emit('ondragend', event)
    drag.value = false
    // $nextTick(function () {
    //     initInteract($refs.targetEl)
    // })
}

function handleDropActivate(event) {

}
function handleDragEnter(event) {
    console.log('dragEnter', event)
}
function handleDragLeave(event) {
    console.log('dragLeave', event)
}
function handleDrop(event) {

}
function handleDropDeactivate(event) {

}
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
    }
</style>
