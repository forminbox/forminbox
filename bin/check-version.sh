#!/usr/bin/env bash
# Verifies that a release tag matches every place the version is recorded,
# so header, constant, and readme can never drift (ARCHITECTURE §9).
#
# Usage: bin/check-version.sh v0.1.0
set -euo pipefail

TAG="${1:?usage: bin/check-version.sh vX.Y.Z}"
VERSION="${TAG#v}"

fail=0

check() {
	local label="$1" actual="$2"

	if [ "$actual" = "$VERSION" ]; then
		echo "ok   ${label}: ${actual}"
	else
		echo "FAIL ${label}: '${actual}' != '${VERSION}'"
		fail=1
	fi
}

header=$(sed -n 's/^ \* Version:[[:space:]]*//p' forminbox.php | tr -d '[:space:]')
constant=$(sed -n "s/^define( 'FORMINBOX_VERSION', '\(.*\)' );$/\1/p" forminbox.php)
stable=$(sed -n 's/^Stable tag:[[:space:]]*//p' readme.txt | tr -d '[:space:]')
package=$(sed -n 's/^\t"version": "\(.*\)",$/\1/p' package.json)

check "plugin header"      "$header"
check "FORMINBOX_VERSION"  "$constant"
check "readme stable tag"  "$stable"
check "package.json"       "$package"

exit "$fail"
