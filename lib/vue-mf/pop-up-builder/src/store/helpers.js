import { v4 as uuidv4 } from 'uuid'
import cloneDeep from 'lodash/cloneDeep'
import axios from 'axios'

function saveToLocalStorage(data) {
    localStorage.setItem('tiki-pop-up-blocks', JSON.stringify(data))
}

function parseAttributesDefaults(attributes) {
    let defaults = {}
    if (attributes) {
        Object.getOwnPropertyNames(attributes).forEach(propName => {
            if (attributes[propName].hasOwnProperty('default')) {
                defaults[propName] = attributes[propName].default
            }
        })
    }
    return defaults
}

function createBlock(blockType, attributes, innerBlocks = []) {
    let id = uuidv4()
    let { name, parent, title } = blockType
    return { id, parent, title, name, innerBlocks, attributes: { ...attributes, ...parseAttributesDefaults(blockType.attributes) } }
}

function cloneBlockDeepBulk(blocks, blockIds = []) {
    let clonedBlocks = { innerBlocks: [], blocks: {} }

    blockIds.map(blockId => {
        let clonedBlock = cloneBlockDeep(blocks, blockId)
        clonedBlocks.innerBlocks.push(clonedBlock.rootId)

        Object.getOwnPropertyNames(clonedBlock.blocks).forEach(blockId => {
            clonedBlocks.blocks[blockId] = clonedBlock.blocks[blockId]
        })
    })

    return clonedBlocks
}

function cloneBlockDeep(blocks, rootId) {
    let ids = getDescendantIds(blocks, rootId)
    let clonedBlocks = {}
    ids.forEach(id => clonedBlocks[id] = cloneDeep(blocks[id]))

    return setBlockNewIdsDeep(clonedBlocks, rootId)
}

function setBlockNewIdsDeep(blocks, rootId) {
    let newBlocks = {}
    let blockIds = Object.getOwnPropertyNames(blocks)
    let newRootId = ''

    blockIds.forEach(id => {
        let newId = uuidv4()
        if (id === rootId) newRootId = newId

        blockIds.forEach(blockId => {
            let foundIndex = blocks[blockId].innerBlocks.findIndex(blockId => blockId === id)
            if (foundIndex !== -1) {
                blocks[blockId].innerBlocks.splice(foundIndex, 1, newId)
            }
        })
        newBlocks[newId] = { ...blocks[id], id: newId }
    })
    return {
        rootId: newRootId,
        blocks: newBlocks
    }
}

function getParentBlockId(blocks, id) {
    let parentBlock = null
    Object.getOwnPropertyNames(blocks).some(blockId => {
        if (blocks[blockId].innerBlocks.find(innerId => innerId === id)) parentBlock = blocks[blockId]
    })
    return parentBlock && parentBlock.id
}

function getDescendantIds(blocks, id) {
    let currIds = []
    let tempIds = blocks[id].innerBlocks
    let resultIds = [id].concat(tempIds)
    while (tempIds.length > 0) {
        tempIds.forEach(id => currIds = currIds.concat(blocks[id].innerBlocks))
        tempIds = currIds
        resultIds = resultIds.concat(currIds)
        currIds = []
    }
    return resultIds
}

function swapIndices(i1, i2, array) {
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

// function arrayInsertElement(i, element, array) {
//     array.splice(index, 0, element)
// }

// function arrayReplaceElement(i, element, array) {
//     array.splice(i, 1, element)
// }

// function arrayRemoveElement(i, array) {
//     array.splice(i, 1)
// }

function registerStoreModule(storeOptions, store) {
    // Set dynamic store options with APIs and fields
    storeOptions.forEach(option => {
        let options = {
            namespaced: true,
            state: {},
            getters: {},
            actions: {},
            mutations: {}
        }

        options.state[option.field] = null
        options.getters['get_' + option.field] = (state) => state[option.field]
        options.actions['add_' + option.field] = ({ commit }) => {
            return axios(option.url)
                .then(res => {
                    commit('set_' + option.field, res.data)
                })
        }
        options.mutations['set_' + option.field] = (state, data) => {
            state[option.field] = data
        }

        // Register store module
        store.registerModule('module_' + option.field, options)
    })
}

export {
    saveToLocalStorage,
    parseAttributesDefaults,
    createBlock,
    cloneBlockDeep,
    cloneBlockDeepBulk,
    setBlockNewIdsDeep,
    getParentBlockId,
    getDescendantIds,
    swapIndices,
    registerStoreModule
}
