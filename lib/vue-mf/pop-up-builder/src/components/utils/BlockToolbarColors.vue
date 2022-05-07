<script>
export default {
    name: 'BlockToolbarColors'
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
    }
})

const { setStyles } = useState(props.block.id)

const styles = ref({
    backgroundColor: props.block.styles?.backgroundColor,
})

const handleUpdate = (field, value) => {
    styles.value[field] = value
    setStyles(styles.value)
}
</script>

<template>
    <ButtonToolbar>
        <ButtonGroup class="mr-1" sm>
            <VDropdown>
                <Button variant="default" sm>
                    <i class="fas fa-fill-drip"></i>
                </Button>
                <template #popper>
                    <div class="p-1" style="width: 120px">
                        <ColorPicker
                            :pureColor="styles.backgroundColor"
                            useType="pure"
                            format="hex"
                            @pureColorChange="(value) => handleUpdate('backgroundColor', value)"
                        />
                    </div>
                </template>
            </VDropdown>
        </ButtonGroup>
    </ButtonToolbar>
</template>
