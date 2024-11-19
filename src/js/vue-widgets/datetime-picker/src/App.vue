<template>
    <link rel="stylesheet" :href="themeCss" :data-testid="DATA_TEST_ID.CSS_THEME">
    <div :data-testid="DATA_TEST_ID.CONTAINER">
        <VueDatePicker v-model="date" :timezone="tz" :locale="language" input-class-name="form-control tiki-form-control"
            :enable-time-picker="enableTimePicker" :range="rangePicker" @update:model-value="handleDatetimeChange"
            :cancelText="cancelText" :selectText="selectText" :format="formatFn" :data-testid="DATA_TEST_ID.DATE_PICKER" ref="datePicker">
            <template #menu-header>
                <div class="m-2" :data-testid="DATA_TEST_ID.SHORTCUT_TODAY">
                    <div class="d-flex justify-content-between" v-if="rangePicker">
                        <button type="button" class="btn btn-outline-dark btn-sm w-100 me-1" @click="handleTodayClick" :data-testid="DATA_TEST_ID.SHORTCUT_TODAY_FROM">Today</button>
                        <span class="d-flex align-items-center">-</span>
                        <button type="button" class="btn btn-outline-dark btn-sm w-100 ms-1" @click="e => handleTodayClick(e, true)" :data-testid="DATA_TEST_ID.SHORTCUT_TODAY_TO">Today</button>
                    </div>
                    <button type="button" class="btn btn-outline-dark btn-sm w-100" @click="handleTodayClick" :data-testid="DATA_TEST_ID.SHORTCUT_TODAY_FROM" v-else>Today</button>
                </div>
            </template>
        </VueDatePicker>
        <div class="mt-3" v-if="enableTimezonePicker" :data-testid="DATA_TEST_ID.TIMEZONE_CONTAINER">
            <label for="timezone" class="form-label" :data-testid="DATA_TEST_ID.LABEL_TIMEZONE_CONTAINER">{{ TEXT.LABEL_TIMEZONE_CONTAINER }}</label>
            <select class="form-select" aria-label="Select a timezone" id="timezone" v-model="selectedTz" :data-testid="DATA_TEST_ID.TIMEZONE_SELECT" @change="handleTzChange">
                <option v-for="(timezone, index) in timezones" :key="index" :value="timezone">{{ timezone }} ({{
                    getTimezoneOffset(timezone) }})</option>
            </select>
        </div>
        <div class="mt-1" v-if="! enableTimezonePicker && enableTimePicker && selectedTz" :data-testid="DATA_TEST_ID.TIMEZONE_CONTAINER">
            {{ selectedTz }} <span v-if="selectedTz != 'UTC'">({{ getTimezoneOffset(selectedTz) }})</span>
        </div>
    </div>
</template>

<script>
const uniqueId = '1720000034990'; // ensure this is unique across all components so that in any way we don't end up with duplicate data-test-id resulting in false positive tests
export const DATA_TEST_ID = {
    CSS_THEME: `css-theme-${uniqueId}`,
    CONTAINER: `container-${uniqueId}`,
    DATE_PICKER: `date-picker-${uniqueId}`,
    TIMEZONE_CONTAINER: `timezone-container-${uniqueId}`,
    LABEL_TIMEZONE_CONTAINER: `timezone-container-label-${uniqueId}`,
    TIMEZONE_SELECT: `timezone-select-${uniqueId}`,
    SHORTCUT_TODAY: `shortcut-today-${uniqueId}`,
    SHORTCUT_TODAY_FROM: `shortcut-today-from-${uniqueId}`,
    SHORTCUT_TODAY_TO: `shortcut-today-to-${uniqueId}`,
}

export const TEXT = {
    LABEL_TIMEZONE_CONTAINER: 'Timezone',
    LABEL_DEFAULT_CANCEL_BUTTON: 'Cancel',
    LABEL_DEFAULT_SELECT_BUTTON: 'Select',
}
</script>

<script setup>
import { ref, computed, watchEffect } from 'vue';
import VueDatePicker from '@vuepic/vue-datepicker';
import moment from 'moment-timezone/builds/moment-timezone-with-data-10-year-range.js';
import * as locale from 'date-fns/locale';
import { convertToUnixTimestamp, daylightDiffAgainstBrowserTz, formatDate, goToURLWithData } from './helpers/helpers';

const timezones = moment.tz.names();

const props = defineProps({
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
    enableTimePicker: {
        type: Number,
        default: 0,
    },
    enableTimezonePicker: {
        type: Number,
        default: 0,
    },
    rangePicker: {
        type: Boolean,
        default: false,
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
    },
    dateTimeInput: {
        type: Object
    },
    toDateTimeInput: {
        type: Object
    },
    timezoneInput: {
        type: Object
    }
});

// Convert props that should be booleans from string to number
// Boolean props will be : "0" => false, "1" => true
// We can't directly use true/false because html attributes are always strings. A non-zero length string is always true
const enableTimePicker = computed(() => Number(props.enableTimePicker));
const enableTimezonePicker = Number(props.enableTimezonePicker);

const date = ref(getDefaultDate(props.timestamp, props.toTimestamp));
const selectedTz = ref(props.timezone);
const datePicker = ref(null);

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
    // deal with vue-datepicker bug related to daylight saving - it doesn't reference the target date's DST pref
    // but acts as if it is the current date DST pref
    if (Array.isArray(value)) {
        for (let i = 0; i < value.length; i++) {
            if (value[i] === null) {
                continue;
            }
            value[i] = moment(value[i]).add(daylightDiffAgainstBrowserTz(value[i], selectedTz.value), 'second').toDate();
        }
    } else {
        value = moment(value).add(daylightDiffAgainstBrowserTz(value, selectedTz.value), 'second').toDate();
    }
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

const handleTodayClick = (_, toDate = false) => {
    const now = moment().unix() * 1000;

    if (toDate) {
        handleDatetimeChange([date.value[0], now]);
    } else {
        handleDatetimeChange(props.rangePicker ? [now, date.value[1]] : now);
    }

    // In the test environment, we do not yet have access to the closeMenu() method. However, not covering it in the test for now is not a big deal.
    datePicker.value.closeMenu?.();
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
    if (!props.rangePicker) {
        return fromTimestamp * 1000;
    }
    return [fromTimestamp * 1000, toTimestamp * 1000];
};

const getUnixTimestamp = (date) => convertToUnixTimestamp(date);

function getTimezoneOffset(timezone) {
    const currentTime = moment.tz(timezone);

    const offsetMinutes = currentTime.utcOffset();
    const offsetHours = Math.floor(Math.abs(offsetMinutes) / 60);
    const offsetMinutesRemainder = Math.abs(offsetMinutes) % 60;

    const offsetSign = offsetMinutes < 0 ? '-' : '+';

    const offsetString = `GMT${offsetSign}${String(offsetHours).padStart(2, '0')}:${String(offsetMinutesRemainder).padStart(2, '0')}`;

    return offsetString;
}

const formatFn = computed(() => {
    const timePicker = Boolean(enableTimePicker.value);
    return (value) => formatDate(value, Boolean(timePicker), locale[props.language])
});

/*
====================
*/

watchEffect(() => {
    props.dateTimeInput.value = unixTimestamp.value;
    if (props.rangePicker) {
        props.toDateTimeInput.value = toUnixTimestamp.value;
    }
    if (enableTimezonePicker) {
        props.timezoneInput.value = selectedTz.value;
    }
}, { flush: 'post' });
</script>