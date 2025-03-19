<script setup>
import { ref } from 'vue';
import * as Icons from '@element-plus/icons-vue';
import ConfigWrapper from '../ConfigWrapper.vue';

const props = defineProps(["placeholder", "value", "prefixIcon", "suffixIcon", "clearable", "showPassword", "_emit", "_expose"]);
const showPassword = props.showPassword === "true";
const clearable = props.clearable === "true";
const prefixIcon = props.prefixIcon ? Icons[props.prefixIcon] : null;
const suffixIcon = props.suffixIcon ? Icons[props.suffixIcon] : null;

const modelValue = ref(props.value);

props._expose({ value: modelValue });

const handleChange = (value) => {
    props._emit("change", value);
};

const handleInput = (value) => {
    props._emit("input", value);
};
</script>

<script>
export const DATA_TEST_ID = {
    INPUT: 'input',
};
</script>

<template>
    <ConfigWrapper locale="en">
        <el-input
            v-model="modelValue"
            :placeholder="placeholder"
            :prefix-icon="prefixIcon"
            :suffix-icon="suffixIcon"
            :clearable="clearable"
            :show-password="showPassword"
            @change="handleChange"
            @input="handleInput"
            :data-testid="DATA_TEST_ID.INPUT"
        />
    </ConfigWrapper>
</template>
