import { test, expect } from '../../common/test';
import { login } from "./helpers";

// The page listing rendering for the admin is a smoke check (smoke/tc01).
// Seam: tiki-index.php's missing-page handling and the create link it offers a user
// with edit rights.
test.describe("TC-02 — Wiki page access", () => {
    test("a nonexistent page offers the admin to create it", async ({ page }) => {
        await login(page);
        const missing = `E2E-DoesNotExist-${Date.now()}`;
        await page.goto(`/tiki-index.php?page=${encodeURIComponent(missing)}`);
        const main = page.locator("#col1");
        await expect(main).toContainText("Page not found");
        await expect(main.locator(`a[href*="tiki-editpage.php"][href*="${encodeURIComponent(missing)}"]`).first()).toBeVisible();
    });
});
