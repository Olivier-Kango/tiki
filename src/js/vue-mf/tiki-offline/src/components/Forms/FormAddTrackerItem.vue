<template>
    <!-- Modal -->
    <div class="modal fade" id="formAddTrackerItemModal" tabindex="-1" role="dialog" aria-labelledby="formAddTrackerItemLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="formAddTrackerItemLabel">Create tracker item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Render the form here -->
                    <form>
                        <child-component v-for="(field, index) in formFields" :key="index" :field="field"
                            :initialValue="initialValues[field.name]" @input="updateValue(field.name, $event)">
                            <div class="tracker-field-group mb-3">
                                <label :for="field.ins_id">
                                    {{field.name}}
                                </label>
                                <div :id="fieldId(field.fieldId)">
                                    <Text v-if="field.type == 't'" :id=field.ins_id :name=field.ins_id />
                                </div>
                            </div>
                        </child-component>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" @click="submitForm">Create Item</button>
                </div>
            </div>
        </div>
    </div>
    <Button variant="primary" sm data-bs-toggle="modal" data-bs-target="#formAddTrackerItemModal">
        <i class="fas fa-plus"></i>
        <span class="ml-2">Create item</span>
    </Button>
</template>

<script setup>
// Import form field components
// Todo: Import only if needed
import Checkbox from '../Fields/CheckBox.vue';
import DateTime from '../Fields/DateTime.vue';
import Dropdown from '../Fields/Dropdown.vue';
import ItemLink from '../Fields/ItemLink.vue';
import ItemList from '../Fields/ItemList.vue';
import MultipleSelection from '../Fields/MultipleSelection.vue';
import Numeric from '../Fields/Numeric.vue';
import Text from '../Fields/Text.vue';
import UserSelector from '../Fields/UserSelector.vue';
import { Button } from '@vue-mf/styleguide';
import store from '../../store';

</script>

<script>
export default {
    name: 'FormAddTrackerItem',
    // Props received from parent component
    props: {
        trackerId: int,
        formFields: Array, // Configuration object defining form fields
        initialValues: Object // Initial values if editing
    },
    // Registering child components
    components: {
        Checkbox,
        DateTime,
        Dropdown,
        ItemLink,
        ItemList,
        MultipleSelection,
        Numeric,
        Text,
        UserSelector,
    },
    methods: {
        // form field value in parent component's data
        updateValue(fieldName, value) {
            this.$emit('input', { [fieldName]: value });
        },
        // handle form submission
        submitForm() {
            // TODO: prepare field values as an object ins_ID => value
            // store.commit('createItem', trackerId, fieldValues);
        },
        fieldId(fieldId) {
            return "trackerinput_" + fieldId;
        }
    }
};
</script>