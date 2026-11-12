#!/usr/bin/env bash
# Guard for the e2e-others CI job: E2E_EXCLUDE_SUITES must name exactly the domains
# that have a job of their own (E2E_SUITE in .gitlab-ci.yml, the e2e-suite matrix and
# e2e-smoke), and each of them must have a folder under suites/. A domain in the
# matrix but not in the list runs twice; one in the list but not in the matrix runs
# nowhere.
#
# Usage: E2E_EXCLUDE_SUITES="..." lib/test/e2e/bin/check-excluded-suites.sh [path/to/.gitlab-ci.yml]
set -euo pipefail

E2E_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
CI_FILE="${1:-$E2E_DIR/../../../.gitlab-ci.yml}"

# Every value E2E_SUITE takes: `E2E_SUITE: smoke`, and the items under `- E2E_SUITE:`.
with_job=$(awk '
    /^[[:space:]]*#/ { next }
    in_list && match($0, /^[[:space:]]*- [A-Za-z0-9_-]+[[:space:]]*$/) {
        if (match($0, /[^[:space:]]/) - 1 > list_indent) { sub(/^[[:space:]]*- /, ""); sub(/[[:space:]]*$/, ""); print; next }
    }
    { in_list = 0 }
    /^[[:space:]]*-?[[:space:]]*E2E_SUITE:[[:space:]]*$/ { in_list = 1; list_indent = match($0, /[^[:space:]]/) - 1; next }
    /^[[:space:]]*E2E_SUITE:[[:space:]]*[A-Za-z0-9_-]+[[:space:]]*$/ { sub(/^[[:space:]]*E2E_SUITE:[[:space:]]*/, ""); sub(/[[:space:]]*$/, ""); print }
' "$CI_FILE" | sort -u)
excluded=$(printf '%s\n' ${E2E_EXCLUDE_SUITES:-} | sort -u)

status=0
for suite in $excluded; do
    [ -d "$E2E_DIR/suites/$suite" ] || { echo "E2E_EXCLUDE_SUITES names $suite, but lib/test/e2e/suites/$suite/ does not exist: update the e2e-suite matrix and this list"; status=1; }
done
for suite in $(comm -23 <(echo "$with_job") <(echo "$excluded")); do
    echo "$suite has its own E2E_SUITE job but is missing from E2E_EXCLUDE_SUITES: e2e-others would run it a second time"; status=1
done
for suite in $(comm -13 <(echo "$with_job") <(echo "$excluded")); do
    echo "E2E_EXCLUDE_SUITES names $suite, but no job sets E2E_SUITE to it: its specs would run nowhere"; status=1
done
[ "$status" -eq 0 ] && echo "E2E_EXCLUDE_SUITES matches the domains with their own job: $(echo $with_job)"
exit "$status"
