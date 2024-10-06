#!/bin/bash

OPT_AUTO_INSTALL_WITH_APT_FAST="--auto-install-with-apt-fast"

function install() {
	apt-fast --yes --no-install-recommends install libxml2-utils
}


if ! hash xmllint 2>/dev/null; then
	if [ $# -eq 0 ]; then
		echo "xmllint is required but it's not installed, install it with e.g. apt install libxml2-utils, or run $0 $OPT_AUTO_INSTALL_WITH_APT_FAST"
		exit 1
	else
		if [ "$1" = "$OPT_AUTO_INSTALL_WITH_APT_FAST" ]; then
			echo "xmllint is required, will be installed automatically"
			install
			if ! install; then
				echo "Possible stale package info, updating the info and retrying the install"
				sudo apt update
				install
			fi
		fi
	fi
fi
xmllint --schema vendor/spaze/phpcs-phar/phpcs.xsd --noout phpcs.xml
