import { shallowRef } from "vue";

/*
    The tags rendered by el-select carry their label, never the value behind it, and two selects
    can label the same value differently. What is being dragged is kept here instead.

    Each field is a custom element of its own, so they share no reactive scope: this state is read
    straight from the module, which every field watches by reading it.
*/
export const draggedItem = shallowRef(null);

/*
    Shallow, because what is held here is compared by identity: a deep ref would hand out proxies
    of the field instead of the field itself.
*/
export const refusal = shallowRef(null);

/**
 * Remember what is being dragged, so the field it is dropped on can identify it
 * @param {Object} item item being dragged
 * @param {String} item.value value of the dragged item
 * @returns {void}
 */
export function startDrag(item) {
    draggedItem.value = item && item.value !== undefined ? { value: item.value } : null;
    refusal.value = null;
    // Between two fields the pointer crosses the page, which would go back to an arrow on the way
    document.body.classList.add("carrying-a-value");
}

/**
 * Forget the dragged item, whether it was dropped or not
 * @returns {void}
 */
export function endDrag() {
    draggedItem.value = null;
    refusal.value = null;
    document.body.classList.remove("carrying-a-value");
}

/**
 * Say why a field turns a drop down, or that it no longer does. Only the last word counts,
 * whoever had it: a field taking the value in speaks for every other.
 * @param {Object} field the field having its say
 * @param {String} reason empty when the value is welcome
 * @returns {void}
 */
export function setRefusal(field, reason) {
    refusal.value = reason ? { field, reason } : null;
}

export function refusalFor(field) {
    return refusal.value && refusal.value.field === field ? refusal.value.reason : "";
}

export function isRefusing() {
    return refusal.value !== null;
}

/**
 * Whether the user asked for a copy rather than a move, holding the modifier their system copies
 * with while dragging: Ctrl on Windows and Linux, Option on macOS
 * @param {Event} originalEvent the DOM event SortableJS is reacting to
 * @returns {Boolean}
 */
export function isCopyRequested(originalEvent) {
    return Boolean(originalEvent && (originalEvent.ctrlKey || originalEvent.altKey));
}

/*
    The fields are drawn inside shadow roots, which document.elementFromPoint stops at: it answers
    with the element holding the root. Its own elementFromPoint carries the search on.
*/
function deepestElementAt(x, y) {
    let element = document.elementFromPoint(x, y);
    while (element && element.shadowRoot) {
        const inner = element.shadowRoot.elementFromPoint(x, y);
        if (!inner || inner === element) {
            break;
        }
        element = inner;
    }
    return element;
}

/**
 * Whether the value was let go away from the field about to take it, SortableJS leaving it in the
 * last field crossed. Its own revertOnSpill answers this blind to shadow roots, so always yes.
 * @param {Object} event the event SortableJS dispatched for the drop
 * @returns {Boolean}
 */
export function wasReleasedOutside(event) {
    const originalEvent = event && event.originalEvent;
    if (!event || !event.to || !originalEvent || typeof originalEvent.clientX !== "number") {
        return false;
    }
    return !event.to.contains(deepestElementAt(originalEvent.clientX, originalEvent.clientY));
}
