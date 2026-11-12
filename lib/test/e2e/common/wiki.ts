import { Page, expect } from "@playwright/test";

// ── Page creation ──────────────────────────────────────────────────────────────

export async function createWikiPage(page: Page, pageName: string, content: string) {
    await page.goto(`/tiki-editpage.php?page=${encodeURIComponent(pageName)}`);
    await page.waitForLoadState("networkidle");

    // Tiki master uses textarea[name="edit"] (id="editwiki"), not textarea[name="content"]
    await page.locator('textarea[name="edit"], textarea#editwiki').fill(content);

    await page.locator('input[name="save"]').first().click();
    await page.waitForLoadState("networkidle");
}

// ── Page deletion ──────────────────────────────────────────────────────────────

// Removes the whole page by default. On a page with several versions, Tiki's dialog
// removes only the last one unless "Remove all versions" is ticked, so
// `{ allVersions: false }` exercises that last-version-only path.
// The removal must happen: a missing delete action fails here, where the cause is
// visible, rather than later on an assertion about the page's content. Cleanup code
// that may run on a page that was never created passes `{ ifExists: true }`.
export async function deleteWikiPage(
    page: Page,
    pageName: string,
    { allVersions = true, ifExists = false } = {}
) {
    // In Tiki master, tiki-removepage.php is gone.
    // Deletion is via the page bar Bootstrap modal on the page view.
    await page.goto(`/tiki-index.php?page=${encodeURIComponent(pageName)}`);
    await page.waitForLoadState("networkidle");

    // The delete action is inside the "More" dropdown in the page bar
    const moreBtn = page.locator('button:has-text("More"), .dropdown-toggle:has-text("More")');
    const deleteLink = page.locator('a[href*="action=remove_pages"]');
    if (ifExists && (await moreBtn.count()) === 0 && (await deleteLink.count()) === 0) return;
    if (await moreBtn.isVisible()) await moreBtn.click();
    await expect(deleteLink, `no delete action on the page bar of ${pageName}`).toBeVisible();
    await deleteLink.click();

    // Wait for the Bootstrap confirm modal
    const modal = page.locator('.modal.show, .modal[style*="display: block"]');
    await modal.waitFor({ state: "visible", timeout: 8000 });

    // The dialog body arrives over AJAX: wait for its confirm button before reading it.
    const confirmBtn = modal
        .locator('button:has-text("Remove"), button:has-text("Delete"), button:has-text("OK"), input[value="OK"]')
        .first();
    await confirmBtn.waitFor({ state: "visible", timeout: 8000 });

    // The checkbox only appears when the page has more than one version.
    const allBox = modal.locator('input[name="all"]');
    if (allVersions && (await allBox.count()) > 0) await allBox.check();

    // Confirming submits the form and Tiki navigates away from the dialog.
    await confirmBtn.click();
    await expect(modal, `the removal dialog for ${pageName} did not close`).toBeHidden({ timeout: 15000 });
    await page.waitForLoadState("networkidle");
}

// ── Assertions ─────────────────────────────────────────────────────────────────

export async function pageExists(page: Page, pageName: string): Promise<boolean> {
    await page.goto(`/tiki-index.php?page=${encodeURIComponent(pageName)}`);
    await page.waitForLoadState("networkidle");
    const bodyText = (await page.locator("body").textContent()) ?? "";
    // master answers "Page not found"; older branches say the page "does not exist (yet)".
    return !/Page not found|does not exist|not exist yet/.test(bodyText);
}
