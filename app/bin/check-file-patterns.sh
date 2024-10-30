#!/bin/sh

echo "Wrong file patterns:"

APP_FILES=$(find src/ -regex ".*\.phpt?$")
BAD_APP_FILES=$(echo "$APP_FILES" | grep --invert-match "\.php$")
TEST_FILES=$(find tests/ -mindepth 2 -regex ".*\.phpt?$")
BAD_TEST_FILES=$(echo "$TEST_FILES" | grep --invert-match "Test.phpt")

COUNT_APP_FILES=$(echo "$APP_FILES" | wc -l)
COUNT_TEST_FILES=$(echo "$TEST_FILES" | wc -l)

if [ -z "$BAD_APP_FILES$BAD_TEST_FILES" ]; then
	echo "None, all ok ($COUNT_APP_FILES app files, $COUNT_TEST_FILES test files)"
	exit 0
else
	echo "$BAD_APP_FILES$BAD_TEST_FILES"
	echo "($COUNT_APP_FILES app files, $COUNT_TEST_FILES test files)"
	exit 1
fi
