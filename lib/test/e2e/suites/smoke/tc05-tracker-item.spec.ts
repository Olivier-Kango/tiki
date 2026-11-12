// Trackers carry most client data: a registered user adds an item and reads it back.
// The tracker comes from fixtures/smoke/tc05_setup.php.
// Seam: tracker permissions for a registered user, the AJAX insert form and the item view.

import { test, expect } from "../../common/test";
import { useSuiteFixtures } from "../../common/fixtures";
import { login, TEST_USER } from "../../common/auth";

const fx = useSuiteFixtures(test);
const TITLE = `Smoke item ${Date.now()}`;

test.describe("TC-05 — Tracker item", () => {
    test("a registered user adds an item and reads it back", async ({ page }) => {
        const { trackerId, fieldId } = fx.data;
        await login(page, TEST_USER.user, TEST_USER.pass);
        await page.goto(`/tiki-view_tracker.php?trackerId=${trackerId}`);
        // The tracker's own toolbar (templates/tiki-view_tracker.tpl), not the first matching link on the page.
        await page.locator('.t_navbar a.btn:has-text("Create Item")').click();
        const input = page.locator(`input[name="ins_${fieldId}"], textarea[name="ins_${fieldId}"]`);
        await expect(input).toBeVisible({ timeout: 20000 });
        await input.fill(TITLE);
        // The open modal's submit, which Tiki copies from the form's item-submit-btn into the footer.
        await page.locator(".modal.show .modal-footer button.item-submit-btn").click();

        const itemLink = page.locator(`a[href*="itemId"]:has-text("${TITLE}")`).first();
        await expect(itemLink).toBeVisible({ timeout: 30000 });
        await itemLink.click();
        await expect(page.locator("body")).toContainText(TITLE);
    });
});
