<template>
    <div class="dp-editor--container">
        <div class="dp-amount--editor__header">
            <!-- <DurationPickerTitle v-if="store.state.chronometer" /> -->
            <DurationPickerAmounts :duration="store.state.duration" :amounts="getAmounts"></DurationPickerAmounts>
        </div>
        <div class="dp-amount--input__wrapper" v-on:keydown="handleTabKeys">
            <div class="dp-amount--input__left-section">
                <input
                    ref="input"
                    class="numeric duration-field"
                    type="number"
                    placeholder="__"
                    min="-1"
                    v-on:input="handleInput"
                    v-on:keydown="handleKeydown"
                    :value="convertValue"
                >
                <div class="dp-amount--input__label">{{ store.state.activeUnit }}</div>
                <div class="dp-amount--input__controls">
                    <div class="dp-amount--input__btn unselectable" title="-5"
                        v-on:mousedown="startArithmetic(-5)"
                        v-on:mouseleave="stopArithmetic"
                        v-on:mouseup="stopArithmetic"
                    ><i class="fas fa-angle-double-down"></i></div>
                    <div class="dp-amount--input__btn unselectable" title="-1"
                        v-on:mousedown="startArithmetic(-1)"
                        v-on:mouseleave="stopArithmetic"
                        v-on:mouseup="stopArithmetic"
                    ><i class="fas fa-angle-down"></i></div>
                    <div class="dp-amount--input__btn unselectable" title="+1"
                        v-on:mousedown="startArithmetic(1)"
                        v-on:mouseleave="stopArithmetic"
                        v-on:mouseup="stopArithmetic"
                    ><i class="fas fa-angle-up"></i></div>
                    <div class="dp-amount--input__btn unselectable" title="+5"
                        v-on:mousedown="startArithmetic(5)"
                        v-on:mouseleave="stopArithmetic"
                        v-on:mouseup="stopArithmetic"
                    ><i class="fas fa-angle-double-up"></i></div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import DurationPickerAmounts from "./DurationPickerAmounts.vue";
    import DurationPickerTitle from "./DurationPickerTitle.vue";
    import store from '../store';

    export default {
        name: "DurationPickerEditor",
        components: {
            DurationPickerAmounts: DurationPickerAmounts,
            DurationPickerTitle: DurationPickerTitle
        },
        data: function () {
            return {
                currentValue: 0,
                interval: false,
                timeoutId: null,
                store: store,
                inputId: store.state.inputId,
                ignore: false
            }
        },
        props: {
            initialUnit: String,
        },
        mounted: function () {
            this.$nextTick(function () {
                this.$refs.input.focus();
            });
        },
        // updated: function () {
        //     this.$nextTick(function () {
        //         this.$refs.input.focus();
        //     })
        // },
        beforeDestroy: function () {
            clearInterval(this.interval);
            clearTimeout(this.timeoutId);
            this.ignore = true;
        },
        computed: {
            convertValue: function() {
                const amounts = store.__calcDuration(store.getTimestamp(store.state.activeTimestamp).spentTime);
                this.currentValue = amounts[store.state.activeUnit];
                return amounts[store.state.activeUnit];
            },
            getAmounts: function() {
                const index = store.state.activeTimestamp;
                const duration = store.state.timestamps[index].spentTime;
                const amounts = store.__calcDuration(duration);
                return amounts;
            }
        },
        methods: {
            handleKeydown: function (e) {
                if (store.state.playing) {
                    e.preventDefault();
                    return;
                }
            },
            handleInput: function (e) {
                let value = parseInt(e.target.value, 10);
                if (isNaN(value)) {
                    value = 0;
                }
                store.setDurationValue(value - this.currentValue, store.state.activeUnit);

                if (!this.timeoutId) {
                    this.timeoutId = setTimeout(() => {
                        if (!store.state.playing) {
                            this.inputId && store.saveDurationDraft(this.inputId,{ draftAmounts: this.getTotalAmounts() })
                                .then(data => {
                                    if (this.ignore) return;
                                    store.state.draft = data;
                                });
                        }
                        clearTimeout(this.timeoutId);
                        this.timeoutId = null;
                    }, 180);
                }
            },
            handleClickUnit: function(unit) {
                this.$refs.input.focus();
                store.setActiveUnit(unit);
            },
            handleTabKeys: function(e) {
                if (e.shiftKey && e.which === 9) {
                    e.preventDefault();
                    this.prevAmount();
                } else if (e.which === 9) {
                    e.preventDefault();
                    this.nextAmount();
                }
            },
            nextAmount: function() {
                const unit = store.getAmountAfter(store.state.activeUnit);
                store.setActiveUnit(unit);
            },
            prevAmount: function() {
                const unit = store.getAmountBefore(store.state.activeUnit);
                store.setActiveUnit(unit);
            },
            handleArithmetic: function (step) {
                if (store.state.playing) return;
                store.setDurationValue(step, store.state.activeUnit);
                if (!store.state.playing) {
                    this.inputId && store.saveDurationDraft(this.inputId, { draftAmounts: this.getTotalAmounts() })
                        .then(data => {
                            store.state.draft = data;
                        });
                }
            },
            startArithmetic: function (step) {
                if (!this.interval) {
                    this.handleArithmetic(step);
                    this.interval = setInterval(this.handleArithmetic, 180, step);
                }
            },
            stopArithmetic: function () {
                clearInterval(this.interval);
                this.interval = false;
                this.$refs.input.focus();
            },
            getTotalAmounts: function() {
                const totalDuration = store.getTotalDuration();
                const amounts = store.__calcDuration(totalDuration);
                return amounts;
            }
        }
    };
</script>