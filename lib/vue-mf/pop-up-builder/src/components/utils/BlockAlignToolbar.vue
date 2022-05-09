<script>
export default {
    name: 'BlockAlignToolbar'
}
</script>

<script setup>
import { ref } from 'vue'
import { Button, ButtonGroup, ButtonToolbar } from '@vue-mf/styleguide'
import { useState } from '../hooks/useState.js'

const props = defineProps({
    block: {
        type: Object,
        default: () => {}
    },
    cssFlexDirection: Boolean,
    cssJustifyContent: Boolean,
    cssAlignItems: Boolean,
    cssAlignSelf: Boolean
})

const { setStyles } = useState(props.block.id)

const flexDirection = ref([
    { name: 'row', value: 'row' },
    { name: 'column', value: 'column' }
])

const justifyContent = ref([
    { name: 'center', value: 'center' },
    { name: 'between', value: 'space-between' },
    { name: 'around', value: 'space-around' },
    { name: 'evenly', value: 'space-evenly' },
    { name: 'start', value: 'flex-start' },
    { name: 'end', value: 'flex-end' }
])

const alignItems = ref([
    { name: 'center', value: 'center' },
    { name: 'stretch', value: 'stretch' },
    { name: 'baseline', value: 'baseline' },
    { name: 'start', value: 'flex-start' },
    { name: 'end', value: 'flex-end' }
])

const alignSelf = ref([
    { name: 'auto', value: 'auto' },
    { name: 'center', value: 'center' },
    { name: 'stretch', value: 'stretch' },
    { name: 'baseline', value: 'baseline' },
    { name: 'start', value: 'flex-start' },
    { name: 'end', value: 'flex-end' }
])

const styles = ref({
    flexDirection: props.block.styles?.flexDirection,
    justifyContent: props.block.styles?.justifyContent,
    alignItems: props.block.styles?.alignItems,
    alignSelf: props.block.styles?.alignItems
})

const handleUpdate = (field, value) => {
    styles.value[field] = value
    setStyles(styles.value)
}
</script>

<template>
    <ButtonToolbar>
        <ButtonGroup class="mr-1" sm>
            <VDropdown v-if="cssFlexDirection">
                <Button variant="default" sm>
                    Direction
                </Button>
                <template #popper>
                    <div class="p-1" style="width: 120px">
                        <Button
                            v-for="item in flexDirection"
                            :key="'item-' + item.value"
                            class="w-100"
                            :class="styles.flexDirection === item.value && 'bg-dark text-light'"
                            variant="default"
                            @click="handleUpdate('flexDirection', item.value)"
                        >
                            {{ item.name }}
                        </Button>
                    </div>
                </template>
            </VDropdown>

            <VDropdown v-if="cssJustifyContent">
                <Button variant="default" sm>
                    <i class="fas fa-arrows-alt-h"></i>
                </Button>
                <template #popper>
                    <div class="p-1" style="width: 120px">
                        <Button
                            v-for="item in justifyContent"
                            :key="'item-' + item.value"
                            class="w-100"
                            :class="styles.justifyContent === item.value && 'bg-dark text-light'"
                            variant="default"
                            @click="handleUpdate('justifyContent', item.value)"
                        >
                            {{ item.name }}
                        </Button>
                    </div>
                </template>
            </VDropdown>

            <VDropdown v-if="cssAlignItems">
                <Button variant="default" sm>
                    <i class="fas fa-arrows-alt-v"></i>
                </Button>
                <template #popper>
                    <div class="p-1" style="width: 120px">
                        <Button
                            v-for="item in alignItems"
                            :key="'item-' + item.value"
                            class="w-100"
                            :class="styles.alignItems === item.value && 'bg-dark text-light'"
                            variant="default"
                            @click="handleUpdate('alignItems', item.value)"
                        >
                            {{ item.name }}
                        </Button>
                    </div>
                </template>
            </VDropdown>

            <VDropdown v-if="cssAlignSelf">
                <Button variant="default" sm>
                    <i class="fas fa-arrows-alt-v"></i>
                </Button>
                <template #popper>
                    <div class="p-1" style="width: 120px">
                        <Button
                            v-for="item in alignSelf"
                            :key="'item-' + item.value"
                            class="w-100"
                            :class="styles.alignItems === item.value && 'bg-dark text-light'"
                            variant="default"
                            @click="handleUpdate('alignSelf', item.value)"
                        >
                            {{ item.name }}
                        </Button>
                    </div>
                </template>
            </VDropdown>
        </ButtonGroup>
    </ButtonToolbar>
</template>
