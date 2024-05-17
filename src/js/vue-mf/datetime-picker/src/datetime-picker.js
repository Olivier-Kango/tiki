import singleSpaVue from "single-spa-vue";
import singleSpaCss from "single-spa-css";
import { createApp, h } from "vue";
import App from "./App.vue";

import "./custom.scss";

const vueLifecycles = singleSpaVue({
    createApp,
    appOptions: {
        render() {
            return h(App, {
                inputName: this.inputName,
                toInputName: this.toInputName,
                timestamp: this.timestamp,
                toTimestamp: this.toTimestamp,
                timezone: this.timezone,
                timezoneFieldName: this.timezoneFieldName,
                enableTimezonePicker: this.enableTimezonePicker,
                enableTimePicker: this.enableTimePicker,
                goToURLOnChange: this.goToURLOnChange,
                language: this.language,
                selectText: this.selectText,
                cancelText: this.cancelText,
            });
        },
    },
    handleInstance: (app) => {
        if (import.meta.env.MODE === "development") {
            console.log(import.meta.env);
        }
    },
});

const cssLifecycle = singleSpaCss({
    cssUrls: [
        {
            href: "public/generated/js/datetime-picker.css",
        },
    ],
});

export const bootstrap = [cssLifecycle.bootstrap, vueLifecycles.bootstrap];
export const mount = [cssLifecycle.mount, vueLifecycles.mount];
export const unmount = [cssLifecycle.unmount, vueLifecycles.unmount];
export const DatetimePicker = App;
