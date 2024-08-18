import { defineCustomElement, h } from "vue";
import App from "./App.vue";
import styles from "./custom.scss?inline";

customElements.define(
    "datetime-picker",
    defineCustomElement(
        (props, ctx) => {
            const emitValueChange = (detail) => {
                ctx.emit("change", detail);
            };
            return () => h(App, { ...props, emitValueChange }, ctx.slots);
        },
        {
            styles: [styles],
        }
    )
);

new MutationObserver((mutations) => {
    if (mutations.some((m) => m.target.querySelector("datetime-picker"))) {
        const datetimePickers = document.querySelectorAll("datetime-picker");

        /*
            Sync the datetime picker value with the parent form. Essential for data submission.
        */
        datetimePickers.forEach((datetimePicker) => {
            const form = datetimePicker.closest("form");
            datetimePicker.shadowRoot.querySelectorAll("input[type='hidden']").forEach((input) => {
                if (!form.querySelector(`input[name="${input.name}"]`)) {
                    form.appendChild(input);
                }
            });
            const timezone = datetimePicker.shadowRoot.querySelector("select[id='timezone']");
            if (timezone) {
                if (!form.querySelector(`input[name="${timezone.name}"]`)) {
                    const input = document.createElement("input");
                    input.type = "hidden";
                    input.name = timezone.name;
                    input.value = timezone.value;
                    form.appendChild(input);
                }
            }
            datetimePicker.addEventListener("change", function (event) {
                this.shadowRoot.querySelectorAll("input[type='hidden']").forEach((input) => {
                    form.querySelector(`input[name="${input.name}"]`).value = input.value;
                });

                const timezone = this.shadowRoot.querySelector("select[id='timezone']");
                if (timezone) {
                    form.querySelector(`input[name="${timezone.name}"]`).value = timezone.value;
                }
            });
        });
    }
}).observe(document.body, { childList: true, subtree: true });
