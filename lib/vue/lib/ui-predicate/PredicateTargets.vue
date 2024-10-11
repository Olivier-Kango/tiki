<template>
    <select
        class="form-select"
        :value="predicate.target.target_id"
        @change="$emit('change', $event.target.value)">
        <option
            v-for="target in columns.targets"
            :key="target.label"
            :value="target.target_id">{{target.label}}
        </option>
    </select>
</template>

<script>
export default {
    name: 'PredicateTargets',
    props: {
        columns: {
            type: Object,
            required: true,
        },
        predicate: {
            type: Object,
            required: true,
        },
    }
}
</script>