import FontPicker from "@tiki-jsfontpicker";

/**
 * Initializes the FontPicker for a set of elements.
 *
 * @param {string} selector - CSS selector for the elements to bind.
 * @param {Object} options - Configuration options.
 * @param {boolean} options.customizer - Whether this is being used in the Theme Customizer (enables live preview).
 * @param {boolean} options.systemFontsOnly - Hide web fonts (for contexts that do not load them).
 */
export default function initFontPicker(selector, options = {}) {
    document.querySelectorAll(selector).forEach(function (el) {
        try {
            let initialFont = el.value || "";
            // Parse common Tiki font stacks (e.g., "system-ui, -apple-system...")
            if (initialFont) {
                if (initialFont.includes(",")) {
                    initialFont = initialFont.split(",")[0];
                }
                initialFont = initialFont.trim().replace(/['"]/g, "");
            }

            const config = {
                variants: false,
                verbose: false
            };
            if (options.systemFontsOnly) {
                config.googleFonts = [];
            }
            // Render inside the modal, otherwise the picker is hidden behind it and blocked by its focus trap
            const parentModal = el.closest(".modal");
            if (parentModal) {
                config.container = parentModal;
            }

            // Ensure the initial font is available in the picker's database
            if (initialFont) {
                config.extraFonts = [{
                    name: initialFont,
                    category: "sans-serif",
                    variants: ["400", "700"],
                    subsets: ["latin"]
                }];
            }

            // The picker writes "Family:variant" to its input: use a proxy so the submitted value stays a plain family name
            let pickerInput = el;
            if (!options.customizer) {
                pickerInput = document.createElement("input");
                pickerInput.type = "text";
                el.type = "hidden";
                el.after(pickerInput);
            }
            pickerInput.value = initialFont;

            const picker = new FontPicker(pickerInput, config);

            // Handle Font Selection
            picker.on("pick", function (font) {
                const $el = $(el);
                // 1. Live Preview (Theme Customizer mode)
                if (options.customizer) {
                    const targetSelector = $el.attr("data-selector");
                    const element = $el.attr("data-element");

                    let cssFontValue = font.family.name.includes(" ") ? "'" + font.family.name + "'" : font.family.name;

                    if (targetSelector && element) {
                        $(targetSelector).css(element, cssFontValue);
                    }
                    if (typeof updateInputValue === "function") {
                        updateInputValue($el);
                        // Fix for theme-customizer.js checkInputValues overwriting with browser computed style that uses double quotes
                        $el.val(cssFontValue);
                        if (typeof inputJSON !== "undefined") {
                            for (let k in inputJSON) {
                                if (inputJSON[k].selector === targetSelector) {
                                    inputJSON[k].attributes[element] = cssFontValue;
                                }
                            }
                        }
                    }
                } else {
                    el.value = font ? font.family.name : "";
                }

                // 2. Standard Change Event (Wiki Plugin mode)
                el.dispatchEvent(new Event("change", { bubbles: true }));
            });
        } catch (e) {
            console.error("Tiki: Failed to initialize font picker on", el, e); // eslint-disable-line no-console
        }
    });
}
