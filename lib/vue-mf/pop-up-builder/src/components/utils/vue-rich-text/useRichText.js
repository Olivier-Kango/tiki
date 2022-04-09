import { ref } from 'vue'

export function useRichText(initContent) {
    const html = ref(initContent)
    
    const setAction = (action) => {
        html.value = `<${action}>${html.value}</${action}>`
    }

    const setInnerHTML = (content) => {
        html.value = content
    }

    return {
        setAction,
        html,
        setInnerHTML
    }
}
