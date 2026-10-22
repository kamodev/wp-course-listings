#!/usr/bin/env bash
# Check that the plugin header, VERSION constant and readme.txt Stable tag agree.
# Usage: bin/check-version.sh [expected-version]
set -euo pipefail
cd "$(dirname "$0")/.."

header="$(sed -n 's/^[[:space:]*]*Version:[[:space:]]*//p' wp-course-listings.php | head -n 1 | tr -d '\r')"
constant="$(sed -n "s/^const VERSION[[:space:]]*=[[:space:]]*'\([^']*\)';.*/\1/p" wp-course-listings.php)"
stable="$(sed -n 's/^Stable tag:[[:space:]]*//p' readme.txt | tr -d '\r')"
expected="${1:-$header}"

echo "Plugin header: $header"
echo "VERSION:       $constant"
echo "Stable tag:    $stable"

if [ "$header" != "$expected" ] || [ "$constant" != "$expected" ] || [ "$stable" != "$expected" ]; then
	echo "::error::Version numbers do not all match ${expected}" >&2
	exit 1
fi
