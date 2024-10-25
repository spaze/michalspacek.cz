#!/bin/bash

source "$(dirname "$0")/colors.sh"

# The new date is less than a year/365 days into the future because the spec says that it is recommended that the value be less
# than a year https://www.rfc-editor.org/rfc/rfc9116#section-2.5.5-1 so this would generate a warning if it would be more,
# and when running my security.txt checker in strict mode, the warning would turn into an error.
NEW_EXPIRES=$(php -r "echo (new DateTime('first day of this month +1 year midnight UTC'))->format(DATE_RFC3339);")

function update() {
	echo "[${COLOR_LIGHT_GRAY}Updating${COLOR_NORMAL}] $1"
	if ! [ -f "$1" ]; then
		echo "[${COLOR_RED}Error${COLOR_NORMAL}] $1 doesn't exist"
		return
	fi
	UPDATED_NAME=$1-updated
	SIGNED_NAME=$UPDATED_NAME-signed

	# Update the date and remove the PGP headers and signature
	sed "s/Expires: .*/Expires: $NEW_EXPIRES/" "$1" | head --lines=-16 | tail --lines=+4 > "$UPDATED_NAME"
	echo "[${COLOR_GREEN}Updated${COLOR_NORMAL}] Expires in $1 updated to $NEW_EXPIRES"

	gpg --clear-sign --output "$SIGNED_NAME" "$UPDATED_NAME"
	mv "$SIGNED_NAME" "$1"
	rm "$UPDATED_NAME"
	echo "[${COLOR_GREEN}Signed${COLOR_NORMAL}] $1"
}

APP_DIR="$(dirname "$0")/.."
update "$APP_DIR/public/upcwifikeys.com/.well-known/security.txt"
update "$APP_DIR/public/www.michalspacek.com/.well-known/security.txt"
update "$APP_DIR/public/www.michalspacek.cz/.well-known/security.txt"
