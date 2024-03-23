<template>
    <div class="offline-trackers">
        <h2>Offline tracker items</h2>
        <FormAddTrackerItem :formFields="[]" :initialValues="{}" />        
        <OfflineTracker
            v-for="(tracker, index) in trackerData"
            :tracker="tracker"
            :index="index"
        >
            <!--Todo: Add table style in @vue-mf/styleguide-->
            <div class="table-responsive large-table-no-wrap">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th v-for="field in tracker.fields">{{ field.name }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in trackerItems[0]">
                            <template v-if="item.trackerId == tracker.trackerId">
                                <!-- Todo: Show link on one of the main fields-->
                                <td v-for="field in tracker.fields">{{ item[field.ins_id] }}</td>
                            </template>
                        </tr> 
                    </tbody>
                </table>
            </div>
        </OfflineTracker>
    </div>
</template>

<script setup>
    import { inject } from 'vue';
    import OfflineTracker from "./OfflineTracker.vue";
    import FormAddTrackerItem from "./Forms/FormAddTrackerItem.vue";
</script>

<script>
    export default {
        name: "OfflineTrackers",
        components: {
            OfflineTracker: OfflineTracker,
            FormAddTrackerItem: FormAddTrackerItem
        },
        props: {
            trackerData: {
                type: Object
            },
            trackerItems: {
                type: Object
            }
        },
        data: function () {
            return {
                store: inject('store')
            }
        },
    };
    function createTrackerItem () {
        // Load tracker fields and 
        // craft a form accordingly
    }
</script>