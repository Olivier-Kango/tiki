<script>
export default {
    name: 'BlockToolbarImage'
}
</script>

<script setup>
import { ref, watch } from 'vue'
import { Button, ButtonGroup, ButtonToolbar } from '@vue-mf/styleguide'
import { useState } from '../hooks/useState.js'

const props = defineProps({
    block: {
        type: Object,
        default: () => {}
    }
})

const { setStyles, removeStyles } = useState(props.block.id)

const styles = ref({
    backgroundImage: props.block.styles?.backgroundImage,
})

watch(() => props.block.styles, (val, oldVal) => {
    if (val) {
        let { backgroundImage } = val
        styles.value = { backgroundImage }
    }
})

const handleUpdate = (field, value) => {
    styles.value[field] = `url(${value})`
    setStyles(styles.value)
}

function getUrlFromText(text) {
    if (!text) return
    const regex = /\([\s\S]*?\)/g
    const match = text.match(regex)
    return match && match[0].slice(1, match[0].length - 1)
}
</script>

<template>
    <ButtonToolbar>
        <ButtonGroup class="mr-1" sm>
            <VDropdown>
                <Button variant="default" sm>
                    <i class="fas fa-image"></i>
                </Button>
                <template #popper>
                    <div class="p-1">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text" id="inputGroup-sizing-default">url</span>
                            </div>
                            <input 
                                type="url"
                                name="url"
                                :value="getUrlFromText(styles.backgroundImage)"
                                @input="(e) => handleUpdate('backgroundImage', e.target.value)"
                                class="form-control"
                                placeholder="Enter image url..."
                                aria-label="Default"
                                aria-describedby="inputGroup-sizing-default"
                            >
                        </div>
                        <Button variant="default" sm @click="() => removeStyles(['backgroundImage'])">
                            <i class="fas fa-eraser"></i>
                        </Button>
                    </div>
                </template>
            </VDropdown>
        </ButtonGroup>
    </ButtonToolbar>
</template>
