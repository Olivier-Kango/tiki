<template>
    <h2>{{tracker.name}}</h2>
    <table class="table">
    <tbody>
        <tr>
            <td>TODO: display existing items entered in offline mode</td>
        </tr>
    </tbody>
    </table>
</template>

<script>
    export default {
        name: "OfflineTracker",
        props: {
            tracker: {
                type: Object
            },
            index: {
                type: Number
            }
        }
    };
</script>
