<template>
    <div class="offline-trackers">
        <h2>Offline tracker items</h2>
        <Button variant="primary" sm @click="createTrackerItem">
            <i class="fas fa-plus"></i> 
            <span class="ml-2">Create item</span>
        </Button>        
        <OfflineTracker
            v-for="(tracker, index) in trackerData"
            :tracker="tracker"
            :index="index"
        >
            <table>
                <thead>Items</thead>
                <tbody>
                    <tr v-for="item in trackerItems[0]">
                        <template v-if="item.trackerId == tracker.trackerId">
                            <!-- Todo: Show one of the mains fields-->
                            <td>{{ item }}</td>
                        </template>
                    </tr> 
                </tbody>
            </table>
        </OfflineTracker>
    </div>
</template>

<script setup>
    import { inject } from 'vue';
    import { Button } from '@vue-mf/styleguide';
    import OfflineTracker from "./OfflineTracker.vue";
</script>

<script>
    export default {
        name: "OfflineTrackers",
        components: {
            OfflineTracker: OfflineTracker
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