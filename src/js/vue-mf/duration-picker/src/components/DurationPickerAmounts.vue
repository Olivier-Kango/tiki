<template>
    <div class="dp-amount--container">
        <div v-for="unit in duration.units" :key="unit"
            class="dp-amount--view" :class="{ active: store.state.activeUnit === unit }"
            :title="unit" v-on:click="setUnit(unit)"
        >
            <span class="dp-amount--preview__value">{{ amounts[unit] }}{{ unit.charAt(0) }}</span>
        </div>
        <span class="dp-alert" title="Not saved" v-if="store.state.draft && !store.state.playing">
            <i class="fas fa-exclamation"></i>
        </span>
        <span class="dp-clock-spin">
            <i class="far fa-clock fa-spin" v-if="store.state.playing"></i>
        </span>
    </div>
</template>

<script>
    import { inject } from 'vue';

    export default {
        name: "DurationPickerAmounts",
        data: function () {
            return {
                store: inject('store'),
            }
        },
        props: {
            duration: Object,
            amounts: Object
        },
        methods: {
            setUnit: function (unit) {
                this.store.setActiveUnit(unit);
            }
        }
    };
</script>