/**
 * (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
 *
 * All Rights Reserved. See copyright.txt for details and a complete list of authors.
 * Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
 *
 * Plugin zones: show which part of the page each wiki plugin produced, and offer to edit it.
 *
 * The parser wraps the output of every editable plugin in a pair of html comments carrying the
 * id of its edit icon.
 */

const ID = "plugin-edit-[a-z0-9_]+\\d+"; // only what the parser emits: nothing else is trusted
const START = new RegExp("^tiki-plugin:start (" + ID + ")$", "i");
const END = new RegExp("^tiki-plugin:end (" + ID + ")$", "i");

const HIDE_DELAY = 120; // ms the highlight stays after leaving, so the button can be reached
const NESTED_DELAY = 150; // ms of rest inside a plugin within a plugin before it takes over
const REFRESH_DELAY = 200; // ms after the page changes before the zones are collected again
const OFFSET = 3; // px, the outline-offset of .tiki-plugin-zone-outline
const MIN_SIZE = 80; // px, under this the corners are too close together to be worth choosing
const HYSTERESIS = 0.3; // of half the zone, how far past the middle the corner changes
const SLACK = 4; // px of tolerance when checking the pointer really is over a character

const translate = (text) => (typeof window.tr === "function" ? window.tr(text) : text);

function perFrame(callback) {
    let frame = 0;
    return (...args) => {
        if (frame) {
            return;
        }
        frame = requestAnimationFrame(() => {
            frame = 0;
            callback(...args);
        });
    };
}

/**
 * @param {Object} options
 * @param {string} options.mode "zone" leaves the edit icons out of sight and relies on the
 *                              highlight, "both" keeps them. See wiki_edit_plugin_mode.
 * @param {Element} options.container the page content, for tests
 */
