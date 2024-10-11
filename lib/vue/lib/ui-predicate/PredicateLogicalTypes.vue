<template>
    <select
        class="form-select"
        :value="predicate.logic.logicalType_id"
        @change="$emit('change', $event.target.value)">
        <option
            v-for="logicalType in columns.logicalTypes"
            :key="logicalType.label"
            :value="logicalType.logicalType_id">{{logicalType.label}}
        </option>
    </select>
</template>

<script>
export default {
    name: 'PredicateLogicalTypes',
    props: {
        predicate: {
            type: Object,
            required: true,
        },
        columns: {
            type: Object,
            required: true,
        },
    }
}
</script>