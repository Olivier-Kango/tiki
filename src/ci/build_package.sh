#!/usr/bin/env bash

# (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
#
# All Rights Reserved. See copyright.txt for details and a complete list of authors.
# Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

set -euo pipefail

# Supported actions: all (default), dependencies, package.
ACTION="${1:-all}"

case "$ACTION" in
    all|dependencies|package)
        ;;
    *)
        echo "Usage: $0 [all|dependencies|package]" >&2
        exit 1
        ;;
esac

# Detect CI mode before assigning default directories.
CI_MODE=true
if [[ -n "${SOURCE_DIR+x}" || -n "${OUTPUT_DIR+x}" ]]; then
    CI_MODE=false
fi

# Allow CI and release.php to supply their own directories.
SOURCE_DIR="${SOURCE_DIR:-.}"
OUTPUT_DIR="${OUTPUT_DIR:-$SOURCE_DIR}"

PACKAGE_FILENAME="${PACKAGE_FILENAME:-tiki-package.tar.gz}"
PACKAGE_NAME="${PACKAGE_NAME:-${PACKAGE_FILENAME%.tar.gz}}"
PACKAGE_FORMATS="${PACKAGE_FORMATS:-tar.gz}"

COMPOSER_PHAR="${COMPOSER_PHAR:-}"
COMPOSER=("composer")

# Resolve directories before changing the working directory.
SOURCE_DIR="$(cd "$SOURCE_DIR" && pwd)"
mkdir -p "$OUTPUT_DIR"
OUTPUT_DIR="$(cd "$OUTPUT_DIR" && pwd)"

# As it is now this is really mixing concerns and will be extremely hard to maintain.  DRY and separation of concerns.
#  Some of this is in .gitignore.  If running manually one should really start with git clean.  Not an issue with CI
#  Some of this is removing things specific to the tarball (node_modules, .git)
#  Some of this is removing or modifying things normally versioned in git.  I don't think that should be done, but if so it should be it's own list to be maintained and debated
# benoitg - 2026-09-17

# Git-tracked files and directories intentionally excluded from tarball packages.
PACKAGE_EXCLUDES_TRACKED=(
    "tests"
    "src/ci"
    "src/devtools"
    ".gitignore"
    ".husky"
    "bin"
    ".gitpod"
    ".editorconfig"
    ".gitattributes"
    ".gitlab-ci.yml"
    ".gitlab-ci-local-env"
    ".gitlab-ci-local-variables.yml"
    ".gitpod.yml"
    ".phplint.yml"
    ".prettierrc"
    ".vimrc"
    "eslint.config.js"
    "commitlint.config.cjs"
)

# Non-Git-tracked files/directories excluded from packages.
PACKAGE_EXCLUDES=(
    ".git"
    ".composercache"
    ".DS_Store"
    "*/.DS_Store"
)

install_dependencies()
{
    echo "=> Installing Composer production dependencies..."

    # Support either a system Composer installation or a supplied PHAR.
    if [[ -n "$COMPOSER_PHAR" ]]; then
        if [[ ! -f "$COMPOSER_PHAR" ]]; then
            echo "Composer PHAR not found: $COMPOSER_PHAR" >&2
            exit 1
        fi

        COMPOSER=(php "$COMPOSER_PHAR")
    fi

    COMPOSER_VERSION=$("${COMPOSER[@]}" --version --no-ansi)

    if [[ ! "$COMPOSER_VERSION" =~ Composer[[:space:]]version[[:space:]]2\. ]]; then
        if [[ -n "$COMPOSER_PHAR" ]]; then
            echo "Composer 2 is required." >&2
            exit 1
        fi

        # Keep the existing CI behavior of upgrading Composer when necessary.
        "${COMPOSER[@]}" self-update --2
    fi

    cd "$SOURCE_DIR"

    echo "=> Removing Composer dev dependencies..."

    "${COMPOSER[@]}" --ansi install \
        -d vendor_bundled \
        --no-dev \
        --optimize-autoloader \
        --no-progress \
        --prefer-dist \
        -n

    if [[ "$CI_MODE" == true ]]; then
       echo "=> Generating webservice configurations..."
       php console.php dev:buildwsconfs --generate
    fi
}

