# Spellchecker

Checks spelling in the lines **added** by a change (PHP, JS, TS, Vue, TPL, MD and SCSS files). It runs in CI on every merge request (`spellchecker` job in `.gitlab-ci.yml`) and can be run locally.

## Running it locally

```bash
node src/ci/spellchecker/spellcheck.mjs            # uncommitted changes (default)
node src/ci/spellchecker/spellcheck.mjs --mr       # everything added on your branch since origin/master
```

Run `node src/ci/spellchecker/spellcheck.mjs --help` for all options.

## When CI flags an unknown word

The author of the merge request is responsible for resolving it, in one of two ways:

1. **It is a typo**: fix the spelling.
2. **It is a real Tiki term** (a product name, a feature name, a technical word used across the code): add it to [`dictionary.txt`](dictionary.txt) in the same merge request, so reviewers can check it in the diff.

Guidelines for `dictionary.txt`:

- One word per line, kept in alphabetical order, lowercase unless the casing matters.
- Do not add typos, one-off identifiers or words that only appear in a single place. Reword instead.
- Reviewers should challenge additions that look like misspellings.

## Bulk refresh

`--update-dictionary` adds to `dictionary.txt` the unknown words that are already used at least `--min` times (default 5) in the existing code:

```bash
node src/ci/spellchecker/spellcheck.mjs --update-dictionary --min 5
```

This is meant for an occasional cleanup, not for routine use. There is no fixed owner or schedule. Whoever runs it must review the resulting diff and remove anything that is a misspelling before committing, in a dedicated merge request.
