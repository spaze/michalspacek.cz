#!/bin/sh

# Runs a script from this directory as the web app user, e.g. `bin/as-web-app-user.sh reencrypt.php --dry-run`
# The scripts write the same compiled DI container and log files as the web app, so if executed
# directly they may fail on a dir permission error or leave files the web app can't rewrite

WEB_APP_USER="www-data"
SCRIPT="$1"

if [ -z "$SCRIPT" ]; then
	echo "Usage: $0 <script> [args], e.g. $0 reencrypt.php --dry-run"
	exit 2
fi
shift

SCRIPT_PATH="$(dirname "$0")/$SCRIPT"
if [ ! -x "$SCRIPT_PATH" ]; then
	echo "$SCRIPT_PATH not found or not executable"
	exit 2
fi

exec sudo runuser --user "$WEB_APP_USER" -- "$SCRIPT_PATH" "$@"
