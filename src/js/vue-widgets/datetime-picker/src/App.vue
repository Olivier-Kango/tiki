<template>
    <link rel="stylesheet" :href="themeCss" :data-testid="DATA_TEST_ID.CSS_THEME">
    <div :data-testid="DATA_TEST_ID.CONTAINER">
        <VueDatePicker v-model="date" :timezone="tz" :locale="language" input-class-name="form-control tiki-form-control"
            :enable-time-picker="enableTimePicker" :range="Boolean(toInputName)" @update:model-value="handleDatetimeChange"
            :cancelText="cancelText" :selectText="selectText" :format="formatFn" :data-testid="DATA_TEST_ID.DATE_PICKER"/>
        <input type="hidden" :name="inputName" :value="unixTimestamp" :data-testid="DATA_TEST_ID.HIDDEN_TIMESTAMP_INPUT">
        <input type="hidden" :name="toInputName" :value="toUnixTimestamp" :data-testid="DATA_TEST_ID.HIDDEN_TO_TIMESTAMP_INPUT">
        <input type="hidden" :name="TEXT.USE_DISPLAY_TZ_INPUT_NAME" value="1" v-if="!enableTimezonePicker" :data-testid="DATA_TEST_ID.HIDDEN_USE_DISPLAY_TZ">
        <div class="mt-3" v-if="enableTimezonePicker" :data-testid="DATA_TEST_ID.TIMEZONE_CONTAINER">
            <label for="timezone" class="form-label" :data-testid="DATA_TEST_ID.LABEL_TIMEZONE_CONTAINER">{{ TEXT.LABEL_TIMEZONE_CONTAINER }}</label>
            <select class="form-select" aria-label="Select a timezone" id="timezone" v-model="selectedTz" :name="timezoneFieldName" :data-testid="DATA_TEST_ID.TIMEZONE_SELECT" @change="handleTzChange">
                <option v-for="(timezone, index) in timezones" :key="index" :value="timezone">{{ timezone }} ({{
                    getTimezoneOffset(timezone) }})</option>
            </select>
        </div>
    </div>
</template>

<script>
const uniqueId = '1720000034990'; // ensure this is unique across all components so that in any way we don't end up with duplicate data-test-id resulting in false positive tests
export const DATA_TEST_ID = {
    CSS_THEME: `css-theme-${uniqueId}`,
    CONTAINER: `container-${uniqueId}`,
    DATE_PICKER: `date-picker-${uniqueId}`,
    HIDDEN_TIMESTAMP_INPUT: `hidden-timestamp-input-${uniqueId}`,
    HIDDEN_TO_TIMESTAMP_INPUT: `hidden-to-timestamp-input-${uniqueId}`,
    HIDDEN_USE_DISPLAY_TZ: `hidden-use-display-tz-${uniqueId}`,
    TIMEZONE_CONTAINER: `timezone-container-${uniqueId}`,
    LABEL_TIMEZONE_CONTAINER: `timezone-container-label-${uniqueId}`,
    TIMEZONE_SELECT: `timezone-select-${uniqueId}`,
}

export const TEXT = {
    USE_DISPLAY_TZ_INPUT_NAME: 'useDisplayTz',
    LABEL_TIMEZONE_CONTAINER: 'Timezone',
    LABEL_DEFAULT_CANCEL_BUTTON: 'Cancel',
    LABEL_DEFAULT_SELECT_BUTTON: 'Select',
}
</script>

<script setup>
import { ref, computed } from 'vue';
import VueDatePicker from '@vuepic/vue-datepicker';
import moment from 'moment-timezone';
import * as locale from 'date-fns/locale';
import { convertToUnixTimestamp, formatDate, goToURLWithData } from './helpers/helpers';

const timezones = moment.tz.names();

const props = defineProps({
    inputName: {
        type: String,
        default: 'date',
    },
    timestamp: {
        type: String
    },
    toTimestamp: {
        type: String
    },
    timezone: {
        type: String,
        default: '',
    },
    timezoneFieldName: {
        type: String,
        default: '',
    },
    enableTimePicker: {
        type: Number,
        default: 0,
    },
    enableTimezonePicker: {
        type: Number,
        default: 0,
    },
    toInputName: {
        type: String,
        default: null,
    },
    goToUrlOnChange: {
        type: String
    },
    language: {
        type: String,
        default: 'en'
    },
    cancelText: {
        type: String,
        default: 'Cancel'
    },
    selectText: {
        type: String,
        default: 'Select'
    },
    globalCallback: {
        type: String,
    },
    themeCss: {
        type: String
    },
    emitValueChange: {
        type: Function
    }
});

// Convert props that should be booleans from string to number
// Boolean props will be : "0" => false, "1" => true
// We can't directly use true/false because html attributes are always strings. A non-zero length string is always true
const enableTimePicker = Number(props.enableTimePicker);
const enableTimezonePicker = Number(props.enableTimezonePicker);

const date = ref(getDefaultDate(props.timestamp, props.toTimestamp));
const selectedTz = ref(props.timezone);

const unixTimestamp = computed(() => {
    return Array.isArray(date.value) ? getUnixTimestamp(date.value[0]) : getUnixTimestamp(date.value);
});

const toUnixTimestamp = computed(() => {
    return Array.isArray(date.value) ? getUnixTimestamp(date.value[1]) : 0;
});

const tz = computed(() => {
    return { timezone: selectedTz.value };
});

/*
====================
Handlers
====================
 */

const handleDatetimeChange = (value) => {
    date.value = value;

    if (props.emitValueChange) {
        props.emitValueChange({
            value: value,
            unixTimestamp: unixTimestamp.value,
            toUnixTimestamp: toUnixTimestamp.value,
            timezone: selectedTz.value,
        });
    }

    if (props.goToUrlOnChange || props.globalCallback) {
        goToURLWithData(value, props.goToUrlOnChange, unixTimestamp.value, toUnixTimestamp.value, selectedTz.value, props.globalCallback);
    }
};

const handleTzChange = () => {
    if (props.emitValueChange) {
        props.emitValueChange({
            value: date.value,
            unixTimestamp: unixTimestamp.value,
            toUnixTimestamp: toUnixTimestamp.value,
            timezone: selectedTz.value,
        });
    }
};

/*
====================
Utility functions
====================
 */

function getDefaultDate(fromTimestamp, toTimestamp) {
    if (!fromTimestamp) {
        fromTimestamp = moment().unix();
    }
    if (!toTimestamp) {
        toTimestamp = moment.unix(fromTimestamp).add(2, 'hours').unix();
    }
    if (!props.toInputName) {
        return fromTimestamp * 1000;
    }
    return [fromTimestamp * 1000, toTimestamp * 1000];
};

const getUnixTimestamp = (date) => convertToUnixTimestamp(date, Boolean(enableTimezonePicker && !props.timezone), selectedTz.value);

function getTimezoneOffset(timezone) {
    const currentTime = moment.tz(timezone);

    const offsetMinutes = currentTime.utcOffset();
    const offsetHours = Math.floor(Math.abs(offsetMinutes) / 60);
    const offsetMinutesRemainder = Math.abs(offsetMinutes) % 60;

    const offsetSign = offsetMinutes < 0 ? '-' : '+';

    const offsetString = `GMT${offsetSign}${String(offsetHours).padStart(2, '0')}:${String(offsetMinutesRemainder).padStart(2, '0')}`;

    return offsetString;
}

const formatFn = (value) => formatDate(value, Boolean(enableTimePicker), locale[props.language]);
</script>