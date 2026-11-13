#!/usr/bin/env bash
# One-time setup: installs the coding-standard tooling and wires up the
# pre-commit hook that validates changes against the Magento2 standard.
set -euo pipefail
cd "$(dirname "$0")/.."

(cd dev-tools && composer install)
git config core.hooksPath dev-tools/git-hooks
chmod +x dev-tools/git-hooks/pre-commit

echo "Done. Every commit will now run phpcs (Magento2 standard) against staged PHP/phtml files."
