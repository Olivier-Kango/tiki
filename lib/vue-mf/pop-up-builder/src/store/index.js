import { createStore } from 'vuex'
import logger from "./logger"
import { stratify } from 'd3-hierarchy'
import { v4 as uuidv4 } from 'uuid'

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
        getRootBlock(state) {
            return state.blocks.find(block => block.name === 'Root')
        },
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
        setInitialBlock({ commit }, data) {
            commit('setInitialBlock', {
                id: 'root-container-' + uuidv4(),
                parent_id: null,
                name: 'Root'
            })
        },
        setTemplate({ commit }, data) {
            commit('setInitialTemplate', data)
        },
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
        setInitialBlock(state, data) {
            state.blocks.push(data)
        },
        setInitialTemplate(state, data) {
            state.blocks = data
        },
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
            state.blocks = state.blocks
                .filter(block => !data.descendants().find(node => node.id === block.id))
            saveToLocalStorage(state.blocks)
        },
    }
});

function saveToLocalStorage(data) {
    localStorage.setItem('tiki-pop-up-blocks', JSON.stringify(data))
}
