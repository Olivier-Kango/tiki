import { execSync } from "child_process";
import { existsSync } from "fs";
import { basename, dirname, resolve } from "path";
import { fileURLToPath } from "url";
import type { TestType } from "@playwright/test";

const __dirname = dirname(fileURLToPath(import.meta.url));

// Command used to invoke PHP fixtures (setup/teardown scripts).
// Override with TIKI_FIXTURE_CMD to match your environment. Examples:
//   DDEV (auto-detected):  ddev exec php /var/www/html[/.worktrees/<name>]
//   Plain PHP:             php /absolute/path/to/tiki
//   Docker:                docker exec my-container php /var/www/html
//
// Auto-detection: resolves the Tiki root from this file's location (4 levels up),
// detects git worktrees via "/.worktrees/<name>" in the path, then checks for a
// .ddev directory in the main project root to decide between ddev exec and plain php.
function buildDefaultFixtureCmd(): string {
    const tikiRootOnHost = resolve(__dirname, "../../../..");

    // Detect git worktree: path ends with /.worktrees/<name>
    const worktreeMatch = tikiRootOnHost.match(/\.worktrees\/([^/]+)$/);

    // Main project root is where .ddev/ lives (strip worktree suffix if present)
    const mainProjectRoot = worktreeMatch
        ? tikiRootOnHost.replace(/\/\.worktrees\/[^/]+$/, "")
        : tikiRootOnHost;

    if (existsSync(resolve(mainProjectRoot, ".ddev"))) {
        // DDEV project detected — PHP runs inside the container
        const containerPath = worktreeMatch
            ? `/var/www/html/.worktrees/${worktreeMatch[1]}`
            : "/var/www/html";
        return `ddev exec php ${containerPath}`;
    }

    // No DDEV — plain PHP with the absolute host path
    return `php ${tikiRootOnHost}`;
}

// Self-contained mode (TIKI_E2E_WEBSERVER=1) serves THIS checkout via php -S,
// so fixtures must run with the host PHP against this checkout. It deliberately
// overrides TIKI_FIXTURE_CMD: honoring a ddev/docker command from .env here
// would mutate a different instance than the one under test.
export const FIXTURE_CMD =
    process.env.TIKI_E2E_WEBSERVER === "1"
        ? `php ${resolve(__dirname, "../../../..")}`
        : (process.env.TIKI_FIXTURE_CMD ?? buildDefaultFixtureCmd());

// Every PHP fixture runs through fixtures/bootstrap.php, which owns the CLI guard, the
// Tiki bootstrap, admin permissions and the JSON in/out contract, so the fixture files
// themselves hold only their own logic. Script paths are relative to fixtures/, which
// keeps callers from having to know where the Tiki root sits inside a container.
const BOOTSTRAP_SCRIPT = "lib/test/e2e/fixtures/bootstrap.php";

/**
 * Build the shell command that runs one PHP fixture through the bootstrap.
 *
 * @param script Path relative to lib/test/e2e/fixtures/, e.g. "shared-secrets/tc04_setup.php"
 * @param input  Optional payload for the fixture, passed base64-encoded so it survives
 *               `ddev exec` / docker / plain php quoting.
 */
export function fixtureCommand(script: string, input?: unknown): string {
    const arg =
        input === undefined
            ? ""
            : ` ${Buffer.from(JSON.stringify(input)).toString("base64")}`;
    return `${FIXTURE_CMD}/${BOOTSTRAP_SCRIPT} ${script}${arg}`;
}

// Subpath prefix extracted from TIKI_BASE_URL by playwright.config.ts.
// Empty string for root installs (DDEV, bare localhost) — no-op in that case.
export const BASE_PATH: string = process.env.TIKI_BASE_PATH ?? "";

// ── Convention-based suite fixtures (system-wide setup/teardown) ─────────────

export interface SuiteFixtures {
    /** Parsed JSON printed by tcNN_setup.php ({} until setup has run). */
    data: Record<string, any>;
    /** True once setup ran successfully. */
    loaded: boolean;
}

/**
 * Wire the PHP setup/teardown fixtures for a spec file by naming convention.
 *
 * Call once at the top of any spec:
 *
 *     const fx = useSuiteFixtures(test);
 *
 * For a spec at suites/<suite>/tcNN-*.spec.ts the runner looks for
 *     fixtures/<suite>/tcNN_setup.php     → run in beforeAll (required)
 *     fixtures/<suite>/tcNN_teardown.php  → run in afterAll (optional)
 * Calling it declares that the spec needs a setup: a missing or misnamed
 * setup script, or a spec not named tcNN-*.spec.ts, fails the spec file with
 * the path it looked for, instead of running the tests without their data.
 *
 * Contract:
 *  - setup prints a single JSON object on stdout → exposed as `fx.data`;
 *  - teardown receives that same JSON back as `$input`, handed over
 *    base64-encoded so it is shell-safe through `ddev exec` / docker / plain php;
 *  - if setup fails, the spec file fails in CI (CI set); locally it is
 *    skipped with a warning pointing at TIKI_FIXTURE_CMD.
 */
