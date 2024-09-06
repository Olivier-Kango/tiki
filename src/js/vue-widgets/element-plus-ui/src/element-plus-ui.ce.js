import { defineCustomElement, h, watch, reactive } from "vue";
import App from "./App.vue";
import styles from "./custom.scss?inline";

customElements.define(
    "element-plus-ui",
    defineCustomElement(
        (props, ctx) => {
            const internalState = reactive({ ...props });

            const emitValueChange = (detail) => {
                ctx.emit("change", detail);
            };

            // Allow to update the comoponent state by changing HTML element attributes
            watch(
                () => props,
                (newProps) => {
                    Object.keys(newProps).forEach((key) => {
                        internalState[key] = newProps[key];
                    });
                },
                { immediate: true, deep: true }
            );
            return () => h(App, { ...internalState, emitValueChange }, ctx.slots);
        },
        {
            styles: [styles],
        }
    )
);

/*
    Sync the Transfer hidden select with the form
*/
new MutationObserver((mutations) => {
    if (mutations.some((m) => m.target.querySelector("element-plus-ui"))) {
        const elements = document.querySelectorAll("element-plus-ui");
        elements.forEach((el) => {
            const fieldName = el.getAttribute("field-name");
            let select = el.closest("form").querySelector(`select[name="${fieldName}"]`);
            if (!select) {
                select = document.createElement("select");
                select.name = fieldName;
                select.multiple = true;
                select.setAttribute("aria-hidden", "true");
                const defaultValues = el.getAttribute("default-value");
                if (defaultValues) {
                    const values = JSON.parse(defaultValues);
                    values.forEach((v) => {
                        const option = document.createElement("option");
                        option.value = v;
                        option.selected = true;
                        select.appendChild(option);
                    });
                }
                // Enclose it in a hidden div so its related error messages are hidden too
                const div = document.createElement("div");
                div.style.display = "none";
                div.appendChild(select);
                el.parentNode.insertAdjacentElement("afterend", div);
            }
            // TODO: Take this out of the mutation observer so the event handler is not attached multiple times,
            // which cause the select to also trigger the cahnge event serveral times
            el.addEventListener("change", function (event) {
                const value = event.detail[0].value;
                select.innerHTML = "";
                value.forEach((v) => {
                    const option = document.createElement("option");
                    option.value = v;
                    option.selected = true;
                    select.appendChild(option);
                });
                $(select).trigger("change");
            });
        });
    }
}).observe(document.body, { childList: true, subtree: true });
