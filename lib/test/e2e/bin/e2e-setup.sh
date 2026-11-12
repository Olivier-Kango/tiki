#!/usr/bin/env bash
#
# Tiki E2E onboarding helper.
#
# Gets a new tester from a fresh clone to a green harness run in one command:
#   1. ensures lib/test/e2e/.env exists (copies from .env.template if missing)
#   2. installs the Playwright dependency + Chromium browser if missing
#   3. runs the harness suite (subpath routing + live environment readiness)
#
# Environment prerequisites (test users, User Encryption feature) are seeded
# automatically by common/global-setup.ts on the first test run — no manual
# admin toggling required.
#
# Usage (from anywhere):
#   lib/test/e2e/bin/e2e-setup.sh              # setup + harness suite
#   lib/test/e2e/bin/e2e-setup.sh --no-run     # setup only, skip the harness run
#
set -euo pipefail

E2E_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
REPO_ROOT="$(cd "$E2E_DIR/../../.." && pwd)"
cd "$E2E_DIR"

RUN_HARNESS=1
[[ "${1:-}" == "--no-run" ]] && RUN_HARNESS=0

info()  { printf '\033[1;34m[e2e setup]\033[0m %s\n' "$1"; }
warn()  { printf '\033[1;33m[e2e setup]\033[0m %s\n' "$1"; }

# 1 — .env
if [[ -f .env ]]; then
    info ".env already present — leaving it untouched."
else
    cp .env.template .env
    warn "Created .env from .env.template."
    warn "Review lib/test/e2e/.env and adjust TIKI_BASE_URL / credentials for your setup before relying on it."
fi

# 2 — dependencies
# lib/test/e2e is an npm workspace of the Tiki root, so install from the repo root: running
# `npm install` from a subdirectory triggers the root postinstall (patch-package)
# in a context where its bin is unavailable and fails on a fresh clone.
if ! node -e "require.resolve('@playwright/test')" >/dev/null 2>&1; then
    info "Installing npm dependencies…"
    (cd "$REPO_ROOT" && npm install)
else
    info "npm dependencies already installed."
fi

info "Ensuring Chromium is installed…"
npx playwright install chromium

# 3 — harness run
if [[ "$RUN_HARNESS" -eq 1 ]]; then
    info "Running harness suite…"
    npx playwright test suites/harness/
    info "Harness suite passed. You're ready to write and run tests."
else
    info "Setup complete (harness run skipped). Run: npx playwright test suites/harness/"
fi
