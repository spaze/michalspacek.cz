#!/bin/sh

echo "Dependabot directories that no longer exist:"

ROOT=$(git rev-parse --show-toplevel)
CONFIG="$ROOT/.github/dependabot.yml"
if [ ! -f "$CONFIG" ]; then
	echo "Cannot find $CONFIG"
	exit 1
fi

MISSING=""
DIRS=$(grep -E '^[[:space:]]*directory:' "$CONFIG" | sed -E 's/^[[:space:]]*directory:[[:space:]]*//; s/"//g')
for dir in $DIRS; do
	rel="${dir#/}"
	[ -z "$rel" ] && rel="."
	if [ ! -e "$ROOT/$rel" ]; then
		MISSING="$MISSING $dir"
	fi
done

if [ -z "$MISSING" ]; then
	echo "None, all watched directories exist"
	exit 0
else
	echo "$MISSING"
	echo "A directory: in dependabot.yml points outside the repo, so Dependabot silently stops updating it. A rename probably left dependabot.yml behind, like site/ to app/ did."
	exit 1
fi
