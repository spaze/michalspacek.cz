#!/bin/sh

SITEDIR="$(dirname "$0")/.."
chmod --verbose a+w "$SITEDIR/../uploads/" "$SITEDIR/log/" "$SITEDIR/temp/" "$SITEDIR/public/www.michalspacek.cz/i/build/"
