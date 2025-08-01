#!/usr/bin/env bash
set -e

# This script ensures that caret (^) version constraints are not used in composer.json
# as per Tiki's dependency policy:
# https://gitlab.com/tikiwiki/tiki/-/blob/master/vendor_bundled/composer.json
# Instead, tilde (~) should be used unless there's a documented exception.

echo "Checking for ^ operators in composer.json require and require-dev..."

MATCHES=$(jq '.require, .["require-dev"]' vendor_bundled/composer.json | grep -E '": "\^' || true)

if [ -n "$MATCHES" ]; then
  echo "Error: '^' operator found in composer.json. Use tilde (~) instead on these dependencies:"
  echo "$MATCHES"
  exit 1
else
  echo "Check passed: No ^ operator found in composer.json."
fi
