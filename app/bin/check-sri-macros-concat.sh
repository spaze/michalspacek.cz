#!/bin/sh

echo "Wrong spaze/sri-macros concatenations (without spaces, e.g. {script foo+bar}):"

BAD_LINES=$(grep --recursive --perl-regexp --ignore-case --line-number "{(script|stylesheet)" app/ | grep --perl-regexp "[^\s]\+|\+[^\s]")
if [ "$BAD_LINES" ]; then
	echo "$BAD_LINES" | grep --color ".+."
	exit 1
else
	echo "None, all have spaces around the plus sign (foo + bar, not foo+bar)"
	exit 0
fi
