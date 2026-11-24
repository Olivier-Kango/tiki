// Local media fixtures; YouTube's API is simulated and external requests are blocked.
const { chromium } = require('playwright');
const assert = require('node:assert/strict');
const { resolve } = require('node:path');
const sass = require('sass');
const { readFileSync } = require('node:fs');
const coreJs = readFileSync(resolve(__dirname, '../lib/tiki-js.js'), 'utf8');
const translations = coreJs.slice(coreJs.indexOf('function tr(str)'), coreJs.indexOf('// end translation'));
const css = sass.compile(resolve(__dirname, '../themes/base_files/scss/_article-layout.scss')).css;
(async () => {
    const browser = await chromium.launch({headless: true, executablePath: process.env.CHROMIUM_PATH, args: ['--no-sandbox']});
    try {
        const page = await browser.newPage({viewport: {width: 390, height: 844}});
        await page.route('**/*', route => route.abort());
        async function fixture(content, beforeScript, youtube = true) {
            await page.setContent(`<html lang="fr"><style>body{--bs-body-bg:white;--bs-body-color:black;--bs-primary:navy} .visually-hidden{position:absolute;clip-path:inset(50%)}${css}</style><div class="tiki-article-page" data-article-youtube="${youtube ? 'y' : 'n'}"><article class="article"><header class="articletitle">Title</header><div class="articlebody">${content}</div></article></div></html>`);
            await page.addScriptTag({content: translations});
            await page.evaluate(() => {
                delete document.documentElement.dataset.articleMediaReady;
                window.mediaCalls = [];
                window.testPlayers = [];
                for (const media of document.querySelectorAll('audio,video')) {
                    let paused = true;
                    Object.defineProperty(media, 'paused', {get: () => paused});
                    media.play = async () => {paused = false; window.mediaCalls.push(media.id); media.dispatchEvent(new Event('play'));};
                    media.pause = () => {paused = true; media.dispatchEvent(new Event('pause'));};
                }
                window.YT = {Player: function (frame, {events}) {
                    let state = 2;
                    const player = {
                        playVideo() {state = 1; events.onStateChange({data: 1});},
                        pauseVideo() {state = 2; events.onStateChange({data: 2});},
                        getPlayerState() {return state;},
                        finish() {state = 0; events.onStateChange({data: 0});},
                        blocked() {events.onAutoplayBlocked();}
                    };
                    window.testPlayers.push(player);
                    setTimeout(() => events.onReady(), 0);
                    return player;
                }};
            });
            if (beforeScript) await page.evaluate(beforeScript);
            await page.addScriptTag({path: resolve(__dirname, '../lib/jquery_tiki/article-media.js')});
        }
        await fixture('<audio src="translated.wav" controls></audio>', () => {
            window.lang = {
                'Article playback': 'Artikelwiedergabe',
                'Listen to the article': 'Artikel anhören',
                'Play': 'Abspielen',
                'Pause': 'Pause machen',
                'Media unavailable.': 'Medium nicht verfügbar.'
            };
        });
        assert.equal(await page.locator('.article-media').getAttribute('aria-label'), 'Artikelwiedergabe');
        assert.equal(await page.locator('.article-media-label').textContent(), 'Artikel anhören');
        assert.equal(await page.locator('.article-media button').getAttribute('aria-label'), 'Abspielen');
        await page.locator('.article-media button').click();
        assert.equal(await page.locator('.article-media button').getAttribute('aria-label'), 'Pause machen');
        await page.locator('audio').evaluate(e => e.dispatchEvent(new Event('error')));
        assert.equal(await page.locator('.article-media [role="status"]').textContent(), 'Medium nicht verfügbar.');
        const youtubeFrame = '<iframe data-tiki-youtube="y" src="https://www.youtube.com/embed/abcdefghijk"></iframe>';
        await fixture(youtubeFrame, () => {delete window.YT;}, false);
        assert.equal(await page.locator('.article-media').count(), 0, 'YouTube opt-out must not create controls');
        assert.equal(await page.locator('script[src="https://www.youtube.com/iframe_api"]').count(), 0, 'YouTube opt-out must not load the API');
        assert.equal(await page.locator('iframe').getAttribute('src'), 'https://www.youtube.com/embed/abcdefghijk', 'Opt-out must leave the embedded player unchanged');
        await fixture(youtubeFrame.replace('data-tiki-youtube="y"', ''), () => {delete window.YT;});
        assert.equal(await page.locator('.article-media').count(), 0, 'Raw YouTube iframes must not bypass PluginYoutube integration');
        assert.equal(await page.locator('script[src="https://www.youtube.com/iframe_api"]').count(), 0);
        await fixture('<audio src="local.wav" controls></audio>' + youtubeFrame, () => {delete window.YT;}, false);
        await page.locator('.article-media button').click();
        assert.equal(await page.locator('.article-media button').getAttribute('aria-pressed'), 'true', 'HTML5 playback must work with YouTube disabled');
        assert.equal(await page.locator('script[src="https://www.youtube.com/iframe_api"]').count(), 0);
        await fixture('<p>Text-only article</p>');
        assert.equal(await page.locator('.article-media,.article-media-position').count(), 0, 'No button or placeholder without media');
        await fixture('<audio></audio><p>No playable media</p>');
        assert.equal(await page.locator('.article-media').count(), 0);
        await fixture('<div class="wikiplugin_articles article"><div class="articlebody"><audio src="nested.wav"></audio></div></div>');
        assert.equal(await page.locator('.article-media').count(), 0, 'Embedded article media is excluded');
        await fixture('<audio id="audio" src="audio.wav" controls></audio><video id="video" src="video.mp4" controls></video><iframe data-tiki-youtube="y" src="http://www.youtube-nocookie.com/embed/abcdefghijk"></iframe>');
        assert.match(await page.locator('iframe').getAttribute('src'), /^https:\/\//, 'Local HTTP YouTube embeds must use HTTPS');
        const dock = page.locator('.article-media');
        const button = dock.locator('button');
        assert.equal(await button.getAttribute('aria-pressed'), 'false');
        assert.equal(await dock.evaluate(d => getComputedStyle(d).borderRadius), '999px', 'Playback control remains a pill');
        assert.equal(await dock.locator('svg').count(), 1, 'Playback icon must not depend on icon fonts');
        assert.deepEqual(await page.evaluate(() => window.mediaCalls), [], 'No playback on page load');
        await button.click();
        assert.equal(await button.getAttribute('aria-pressed'), 'true');
        assert(await dock.evaluate(d => d.classList.contains('is-floating')));
        await button.click();
        assert.equal(await button.getAttribute('aria-pressed'), 'false');
        await button.click();
        await page.locator('#audio').evaluate(a => a.dispatchEvent(new Event('ended')));
        assert(await page.locator('.article-media-label').textContent().then(t => t.includes('2/3')));
        assert.equal((await page.evaluate(() => window.mediaCalls)).at(-1), 'video');
        await page.locator('#video').evaluate(a => a.dispatchEvent(new Event('ended')));
        await page.waitForFunction(() => document.querySelector('.article-media-label').textContent.includes('3/3'));
        assert.equal(await button.getAttribute('aria-pressed'), 'true');
        await page.evaluate(() => window.testPlayers[0].blocked());
        assert.equal(await button.getAttribute('aria-pressed'), 'false');
        await button.click();
        await page.evaluate(() => window.testPlayers[0].finish());
        assert.equal(await button.getAttribute('aria-pressed'), 'false');
        assert(!await dock.evaluate(d => d.classList.contains('is-floating')));
        assert.match(await page.locator('.article-media-label').textContent(), /1\/3/);
        await button.click();
        const bounds = await dock.boundingBox();
        assert(bounds.x >= 0 && bounds.x + bounds.width <= 390, 'Mobile dock fits viewport');
        await page.evaluate(() => window.dispatchEvent(new Event('scroll')));
        assert(await dock.evaluate(d => d.classList.contains('is-compact')));
        await fixture('<iframe data-tiki-youtube="y" src="https://www.youtube.com/embed/abcdefghijk"></iframe>', () => {delete window.YT;});
        await button.click();
        await button.click();
        // Cancel pending API startup before readiness; it must not launch playback later.
        await page.evaluate(() => {
            window.pendingPlay = false;
            window.YT = {Player: function (frame, {events}) {
                setTimeout(() => events.onReady(), 0);
                return {pauseVideo() {}, playVideo() {window.pendingPlay = true;}};
            }};
            window.onYouTubeIframeAPIReady();
        });
        await page.waitForTimeout(30);
        assert.equal(await page.evaluate(() => window.pendingPlay), false);
        // Exercise real Chromium playback as well as the deterministic mixed-media state tests.
        const wav = Buffer.alloc(44 + 16000 * 2 * 3);
        wav.write('RIFF', 0); wav.writeUInt32LE(wav.length - 8, 4); wav.write('WAVEfmt ', 8);
        wav.writeUInt32LE(16, 16); wav.writeUInt16LE(1, 20); wav.writeUInt16LE(1, 22);
        wav.writeUInt32LE(16000, 24); wav.writeUInt32LE(32000, 28);
        wav.writeUInt16LE(2, 32); wav.writeUInt16LE(16, 34); wav.write('data', 36);
        wav.writeUInt32LE(wav.length - 44, 40);
        const real = await browser.newPage();
        await real.route('**/*', route => route.abort());
        const source = 'data:audio/wav;base64,' + wav.toString('base64');
        await real.setContent(`<div class="tiki-article-page"><article class="article"><header class="articletitle">Title</header><div class="articlebody"><audio src="${source}" controls></audio><audio src="${source}" controls></audio></div></article></div>`);
        await real.addScriptTag({content: translations});
        await real.addScriptTag({path: resolve(__dirname, '../lib/jquery_tiki/article-media.js')});
        await real.locator('.article-media button').click();
        await real.waitForFunction(() => document.querySelector('audio').currentTime > 0.1);
        await real.locator('.article-media button').click();
        assert(await real.locator('audio').first().evaluate(a => a.paused));
        await real.locator('.article-media button').click();
        await real.locator('audio').first().evaluate(a => {a.currentTime = a.duration - 0.05;});
        await real.waitForFunction(() => document.querySelectorAll('audio')[1].currentTime > 0.1);
        assert(await real.locator('audio').first().evaluate(a => a.paused));
        await real.close();
        process.stdout.write('PASS: no-media isolation, HTML5 audio/video, mixed YouTube playlist, pause, completion, blocked playback, cancellation and mobile floating control\n');
    } finally {await browser.close();}
})().catch(error => {process.stderr.write(error.stack + '\n'); process.exitCode = 1;});
