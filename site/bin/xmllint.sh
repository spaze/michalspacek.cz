#!/bin/bash

OPT_AUTO_INSTALL_WITH_APT_FAST="--auto-install-with-apt-fast"

if ! hash xmllint 2>/dev/null; then
	if [ $# -eq 0 ]; then
		echo "xmllint is required but it's not installed, install it with e.g. apt install libxml2-utils, or run $0 $OPT_AUTO_INSTALL_WITH_APT_FAST"
		exit 1
	else
		if [ "$1" = "$OPT_AUTO_INSTALL_WITH_APT_FAST" ]; then
			echo "xmllint is required, will be installed automatically"
			apt-fast --yes --no-install-recommends install libxml2-utils
		fi
	fi
fi
xmllint --schema vendor/squizlabs/php_codesniffer/phpcs.xsd --noout phpcs.xml
