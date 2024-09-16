<script setup>
import { ref, watch, computed } from 'vue';

const props = defineProps(['options', 'placeholder', 'emitValueChange', 'value', 'multiple', 'isInvalid', 'max', 'clearable', 'collapseTags', 'filterable', 'allowCreate', 'maxCollapseTags']);

const modelValue = ref(JSON.parse(props.value));

watch(() => props.value, (newValue) => {
    modelValue.value = JSON.parse(newValue);
});

const isInvalid = computed(() => props.isInvalid ? JSON.parse(props.isInvalid): false);

const clearable = props.clearable ? JSON.parse(props.clearable): false;
const collapseTags = props.collapseTags ? JSON.parse(props.collapseTags): false;
const filterable = props.filterable ? JSON.parse(props.filterable): false;
const allowCreate = props.allowCreate ? JSON.parse(props.allowCreate): false;

const handleValueChange = (value) => {
    props.emitValueChange({
        value,
    });
};
</script>

<script>
const uniqueId = new Date().getTime();
export const DATA_TEST_ID = {
    SELECT_WRAPPER: `select-wrapper-${uniqueId}`,
    SELECT_ELEMENT: `select-element-${uniqueId}`,
    SELECT_OPTION: `select-option-${uniqueId}`,
};
</script>

<template>
    <div 
        :class="{ 'invalid': isInvalid }"
        :data-testid="DATA_TEST_ID.SELECT_WRAPPER"
    >
        <el-select
            v-model="modelValue"
            :multiple="multiple"
            :filterable
            :allow-create
            default-first-option
            :reserve-keyword="false"
            :placeholder="placeholder"
            :teleported="false"
            @change="handleValueChange"
            :multiple-limit="max" :clearable :collapse-tags
            :max-collapse-tags
            :data-testid="DATA_TEST_ID.SELECT_ELEMENT"
        >
            <el-option 
                v-for="item in JSON.parse(props.options)"
                :key="item.value"
                :label="item.label"
                :value="item.value"
                :disabled="item.disabled"
                :data-testid="DATA_TEST_ID.SELECT_OPTION"
            />
        </el-select>
    </div>
</template>