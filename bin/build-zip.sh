#!/usr/bin/env bash
# Assembles the release ZIP honoring .distignore. Expects production
# artifacts to exist already:
#   composer install --no-dev --optimize-autoloader
#   npm ci && npm run build
#
# Output: dist/forminbox.zip (contains a single forminbox/ directory).
set -euo pipefail

cd "$(dirname "$0")/.."

test -f build/admin.js || { echo "build/admin.js missing — run npm run build"; exit 1; }
test -f vendor/autoload.php || { echo "vendor/autoload.php missing — run composer install"; exit 1; }

if [ -d vendor/phpunit ]; then
	echo "vendor/ contains dev dependencies — run composer install --no-dev"
	exit 1
fi

rm -rf dist
mkdir -p dist/forminbox

rsync -a --exclude-from=.distignore --exclude=dist ./ dist/forminbox/

( cd dist && zip -rq forminbox.zip forminbox )

echo "dist/forminbox.zip:"
unzip -l dist/forminbox.zip | tail -3

# The ZIP must never contain sources, tests, or CI config.
for forbidden in client/ tests/ .github/ node_modules/ package-lock.json composer.lock; do
	if unzip -l dist/forminbox.zip | grep -q "forminbox/${forbidden}"; then
		echo "FAIL: ${forbidden} leaked into the ZIP"
		exit 1
	fi
done

echo "ZIP contents verified clean."
