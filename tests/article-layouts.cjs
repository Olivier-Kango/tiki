// Local layout and interaction fixtures. No live site or external media is contacted.
const { chromium } = require(process.env.PLAYWRIGHT_PATH || 'playwright');
const { resolve } = require('node:path');
const assert = require('node:assert/strict');
const sass = require('sass');
const readingCss = sass.compile(resolve(__dirname, '../themes/base_files/scss/_article-layout.scss')).css;
const css = sass.compile(resolve(__dirname, '../themes/base_files/scss/_articles-tiles.scss')).css;
const image = 'data:image/svg+xml,' + encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" width="800" height="600"><rect width="800" height="600" fill="#427a92"/></svg>');
function card(id, withImage = true, floating = false) {
    return `<article class="article wikiplugin_articles"><header class="articletitle"><h2><a href="https://fixture.test/article/${id}?autoplay=1">Article ${id}</a></h2><span class="titleb">Author · Date</span></header>
        <div class="articleheading ${floating ? '' : 'd-md-flex'}"><div class="article-list-image ${floating ? 'float-start' : 'flex-shrink-0'} me-3">${withImage ? `<a href="https://fixture.test/article/${id}?autoplay=1"><img class="article-image" src="${image}" alt="Article illustration"></a>` : ''}</div>
        <div class="articleheadingtext ${floating ? '' : 'flex-grow-1'}"><p>An article introduction with enough text to check the responsive arrangement.</p></div></div>
        <div class="articletrailer"><a class="more" href="https://fixture.test/article/${id}?autoplay=1">Read more</a><button type="button">Actions</button></div></article>`;
}
(async () => {
    const browser = await chromium.launch({ headless: true, args: ['--no-sandbox'],
        ...(process.env.CHROMIUM_PATH ? { executablePath: process.env.CHROMIUM_PATH } : {}) });
    try {
        for (const width of [320, 390, 768, 1440]) {
            for (const layout of ['classic', 'tiles', 'list', 'overlay', 'magazine', 'compact']) {
                const page = await browser.newPage({ viewport: { width, height: 1000 } });
                await page.route('**/*', route => route.abort());
                const classes = `articles-layout articles-layout-${layout}`;
                await page.setContent(`<style>*{box-sizing:border-box}body{margin:16px;font-family:sans-serif;--bs-border-color:#ccc;--bs-border-radius:6px;--bs-border-radius-lg:10px;--bs-tertiary-bg:#f5f5f5}img{max-width:100%}.article{margin-bottom:24px}.titleb{display:block}.articleheadingtext{min-width:0}.flex-grow-1{flex-grow:1!important}${css}</style>
                    <div class="${classes}">${card(1)}${card(2, true, true)}${card(3, false)}<nav class="pagination">Pagination</nav></div>
                    <style>.articles-pagination .pagination{display:flex;list-style:none;padding:0}.page-item:first-child .page-link{border-top-left-radius:.375rem;border-bottom-left-radius:.375rem}.page-item:last-child .page-link{border-top-right-radius:.375rem;border-bottom-right-radius:.375rem}</style>
                    <nav class="articles-pagination"><ul class="pagination"><li class="page-item disabled"><span class="page-link">«</span></li><li class="page-item active"><span class="page-link">1</span></li><li><a class="page-link" href="?offset=3">2</a></li><li class="page-item"><a class="page-link" href="?offset=3">»</a></li></ul></nav>`);
                await page.addScriptTag({ path: resolve(__dirname, '../lib/jquery_tiki/article-cards.js') });
                await page.evaluate(() => {
                    window.linkClicks = 0;
                    document.querySelectorAll('a').forEach(link => link.addEventListener('click', event => { event.preventDefault(); window.linkClicks++; }));
                });
                const metrics = await page.evaluate(() => {
                    const article = document.querySelector('article');
                    const box = element => { const b = element.getBoundingClientRect(); return { x: b.x, y: b.y, width: b.width, height: b.height }; };
                    return { first: box(article), second: box(document.querySelectorAll('article')[1]),
                        image: box(article.querySelector('img')), title: box(article.querySelector('h2')), intro: box(article.querySelector('.articleheadingtext')),
                        color: getComputedStyle(article).color, scroll: document.documentElement.scrollWidth };
                });
                assert(metrics.scroll <= width, `${layout}: horizontal overflow`);
                const pagerShapes = await page.locator('.articles-pagination .page-link').evaluateAll(links => links.map(link => {
                    const style = getComputedStyle(link), box = link.getBoundingClientRect();
                    return [box.width, box.height, style.borderTopLeftRadius, style.borderTopRightRadius,
                        style.borderBottomLeftRadius, style.borderBottomRightRadius];
                }));
                assert(pagerShapes.every(shape => JSON.stringify(shape) === JSON.stringify(pagerShapes[0])), 'Pagination arrows and numbers must share one shape despite Bootstrap corner rules');

                const typeSizes = await page.locator('article').first().evaluate(article => ({
                    title: parseFloat(getComputedStyle(article.querySelector('h2')).fontSize),
                    meta: parseFloat(getComputedStyle(article.querySelector('.titleb')).fontSize),
                }));
                assert(typeSizes.meta < typeSizes.title, 'Publication details must remain smaller than the headline');
                if (layout === 'list' || layout === 'compact') {
                    const surface = await page.locator('article').first().evaluate(el => {
                        const style = getComputedStyle(el);
                        return {border: style.borderTopColor, radius: parseFloat(style.borderTopLeftRadius), shadow: style.boxShadow};
                    });
                    assert.equal(surface.border, 'rgba(0, 0, 0, 0)', 'List cards must not have a hard outline');
                    assert(surface.radius >= 16, 'List cards should share the rounded visual language');
                    assert.notEqual(surface.shadow, 'none');
                }
                if (layout === 'overlay' || layout === 'magazine') {
                    const background = await page.locator('.article-list-image').first().evaluate(el => ({
                        radius: getComputedStyle(el).borderTopLeftRadius,
                        overflow: getComputedStyle(el).overflow,
                    }));
                    assert(parseFloat(background.radius) >= 16, 'Background photos must use the card radius, not inherit a square display-contents parent');
                    assert.equal(background.overflow, 'hidden');
                    assert.equal(await page.locator('.articleheadingtext').first().evaluate(el => getComputedStyle(el).flexGrow), '0', 'Background captions must not stretch away from their footer');
                }

                if (['tiles', 'overlay', 'magazine'].includes(layout)) {
                    const divider = await page.locator('article').first().locator('.articletrailer').evaluate(el => {
                        const style = getComputedStyle(el, '::before');
                        return {opacity: Number(style.opacity), height: style.height, pointerEvents: style.pointerEvents};
                    });
                    assert(divider.opacity > 0 && divider.opacity <= .1, 'Footer divider must remain nearly transparent');
                    assert.equal(divider.height, '1px');
                    assert.equal(divider.pointerEvents, 'none', 'Decorative divider must not intercept clicks');
                    assert.equal(await page.locator('.articleheadingtext').first().evaluate(el => getComputedStyle(el).webkitLineClamp), '6');
                    if (width === 390) {
                        await page.locator('.articleheadingtext').first().evaluate(el => {
                            const button = document.createElement('button');
                            button.textContent = 'Embedded control';
                            el.append(button);
                        });
                        assert.notEqual(await page.locator('.articleheadingtext').first().evaluate(el => getComputedStyle(el).webkitLineClamp), '6', 'Interactive introductions must not be clipped');
                        await page.locator('.articleheadingtext button').first().evaluate(el => el.remove());
                    }
                }

                assert.equal(await page.locator('h2 a').first().getAttribute('href'), 'https://fixture.test/article/1?autoplay=1');
                if (width < 768) assert(metrics.second.y > metrics.first.y, `${layout}: mobile cards must stack`);
                if (width === 1440 && ['tiles', 'overlay'].includes(layout)) assert(Math.abs(metrics.second.y - metrics.first.y) < 1, `${layout}: desktop grid missing`);
                if (width === 1440 && layout === 'list') {
                    assert(metrics.title.x > metrics.image.x + metrics.image.width - 1, 'Horizontal list image must be beside text');
                    assert(metrics.title.y < metrics.intro.y, 'The title must precede the introduction');
                }
                if (layout === 'overlay' || layout === 'magazine') {
                    assert(Math.abs(metrics.image.height - metrics.first.height) < 4, 'Background photo must fill the card');
                    assert.equal(metrics.color, 'rgb(255, 255, 255)');
                }
                if (layout === 'classic') {
                    assert(metrics.second.y > metrics.first.y, 'Refined classic remains an editorial list');
                    assert(metrics.title.y < metrics.image.y, 'Classic title must precede the lead image');
                }
                if (width === 1440 && layout === 'magazine') {
                    assert(metrics.first.width > metrics.second.width * 1.8, 'Magazine first card must span the row');
                    assert(metrics.second.y >= metrics.first.y + metrics.first.height, 'Magazine second row must follow its lead');
                }
                if (layout === 'compact') {
                    assert.equal(await page.locator('.articleheadingtext').first().isVisible(), false, 'Compact list omits the excerpt visually');
                    assert(metrics.image.width <= 90, 'Compact list uses a thumbnail');
                }
                if (width === 1440 && ['tiles', 'overlay'].includes(layout)) {
                    for (const columns of ['2', '3', '4']) {
                        await page.locator('.articles-layout').evaluate((root, value) => root.dataset.articleColumns = value, columns);
                        const count = await page.locator('.articles-layout').evaluate(root => getComputedStyle(root).gridTemplateColumns.split(' ').length);
                        assert.equal(count, Number(columns), 'Requested wide-screen column count missing');
                    }
                    await page.locator('.articles-layout').evaluate(root => root.dataset.articleColumns = 'auto');
                }
                if (width === 1440 && ['tiles', 'overlay', 'magazine'].includes(layout)) {
                    const card = page.locator('article').first();
                    await card.hover();
                    await page.waitForFunction(() => {
                        const transform = getComputedStyle(document.querySelector('article')).transform;
                        return transform !== 'none' && Math.abs(new DOMMatrixReadOnly(transform).m42 + 4) < 0.1;
                    });
                    const lifted = await card.boundingBox();
                    assert(Math.abs(lifted.y - metrics.first.y + 4) < 0.2, 'Visual card should rise gently on hover');
                    const neighbor = await page.locator('article').nth(1).boundingBox();
                    assert(Math.abs(neighbor.y - metrics.second.y) < 0.2, 'Hover must not shift neighbouring cards');
                    assert(Math.abs((await card.locator('img').boundingBox()).width - metrics.image.width) < 0.2, 'Photo must not zoom independently of the card');
                    assert.equal(await card.locator('img').evaluate(el => getComputedStyle(el).transform), 'none');
                    assert.equal(await card.locator('h2 a').evaluate(el => getComputedStyle(el).textDecorationLine), 'underline', 'Hovering the card must underline its title');
                    assert.equal(await card.evaluate(el => getComputedStyle(el).borderTopColor), 'rgba(0, 0, 0, 0)', 'Card outline must remain invisible on hover');
                    await page.emulateMedia({ reducedMotion: 'reduce' });
                    assert.equal(await card.evaluate(el => getComputedStyle(el).transform), 'none', 'Reduced motion must disable card movement');
                    assert.equal(await card.locator('img').evaluate(el => getComputedStyle(el).transform), 'none', 'Reduced motion must disable photo zoom');
                    await page.mouse.move(0, 0);
                    await page.emulateMedia({ reducedMotion: 'no-preference' });
                }
                if (layout === 'overlay' || layout === 'magazine') {
                    await page.emulateMedia({ media: 'print' });
                    assert.equal(await page.locator('article').first().evaluate(card => getComputedStyle(card).color), 'rgb(0, 0, 0)', 'Printed overlays need dark text');
                    await page.emulateMedia({ media: 'screen' });
                }
                await page.locator('h2 a').first().click();
                assert.equal(await page.evaluate(() => window.linkClicks), 1, 'Native title link must work once');
                await page.locator('button').first().click();
                assert.equal(await page.evaluate(() => window.linkClicks), 1, 'Actions must not open the card');

                const clickable = ['tiles', 'overlay', 'magazine'].includes(layout);
                await page.locator('article').first().click({ position: { x: 4, y: 4 } });
                const expectedClicks = clickable ? 2 : 1;
                assert.equal(await page.evaluate(() => window.linkClicks), expectedClicks, 'Only visual layouts open on a card background click');
                if (width === 390) {
                    await page.locator('h2 a').first().focus();
                    await page.keyboard.press('Enter');
                    assert.equal(await page.evaluate(() => window.linkClicks), expectedClicks + 1, 'Keyboard title navigation remains native');
                    if (clickable) {
                        await page.locator('article').first().evaluate(article => {
                            const range = document.createRange();
                            range.selectNodeContents(article.querySelector('p'));
                            window.getSelection().removeAllRanges();
                            window.getSelection().addRange(range);
                            article.dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true, button: 0 }));
                        });
                        assert.equal(await page.evaluate(() => window.linkClicks), expectedClicks + 1, 'Text selection must not open the article');
                        await page.evaluate(() => window.getSelection().removeAllRanges());
                        await page.locator('article').first().dispatchEvent('click', { ctrlKey: true, button: 0 });
                        assert.equal(await page.evaluate(() => window.linkClicks), expectedClicks + 1, 'Modified clicks must not trigger ordinary navigation');
                        await page.locator('article').first().evaluate(article => {
                            const controls = document.createElement('div');
                            controls.innerHTML = '<a href="https://fixture.test/other">Related link</a><audio controls></audio><video controls></video><input><textarea></textarea><button>More actions</button><span role="button">Widget</span><span contenteditable="true">Edit</span><details><summary>Details</summary>Content</details><article class="article"><p>Nested article</p></article>';
                            article.append(controls);
                            controls.querySelector('a').addEventListener('click', event => event.preventDefault());
                            for (const child of controls.querySelectorAll('a,audio,video,input,textarea,button,[role=button],[contenteditable],summary,p')) {
                                child.dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true, button: 0 }));
                            }
                            controls.remove();
                        });
                        assert.equal(await page.evaluate(() => window.linkClicks), expectedClicks + 1, 'Media, widgets and nested articles must not open the outer card');
                        // Loading the enhancement twice must not register duplicate listeners.
                        await page.addScriptTag({ path: resolve(__dirname, '../lib/jquery_tiki/article-cards.js') });
                        await page.locator('article').first().click({ position: { x: 4, y: 4 } });
                        assert.equal(await page.evaluate(() => window.linkClicks), expectedClicks + 2, 'A repeated script load must still open the title link once');
                    }
                }

                if (process.env.ARTICLE_LAYOUT_SCREENSHOTS) await page.screenshot({ path: `${process.env.ARTICLE_LAYOUT_SCREENSHOTS}/${layout}-${width}.png`, fullPage: true });
                await page.close();
                process.stdout.write(`PASS ${layout} ${width}: responsive layout, image/no image, links and controls\n`);
            }
            // The reading page retains its improved introduction independently of the list layout.
            const page = await browser.newPage({ viewport: { width, height: 1000 } });
            await page.route('**/*', route => route.abort());
            for (const withImage of [true, false]) {
                await page.setContent(`<style>*{box-sizing:border-box}body{margin:16px;font:16px sans-serif;--bs-border-color:#ccc}img{max-width:100%}${readingCss}</style>
                    <div class="tiki-article-page"><article class="article">
                    <header class="articletitle"><h1>An improved article title with a readable introduction</h1><span class="titleb">Author · Publication date</span></header>
                    <div class="articleheading"><div>${withImage ? `<img class="article-image" src="${image}" alt="Article illustration">` : ''}</div>
                    <div><p>The introduction appears beside the image on a wide screen and underneath it on mobile.</p></div></div>
                    <nav class="article-navigation"><a rel="prev" href="#prev">Previous<span class="article-navigation-title">A previous article with a descriptive title</span></a><a rel="next" href="#next">Next<span class="article-navigation-title">The next article in publication order</span></a></nav>
                    <div class="articlebody"><p>The article body remains available.</p><audio controls preload="none"></audio>
                    <div class="article"><div class="articleheading nested-introduction">Embedded article content</div></div></div>
                    </article></div>`);
                const state = await page.evaluate(() => {
                    const intro = document.querySelector('.tiki-article-page > .article > .articleheading');
                    const rect = element => { const b = element.getBoundingClientRect(); return { x: b.x, y: b.y, right: b.right, bottom: b.bottom }; };
                    return { scroll: document.documentElement.scrollWidth, image: intro.querySelector('img') ? rect(intro.querySelector('img')) : null,
                        text: rect(intro.lastElementChild), columns: getComputedStyle(intro).gridTemplateColumns.split(' ').length,
                        titleSize: parseFloat(getComputedStyle(document.querySelector('h1')).fontSize),
                        nativeControls: document.querySelector('audio').controls,
                        nestedDisplay: getComputedStyle(document.querySelector('.nested-introduction')).display };
                });
                assert(state.scroll <= width, 'Reading page must not overflow horizontally');
                assert(state.titleSize >= 30, 'Reading title must remain prominent');
                assert(state.nativeControls, 'Native media controls must remain available');
                assert.equal(state.nestedDisplay, 'block', 'Reading layout must not restyle embedded article introductions');
                if (!withImage) assert.equal(state.columns, 1, 'An introduction without image uses the full width');
                else if (width < 768) assert(state.text.y >= state.image.bottom, 'Mobile introduction must follow the image');
                else assert(state.text.x >= state.image.right, 'Desktop introduction must appear beside the image');
                assert.equal(await page.locator('.article-media-control').count(), 0, 'No additional floating media control');
                const navigation = await page.locator('.article-navigation').evaluate(nav => getComputedStyle(nav).gridTemplateColumns.split(' ').length);
                assert.equal(navigation, width < 480 ? 1 : 2, 'Previous/next navigation must adapt to narrow screens');
                if (withImage && process.env.ARTICLE_LAYOUT_SCREENSHOTS) await page.screenshot({ path: `${process.env.ARTICLE_LAYOUT_SCREENSHOTS}/reading-${width}.png`, fullPage: true });
            }
            await page.close();
            process.stdout.write(`PASS reading ${width}: title, image/introduction, image-free article and native media controls\n`);
        }
        const touch = await browser.newPage({viewport: {width: 390, height: 844}, hasTouch: true, isMobile: true});
        await touch.route('**/*', route => route.abort());
        await touch.setContent(`<style>${css}</style><div class="articles-layout articles-layout-tiles">${card(1)}</div>`);
        await touch.addScriptTag({path: resolve(__dirname, '../lib/jquery_tiki/article-cards.js')});
        await touch.locator('article').hover();
        assert.equal(await touch.evaluate(() => matchMedia('(hover: hover) and (pointer: fine)').matches), false);
        assert.equal(await touch.locator('article').evaluate(el => getComputedStyle(el).transform), 'none', 'Touch cards must not retain a hover lift');
        await touch.close();
        process.stdout.write('PASS hover elevation, stable photos, title underline, stable neighbours, reduced motion and touch behaviour\n');
    } finally { await browser.close(); }
})().catch(error => { process.stderr.write(`${error.stack || error}\n`); process.exitCode = 1; });
