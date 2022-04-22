import { createStore } from 'vuex'
import logger from "./logger"
import { v4 as uuidv4 } from 'uuid'
import {
    saveToLocalStorage,
    parseAttributesDefaults,
    createBlock,
    cloneBlockDeep,
    getParentBlockId,
    getDescendantIds,
    swapIndices
} from './helpers'

const debug = import.meta.env.MODE !== 'production'

export default createStore({
    modules: {
        // dynamic modules
    },
    strict: debug,
    plugins: debug ? [logger] : [],
    state: {
        mode: 'prod', // 'edit'
        selectedBlock: null,
        highlightedBlock: null,
        blockTypes: {},
        tree: [],
        blocks: {
            'root': {
                id: `root`,
                parentId: null,
                name: 'core/root',
                innerBlocks: [],
                attributes: {}
            }
        }
    },
    getters: {
        getMode(state) {
            return state.mode
        },
        getRootBlock(state) {
            return state.blocks['root']
        },
        getBlock(state) {
            return (id) => state.blocks[id]
        },
        getParentBlock(state) {
            return (id) => state.blocks[getParentBlockId(state.blocks, id)]
        },
        getBlocks(state) {
            return state.blocks
        },
        getBlockList(state) {
            return Object.getOwnPropertyNames(state.blockTypes)
        },
        getBlockType(state) {
            return type => state.blockTypes[type]
        },
        getSelected(state) {
            return state.blocks[state.selectedBlock]
        },
        getHighlighted(state) {
            return state.blocks[state.highlightedBlock]
        }
    },
    actions: {
        addBlockType({ commit }, data) {
            commit('addBlockType', data)
        },
        setMode({ commit }, data) {
            commit('setMode', data)
        },
        setSelected({ commit }, data) {
            commit('setSelected', data)
        },
        setHighlighted({ commit }, data) {
            commit('setHighlighted', data)
        },
        setBlocks({ commit }, data) {
            commit('setBlocks', data)
        },
        updateBlock({ commit, state }, data) {
            commit('updateBlock', data)
            saveToLocalStorage(state.blocks)
        },
        insertBlock({ commit, state }, data) {
            commit('insertBlock', data)
            saveToLocalStorage(state.blocks)
        },
        insertBlockAfter({ commit, state }, data) {
            commit('insertBlockAfter', data)
            saveToLocalStorage(state.blocks)
        },
        removeBlock({ commit, state }, data) {
            commit('removeBlock', data)
            saveToLocalStorage(state.blocks)
        },
        moveBlockBackward({ commit, state }, data) {
            commit('moveBlockBackward', data)
            saveToLocalStorage(state.blocks)
        },
        moveBlockForward({ commit, state }, data) {
            commit('moveBlockForward', data)
            saveToLocalStorage(state.blocks)
        },
        cloneBlock({ commit }, data) {
            commit('cloneBlock', data)
        },
    },
    mutations: {
        addBlockType(state, data) {
            state.blockTypes = { ...state.blockTypes, ...data }
        },
        setMode(state, data) {
            state.mode = data;
        },
        setBlocks(state, data) {
            state.blocks = data;
        },
        setSelected(state, data) {
            state.selectedBlock = data;
        },
        setHighlighted(state, data) {
            state.highlightedBlock = data;
        },
        updateBlock(state, data) {
            let { block, field, value } = data
            state.blocks[block.id][field] = value
        },
        insertBlock(state, { name, targetId, position = 0 }) {
            let block = createBlock(state.blockTypes[name])
            state.blocks[block.id] = block
            state.blocks[targetId].innerBlocks.splice(position, 0, block.id)
        },
        insertBlockAfter(state, { name, targetId }) {
            let block = createBlock(state.blockTypes[name])
            state.blocks[block.id] = block

            let parentBlockId = getParentBlockId(state.blocks, targetId)
            let targetIndex = state.blocks[parentBlockId].innerBlocks.findIndex(id => id === targetId)
            state.blocks[parentBlockId].innerBlocks.splice(targetIndex + 1, 0, block.id)
        },
        createBlock(state, name) {
            let id = uuidv4()
            let blockType = state.blockTypes[name]
            state.blocks[id] = { id, name, innerBlocks: [], attributes: parseAttributesDefaults(blockType.attributes) }
        },
        removeBlock(state, blockId) {
            // Removes id in parent innerBlocks
            let parentInnerBlocks = state.blocks[getParentBlockId(state.blocks, blockId)].innerBlocks
            let index = parentInnerBlocks.findIndex(id => id === blockId)
            parentInnerBlocks.splice(index, 1)
            // Removes all
            // node.descendants returns an array of descendant nodes: the given node, then each child, and each child’s child, and so on.
            let ids = getDescendantIds(state.blocks, blockId)
            ids.forEach(id => delete state.blocks[id])
        },
        moveBlockBackward(state, blockId) {
            let parentId = getParentBlockId(state.blocks, blockId)
            let index = state.blocks[parentId].innerBlocks.findIndex(id => id === blockId)
            if (index > 0)
                swapIndices(index, index - 1, state.blocks[parentId].innerBlocks) 
        },
        moveBlockForward(state, blockId) {
            let parentId = getParentBlockId(state.blocks, blockId)
            let index = state.blocks[parentId].innerBlocks.findIndex(id => id === blockId)
            if (index < state.blocks[parentId].innerBlocks.length - 1)
                swapIndices(index, index + 1, state.blocks[parentId].innerBlocks) 
        },
        cloneBlock(state, blockId) {
            let res = cloneBlockDeep(state.blocks, blockId)
            
            console.log(res)
        }
    }
});
