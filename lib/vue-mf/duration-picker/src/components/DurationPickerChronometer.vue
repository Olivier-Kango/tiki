<template>
    <div class="dp-chronometer__container">
        <div class="dp-chronometer__group">
            <!-- <span class="dp-chronometer-btn unselectable" v-on:click="addTimer" title="add time">
                <i class="fas fa-plus"></i>
            </span> -->
            <span class="dp-chronometer-btn unselectable" v-if="show" v-on:click="startTimer" title="start">
                <i class="fas fa-play"></i>
            </span>
            <span class="dp-chronometer-btn unselectable" v-if="!show" v-on:click="stopTimer" title="pause">
                <i class="fas fa-pause"></i>
            </span>
            <!-- <span class="dp-chronometer-btn unselectable dp-danger" v-on:click="deleteTimestamp" title="delete">
                <i class="fas fa-trash"></i>
            </span> -->
            <span class="dp-chronometer-btn unselectable dp-danger" v-on:click="resetTimer" title="reset">
                <i class="fas fa-undo-alt"></i>
            </span>
            <!-- <span class="dp-chronometer-btn unselectable" v-on:click="prevTimestamp" title="prev">
                <i class="fas fa-chevron-left"></i>
            </span> -->
            <!-- <span class="dp-chronometer-btn unselectable" v-on:click="nextTimestamp" title="next">
                <i class="fas fa-chevron-right"></i>
            </span> -->
        </div>
    </div>
</template>

<script>
    import moment from 'moment';
    import momentDurationFormatSetup from 'moment-duration-format';
    import store from '../store';

    momentDurationFormatSetup(moment);
    
    export default {
        name: "DurationPickerChronometer",
        data: function () {
            return {
                store: store,
                inputId: store.state.inputId,
                startId: false,
                intervalId: null,
                startDuration: null,
                show: true,
                timestamp: null,
                startTime: null
            };
        },
        mounted: function () {
            if (store.state.draft && store.state.draft.startTime) {
                this.startTime = store.state.draft.startTime;
                this.startTimer();
            }
        },
        beforeDestroy: function () {
            // cancelAnimationFrame(this.startId);
            clearInterval(this.intervalId);
        },
        methods: {
            startTimer: function () {
                if (this.startId) return;
                store.setPlaying(true);
                this.show = false;
                if (!this.startTime) {
                    this.startTime = moment();
                    this.inputId && store.saveDurationDraft(this.inputId, { startTime: this.startTime.toISOString() })
                        .then(data => {
                            store.state.draft = data;
                        });
                }
                this.timestamp = store.getTimestamp(store.state.activeTimestamp);
                this.updateActiveTimestamp({startTime: this.startTime});

                // this.startId = requestAnimationFrame(this.startChronometer);
                this.intervalId = setInterval(this.startChronometerInterval, 1000);
            },
            startChronometer: function() {
                const momentCurrentTime = moment();
                const millisecondsDiff = momentCurrentTime.diff(this.startTime);
                this.updateActiveTimestamp({spentTime: moment.duration(millisecondsDiff).add(this.timestamp.spentTime)})
                this.startId = requestAnimationFrame(this.startChronometer);
            },
            startChronometerInterval: function () {
                const momentCurrentTime = moment();
                const millisecondsDiff = momentCurrentTime.diff(this.startTime);
                const currentSpentTime = moment.duration(millisecondsDiff).add(this.timestamp.spentTime);
                this.updateActiveTimestamp({spentTime: currentSpentTime});
            },
            stopTimer: function () {
                this.inputId && store.saveDurationDraft(this.inputId, { startTime: '', draftAmounts: this.getTotalAmounts() })
                    .then(data => {
                        store.state.draft = data;
                    });
                // cancelAnimationFrame(this.startId);
                clearInterval(this.intervalId);
                this.startTime = null;
                this.startId = false;
                this.show = true;
                store.setPlaying(false);
                this.updateActiveTimestamp({stopTime: moment()});
            },
            getTotalAmounts: function() {
                const totalDuration = store.getTotalDuration();
                const amounts = store.__calcDuration(totalDuration);
                return amounts;
            },
            resetTimer: function () {
                if (store.state.playing) return;
                if (confirm("Reset to initial state ?")) {
                    // cancelAnimationFrame(this.startId);
                    clearInterval(this.intervalId);
                    this.startId = false;
                    this.show = true;

                    store.resetIntitialDuration();
                    this.inputId && store.removeDurationDraft(this.inputId).then(data => {
                        store.state.draft = false;
                    });
                }
            },
            addTimer: function() {
                if (store.state.playing) return;
                store.setTimestamp('add', {
                    spentTime: moment.duration(0)
                });
            },
            deleteTimestamp: function () {
                if (store.state.playing) {
                    confirm(`Can't delete while playing...`)
                    return;
                }
                if (store.state.playing || store.state.activeTimestamp === 0) {
                    confirm(`Can't delete first timestamp...`)
                    return;
                }
                const timestamp = store.getTimestamp(store.state.activeTimestamp);
                if (confirm(`Remove timestamp ${timestamp.spentTime.format('h [hrs], m [min], s [sec]')}?`)) {
                    store.setTimestamp('delete', timestamp);
                }
            },
            updateActiveTimestamp: function (updatesObj) {
                store.setTimestamp('update', {
                    ...store.getTimestamp(store.state.activeTimestamp),
                    ...updatesObj
                });
            },
            prevTimestamp: function () {
                store.setActiveTimestamp(store.state.activeTimestamp - 1);
            },
            nextTimestamp: function () {
                store.setActiveTimestamp(store.state.activeTimestamp + 1);
            }
        }
    };
</script>