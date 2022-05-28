<script>
export default {
    name: 'BlockContext'
}
</script>

<script setup>
import { ref, watch, nextTick } from 'vue'
import { useState } from '../hooks/useState'
import cloneDeep from 'lodash/cloneDeep'

const props = defineProps({
    block: {
        type: Object,
        default() {
            return {}
        }
    },
    context: {
        type: Object,
        default() {
            return {}
        }
    }
})

const { setAttributes } = useState(props.block.id)

const blockContext = ref()

watch(() => props.block, (val, oldVal) => {
    let currBlockContext = props.block.attributes.context
    if (currBlockContext) {
        blockContext.value = cloneDeep(currBlockContext)
    } else {
        blockContext.value = cloneDeep(props.context)
    }
}, { immediate: true })

const handleSave = (selected, action) => {
    nextTick(() => {
        setAttributes({
            context: {
                ...blockContext.value
            }
        })
    })
}
</script>

<template>
    <div v-for="(action, index) in blockContext.actions" :key="action.name">
        <h6>{{ action.title }}</h6>
        <span>{{ action.description }} on</span>
        <VueSelect
            class="d-inline-block ms-2"
            v-model="action.selectedEvents"
            :options="action.events"
            multiple
            taggable
            @selected="handleSave"
            @removed="handleSave"
        >
            <template #tag="{ option }">
                <div>
                    {{ option }}
                </div>
            </template>
        </VueSelect>
    </div>
</template>
