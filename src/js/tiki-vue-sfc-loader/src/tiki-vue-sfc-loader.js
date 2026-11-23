import { loadModule } from "vue3-sfc-loader";

const vueBuildImporters = {
    dev: () => import("vue-sfc"),
    prod: () => import("vue-sfc-prod"),
};

const options = {
    async getFile(url) {
        const res = await fetch(url);
        if (!res.ok) {
            throw Object.assign(new Error(res.statusText + " " + url), { res });
        }
        return {
            getContentData: (asBinary) => (asBinary ? res.arrayBuffer() : res.text()),
        };
    },
    addStyle(textContent) {
        const style = Object.assign(document.createElement("style"), { textContent });
        const ref = document.head.getElementsByTagName("style")[0] || null;
        document.head.insertBefore(style, ref);
    },
};

/**
 * Mounts a dynamically loaded Vue component to a specific DOM element.
 * @param {string} targetId - The ID of the DOM element to mount the component.
 * @param {string} componentUrl - The URL of the `.vue` component.
 * @param {object} [propsData={}] - The props data to pass to the component.
 * @param {"dev"|"prod"} [build="dev"] - Development runtime (warnings and devtools) or production runtime.
 */
export async function mountApp(targetId, componentUrl, propsData = {}, build = "dev") {
    const loadVue = vueBuildImporters[build] ?? vueBuildImporters.dev;
    const Vue = await loadVue();
    const app = Vue.createApp({
        render() {
            return Vue.h(
                Vue.defineAsyncComponent(() =>
                    loadModule(componentUrl, {
                        ...options,
                        moduleCache: { vue: Vue },
                    })
                ),
                {
                    ...propsData,
                }
            );
        },
    });

    app.mount(`#${targetId}`);
}
