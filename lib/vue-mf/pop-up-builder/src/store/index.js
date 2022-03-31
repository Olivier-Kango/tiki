import { createStore } from 'vuex'
import logger from "./logger"
import { stratify } from 'd3-hierarchy'

const debug = import.meta.env.MODE !== 'production'

export default createStore({
    modules: {
        // dynamic modules
    },
    strict: debug,
    plugins: debug ? [logger] : [],
    state: {
        blockList: [
            'Button',
            'Col',
            'Container',
            'Image',
            'Paragraph',
            'Row',
            'Title'
        ],
        blocks: [
            {
                id: 1,
                parent_id: null,
                name: 'Container',
                text: '',
                classes: '',
                styles: {
                    marginTop: '5rem',
                    marginBottom: '5rem'
                }
            }
        ]
    },
    getters: {
        getBlocksTree(state) {
            let stratifyFunc = stratify().id(d => d.id).parentId(d => d.parent_id)
            let tree = stratifyFunc(state.blocks)
            console.log('tree', tree)
            return tree
        },
        getBlocks(state) {
            return state.blocks
        },
        getBlockList(state) {
            return state.blockList
        }
    },
    actions: {
        setBlocks({ commit }, data) {
            commit('setBlocks', data)
        },
        addBlock({ commit }, data) {
            commit('addBlock', data)
        },
        addBlockAfter({ commit }, data) {
            commit('addBlockAfter', data)
        },
        removeBlock({ commit }, data) {
            commit('removeBlock', data)
        }
    },
    mutations: {
        setBlocks(state, data) {
            state.blocks = data;
        },
        addBlock(state, data) {
            state.blocks.push(data)
        },
        addBlockAfter(state, data) {
            let index = state.blocks.findIndex(el => el.id === data.afterBlock.id)
            state.blocks.splice(index + 1, 0, data.block)
        },
        removeBlock(state, data) {
            // Removes all
            // node.descendants returns an array of descendant nodes: the given node, then each child, and each child’s child, and so on.
            const removeValFromIndex = data.descendants().map(node => {
                let index = state.blocks.findIndex(block => block.id === node.id)
                return index
            })

            for (let i = removeValFromIndex.length - 1; i >= 0; i--) {
                state.blocks.splice(removeValFromIndex[i], 1);
            }
        },
    }
});
