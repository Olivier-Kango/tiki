import { defineCustomElement, h, onMounted, watch, reactive, ref } from "vue";
import App from "./App.vue";
import styles from "./custom.scss?inline";

customElements.define(
    "datetime-picker",
    defineCustomElement(
        (props, ctx) => {
            const internalProps = reactive({ ...props });

            const createInput = (name, value) => {
                const input = document.createElement("input");
                input.type = "hidden";
                input.name = name;
                input.value = value;
                return input;
            };
            const dateTimeInputRef = ref(createInput(ctx.attrs.inputName, ctx.attrs.timestamp));
            const timezoneInputRef = ref(createInput(ctx.attrs.timezoneFieldName, ctx.attrs.timezone));
            const toDateTimeInputRef = ref(createInput(ctx.attrs.toInputName, ctx.attrs.toTimestamp));

            const emitValueChange = (detail) => {
                ctx.emit("change", detail);
            };

            const createInputDataHolders = () => {
                const shadowRoot = document.querySelector(`datetime-picker[input-name="${ctx.attrs.inputName}"]`);
                shadowRoot.appendChild(dateTimeInputRef.value);

                if (ctx.attrs.toInputName) {
                    shadowRoot.appendChild(toDateTimeInputRef.value);
                }

                if (ctx.attrs.enableTimezonePicker) {
                    shadowRoot.appendChild(timezoneInputRef.value);
                }
            };

            onMounted(() => {
                createInputDataHolders();
            });

            watch(
                () => props,
                (newProps) => {
                    Object.keys(newProps).forEach((key) => {
                        internalProps[key] = newProps[key];
                    });
                },
                { immediate: true, deep: true }
            );

            return () =>
                h(
                    App,
                    {
                        ...internalProps,
                        emitValueChange,
                        dateTimeInput: dateTimeInputRef.value,
                        timezoneInput: timezoneInputRef.value,
                        toDateTimeInput: toDateTimeInputRef.value,
                        rangePicker: Boolean(ctx.attrs.toInputName),
                    },
                    ctx.slots
                );
        },
        {
            styles: [styles],
        }
    )
);
