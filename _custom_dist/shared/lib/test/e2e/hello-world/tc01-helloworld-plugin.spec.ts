// Example of a project-specific e2e spec. Copied to _custom/shared/lib/test/e2e/, it runs
// as the "custom" Playwright project next to Tiki's own suites, reusing their helpers
// through the @tiki-e2e alias and their PHP fixture bootstrap: the fixtures for this
// spec are fixtures/hello-world/tc01_setup.php and tc01_teardown.php.
// Seam: a plugin under _custom/shared/wiki-plugins/ loaded by Tiki's plugin registry
// and rendered by the wiki parser on a page.
import { test, expect } from "@tiki-e2e/test";
import { login } from "@tiki-e2e/auth";
import { useSuiteFixtures } from "@tiki-e2e/fixtures";

const fx = useSuiteFixtures(test);

test.describe("TC-01 — HelloWorld sample plugin from _custom", () => {
    test("A wiki page renders the custom plugin's output", async ({ page }) => {
        await login(page);
        await page.goto(`/tiki-index.php?page=${encodeURIComponent(fx.data.pageName)}`);
        await expect(page.locator("#page-data h2")).toHaveText("Hello world plugin: e2e");
    });
});
