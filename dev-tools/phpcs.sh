#!/usr/bin/env bash
# Run the Magento2 coding-standard check against this module.
# Usage: dev-tools/phpcs.sh [phpcs options...]
set -euo pipefail
cd "$(dirname "$0")/.."
exec ./dev-tools/vendor/bin/phpcs --standard=phpcs.xml.dist "$@"
