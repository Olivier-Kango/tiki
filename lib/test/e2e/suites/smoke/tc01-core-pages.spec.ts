// Tiki smoke tests: Tiki's core pages answer and render, for a visitor and for the
// admin, on a fresh install. They test Tiki, not the e2e harness (that is harness/),
// and stay shallow on purpose: one request and one look per page, so a broken
// release is caught in a minute before any domain suite runs.
// Seam: routing, PHP setup, Smarty templates and the built theme assets (public/generated)
// must all be present for a page to render without an error box.

import { test, expect } from "../../common/test";
import type { Cookie, Page } from "@playwright/test";
import { login } from "../../common/auth";
import { expectNoPhpErrorsForAdmin } from "../../common/ui";

// PHP errors printed into the page before Tiki installs its own error handler
// (a fatal error during setup, a parse error).
const PHP_ERROR = /(Fatal error|Parse error|Uncaught (Error|Exception)|Warning<\/b>:|Notice<\/b>:)/;

async function expectPageRenders(page: Page, path: string) {
    const response = await page.goto(path);
    expect(response, `no HTTP response for ${path}`).not.toBeNull();
    expect(response!.status(), `HTTP status of ${path}`).toBeLessThan(400);
    // #col1 is the main column of every Tiki layout; its absence means the page
    // did not render, whatever the status code said.
    await expect(page.locator("#col1"), `${path} did not render the Tiki layout`).toBeVisible();
    expect(await page.content(), `${path} printed a PHP error`).not.toMatch(PHP_ERROR);
}

const VISITOR_PAGES = [
    { name: "home page", path: "/tiki-index.php" },
    { name: "login page", path: "/tiki-login_scr.php" },
];

const ADMIN_PAGES = [
    { name: "home page", path: "/tiki-index.php" },
    { name: "wiki page editor", path: "/tiki-editpage.php?page=HomePage" },
    { name: "page listing", path: "/tiki-listpages.php" },
    { name: "search", path: "/tiki-searchindex.php" },
    { name: "user preferences", path: "/tiki-user_preferences.php" },
    { name: "users admin", path: "/tiki-adminusers.php" },
    { name: "groups admin", path: "/tiki-admingroups.php" },
    { name: "modules admin", path: "/tiki-admin_modules.php" },
    { name: "trackers", path: "/tiki-list_trackers.php" },
    { name: "file galleries", path: "/tiki-list_file_gallery.php" },
];

test.describe("TC-01 — Core pages render", () => {
    test.describe("for a visitor", () => {
        for (const { name, path } of VISITOR_PAGES) {
            test(`${name} (${path})`, async ({ page }) => {
                await expectPageRenders(page, path);
            });
        }
    });

    test.describe("for the admin", () => {
        // One login for all the admin pages: logging in before each of them was a third of
        // the suite's runtime. Each test still gets a fresh context carrying the saved
        // session cookies, so one page that misbehaves cannot affect the next.
        let session: Cookie[] = [];

        test.beforeAll(async ({ browser }) => {
            const context = await browser.newContext();
            const page = await context.newPage();
            await login(page);
            session = await context.cookies();
            await context.close();
        });

        test.beforeEach(async ({ context }) => {
            await context.addCookies(session);
        });

        for (const { name, path } of ADMIN_PAGES) {
            test(`${name} (${path})`, async ({ page }) => {
                await expectPageRenders(page, path);
                await expectNoPhpErrorsForAdmin(page, path);
                // Still the admin: without this, a lost session would pass as a visitor,
                // since the login page Tiki redirects to renders #col1 too.
                await expect(page, `${path} redirected to login`).not.toHaveURL(/tiki-login/);
                await expect(page.locator('a[href*="tiki-logout"]'), `${path} is not logged in`).not.toHaveCount(0);
            });
        }
    });
});
