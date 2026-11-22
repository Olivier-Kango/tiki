import { execFileSync } from "node:child_process";
import { accessSync, constants, readFileSync, writeFileSync } from "node:fs";
import { dirname, extname, relative, resolve } from "node:path";
import { fileURLToPath, pathToFileURL } from "node:url";
import { lint } from "cspell";
import { readSettings, spellCheckDocument } from "cspell-lib";

const print = (text = "") => process.stdout.write(text + "\n");
const fail = (text) => {
    process.stderr.write(text + "\n");
    process.exit(2);
};

const CHECKED_EXTENSIONS = ["php", "js", "mjs", "ts", "vue", "tpl", "md", "scss"];

const root = execFileSync("git", ["rev-parse", "--show-toplevel"], { cwd: dirname(fileURLToPath(import.meta.url)), encoding: "utf8" }).trim();
process.chdir(root);

const CI_TOOLS_PATH = readFileSync("path_constants.php", "utf8").match(/CI_TOOLS_PATH = '(.+)'/)[1];
const DICTIONARY = `${CI_TOOLS_PATH}/spellchecker/dictionary.txt`;

const HELP = `Usage: node spellcheck.mjs [--changed | --mr [--base <branch>] | --since <commit>]

Spellchecks only the lines added by a change.

    --changed         Lines added in the working tree compared to HEAD, and untracked files (default).
    --mr              Lines added on this branch since it forked from <base>.
    --base <branch>   Base branch for --mr. Default: origin/master.
    --since <commit>  Lines added between <commit> and HEAD. Works in shallow clones like CI.
    --update-dictionary  Add to dictionary.txt the unknown words used at least <min> times in the existing code.
    --min <number>       Minimum occurrences for --update-dictionary (default: 5).`;

let mode = "changed";
let base = "origin/master";
let minOccurrences = 5;

const args = process.argv.slice(2);

for (let i = 0; i < args.length; i++) {
    const value = () => args[i + 1] ?? fail(`${args[i]} requires a value.`);

    switch (args[i]) {
        case "--changed":
        case "--mr":
            mode = args[i].slice(2);
            break;
        case "--since":
            mode = "since";
            base = value();
            i++;
            break;
        case "--base":
            base = value();
            i++;
            break;
        case "--update-dictionary":
            mode = "dictionary";
            break;
        case "--min":
            if (!/^\d+$/.test(value())) {
                fail("--min requires a number.");
            }
            minOccurrences = Number(args[++i]);
            break;
        case "--help":
        case "-h":
            print(HELP);
            process.exit(0);
            break;
        default:
            fail(`Unknown option ${args[i]}. Use --help.`);
    }
}

(mode === "dictionary" ? updateDictionary() : spellcheckAddedLines()).then((exitCode) => process.exit(exitCode));

function git(...gitArgs) {
    try {
        return execFileSync("git", ["-c", "core.quotepath=off", ...gitArgs], {
            encoding: "utf8",
            maxBuffer: 1024 ** 3,
            stdio: ["ignore", "pipe", "pipe"],
        })
            .split("\n")
            .filter(Boolean);
    } catch (error) {
        return fail(`git ${gitArgs.join(" ")} failed:\n${error.stderr || error.message}`);
    }
}

// Returns Map Set of added line numbers or an untracked file
function addedLines() {
    const revisions = { changed: ["HEAD"], mr: [`${base}...HEAD`], since: [base, "HEAD"] }[mode];
    const lines = new Map();
    let file = null;

    for (const line of git("diff", "-U0", "--no-color", "--no-ext-diff", "--diff-filter=d", ...revisions)) {
        if (line.startsWith("+++ ")) {
            file = line.startsWith("+++ b/") ? line.slice(6) : null;
            continue;
        }

        const hunk = file && line.match(/^@@ -\d+(?:,\d+)? \+(\d+)(?:,(\d+))? @@/);

        if (!hunk) {
            continue;
        }

        const start = Number(hunk[1]);
        const count = hunk[2] === undefined ? 1 : Number(hunk[2]);

        if (!lines.has(file)) {
            lines.set(file, new Set());
        }

        for (let n = start; n < start + count; n++) {
            lines.get(file).add(n);
        }
    }

    if (mode === "changed") {
        for (const untracked of git("ls-files", "--others", "--exclude-standard")) {
            lines.set(untracked, true);
        }
    }

    return lines;
}

