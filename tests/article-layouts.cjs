// Local layout and interaction fixtures. No live site or external media is contacted.
const { chromium } = require(process.env.PLAYWRIGHT_PATH || 'playwright');
const { resolve } = require('node:path');
const assert = require('node:assert/strict');
const sass = require('sass');
const readingCss = sass.compile(resolve(__dirname, '../themes/base_files/scss/_article-layout.scss')).css;
const css = sass.compile(resolve(__dirname, '../themes/base_files/scss/_articles-tiles.scss')).css;
const image = 'data:image/svg+xml,' + encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" width="800" height="600"><rect width="800" height="600" fill="#427a92"/></svg>');
function card(id, withImage = true) {
    return `<article class="article wikiplugin_articles"><header class="articletitle"><h2><a href="https://fixture.test/article/${id}?autoplay=1">Article ${id}</a></h2><span class="titleb">Author · Date</span></header>
        <div class="articleheading d-md-flex"><div class="article-list-image me-3">${withImage ? `<a href="https://fixture.test/article/${id}?autoplay=1"><img class="article-image" src="${image}" alt="Article illustration"></a>` : ''}</div>
        <div class="articleheadingtext"><p>An article introduction with enough text to check the responsive arrangement.</p></div></div>
        <div class="articletrailer"><a class="more" href="https://fixture.test/article/${id}?autoplay=1">Read more</a><button type="button">Actions</button></div></article>`;
}
(async () => {
    const browser = await chromium.launch({ headless: true, args: ['--no-sandbox'],
        ...(process.env.CHROMIUM_PATH ? { executablePath: process.env.CHROMIUM_PATH } : {}) });
    try {
        for (const width of [390, 1440]) {
            for (const layout of ['classic', 'tiles', 'list', 'overlay']) {
                const page = await browser.newPage({ viewport: { width, height: 1000 } });
                await page.route('**/*', route => route.abort());
                const classes = layout === 'classic' ? '' : `articles-layout articles-layout-${layout}`;
                await page.setContent(`<style>*{box-sizing:border-box}body{margin:16px;font-family:sans-serif;--bs-border-color:#ccc;--bs-border-radius:6px;--bs-border-radius-lg:10px;--bs-tertiary-bg:#f5f5f5}img{max-width:100%}.article{margin-bottom:24px}.titleb{display:block}.articleheadingtext{min-width:0}${css}</style>
                    <div class="${classes}">${card(1)}${card(2)}${card(3, false)}<nav class="pagination">Pagination</nav></div>`);
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
                if (layout === 'classic') {
                    assert(metrics.second.y > metrics.first.y, 'Classic list must stay stacked');
                    assert.equal(await page.locator('h2 a').first().getAttribute('href'), 'https://fixture.test/article/1?autoplay=1');
                } else {
                    assert.equal(await page.locator('h2 a').first().getAttribute('href'), 'https://fixture.test/article/1?autoplay=1');
                    if (width === 390) assert(metrics.second.y > metrics.first.y, `${layout}: mobile cards must stack`);
                    if (width === 1440 && layout !== 'list') assert(Math.abs(metrics.second.y - metrics.first.y) < 1, `${layout}: desktop grid missing`);
                    if (width === 1440 && layout === 'list') {
                        assert(metrics.title.x > metrics.image.x + metrics.image.width - 1, 'Horizontal list image must be beside text');
                        assert(metrics.title.y < metrics.intro.y, 'The title must precede the introduction');
                    }
                    if (layout === 'overlay') {
                        assert(Math.abs(metrics.image.height - metrics.first.height) < 4, 'Background photo must fill the card');
                        assert.equal(metrics.color, 'rgb(255, 255, 255)');
                    }
                    await page.locator('h2 a').first().click();
                    assert.equal(await page.evaluate(() => window.linkClicks), 1, 'Native title link must work once');
                    await page.locator('button').first().click();
                    assert.equal(await page.evaluate(() => window.linkClicks), 1, 'Actions must not open the card');

                }
                if (process.env.ARTICLE_LAYOUT_SCREENSHOTS) await page.screenshot({ path: `${process.env.ARTICLE_LAYOUT_SCREENSHOTS}/${layout}-${width}.png`, fullPage: true });
                await page.close();
                console.log(`PASS ${layout} ${width}: responsive layout, image/no image, links and controls`);
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
                else if (width === 390) assert(state.text.y >= state.image.bottom, 'Mobile introduction must follow the image');
                else assert(state.text.x >= state.image.right, 'Desktop introduction must appear beside the image');
                assert.equal(await page.locator('.article-media-control').count(), 0, 'No additional floating media control');
                if (withImage && process.env.ARTICLE_LAYOUT_SCREENSHOTS) await page.screenshot({ path: `${process.env.ARTICLE_LAYOUT_SCREENSHOTS}/reading-${width}.png`, fullPage: true });
            }
            await page.close();
            console.log(`PASS reading ${width}: title, image/introduction, image-free article and native media controls`);
        }
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
