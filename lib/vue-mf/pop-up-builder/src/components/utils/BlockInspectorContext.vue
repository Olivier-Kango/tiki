<script>
export default {
    name: 'BlockInspectorContext'
}
</script>

<script setup>
import { ref } from 'vue'
import BlocksStoreView from './BlocksStore/BlocksStoreView.vue'
import store from '../../store'
import _get from 'lodash/get'
import _isObject from 'lodash/isObject'

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

const propsPath = ref(getInitialPathArr())

const handleSelected = (selected, index = 0) => {
    propsPath.value = propsPath.value.slice(0, index + 1)
    propsPath.value.splice(index, 1, selected)

    let path = propsPath.value.join('.')
    let value = _get(props.context, path)
    let hasObjectInside = _isObject(value) && !Array.isArray(value)

    if (hasObjectInside) propsPath.value.push('')
    if (!hasObjectInside) {
        saveAttributeField()
    }
}

const handleRemoved = (removed, index = 0) => {
    propsPath.value = propsPath.value.slice(0, index)
    propsPath.value.push('')
    saveAttributeField()
}

const getOptions = index => {
    let path = propsPath.value.slice(0, index)
    let value = _get(props.context, path.join('.'))

    if (Array.isArray(props.context)) return props.context
    if (index === 0 && !value) {
        return props.context && Object.getOwnPropertyNames(props.context)
    } else if (_isObject(value) && !Array.isArray(value)) {
        return Object.getOwnPropertyNames(value)
    } else {
        return Object.getOwnPropertyNames(props.context)
    }
}

function saveAttributeField() {
    store.dispatch('updateBlock', {
        block: props.block,
        field: 'attributes',
        value: {...props.block.attributes, field: propsPath.value.filter(el => el).join('.') }
    })
}

function getInitialPathArr() {
    let value = _get(props.context, props.block.attributes.field)
    
    if (value && _isObject(value) && !Array.isArray(value)) {
        let path = []
        path = props.block.attributes.field?.split('.')
        path.push('')
        return path
    } else {
        let path = props.block.attributes.field?.split('.').filter(el => el)
        return path && path.length !== 0 ? path : ['']
    }
}
</script>

<template>
    <div class="block-inspector-context">
        <div v-if="context && Object.getOwnPropertyNames(context)?.length">
            <h5><b>{{ props.block.attributes.field }}</b></h5>
            <div class="d-flex mb-4">
                <VueSelect
                    class="me-2"
                    v-for="(prop, index) in propsPath"
                    v-model="propsPath[index]"
                    :options="getOptions(index) || []"
                    @selected="selected => handleSelected(selected, index)"
                    @removed="selected => handleRemoved(selected, index)"
                />
            </div>
            <h5>Context:</h5>
            <div>
                <pre>{{ JSON.stringify(context, undefined, 4) }}</pre>
            </div>
        </div>

        <BlocksStoreView :field="block.attributes.field"/>
    </div>
</template>