export default function initPluginZones(options = {}) {
    const root = options.container || document.querySelector("#page-data > .content") || document.querySelector("#page-data");
    if (!root || root.tikiPluginZones) {
        return null;
    }

    if (options.mode !== "both") {
        document.body.classList.add("tiki-plugin-zone-mode");
    }

    const rtl = getComputedStyle(root).direction === "rtl";
    let zones = [];
    let containing = new WeakMap(); // element -> the plugins around it, innermost first
    let shown = null; // the plugin being highlighted
    let corner = null; // which corner of it the button is on
    let rested = null; // the nested plugin the pointer has rested on
    let hideTimer = null;
    let restTimer = null;

    // --- finding the plugins -------------------------------------------------------------

    /** Every plugin on the page, innermost first, each linked to the plugin it sits in. */
    function collect() {
        const found = [];
        const open = [];
        const walker = document.createTreeWalker(root, NodeFilter.SHOW_COMMENT);

        while (walker.nextNode()) {
            const comment = walker.currentNode;
            const text = comment.data.trim();
            const start = START.exec(text);
            const end = END.exec(text);

            if (start) {
                open.push({ id: start[1], start: comment, parentId: open.length ? open[open.length - 1].id : null });
                continue;
            }
            if (!end || !open.length || open[open.length - 1].id !== end[1]) {
                continue;
            }

            const zone = open.pop();
            const icon = document.getElementById(zone.id);
            zone.depth = open.length;
            zone.icon = icon && icon.classList.contains("editplugin") ? icon : null;
            zone.data = (window.tikiPluginZoneData || {})[zone.id] || null;
            if (!zone.icon && !zone.data) {
                continue; // no edit icon of ours and no parameters: stale or forged markers
            }
            zone.name = (zone.icon && zone.icon.dataset.plugin) || zone.data.type;
            zone.end = comment;
            zone.range = document.createRange();
            zone.range.setStartAfter(zone.start);
            zone.range.setEndBefore(comment);
            found.push(zone);
        }

        found.sort((a, b) => b.depth - a.depth);
        found.forEach((zone) => {
            zone.parent = found.find((other) => other.id === zone.parentId) || null;
        });
        return found;
    }

    /** Box around everything the plugin produced, or null when it shows nothing. */
    function boxOf(zone) {
        // Measured on every use: plugins such as PluginPivotTable draw themselves after load.
        let left = Infinity;
        let top = Infinity;
        let right = -Infinity;
        let bottom = -Infinity;

        for (const rect of zone.range.getClientRects()) {
            if (rect.width < 1 || rect.height < 1) {
                continue;
            }
            left = Math.min(left, rect.left);
            top = Math.min(top, rect.top);
            right = Math.max(right, rect.right);
            bottom = Math.max(bottom, rect.bottom);
        }

        return left === Infinity ? null : { left, top, width: right - left, height: bottom - top };
    }

    function holds(zone, node) {
        const parent = node.parentNode;
        if (!parent) {
            return false;
        }
        const index = Array.prototype.indexOf.call(parent.childNodes, node);
        return zone.range.comparePoint(parent, index) === 0 && zone.range.comparePoint(parent, index + 1) === 0;
    }

    /** The plugins under a point, innermost first. */
    function pluginsAt(element, x, y) {
        if (containing.has(element)) {
            return containing.get(element);
        }

        const chain = [];
        for (let node = element; node && node !== root; node = node.parentNode) {
            zones.forEach((zone) => {
                if (!chain.includes(zone) && holds(zone, node)) {
                    chain.push(zone);
                }
            });
        }

        if (chain.length) {
            containing.set(element, chain); // which element is in which plugin never changes
            return chain;
        }
        return charactersAt(element, x, y);
    }

    /**
     * Plugins whose output is only text, such as PluginNow, sit inside a paragraph that belongs to no plugin.
     */
    function charactersAt(element, x, y) {
        const caret = document.caretPositionFromPoint
            ? document.caretPositionFromPoint(x, y)
            : document.caretRangeFromPoint && document.caretRangeFromPoint(x, y);
        const node = caret && (caret.offsetNode || caret.startContainer);
        if (!node || !element.contains(node)) {
            return [];
        }

        const offset = caret.offset !== undefined ? caret.offset : caret.startOffset;
        const probe = document.createRange();
        probe.setStart(node, offset);
        probe.setEnd(node, Math.min(offset + 1, (node.textContent || "").length));
        const box = probe.getBoundingClientRect();
        const over = (box.width || box.height) && x >= box.left - SLACK && x <= box.right + SLACK && y >= box.top - SLACK && y <= box.bottom + SLACK;

        return over ? zones.filter((zone) => zone.range.isPointInRange(node, offset)) : [];
    }

    // --- the highlight -------------------------------------------------------------------

    const outline = document.createElement("div");
    outline.className = "tiki-plugin-zone-outline";
    outline.setAttribute("aria-hidden", "true");

    const label = document.createElement("span");
    label.className = "tiki-plugin-zone-label";

    const button = document.createElement("button");
    button.type = "button";
    button.className = "btn btn-xs btn-primary tiki-plugin-zone-edit";

    outline.append(label, button);
    document.body.appendChild(outline);

    function show(zone, pointer) {
        clearTimeout(hideTimer);
        if (zone !== shown) {
            corner = null;
        }
        shown = zone;

        const visible = boxOf(zone);
        // A plugin can show nothing at all, PluginJq for one: point at its icon instead.
        const icon = zone.icon ? zone.icon.getBoundingClientRect() : { left: 0, top: 0 };
        const box = visible || { left: icon.left, top: icon.top, width: 24, height: 24 };

        outline.classList.add("shown");
        outline.classList.toggle("empty", !visible);
        outline.style.left = box.left + window.scrollX + "px";
        outline.style.top = box.top + window.scrollY + "px";
        outline.style.width = box.width + "px";
        outline.style.height = box.height + "px";

        label.textContent = visible ? zone.name : zone.name + " · " + translate("no visible output");
        label.hidden = !!pointer; // the button names the plugin already

        button.classList.toggle("shown", !!pointer);
        if (!pointer) {
            return; // hovering the icon: the highlight alone answers "which content is this"
        }

        const name = zone.name.toUpperCase();
        button.textContent = zone.parent
            ? translate("Edit") + " " + name + " " + translate("in") + " " + zone.parent.name.toUpperCase()
            : translate("Edit") + " " + name;
        button.setAttribute("aria-label", translate("Edit plugin") + " " + name);

        corner = corner || { right: rtl, bottom: false };
        corner.right = side(pointer.x, box.left, box.width, corner.right);
        corner.bottom = side(pointer.y, box.top, box.height, corner.bottom);
        button.classList.toggle("is-right", corner.right);
        button.classList.toggle("is-bottom", corner.bottom);
        keepVisible();
    }

    /**
     * Which half of the plugin the pointer is in, along one axis. It only changes once the
     * pointer is clearly past the middle, so a drifting hand does not make the button flicker,
     * and it stays put on plugins too small for the two corners to differ.
     */
    function side(at, start, size, was) {
        if (size <= MIN_SIZE) {
            return was;
        }
        const middle = start + size / 2;
        const margin = Math.min((size / 2) * HYSTERESIS, 120);
        if (at > middle + margin) {
            return true;
        }
        return at < middle - margin ? false : was;
    }

    /**
     * A plugin can be taller than the window, and a page can load in the middle of one, which
     * would leave the button out of sight. Keep it against the visible part of the plugin.
     */
    function keepVisible() {
        if (!shown || !button.classList.contains("shown")) {
            return;
        }

        button.style.top = "";
        button.style.bottom = "";

        const box = outline.getBoundingClientRect();
        const rect = button.getBoundingClientRect();
        const room = rect.height + OFFSET;
        const above = rect.top < 0 && box.bottom > room;
        const below = rect.bottom > window.innerHeight && box.top < window.innerHeight - room;
        if (!above && !below) {
            return;
        }

        button.style.top = Math.max(0, above ? -box.top : window.innerHeight - box.top - rect.height) + "px";
        button.style.bottom = "auto";
    }

    function hide(delay) {
        clearTimeout(hideTimer);
        clearTimeout(restTimer);
        rested = null;
        hideTimer = setTimeout(() => {
            outline.classList.remove("shown");
            button.classList.remove("shown");
            shown = null;
        }, delay || 0);
    }

    /**
     * Which plugin of a nest the pointer means: the outer one, since that is what a reader
     * points at, and a plugin filling its parent would otherwise make the parent unreachable.
     * Resting on the inner one hands it over, so both stay within reach.
     */
    function meant(chain, pointer) {
        const inner = chain[0];
        const outer = chain[chain.length - 1];
        if (inner === outer || rested === inner) {
            clearTimeout(restTimer);
            return rested === inner ? inner : outer;
        }

        clearTimeout(restTimer);
        restTimer = setTimeout(() => {
            rested = inner;
            show(inner, pointer);
        }, NESTED_DELAY);

        return chain.includes(shown) ? shown : outer;
    }

    // --- what the reader does ------------------------------------------------------------

    const onMove = perFrame((event) => {
        if (event.target.closest && event.target.closest("a.editplugin, .tiki-plugin-zone-stop")) {
            return; // those carry their own handlers
        }

        const pointer = { x: event.clientX, y: event.clientY };
        const chain = pluginsAt(event.target, pointer.x, pointer.y);
        if (chain.length) {
            show(meant(chain, pointer), pointer);
        } else if (shown) {
            hide(HIDE_DELAY);
        }
    });

    root.addEventListener("pointermove", (event) => {
        if (event.pointerType !== "touch") {
            onMove(event); // nothing to hover on a touch screen, the icons stay the way in
        }
    });
    root.addEventListener("pointerleave", () => hide(HIDE_DELAY));

    const onScroll = perFrame(keepVisible);
    window.addEventListener("scroll", onScroll, { passive: true });
    window.addEventListener("resize", onScroll, { passive: true });

    button.addEventListener("pointerenter", () => clearTimeout(hideTimer));
    button.addEventListener("pointerleave", () => hide(HIDE_DELAY));
    button.addEventListener("click", () => openEditor(shown, button));
    document.addEventListener("keydown", (event) => event.key === "Escape" && hide());

    function openEditor(zone, trigger) {
        if (!zone) {
            return;
        }
        if (zone.icon) {
            zone.icon.click(); // the icon carries the handler the parser gave it
        } else if (typeof window.popupPluginForm === "function") {
            const plugin = zone.data;
            window.popupPluginForm("editwiki", plugin.type, plugin.index, plugin.page, plugin.args, plugin.isMarkdown, plugin.body, trigger);
        }
    }

    /**
     * What a keyboard reaches: the edit icon where there is one, otherwise a button of ours
     * where the icon would have been, out of sight until tabbed to.
     */
    function anchorFor(zone) {
        if (zone.icon) {
            return zone.icon;
        }
        const next = zone.end.nextElementSibling;
        if (next && next.classList.contains("tiki-plugin-zone-stop")) {
            return next;
        }

        const stop = document.createElement("button");
        stop.type = "button";
        stop.className = "tiki-plugin-zone-stop";
        stop.textContent = translate("Edit plugin") + " " + zone.name.toUpperCase();
        stop.addEventListener("click", () => openEditor(byId(stop.dataset.zone), stop));
        stop.dataset.zone = zone.id;
        zone.end.parentNode.insertBefore(stop, zone.end.nextSibling);
        return stop;
    }

    const byId = (id) => zones.find((zone) => zone.id === id) || null;

    function bindAnchors() {
        for (const zone of zones) {
            const anchor = anchorFor(zone);
            if (anchor.tikiPluginZone) {
                continue; // already bound, on an earlier pass
            }
            anchor.tikiPluginZone = zone.id;
            anchor.addEventListener("mouseenter", () => show(byId(anchor.tikiPluginZone)));
            anchor.addEventListener("focus", () => show(byId(anchor.tikiPluginZone)));
            anchor.addEventListener("mouseleave", () => hide());
            anchor.addEventListener("blur", () => hide());
        }
    }

    function refresh() {
        zones = collect();
        containing = new WeakMap();
        bindAnchors();
    }

    refresh();

    let refreshTimer = null;
    const ours = (node) => node.nodeType === 1 && node.classList.contains("tiki-plugin-zone-stop");
    new MutationObserver((records) => {
        if (records.every((record) => [...record.addedNodes].every(ours) && [...record.removedNodes].every(ours))) {
            return;
        }
        clearTimeout(refreshTimer);
        refreshTimer = setTimeout(refresh, REFRESH_DELAY);
    }).observe(root, { childList: true, subtree: true });

    root.tikiPluginZones = { refresh, zones: () => zones };
    return root.tikiPluginZones;
}
