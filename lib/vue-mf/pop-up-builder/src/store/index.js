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
        selected: null,
        highlighted: null,
        blockSchema: {
            id: '',
            parent_id: '',
            name: '',
            classes: [],
            styles: {},
            attributes: {
                textContent: ''
            }
        },
        blockList: [
            'Button',
            'Col',
            'Container',
            'Image',
            'Paragraph',
            'Row',
            'Title'
        ],
        blocks: []
    },
    getters: {
        getBlocksTree(state) {
            let stratifyFunc = stratify().id(d => d.id).parentId(d => d.parent_id)
            let tree = stratifyFunc(state.blocks)
            return tree
        },
        getBlocks(state) {
            return state.blocks
        },
        getSelected(state) {
            return state.selected
        },
        getHighlighted(state) {
            return state.highlighted
        },
        getBlockList(state) {
            return state.blockList
        }
    },
    actions: {
        setBlocks({ commit }, data) {
            commit('setBlocks', data)
        },
        setSelected({ commit }, data) {
            commit('setSelected', data)
        },
        setHighlighted({ commit }, data) {
            commit('setHighlighted', data)
        },
        toggleSelected({ commit }, data) {
            commit('toggleSelected', data)
        },
        updateBlock({ commit }, data) {
            commit('updateBlock', data)
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
        setSelected(state, data) {
            state.selected = data;
        },
        setHighlighted(state, data) {
            state.highlighted = data;
        },
        updateBlock(state, data) {
            let { block, field, value } = data
            let index = state.blocks.findIndex(el => el.id === block.id)
            state.blocks[index][field] = value
            saveToLocalStorage(state.blocks)
        },
        addBlock(state, data) {
            let block = {...state.blockSchema, ...data}
            state.blocks.unshift(block)
            saveToLocalStorage(state.blocks)
        },
        addBlockAfter(state, data) {
            let index = state.blocks.findIndex(el => el.id === data.afterBlock.id)
            let block = {...state.blockSchema, ...data.block}
            state.blocks.splice(index + 1, 0, block)
            saveToLocalStorage(state.blocks)
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
            saveToLocalStorage(state.blocks)
        },
    }
});

function saveToLocalStorage(data) {
    localStorage.setItem('tiki-pop-up-blocks', JSON.stringify(data))
}
