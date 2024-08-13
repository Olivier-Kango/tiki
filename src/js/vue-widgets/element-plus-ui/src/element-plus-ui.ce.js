import { defineCustomElement, h } from "vue";
import App from "./App.vue";
import styles from "./custom.scss?inline";

customElements.define(
    "element-plus-ui",
    defineCustomElement({
        render: (props, { slots }) => {
            return h(App, props, slots);
        },
        styles: [styles],
    })
);

/*
    Sync the Transfer hidden select with the form
*/
const form = document.querySelector("element-plus-ui").closest("form");
form.addEventListener("submit", () => {
    const elements = form.querySelectorAll("element-plus-ui");
    elements.forEach((el) => {
        const select = el.shadowRoot.querySelector("select[aria-hidden='true']");
        form.appendChild(select);
    });
});
