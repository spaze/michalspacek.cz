#!/bin/sh

# Writable dirs
ROOT_DIR="$(realpath "$(dirname "$0")/../..")"
chmod --verbose a+w "$ROOT_DIR/uploads/" "$ROOT_DIR/app/log/" "$ROOT_DIR/app/temp/" "$ROOT_DIR/app/public/www.michalspacek.cz/i/build/"

# Set sticky bit to the session directory and make it writable, even if unused
chmod --verbose 1733 "$ROOT_DIR/sessions/"

# Executable files
FILES=$(git ls-files --stage | grep "^100755" | grep --only-matching --perl-regexp "(?<=\t).*$")
for FILE in $FILES; do
	chmod a+x "$FILE"
done
