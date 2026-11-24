/* Enhance visual article cards while keeping their native links and controls. */
(() => {
    'use strict';
    if (document.documentElement.dataset.articleCardsReady) return;
    document.documentElement.dataset.articleCardsReady = '1';

    const layouts = '.articles-layout:is(.articles-layout-tiles, .articles-layout-overlay, .articles-layout-magazine)';
    const interactive = 'a, button, input, select, textarea, label, form, audio, video, iframe, object, embed, details, summary, [role="button"], [role="link"], [contenteditable]:not([contenteditable="false"]), [data-bs-toggle]';
    document.addEventListener('click', event => {
        if (event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        if (!(event.target instanceof Element)) return;
        const card = event.target.closest('article.article');
        if (!card?.matches(`${layouts} > article.article`)) return;
        if (event.target.closest(interactive) || window.getSelection()?.toString()) return;
        const link = card.querySelector(':scope > .articletitle h2 a[href]');
        if (link) link.click();
    });
})();
