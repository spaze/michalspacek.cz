#!/bin/bash

OPT_AUTO_INSTALL_WITH_APT="--auto-install-with-apt"
OPT_AUTO_DOWNLOAD_XSD="--auto-download-xsd"
XSD_VENDOR="vendor-dev/vendor/spaze/phpcs-phar/phpcs.xsd"
XSD_URL="https://raw.githubusercontent.com/spaze/phpcs-phar/main/phpcs.xsd"
XSD_TMP="/tmp/phpcs.xsd"

AUTO_INSTALL=false
AUTO_DOWNLOAD_XSD=false

for arg in "$@"; do
	case "$arg" in
		"$OPT_AUTO_INSTALL_WITH_APT") AUTO_INSTALL=true ;;
		"$OPT_AUTO_DOWNLOAD_XSD") AUTO_DOWNLOAD_XSD=true ;;
	esac
done

function install() {
	sudo apt --yes --no-install-recommends install libxml2-utils
}


if ! hash xmllint 2>/dev/null; then
	if [ "$AUTO_INSTALL" = false ]; then
		echo "xmllint is required but it's not installed, install it with e.g. apt install libxml2-utils, or run $0 $OPT_AUTO_INSTALL_WITH_APT"
		exit 1
	else
		echo "xmllint is required, will be installed automatically"
		install
		if ! install; then
			echo "Possible stale package info, updating the info and retrying the install"
			sudo apt update
			install
		fi
	fi
fi

if [ "$AUTO_DOWNLOAD_XSD" = true ]; then
	echo "Downloading XSD from $XSD_URL"
	curl --fail --silent --show-error --location "$XSD_URL" --output "$XSD_TMP"
	XSD="$XSD_TMP"
else
	if ! [ -f "$XSD_VENDOR" ]; then
		echo "XSD not found at $XSD_VENDOR, install vendor-dev first or download it automatically with $0 $OPT_AUTO_DOWNLOAD_XSD"
		exit 2
	fi
	XSD="$XSD_VENDOR"
fi

xmllint --schema "$XSD" --noout phpcs.xml
