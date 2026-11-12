import { Page, expect } from "@playwright/test";

// ── Bootstrap tab activation ──────────────────────────────────────────────────

/**
 * Activate a Bootstrap tab and wait for its pane to become visible.
 *
 * Encapsulates the async-loading quirks of Bootstrap across Tiki versions:
 *  - master (v29.x): Bootstrap loads as an async ES module — wait for
 *    window.bootstrap to appear, then activate via the Tab API.
 *  - master fallback: resolve the module path from the page's import map
 *    and dynamic-import it.
 *  - v27.x: Bootstrap is bundled synchronously — a plain click works.
 *
 * @param page           Playwright page
 * @param anchorSelector selector of the tab's <a> anchor (e.g. "a[href='#pane-id']")
 * @param paneSelector   selector of the tab pane that must become visible
 */
export async function activateBootstrapTab(
    page: Page,
    anchorSelector: string,
    paneSelector: string
) {
    // Wait for the anchor to be present in the DOM before evaluating — the load
    // event fires before Smarty/JS has necessarily rendered the tab nav markup.
    await page.locator(anchorSelector).waitFor({ state: "attached" });
    await page.evaluate(async (selector: string) => {
        const tabEl = document.querySelector(selector) as HTMLElement | null;
        if (!tabEl) throw new Error(`Tab anchor not found: ${selector}`);
        // Wait up to 5 s for the async Bootstrap ES module to register itself.
        if (!(window as any).bootstrap?.Tab) {
            let attempts = 0;
            while (!(window as any).bootstrap?.Tab && attempts < 100) {
                await new Promise((r) => setTimeout(r, 50));
                attempts++;
            }
        }
        if ((window as any).bootstrap?.Tab) {
            (window as any).bootstrap.Tab.getOrCreateInstance(tabEl).show();
            return;
        }
        // Fallback: dynamic import via the page's import map (master).
        const importMapEl = document.querySelector(
            'script[type="importmap"]'
        ) as HTMLScriptElement | null;
        if (importMapEl) {
            const map = JSON.parse(importMapEl.textContent ?? "{}");
            const bsPath = map.imports?.bootstrap;
            if (bsPath) {
                const bs = await import(bsPath);
                bs.Tab.getOrCreateInstance(tabEl).show();
                return;
            }
        }
        // Last resort: plain click (v27.x sync-loaded Bootstrap).
        tabEl.click();
    }, anchorSelector);
    await page.locator(paneSelector).waitFor({ state: "visible" });
}

// ── PHP errors shown to the admin ─────────────────────────────────────────────

// Once set up, Tiki hides PHP warnings from the page (display_errors off) and lists
// them for the admin in the footer's .errors-wrapper (templates/footer.tpl), so that
// box is where an admin page's PHP errors show. A visitor never sees it: for the
// visitor pages, the check is the status, the layout and the early-error pattern.
export async function expectNoPhpErrorsForAdmin(page: Page, path: string) {
    const errors = (await page.locator("#errors").allTextContents()).join(" ").replace(/\s+/g, " ").trim();
    expect(await page.locator(".errors-wrapper").count(), `${path} raised PHP errors: ${errors}`).toBe(0);
}
