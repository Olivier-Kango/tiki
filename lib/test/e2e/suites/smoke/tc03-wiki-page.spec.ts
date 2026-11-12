// The wiki page is Tiki's core object: the admin can write one and read it back.
// Only the vital path lives here; editing, versions and deletion are the
// wiki-pages suite's journeys. The page is left in place to keep smoke short: CI
// provisions a database per run and discards it, and locally each run's page has a
// unique name, so it never collides with the next run.
// Seam: the editor form, the save handler and the wiki parser rendering #page-data.

import { test, expect } from "../../common/test";
import { login } from "../../common/auth";
import { createWikiPage } from "../../common/wiki";

const PAGE_NAME = `E2E-Smoke-${Date.now()}`;

test.describe("TC-03 — Wiki page", () => {
    test("the admin creates a page and reads it", async ({ page }) => {
        await login(page);
        await createWikiPage(page, PAGE_NAME, "Smoke content.");
        await page.goto(`/tiki-index.php?page=${encodeURIComponent(PAGE_NAME)}`);
        await expect(page.locator("#page-data")).toContainText("Smoke content.");
    });
});
