// A wiki page's whole life through the page bar: edited into a second version,
// rolled back by removing its last version, then removed for good. Creating and
// reading a page is the smoke suite's check (smoke/tc03), so here it is setup.
// Seam: the editor form, the page history and the AJAX removal dialog must agree
// on what a version is; only the assembled page shows that.

import { test, expect } from '../../common/test';
import { login, createWikiPage, deleteWikiPage, pageExists } from "./helpers";

const PAGE_NAME = `E2E-Wiki-${Date.now()}`;
const FIRST = "First version, written by the Playwright E2E suite.";
const SECOND = "Second version, written by the Playwright E2E suite.";

async function readPage(page: import("@playwright/test").Page) {
    await page.goto(`/tiki-index.php?page=${encodeURIComponent(PAGE_NAME)}`);
    return page.locator("#page-data");
}

test.describe("TC-01 — Wiki page lifecycle", () => {
    test.describe.configure({ mode: "serial" });

    test.afterAll(async ({ browser }) => {
        // Safety net when an earlier step failed mid-way.
        const ctx = await browser.newContext();
        const p = await ctx.newPage();
        try {
            await login(p);
            await deleteWikiPage(p, PAGE_NAME, { ifExists: true });
        } finally {
            await ctx.close();
        }
    });

    test("an edit becomes the page's current version", async ({ page }) => {
        await login(page);
        await createWikiPage(page, PAGE_NAME, FIRST);
        await createWikiPage(page, PAGE_NAME, SECOND);
        const content = await readPage(page);
        await expect(content).toContainText(SECOND);
        await expect(content).not.toContainText(FIRST);
    });

    test("removing the last version brings the previous one back", async ({ page }) => {
        await login(page);
        await deleteWikiPage(page, PAGE_NAME, { allVersions: false });
        const content = await readPage(page);
        await expect(content).toContainText(FIRST);
        await expect(content).not.toContainText(SECOND);
    });

    test("removing the page leaves nothing behind", async ({ page }) => {
        await login(page);
        await createWikiPage(page, PAGE_NAME, SECOND);
        await deleteWikiPage(page, PAGE_NAME);
        expect(await pageExists(page, PAGE_NAME)).toBe(false);
    });
});
