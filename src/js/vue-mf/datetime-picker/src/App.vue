<template>
    <div>
        <VueDatePicker v-model="date" :timezone="tz" :locale="language" input-class-name="tiki-form-control"
            :enable-time-picker="enableTimePicker" :range="Boolean(toInputName)" @update:model-value="goToURLWithData"
            :cancelText="cancelText" :selectText="selectText" :format="formatDate"/>
        <input type="hidden" :name="inputName" :value="unixTimestamp">
        <input type="hidden" :name="toInputName" :value="toUnixTimestamp">
        <input type="hidden" name="useDisplayTz" value="1" v-if="!enableTimezonePicker">
        <div class="mt-3" v-if="enableTimezonePicker">
            <label for="timezone" class="form-label">Timezone</label>
            <select class="form-select" aria-label="Select a timezone" v-model="selectedTz" :name="timezoneFieldName">
                <option v-for="(timezone, index) in timezones" :key="index" :value="timezone">{{ timezone }} ({{
                    getTimezoneOffset(timezone) }})</option>
            </select>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import VueDatePicker from '@vuepic/vue-datepicker';
import moment from 'moment-timezone';
import { format } from 'date-fns';
import * as locale from 'date-fns/locale';

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
        type: Boolean,
        default: true,
    },
    enableTimezonePicker: {
        type: Boolean,
        default: false,
    },
    toInputName: {
        type: String,
        default: null,
    },
    goToURLOnChange: {
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
});

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

const goToURLWithData = (data) => {
    date.value = data;
    if (props.goToURLOnChange) {
        const url = new URL(props.goToURLOnChange, window.location.origin);
        if(data) {
            url.searchParams.set('todate', unixTimestamp.value);
            url.searchParams.set('enddate', toUnixTimestamp.value);
            url.searchParams.set('tzname', selectedTz.value);
            url.searchParams.set('tzoffset', moment.tz(selectedTz.value).utcOffset());
        } else {
            url.searchParams.delete('todate');
            url.searchParams.delete('enddate');
            url.searchParams.delete('tzname');
            url.searchParams.delete('tzoffset');
        }
        window.location.href = url
    }
};

$('body').applySelect2();
$('body').on('change', `select[name="${props.timezoneFieldName}"]`, function (e) {
    selectedTz.value = e.target.value;
});

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

function getUnixTimestamp(date) {
    if (!props.enableTimezonePicker && !props.timezone) {
        return moment(date).unix();
    }

    const browserOffset = new Date().getTimezoneOffset() * 60;
    const selectedTzOffset = moment.tz(selectedTz.value).utcOffset() * 60;
    return moment(date).unix() + browserOffset + selectedTzOffset;
};

function getTimezoneOffset(timezone) {
    const currentTime = moment.tz(timezone);

    const offsetMinutes = currentTime.utcOffset();
    const offsetHours = Math.floor(Math.abs(offsetMinutes) / 60);
    const offsetMinutesRemainder = Math.abs(offsetMinutes) % 60;

    const offsetSign = offsetMinutes < 0 ? '-' : '+';

    const offsetString = `GMT${offsetSign}${String(offsetHours).padStart(2, '0')}:${String(offsetMinutesRemainder).padStart(2, '0')}`;

    return offsetString;
}

const formatDate = (value) => {
    const formatValue = (date) => format(date, props.enableTimePicker ? 'Pp': 'P', { locale: locale[props.language] });
    return Array.isArray(value) ? value.map(formatValue).join(' - ') : formatValue(value);
}
</script>