async function runCspell(files, options = {}) {
    const issues = [];
    const reporter = {
        issue: (issue) => issues.push(issue),
        error: (message, error) => fail(`${message}: ${error.message}`),
        info() {},
        debug() {},
        progress() {},
        result() {},
    };

    await lint(files, { config: "cspell.json", mustFindFiles: false, ...options }, reporter);

    return issues;
}

async function spellcheckAddedLines() {
    const added = new Map([...addedLines()].filter(([file]) => CHECKED_EXTENSIONS.includes(extname(file).slice(1).toLowerCase())));

    if (added.size === 0) {
        print("No added lines to spellcheck.");
        return 0;
    }

    const issues = (await runCspell([...added.keys()], { showSuggestions: true })).filter((issue) => {
        const lines = added.get(relative(root, fileURLToPath(issue.uri)).replaceAll("\\", "/"));
        return lines === true || lines?.has(issue.row);
    });

    if (issues.length === 0) {
        print(`Spellcheck passed: no unknown words in lines added to ${added.size} file(s).`);
        return 0;
    }

    for (const issue of issues) {
        const file = relative(root, fileURLToPath(issue.uri)).replaceAll("\\", "/");
        const suggestions = issue.suggestions?.length ? ` Suggestions: [${issue.suggestions.slice(0, 5).join(", ")}]` : "";
        print(`${file}:${issue.row}:${issue.col} - Unknown word (${issue.text})${suggestions}`);
    }

    print();
    print(`${issues.length} unknown word(s) in added lines.`);
    print(`Fix the spelling, or add real Tiki-specific words to ${DICTIONARY}.`);

    return 1;
}

// Adds the words used at least minOccurrences times in the existing code to dictionary.txt.
async function updateDictionary() {
    const counts = new Map();

    for (const issue of await runCspell([`**/*.{${CHECKED_EXTENSIONS.join(",")}}`])) {
        const word = issue.text.toLowerCase();
        counts.set(word, (counts.get(word) ?? 0) + 1);
    }

    const candidates = [...counts].filter(([, count]) => count >= minOccurrences).map(([word]) => word);

    if (candidates.length === 0) {
        print(`No word is used at least ${minOccurrences} times. dictionary.txt is unchanged.`);
        return 0;
    }

    const dictionary = readFileSync(DICTIONARY, "utf8").split(/\r?\n/).filter(Boolean);
    let words = [...new Set([...dictionary, ...candidates])];

    const settings = await readSettings(resolve("cspell.json"));
    settings.dictionaries = settings.dictionaries.filter((name) => name !== "tiki");
    const result = await spellCheckDocument(
        { uri: pathToFileURL(resolve("words.txt")).href, text: words.join("\n"), languageId: "plaintext" },
        { generateSuggestions: true, noConfigSearch: true },
        settings
    );
    const misspellings = [...new Set(result.issues.filter((issue) => issue.hasPreferredSuggestions).map((issue) => issue.text.toLowerCase()))];

    words = words.filter((word) => !misspellings.includes(word)).sort();
    const addedWords = words.filter((word) => !dictionary.includes(word));
    const removedWords = dictionary.filter((word) => !words.includes(word));

    if (misspellings.length) {
        print(`${misspellings.length} common misspelling(s) left out: ${misspellings.join(", ")}\n`);
    }

    if (addedWords.length === 0 && removedWords.length === 0) {
        print("dictionary.txt is already up to date.");
        return 0;
    }

    try {
        accessSync(DICTIONARY, constants.W_OK);
        writeFileSync(DICTIONARY, words.join("\n") + "\n");
        print(`${addedWords.length} word(s) added to and ${removedWords.length} removed from ${DICTIONARY}`);
        return 0;
    } catch {
        print(`Cannot write ${DICTIONARY}. Check permissions or add manually.\n`);
    }

    if (addedWords.length) {
        print(`Add these ${addedWords.length} word(s) to dictionary.txt.\n`);
        print(addedWords.join("\n") + "\n");
    }

    if (removedWords.length) {
        print(`Remove these ${removedWords.length} word(s) from dictionary.txt:\n`);
        print(removedWords.join("\n") + "\n");
    }

    print("Or rerun this command with permission to write the file.");

    return 1;
}
