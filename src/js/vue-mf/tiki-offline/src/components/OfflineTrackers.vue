<template>
    <div class="offline-trackers">
        <OfflineTracker
            v-for="(tracker, index) in trackerData"
            :tracker="tracker"
            :index="index"
        />
    </div>
</template>

<script>
    import { inject } from 'vue';
    import OfflineTracker from "./OfflineTracker.vue";

    export default {
        name: "OfflineTrackers",
        components: {
            OfflineTracker: OfflineTracker
        },
        props: {
            trackerData: {
                type: Object
            }
        },
        data: function () {
            return {
                store: inject('store')
            }
        },
    };
</script>