import { defineCustomElement, h } from "vue";
import App from "./App.vue";
import styles from "./custom.scss?inline";

customElements.define(
    "datetime-picker",
    defineCustomElement({
        render: (props, { slots }) => {
            return h(App, props, slots);
        },
        styles: [styles],
    })
);

new MutationObserver((mutations) => {
    if (mutations.some((m) => m.target.querySelector("datetime-picker"))) {
        const datetimePickers = document.querySelectorAll("datetime-picker");

        /*
            Sync the hidden inputs and the timezone picker with the form element. Essential for data submission.
        */
        datetimePickers.forEach((datetimePicker) => {
            const form = datetimePicker.closest("form");
            form.onsubmit = (e) => {
                e.preventDefault();
                const elements = form.querySelectorAll("datetime-picker");
                elements.forEach((el) => {
                    el.shadowRoot.querySelectorAll("input[type='hidden']").forEach((input) => {
                        form.appendChild(input);
                    });
                    const timezone = el.shadowRoot.querySelector("select[id='timezone']");
                    if (timezone) {
                        form.appendChild(timezone);
                    }
                });
                form.submit();
            };
        });
    }
}).observe(document.body, { childList: true, subtree: true });
