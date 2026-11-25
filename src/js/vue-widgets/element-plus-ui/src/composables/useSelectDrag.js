import { computed, onMounted, onUnmounted, ref } from "vue";
import Sortable from "sortablejs";
import { sortOptions } from "../helpers/select/sortable";
import {
    draggedItem,
    startDrag,
    endDrag,
    setRefusal,
    refusalFor,
    isRefusing,
    isCopyRequested,
    wasReleasedOutside,
} from "../helpers/select/dragBetweenFields";
import { usePropParsers } from "./usePropParsers";

/**
 * Carries the selected values of a multiple select around: reordered inside their own field when
 * the field is ordered, and moved or copied to another field sharing its drag group.
 * @param {Object} context
 * @param {Object} context.props the props of the select component
 * @param {import('vue').Ref<Boolean>} context.multiple whether the field holds several values
 * @param {import('vue').Ref<HTMLElement>} context.wrapperRef the element wrapping the select
 * @param {import('vue').Ref<Object>} context.selectRef the el-select component
 * @param {import('vue').Ref<Array>} context.modelValue the values of the field
 * @param {Function} context.onValueChange called with the values once the drag changed them
 * @returns {{refusalMessage: import('vue').ComputedRef<String>, carryingAValue: import('vue').Ref<Boolean>, valuesCanBeCarried: import('vue').Ref<Boolean>}}
 */
export function useSelectDrag({ props, multiple, wrapperRef, selectRef, modelValue, onValueChange }) {
    const { parseValue } = usePropParsers();
    const translateFn = window.tr ?? ((text) => text);

    let draggedNodeHome;
    let sortable = null;
    const carryingAValue = computed(() => draggedItem.value !== null);
    /*
        Several preferences and field options have to agree before anything can be carried, which is
        settled below. Told to the component, so that the cursors are drawn on the fields it applies
        to and nowhere else.
    */
    const valuesCanBeCarried = ref(false);
    const refusalReason = computed(() => refusalFor(sortable));
    const refusalMessage = computed(() => {
        if (refusalReason.value === "duplicate") {
            return translateFn("This user is already selected in this field.");
        }
        if (refusalReason.value === "not-offered") {
            return translateFn("This user does not belong to any group allowed for this field.");
        }
        return "";
    });
    /*
        The options of a field filtered by group hold whom it shows, not whom it may hold, and the
        filter is a way of searching: refusing on it would turn it into a rule.
    */
    const acceptableValues = computed(() => {
        const allowed = parseValue(props.allowedValues);
        if (Array.isArray(allowed)) {
            return new Set(allowed);
        }
        return new Set((parseValue(props.options) || []).map((option) => option.value));
    });

    const stopRefusing = () => setRefusal(sortable, "");

    const acceptsDrop = (to, from) => {
        if (to === from) {
            setRefusal(to, "");
            return true;
        }
        const item = draggedItem.value;
        if (!item) {
            return false;
        }
        if (modelValue.value.includes(item.value)) {
            setRefusal(to, "duplicate");
            return false;
        }
        if (!acceptableValues.value.has(item.value)) {
            setRefusal(to, "not-offered");
            return false;
        }
        setRefusal(to, "");
        return true;
    };

    /*
        SortableJS parks the value in any field crossed on the way that accepts it. What is under the
        pointer at the release speaks for the drop as a whole, so that a field merely crossed keeps
        nothing: neither a value the field the user was aiming for turned down, nor one let go of
        away from every field.
    */
    const handleDropFromOtherField = (event) => {
        const item = draggedItem.value;
        if (!item || isRefusing() || wasReleasedOutside(event) || modelValue.value.includes(item.value)) {
            return;
        }
        modelValue.value = [...modelValue.value, item.value];
        onValueChange(modelValue.value);
    };

    const handleTakenByOtherField = (event) => {
        const item = draggedItem.value;
        if (!item || isRefusing() || wasReleasedOutside(event) || isCopyRequested(event?.originalEvent)) {
            return;
        }
        modelValue.value = modelValue.value.filter((value) => value !== item.value);
        onValueChange(modelValue.value);
    };

    /**
     * Puts the dragged tag back in the field it was taken from, once the drop is over.
     *
     * Dropping a tag on another field moves it there, and the fields do not redraw their tags: one
     * would go on showing a user it no longer holds, while the other shows one it does not. Nothing
     * is logged in the console, and it does not correct itself.
     * @param {Object} event the event SortableJS dispatched for the drop
     * @returns {void}
     */
    const putTagBack = (event) => {
        if (event.from !== event.to && draggedNodeHome !== undefined) {
            event.from.insertBefore(event.item, draggedNodeHome);
        }
        draggedNodeHome = undefined;
    };

    onMounted(() => {
        try {
            const orderingConfig = props.ordering ? JSON.parse(props.ordering) : null;
            const dragGroup = props.dragGroup || "";
            if (!(orderingConfig || dragGroup) || !multiple.value || !wrapperRef.value) {
                return;
            }
            const selectionElement = wrapperRef.value.querySelector(".el-select__selection");
            if (!selectionElement) {
                return;
            }
            sortable = new Sortable(selectionElement, {
                animation: 150,
                sort: Boolean(orderingConfig),
                /*
                    The wrapper holding the text input is no value of the field: it cannot be carried
                    off, and it is not the last of them either, which is where a field ends for the
                    drop landing past its values.
                */
                draggable: ".el-select__selected-item:not(.el-select__input-wrapper)",
                group: dragGroup ? { name: dragGroup, put: acceptsDrop } : undefined,
                /*
                    Carries the values around itself instead of leaving it to the drag and drop of the
                    browser, whose events say nothing of the modifier keys on Linux: a copy could never
                    be told apart from a move. Plain mouse events answer for every system.
                */
                forceFallback: true,
                onStart: (event) => {
                    draggedNodeHome = event.item.nextSibling;
                    // Taking hold of a value opens the list of choices, which covers the field below
                    selectRef.value?.blur();
                    startDrag({ value: modelValue.value[event.oldIndex] });
                },
                onSort: (event) => {
                    if (event.from !== event.to) {
                        return;
                    }
                    sortOptions(wrapperRef.value, JSON.parse(props.options));
                },
                onAdd: handleDropFromOtherField,
                onRemove: handleTakenByOtherField,
                onEnd: (event) => {
                    putTagBack(event);
                    endDrag();
                },
            });
            valuesCanBeCarried.value = true;
            /*
                A field is asked whether it takes the value while the pointer is on it, never told the
                pointer went elsewhere, so its refusal would stay on the screen. Ordinary mouse events
                still reach it, what follows the pointer being closed to them.
            */
            wrapperRef.value.addEventListener("mouseleave", stopRefusing);
        } catch (error) {
            console.warn("Invalid ordering configuration:", error); // eslint-disable-line no-console
        }
    });

    onUnmounted(() => {
        wrapperRef.value?.removeEventListener("mouseleave", stopRefusing);
    });

    return { refusalMessage, carryingAValue, valuesCanBeCarried };
}
