<template>
    <div class="duration-picker" data-field-type="DUR">
        <div v-on:click="showModal">
            <DurationPickerAmounts :duration="store.state.duration" :amounts="getTotalAmounts"></DurationPickerAmounts>
        </div>
        <transition name="fade">
            <DurationPickerModal v-show="show" :handle-close-modal="handleCloseModal"></DurationPickerModal>
        </transition>
        <input type="hidden" :name="store.state.inputName" :value="getAmountsTotalStringified">
    </div>
</template>

<script>
    import { inject } from 'vue';
    import DurationPickerAmounts from "./DurationPickerAmounts.vue";
    import DurationPickerModal from "./DurationPickerModal.vue";

    export default {
        name: "DurationPicker",
        components: {
            DurationPickerAmounts: DurationPickerAmounts,
            DurationPickerModal: DurationPickerModal
        },
        data: function () {
            return {
                show: false,
                amounts: {},
                store: inject('store')
            }
        },
        computed: {
            getTotalAmounts: function() {
                const totalDuration = this.store.getTotalDuration();
                const amounts = this.store.__calcDuration(totalDuration);
                return amounts;
            },
            getAmountsTotalStringified: function() {
                const totalDuration = this.store.getTotalDuration();
                const amounts = this.store.__calcDuration(totalDuration);
                return JSON.stringify(amounts);
            }
        },
        methods: {
            handleCloseModal: function () {
                this.show = false;
            },
            showModal: function () {
                this.show = true;
            }
        }
    };
</script>