create_packages()
{
    cd "$SOURCE_DIR"

    echo "=> Cleanup..."

    # Remove node_modules recursively.
    find "$SOURCE_DIR" \
        -type d \
        -name node_modules \
        -prune \
        -exec rm -rf {} +

    # CI mode: restore tracked files and remove generated files from temp/.
    if [[ "$CI_MODE" == true ]]; then
        git restore --worktree -- temp/
        git clean -fdx -- temp/
    fi

    echo "=> Optimize language files..."

    STRIPCOMMENTS_SCRIPT="$SOURCE_DIR/src/ci/stripcomments.php"

    while IFS= read -r -d '' language_file; do
        php "$STRIPCOMMENTS_SCRIPT" "$language_file"
    done < <(find "$SOURCE_DIR/lang" -type f -name language.php -print0)

    echo "=> Permissions..."

    find "$SOURCE_DIR" -type f -exec chmod 0664 {} +
    chmod 0775 "$SOURCE_DIR/setup.sh"
    find "$SOURCE_DIR" -type d -exec chmod 0755 {} +

    echo "=> Creating packages..."

    TAR_EXCLUDES=()
    ZIP_EXCLUDES=()
    SEVENZIP_EXCLUDES=()

    for path in "${PACKAGE_EXCLUDES[@]}" "${PACKAGE_EXCLUDES_TRACKED[@]}"; do
      TAR_EXCLUDES+=("--exclude=$path")
      ZIP_EXCLUDES+=("-x" "$path")
      SEVENZIP_EXCLUDES+=("-xr!$path")
    done

    # Store temporary archives in OUTPUT_DIR to avoid cross-filesystem mv issues.
    if [[ "$SOURCE_DIR" == "$OUTPUT_DIR" ]]; then
        # Keep temporary archives outside the source to avoid including them in the package.
        package_tmp_dir="$(mktemp -d "${TMPDIR:-/tmp}/tiki-package.XXXXXX")"
    else
        package_tmp_dir="$(mktemp -d "$OUTPUT_DIR/.package.XXXXXX")"
    fi

    trap 'rm -rf "$package_tmp_dir"' EXIT

    for format in $PACKAGE_FORMATS; do
        archive="$OUTPUT_DIR/$PACKAGE_NAME.$format"
        temporary_archive="$package_tmp_dir/$PACKAGE_NAME.$format"

        echo "=> Creating $PACKAGE_NAME.$format..."

        case "$format" in
            tar.gz)
                # COPYFILE_DISABLE prevents macOS metadata from entering archives.
                COPYFILE_DISABLE=1 tar -czpf "$temporary_archive" \
                    "${TAR_EXCLUDES[@]}" \
                    -C "$SOURCE_DIR" .
                ;;

            tar.bz2)
                COPYFILE_DISABLE=1 tar -cjpf "$temporary_archive" \
                    "${TAR_EXCLUDES[@]}" \
                    -C "$SOURCE_DIR" .
                ;;

            tar.xz)
                COPYFILE_DISABLE=1 tar -cJpf "$temporary_archive" \
                    "${TAR_EXCLUDES[@]}" \
                    -C "$SOURCE_DIR" .
                ;;

            zip)
                (
                    cd "$SOURCE_DIR"
                    zip -ry "$temporary_archive" . \
                        "${ZIP_EXCLUDES[@]}" \
                        -9
                )
                ;;

            7z)
                if ! command -v 7za >/dev/null 2>&1; then
                    echo "7za not installed. Archive creation failed." >&2
                    exit 1
                fi

                (
                    cd "$SOURCE_DIR"
                    7za a "$temporary_archive" . \
                        "${SEVENZIP_EXCLUDES[@]}" \
                        -mx=9
                )
                ;;

            *)
                echo "Unsupported package format: $format" >&2
                exit 1
                ;;
        esac

        mv "$temporary_archive" "$archive"

        echo "=> Package created: $archive"
    done
}

# Run the requested operation.
case "$ACTION" in
    all)
        install_dependencies
        create_packages
        ;;
    dependencies)
        install_dependencies
        ;;
    package)
        create_packages
        ;;
esac
