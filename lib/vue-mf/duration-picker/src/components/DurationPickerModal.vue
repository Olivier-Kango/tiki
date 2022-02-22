<template>
    <div class="dp-modal--backdrop">
        <div class="dp-modal--container">
            <div class="dp-amount--input__container">
                <div class="dp-amount--input__header">
                    <span class="dp-amount--input__header--btn unselectable" :class="{ active: store.state.view === 'editor'}" v-on:click="gotoEditor">
                        Duration picker
                        <i class="fas fa-edit"></i>
                    </span>
                    <!-- <span class="dp-amount--input__header--btn unselectable" :class="{ active: store.state.view === 'chronometer'}" v-if="store.state.chronometer" v-on:click="gotoChronometer">
                        Start/Stop
                        <i class="fas fa-stopwatch"></i>
                    </span> -->
                    <span class="dp-amount--input__header--btn unselectable inline-edit-save-fields" v-if="saveButton" v-on:click="handleSave">
                        Save
                        <i class="far fa-save"></i>
                    </span>
                    <div class="dp-amount--input__close" v-on:click="handleCloseModal" title="close">
                        <i class="fas fa-times"></i>
                    </div>
                </div>
                <DurationPickerEditor v-show="store.state.view === 'editor'" />
                <DurationPickerHistory v-if="store.state.chronometer" v-show="store.state.view === 'chronometer'" />
                <DurationPickerChronometer v-if="store.state.chronometer" />
            </div>
        </div>
    </div>
</template>

<script>
    import { inject } from 'vue';
    import DurationPickerEditor from "./DurationPickerEditor.vue";
    import DurationPickerChronometer from "./DurationPickerChronometer.vue";
    import DurationPickerHistory from "./DurationPickerHistory.vue";

    export default {
        name: "DurationPickerModal",
        components: {
            DurationPickerChronometer: DurationPickerChronometer,
            DurationPickerEditor: DurationPickerEditor,
            DurationPickerHistory: DurationPickerHistory
        },
        mounted: function () {
            if ($(this.$el).closest('.modal').length > 0) {
                this.saveButton = false;
            }
        },
        data: function () {
            return {
                store: inject('store'),
                saveButton: true
            }
        },
        props: {
            handleCloseModal: Function
        },
        methods: {
            gotoEditor: function() {
                this.store.setView('editor')
            },
            gotoChronometer: function() {
                this.store.setView('chronometer')
            },
            handleSave: function () {
                // Saving is done in inline-edit.js
                this.store.state.inputId && this.store.removeDurationDraft(this.store.state.inputId).then(data => {
                    this.store.state.draft = false;
                });
            }
        }
    };
</script>