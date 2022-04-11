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
        blockList: [
            'core/button',
            'core/column',
            'core/columns',
            'core/container',
            'core/image',
            'core/paragraph',
            'core/title'
        ],
        blocks: [],
        tree: []
    },
    getters: {
        getRootBlock(state) {
            return state.blocks.find(block => block.name === 'core/root')
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
            let block = { id: uuidv4(), parent_id: data.inBlock.id, ...data.block }
            state.blocks.unshift(block)
        },
        addBlockAfter(state, data) {
            let index = state.blocks.findIndex(el => el.id === data.inBlock.id)
            let block = { id: uuidv4(), parent_id: data.inBlock.parent_id, ...data.block }
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

// function arrayInsertElement(i, element, array) {
//     array.splice(index, 0, element)
// }

// function arrayReplaceElement(i, element, array) {
//     array.splice(i, 1, element)
// }

// function arrayRemoveElement(i, array) {
//     array.splice(i, 1)
// }

function arraySwapElements(i1, i2, array) {
    if (i1 >= array.length) {
        console.warn(`Index ${i1} is outside the array.`, array)
        return array
    }
    if (i2 >= array.length) {
        console.warn(`Index ${i2} is outside the array.`, array)
        return array
    }
    let temp = array[i2]
    array.splice(i2, 1, array[i1])
    array.splice(i1, 1, temp)

    return array
}