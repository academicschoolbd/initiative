#!/usr/bin/env bash
# -----------------------------------------------------------------------
# Smart Maheshkhali — build a single deployable ZIP for cPanel uploads.
#
# Bundles the entire project into one ZIP file you can upload via
# cPanel File Manager -> Upload -> Extract. Excludes:
#   - git metadata        (.git/, .gitignore, .gitattributes)
#   - editor / OS junk    (.idea/, .vscode/, .DS_Store, Thumbs.db)
#   - the build script    (bin/)
#   - any local secrets   (config.php, *.log)
#   - any local DB state  (*.sqlite, *.sqlite-journal, -wal, -shm)
#
# Usage:
#   bin/build-deploy-zip.sh                       # default filename
#   bin/build-deploy-zip.sh smartmkhali-prod.zip  # custom filename
#
# Requirements: bash, zip (`apt install zip` / `brew install zip`).
# -----------------------------------------------------------------------

set -euo pipefail

# Move to project root regardless of where we are invoked from.
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "${SCRIPT_DIR}/.."

if ! command -v zip >/dev/null 2>&1; then
    echo "Error: 'zip' is not installed." >&2
    echo "  Debian/Ubuntu: sudo apt install zip" >&2
    echo "  macOS (brew):  brew install zip"     >&2
    exit 1
fi

VERSION="$(date +%Y%m%d-%H%M%S)"
OUTPUT="${1:-smartmaheshkhali-deploy-${VERSION}.zip}"

# Wipe any previous file with the same name so the zip command does not
# append into it (which would silently include stale entries).
rm -f -- "${OUTPUT}"

zip -r --quiet "${OUTPUT}" . \
    -x '.git/*' \
    -x '.git' \
    -x '.gitignore' \
    -x '.gitattributes' \
    -x '.idea/*' \
    -x '.vscode/*' \
    -x 'bin/*' \
    -x 'config.php' \
    -x '*.log' \
    -x '*.sqlite' \
    -x '*.sqlite-journal' \
    -x '*.sqlite-wal' \
    -x '*.sqlite-shm' \
    -x '.DS_Store' \
    -x 'Thumbs.db' \
    -x '*~' \
    -x '*.swp'

# Reassuring summary.
SIZE=$(du -h "${OUTPUT}" | cut -f1)
COUNT=$(unzip -l "${OUTPUT}" | tail -1 | awk '{print $2}')

cat <<EOF

  Built ${OUTPUT}  (${SIZE}, ${COUNT} files)

Next steps for cPanel:
  1. Open cPanel -> File Manager.
  2. Upload ${OUTPUT} into the folder you want the site to live in
     (typically public_html/ or public_html/initiative/).
  3. Right-click the ZIP -> Extract.
  4. Right-click config.example.php -> Copy -> name the copy config.php.
  5. Edit config.php and paste in your bcrypt admin password hash and
     base_path.
  6. Open https://your-domain/ and you should land on the registration
     form.

See README.md -> "cPanel / shared hosting (File Manager upload)" for
the full checklist including permission and security verification.
EOF
