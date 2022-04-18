<script>
export default {
    name: 'App'
}
</script>
<script setup>
import { onBeforeMount, computed } from 'vue'
import BlockEditor from './components/utils/BlockEditor.vue'
import store from './store'
import defaultTemplate from './components/templates/default.json'

const props = defineProps({
    customProps: {
        type: Object
    }
})

onBeforeMount(() => {
    let blocks = null
    let jsonBlocks = localStorage.getItem('tiki-pop-up-blocks')

    if (jsonBlocks) {
        try {
            blocks = JSON.parse(jsonBlocks)
        } catch (err) {
            console.log(err)
        }
    }

    if (blocks) store.dispatch('setBlocks', blocks)
    store.dispatch('setMode', 'edit')

    // let mockBlocks = {
    //     'root': {
    //         id: `root`,
    //         parentId: null,
    //         name: 'core/root',
    //         innerBlocks: ['22', '11'],
    //         attributes: {}
    //     },
    //     '11': {
    //         id: `11`,
    //         parentId: 'root',
    //         name: store.getters.getBlockType("core/container").name,
    //         innerBlocks: ['33'],
    //         attributes: {}
    //     },
    //     '22': {
    //         id: `22`,
    //         parentId: 'root',
    //         name: store.getters.getBlockType("core/title").name,
    //         innerBlocks: [],
    //         attributes: {}
    //     },
    //     '33': {
    //         id: `33`,
    //         parentId: '11',
    //         name: store.getters.getBlockType("core/columns").name,
    //         innerBlocks: ['44', '55'],
    //         attributes: {}
    //     },
    //     '44': {
    //         id: `44`,
    //         parentId: '33',
    //         name: store.getters.getBlockType("core/column").name,
    //         innerBlocks: ['66'],
    //         attributes: {}
    //     },
    //     '55': {
    //         id: `55`,
    //         parentId: '33',
    //         name: store.getters.getBlockType("core/column").name,
    //         innerBlocks: [],
    //         attributes: {}
    //     },
    //     '66': {
    //         id: `66`,
    //         parentId: '44',
    //         name: store.getters.getBlockType("core/paragraph").name,
    //         innerBlocks: [],
    //         attributes: {
    //             textContent: "Cat ipsum dolor sit amet, who's the baby human is washing you why halp oh the horror flee scratch hiss bite. Proudly present butt to human litter box is life, and x pet my belly, you know you want to; seize the hand and shred it!, so claws in your leg. Destroy couch as revenge vommit food and eat it again stare at owner accusingly then wink. I'm going to lap some water out of my master's cup meow nyan fluffness ahh cucumber! and litter box is life, and meow to be let out, demand to have some of whatever the human is cooking,"
    //         }
    //     }
    // }

    // store.dispatch('setBlocks', mockBlocks)
})

const edit = computed(() => store.getters.getMode === 'edit')
</script>

<template>
    <BlockEditor :edit="edit"></BlockEditor>
</template>
