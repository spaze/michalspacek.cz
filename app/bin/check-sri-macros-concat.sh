#!/bin/sh

echo "Wrong spaze/sri-macros concatenations (without spaces, e.g. {script foo+bar}):"

LINES=$(grep --recursive --perl-regexp --ignore-case --line-number "{(script|stylesheet)" src/)
if [ -z "$LINES" ]; then
	echo "The check has found no lines with the macros, did you run it from the app/ directory which has src/ in it?"
	exit 2
fi

BAD_LINES=$(echo "$LINES" | grep --perl-regexp "[^\s]\+|\+[^\s]")
if [ "$BAD_LINES" ]; then
	echo "$BAD_LINES" | grep --color ".+."
	exit 1
else
	echo "None, all $(echo "$LINES" | wc -l) lines have spaces around the plus sign (foo + bar, not foo+bar)"
	exit 0
fi
