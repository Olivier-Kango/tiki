<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { createSuggestionsRequester } from '../../helpers/autocomplete/request';
import ConfigWrapper from '../ConfigWrapper.vue';
import { usePropParsers } from '../../composables/usePropParsers';

const props = defineProps([
    '_expose',
    'value',
    'remoteSourceUrl',
    'sourceList',
    'emitCustomEvent',
    'placeholder',
    'valueKey',
    'language',
    'transformResultFn',
    'remoteQueryKey',
    'wildcardSearch',
    'minChars',
]);

const { normalize } = usePropParsers();
const valueKey = props.valueKey || 'value';
const placeholder = props.placeholder || TEXT.INPUT_PLACEHOLDER;
const shouldRefocusOnBlur = ref(false);

const modelValue = ref(props.value);
const autocompleteRef = ref(null);
const setValue = (val) => {
    modelValue.value = val;
};

// This ensures that if jQuery changes the 'value' prop/attribute, Vue reacts
watch(
    () => props.value,
    (newVal) => {
        modelValue.value = newVal;
    }
);

props._expose({
    value: modelValue,
    setValue,
});

const suggestionsRequester = createSuggestionsRequester(() => ({
    remoteSourceUrl: props.remoteSourceUrl,
    sourceList: props.sourceList ? JSON.parse(props.sourceList) : [],
    remoteQueryKey: props.remoteQueryKey,
    wildcardSearch: normalize(props.wildcardSearch, false),
    minChars: props.minChars,
}));

const handleFetchSuggestions = (query, callback) => {
    suggestionsRequester.request(query, (results) => {
        // An empty list means no request was made (query too short, aborted or failed), so there is nothing to transform
        const isEmptyList = Array.isArray(results) && results.length === 0;
        callback(props.transformResultFn && !isEmptyList ? window[props.transformResultFn](results) : results);

        if (!results || results.length === 0) {
             // If there are no results, set a flag indicating that the next blur event is likely programmatic and should be counteracted by a refocus.
            shouldRefocusOnBlur.value = true;
        }
    });
}

const handleBlur = () => {
    // Refocuses the input if the blur was triggered programmatically by a "no results" event.
    if (shouldRefocusOnBlur.value) {
        autocompleteRef.value?.inputRef?.focus();
        shouldRefocusOnBlur.value = false;
    }
};

const handleSelect = (value) => {
    props.emitCustomEvent('select', value);
}

const handleInput = (value) => {
    if (!value) {
        // Cancel any pending search when the input is cleared
        suggestionsRequester.cancel();
    }
    props.emitCustomEvent('input', value);
}

const handlePressEnter = () => {
    props.emitCustomEvent('pressEnter', modelValue.value);
}

onBeforeUnmount(suggestionsRequester.cancel);

onMounted(() => {
    if (!props.remoteSourceUrl && !props.sourceList) {
        console.error(TEXT.ERROR_NO_REQUIRED_PROPS);
    }
})

</script>

<script>
export const TEXT = {
    ERROR_NO_REQUIRED_PROPS: "The Autocomplete component requires either a remoteSourceUrl or sourceList prop to be set.",
    INPUT_PLACEHOLDER: "Type to search...",
}

const uniqueId = new Date().getTime();
export const DATA_TEST_ID = {
    AUTOCOMPLETE_ELEMENT: `autocomplete-element-${uniqueId}`,
}
</script>

<template>
    <ConfigWrapper :language="language">
        <el-autocomplete
            ref="autocompleteRef"
            v-model="modelValue"
            :debounce="500"
            :trigger-on-focus="false"
            :fetch-suggestions="handleFetchSuggestions"
            :data-testid="DATA_TEST_ID.AUTOCOMPLETE_ELEMENT"
            :placeholder="placeholder"
            :value-key="valueKey"
            :highlight-first-item="true"
            @select="handleSelect"
            @input="handleInput"
            @keyup.enter="handlePressEnter"
            @blur="handleBlur"
            @clear="suggestionsRequester.cancel"
            clearable
            :teleported="false"
        >
            <template #default="{ item }">
                <span v-if="item.html" v-html="item.html"></span>
                <span v-else>{{ item[valueKey] }}</span>
            </template>
        </el-autocomplete>
    </ConfigWrapper>
</template>