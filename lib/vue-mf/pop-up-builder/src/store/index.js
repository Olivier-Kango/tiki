import { createStore } from 'vuex'
import logger from "./logger"
import { stratify } from 'd3-hierarchy'
// import { v4 as uuidv4 } from 'uuid'

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
        blocks: [],
        tree: []
    },
    getters: {
        getRootBlock(state) {
            return state.blocks.find(block => block.name === 'Root')
        },
        getTree(state) {
            return state.tree
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
        setSelected({ commit }, data) {
            commit('setSelected', data)
        },
        setHighlighted({ commit }, data) {
            commit('setHighlighted', data)
        },
        setBlocks({ commit }, data) {
            commit('setBlocks', data)
            commit('setTree')
        },
        updateBlock({ commit, state }, data) {
            commit('updateBlock', data)
            commit('setTree')
            saveToLocalStorage(state.blocks)
        },
        addBlock({ commit, state }, data) {
            commit('addBlock', data)
            commit('setTree')
            saveToLocalStorage(state.blocks)
        },
        addBlockAfter({ commit, state }, data) {
            commit('addBlockAfter', data)
            commit('setTree')
            saveToLocalStorage(state.blocks)
        },
        removeBlock({ commit, state }, data) {
            commit('removeBlock', data)
            commit('setTree')
            saveToLocalStorage(state.blocks)
        }
    },
    mutations: {
        setBlocks(state, data) {
            state.blocks = data;
        },
        setTree(state, data) {
            let stratifyFunc = stratify().id(d => d.id).parentId(d => d.parent_id)
            state.tree = stratifyFunc(state.blocks)
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
        },
        addBlock(state, data) {
            let block = {...state.blockSchema, ...data}
            state.blocks.unshift(block)
        },
        addBlockAfter(state, data) {
            let index = state.blocks.findIndex(el => el.id === data.afterBlock.id)
            let block = {...state.blockSchema, ...data.block}
            state.blocks.splice(index + 1, 0, block)
        },
        removeBlock(state, data) {
            // Removes all
            // node.descendants returns an array of descendant nodes: the given node, then each child, and each child’s child, and so on.
            let node = state.tree.find(node => node.id === data.id)
            state.blocks = state.blocks
                .filter(block => !node.descendants().find(node => node.id === block.id))
        },
    }
});

function saveToLocalStorage(data) {
    localStorage.setItem('tiki-pop-up-blocks', JSON.stringify(data))
}
