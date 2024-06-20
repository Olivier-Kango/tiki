<!--
    Field type: f
-->
<template>
    <datetime-picker
        :input-name="field.ins_id"
        :theme-css="themeCss"
        :timestamp="model.date"
        :timezone="model.timezone"
        to-input-name=""
        to-timestamp=""
        :timezone-field-name="`${field.ins_id}_timezone`"
        :enable-timezone-picker="field.options_map.customTimezone && field.options_map.customTimezone != '0' ? 1 : 0"
        :enable-time-picker="field.options_map.datetime == 'd' ? 0 : 1"
        :language="language"
        :global-callback="globalHandlerName"
    ></datetime-picker>
</template>

<script setup>
    import { computed } from 'vue'
    import store from '../../store';

    const model = defineModel()
    const props = defineProps({
        field: {
            type: Object
        }
    })
    const emit = defineEmits(['input'])

    const themeCss = computed(() => store.getters.getPref('theme_css'))
    const language = computed(() => store.getters.getPref('language'))

    // Note: normal Vue emits/listen doesn't work here as custom element is rendered via render API and event is lost
    const globalHandlerName = "handleDatetimePickerChange" + Math.random().toString(8).slice(2)
    window[globalHandlerName] = (args) => {
        console.log(args)
        emit('update:modelValue', {
            date: args.date,
            timezone: args.tzname,
        })
    }
</script>

<script>
export default {
    name: 'JsCalendar'
}
</script>