export function useSuiteFixtures(test: TestType<any, any>): SuiteFixtures {
    const fx: SuiteFixtures = { data: {}, loaded: false };

    test.beforeAll(async ({}, testInfo) => {
        const scripts = resolveFixtureScripts(testInfo.file);
        if (!scripts) {
            throw new Error(`useSuiteFixtures needs a spec named tcNN-*.spec.ts, got ${basename(testInfo.file)}`);
        }
        if (!scripts.setupExists) {
            throw new Error(`${scripts.label} calls useSuiteFixtures, but ${scripts.setupPath} does not exist`);
        }
        try {
            const out = execSync(fixtureCommand(scripts.setupRel), {
                stdio: "pipe",
            })
                .toString()
                .trim();
            fx.data = JSON.parse(out);
            fx.loaded = true;
        } catch (err) {
            const msg = err instanceof Error ? err.message : String(err);
            // execSync errors keep the child's output in .stdout/.stderr, not
            // in .message — without them a CI log only says "Command failed".
            const { stdout, stderr } = err as { stdout?: Buffer; stderr?: Buffer };
            const output = [
                stderr?.toString().trim() && `  stderr: ${stderr.toString().trim()}`,
                stdout?.toString().trim() && `  stdout: ${stdout.toString().trim()}`,
            ]
                .filter(Boolean)
                .join("\n");
            console.warn(
                `[${scripts.label}] Fixture setup failed${process.env.CI ? "" : " — skipping suite"}.\n` +
                    `  FIXTURE_CMD=${FIXTURE_CMD}\n  Error: ${msg}` +
                    (output ? `\n${output}` : "")
            );
            // In CI the fixture command is known good, so a failed setup is a broken
            // fixture or a broken Tiki: fail the suite rather than report it skipped,
            // which nobody reads on a job that runs on every pipeline. Locally, a
            // misconfigured TIKI_FIXTURE_CMD is the likelier cause, so skip with a hint.
            if (process.env.CI) {
                throw new Error(`${scripts.label} fixture setup failed: ${msg}` + (output ? `\n${output}` : ""));
            }
            test.skip(
                true,
                `${scripts.label} fixture setup failed — check TIKI_FIXTURE_CMD`
            );
        }
    });

    test.afterAll(async ({}, testInfo) => {
        const scripts = resolveFixtureScripts(testInfo.file);
        if (!scripts || !scripts.teardownExists || !fx.loaded) {
            return;
        }
        try {
            execSync(fixtureCommand(scripts.teardownRel, fx.data), {
                stdio: "pipe",
            });
        } catch (_) {
            console.warn(
                `[${scripts.label}] Teardown fixture failed — manual cleanup may be needed`
            );
        }
    });

    return fx;
}

// Where a project's own specs live, and the prefix bootstrap.php resolves against
// the Tiki root instead of lib/test/e2e/fixtures/.
const TIKI_ROOT = resolve(__dirname, "../../../..");
const CUSTOM_E2E_REL = "_custom/shared/lib/test/e2e";
const CUSTOM_E2E_DIR = resolve(TIKI_ROOT, CUSTOM_E2E_REL);

// Derive fixture script paths from a spec file path, or null when the spec
// does not follow the tcNN-*.spec.ts naming convention.
//   core:   suites/<suite>/tcNN-*.spec.ts   → lib/test/e2e/fixtures/<suite>/tcNN_*.php
//   custom: <custom>/<suite>/tcNN-*.spec.ts → <custom>/fixtures/<suite>/tcNN_*.php
function resolveFixtureScripts(specFile: string) {
    const tcMatch = basename(specFile).match(/^(tc\d+)/i);
    if (!tcMatch) {
        return null;
    }
    const tc = tcMatch[1].toLowerCase();
    const suite = basename(dirname(specFile));
    const isCustom = resolve(specFile).startsWith(CUSTOM_E2E_DIR + "/");
    const fixturesDir = isCustom
        ? resolve(CUSTOM_E2E_DIR, "fixtures")
        : resolve(__dirname, "..", "fixtures");
    // bootstrap.php takes core scripts relative to lib/test/e2e/fixtures/, and a
    // project's scripts by their path from the Tiki root.
    const relPrefix = isCustom ? `${CUSTOM_E2E_REL}/fixtures/` : "";
    const setupRel = `${relPrefix}${suite}/${tc}_setup.php`;
    const teardownRel = `${relPrefix}${suite}/${tc}_teardown.php`;
    return {
        label: tc.replace(/^tc/, "TC-").toUpperCase(),
        setupRel,
        teardownRel,
        setupPath: resolve(fixturesDir, suite, `${tc}_setup.php`),
        setupExists: existsSync(resolve(fixturesDir, suite, `${tc}_setup.php`)),
        teardownExists: existsSync(
            resolve(fixturesDir, suite, `${tc}_teardown.php`)
        ),
    };
}
