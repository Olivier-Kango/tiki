// Login is in front of almost every journey: if it breaks, nothing else is worth running.
// Seam: the login form, Tiki's authentication and the session cookie the next page reads.

import { test, expect } from "../../common/test";
import { login, logout, ADMIN, TEST_USER } from "../../common/auth";

test.describe("TC-02 — Login", () => {
    test("the admin logs in and out", async ({ page }) => {
        await login(page, ADMIN.user, ADMIN.pass);
        await logout(page);
        await page.goto("/tiki-index.php");
        await expect(page.locator('a[href*="tiki-logout"]')).toHaveCount(0);
    });

    test("a registered user logs in and out", async ({ page }) => {
        await login(page, TEST_USER.user, TEST_USER.pass);
        await logout(page);
        await page.goto("/tiki-index.php");
        await expect(page.locator('a[href*="tiki-logout"]')).toHaveCount(0);
    });

    test("a wrong password is refused", async ({ page }) => {
        await page.goto("/tiki-login.php");
        await page.locator('input[name="user"]').fill(ADMIN.user);
        await page.locator('input[name="pass"]').fill("not-the-password");
        await page.locator('button[name="login"], input[name="login"]').first().click();
        await page.waitForLoadState("load");
        // Refused, and said so: not just "no session", which a form that never
        // submitted would also give.
        await expect(page.locator("#col1")).toContainText("Invalid username or password");
        await expect(page.locator('a[href*="tiki-logout"]')).toHaveCount(0);
    });
});
