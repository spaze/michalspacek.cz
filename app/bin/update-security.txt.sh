#!/bin/bash

set -o pipefail

# shellcheck source=bin/colors.sh
source "$(dirname "$0")/colors.sh"

# The new date is less than a year/365 days into the future because the spec says that it is recommended that the value be less
# than a year https://www.rfc-editor.org/rfc/rfc9116#section-2.5.5-1 so this would generate a warning if it would be more,
# and when running my security.txt checker in strict mode, the warning would turn into an error.
NEW_EXPIRES=$(php -r "echo (new DateTime('first day of this month +1 year midnight UTC'))->format(DATE_RFC3339);")

# Returns 0, or 1 when the file is missing, 2 when GPG cannot read it or its signature does not verify, 3 when signing
# fails; the script exits with the highest code any of the files returned
function update() {
	echo "[${COLOR_LIGHT_GRAY}Updating${COLOR_NORMAL}] $1"
	if ! [ -f "$1" ]; then
		echo "[${COLOR_RED}Error${COLOR_NORMAL}] $1 doesn't exist"
		return 1
	fi
	UPDATED_NAME=$1-updated
	SIGNED_NAME=$UPDATED_NAME-signed

	# Remove the PGP headers and signature and update the date. GPG prints the text of a tampered file, and of one signed
	# by a key it does not know, so what decides is its exit status, not whether anything came out
	if ! gpg --decrypt --output - "$1" 2>/dev/null | sed "s/Expires: .*/Expires: $NEW_EXPIRES/" > "$UPDATED_NAME"; then
		echo "[${COLOR_RED}Error${COLOR_NORMAL}] gpg cannot read $1 or its signature does not verify, leaving it as it is"
		rm -f "$UPDATED_NAME"
		return 2
	fi
	echo "[${COLOR_GREEN}Updated${COLOR_NORMAL}] Expires in $1 updated to $NEW_EXPIRES"

	# Without the no-manu compatibility flag, a newer GPG adds the manu notation saying which GPG on which system made the signature,
	# and nothing that reads a security.txt needs to know that. An older GPG that does not know the flag, says so and carries on
	# The exit status matters as well as the file: a signed file left by an earlier run that died before the move would
	# pass the size check on its own
	if ! gpg --compatibility-flags no-manu --clear-sign --output "$SIGNED_NAME" "$UPDATED_NAME" || ! [ -s "$SIGNED_NAME" ]; then
		echo "[${COLOR_RED}Error${COLOR_NORMAL}] gpg cannot sign the updated $1, leaving it as it is"
		rm -f "$SIGNED_NAME" "$UPDATED_NAME"
		return 3
	fi
	mv "$SIGNED_NAME" "$1"
	rm "$UPDATED_NAME"
	echo "[${COLOR_GREEN}Signed${COLOR_NORMAL}] $1"
}

APP_DIR="$(dirname "$0")/.."
FAILED=0
for HOST in securitytxtvalidator.com upcwifikeys.com www.michalspacek.com www.michalspacek.cz; do
	update "$APP_DIR/src/SecurityTxt/files/$HOST/security.txt"
	STATUS=$?
	if [ "$STATUS" -gt "$FAILED" ]; then
		FAILED=$STATUS
	fi
done
exit "$FAILED"
