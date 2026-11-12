// Permissions hold: the control panels open for the admin and for nobody else.
// .admin-wrapper wraps the control panels; the admin check proves the landmark exists.
// Seam: Tiki's permission check, the redirect to tiki-login.php and the admin layout.

import { test, expect } from "../../common/test";
import { login, ADMIN, TEST_USER } from "../../common/auth";
import { expectNoPhpErrorsForAdmin } from "../../common/ui";

test.describe("TC-04 — Access control", () => {
    test("the admin opens the control panels", async ({ page }) => {
        await login(page, ADMIN.user, ADMIN.pass);
        await page.goto("/tiki-admin.php");
        await expect(page.locator(".admin-wrapper")).toBeVisible();
        await expectNoPhpErrorsForAdmin(page, "/tiki-admin.php");
    });

    test("a visitor is sent to the login page", async ({ page }) => {
        await page.goto("/tiki-admin.php");
        await expect(page).toHaveURL(/tiki-login/);
        await expect(page.locator(".admin-wrapper")).toHaveCount(0);
    });

    test("a registered user is refused", async ({ page }) => {
        await login(page, TEST_USER.user, TEST_USER.pass);
        await page.goto("/tiki-admin.php");
        // Refused, and told so (check_permission in lib/tikiaccesslib.php), rather
        // than merely sent somewhere without the control panels.
        await expect(page.locator("#col1")).toContainText("You do not have the permission that is needed to use this feature");
        await expect(page.locator(".admin-wrapper")).toHaveCount(0);
    });
});
