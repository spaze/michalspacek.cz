#!/bin/bash

# Dumps each MySQL schema in $DBS to app/db/, schema-only for most tables.
# Data is dumped for the $LOOKUP_DATA allow-list (fixed enumerations).
# Output is deterministic so commits show real schema changes only.
#
# Override the binaries if needed:
# MYSQL="sudo mysql" MYSQLDUMP="sudo mysqldump" make dump-db-schema

set -euo pipefail

MYSQL="${MYSQL:-mysql}"
MYSQLDUMP="${MYSQLDUMP:-mysqldump}"

# Support MYSQL="sudo mysql" or MYSQL="mysql --password"
read -ra MYSQL_CMD <<< "$MYSQL"
read -ra MYSQLDUMP_CMD <<< "$MYSQLDUMP"

REPO_ROOT="$(realpath "$(dirname "$0")/..")"
OUT_DIR="$REPO_ROOT/db"
mkdir --parents "$OUT_DIR"

# Databases to dump, in output order. The output filename is the db name with the
# michalspacek_cz_ prefix stripped, or "default" for the unprefixed main db.
DBS=(
    michalspacek_cz
    michalspacek_cz_pulse
    michalspacek_cz_upckeys
)

# Tables whose data is dumped alongside the schema - empty list = schema-only,
# anything not listed here stays schema-only: no operational, user-derived, or admin-mutated data.
declare -A LOOKUP_DATA=(
    [michalspacek_cz]="\
        auth_token_types \
        languages \
        locales \
        training_application_status \
        training_application_status_flow \
        training_date_status \
        twitter_card_types"
    [michalspacek_cz_pulse]=""
    [michalspacek_cz_upckeys]=""
)

# Strip AUTO_INCREMENT=N (row-count leak), DEFINER='user'@'host' (operator account
# leak on views/triggers/routines), and the per-table character-set save/restore wrappers.
# Add blank lines between table blocks and between distinct INSERT blocks.
scrub() {
    local FILE="$1"
    sed -E \
        -e 's/ AUTO_INCREMENT=[0-9]+//g' \
        -e "s/DEFINER=\`[^\`]+\`@\`[^\`]+\` //g" \
        -e '/^\/\*![0-9]+ SET (@saved_(cs_client|cs_results|col_connection)|character_set_(client|results)|collation_connection) /d' \
        "$FILE" | awk '
            NR > 1 && /^CREATE TABLE/ { print "" }
            NR > 1 && /^INSERT INTO `/ {
                split($0, a, "`")
                if (a[2] != prev_table) print ""
                prev_table = a[2]
            }
            { print }
        ' > "$FILE.scrubbed"
    mv "$FILE.scrubbed" "$FILE"
}

for DB in "${DBS[@]}"; do
    if [[ "$DB" == "michalspacek_cz" ]]; then
        name="default"
    else
        name="${DB#michalspacek_cz_}"
    fi
    OUT="$OUT_DIR/$name.sql"
    TMP="$(mktemp)"
    trap 'rm -f "$TMP"' EXIT

    echo "Dumping $DB -> ${OUT#"$REPO_ROOT"/}"

    # Tables in alphabetical order so diffs reflect schema changes, not whatever order SHOW TABLES happens to return.
    # Capture explicitly so auth/connection failures abort the script instead of looking like "no tables found".
    if ! TABLES_RAW="$("${MYSQL_CMD[@]}" --skip-column-names --execute="SHOW TABLES" "$DB")"; then
        echo "Error: failed to list tables in $DB" >&2
        exit 1
    fi
    if [[ -z "$TABLES_RAW" ]]; then
        echo "Error: no tables in $DB" >&2
        exit 1
    fi
    mapfile -t TABLES < <(sort <<< "$TABLES_RAW")

    echo -e "# Tables" > "$TMP"

    # Schema for every table, plus routines/triggers/views.
    "${MYSQLDUMP_CMD[@]}" \
        --no-data \
        --routines \
        --triggers \
        --compact \
        --skip-dump-date \
        --no-tablespaces \
        "$DB" "${TABLES[@]}" >> "$TMP"

    # Data for $LOOKUP_DATA TABLES, one row per INSERT for readable diffs.
    # mysqldump dumps in the order tables are passed, so no runtime sort needed.
    LOOKUP="${LOOKUP_DATA[$DB]:-}"
    if [[ -n "$LOOKUP" ]]; then
        echo -e "\n# Data" >> "$TMP"
        read -ra LOOKUP_ARR <<< "$LOOKUP"
        "${MYSQLDUMP_CMD[@]}" \
            --no-create-info \
            --skip-extended-insert \
            --compact \
            --skip-dump-date \
            --no-tablespaces \
            --single-transaction \
            "$DB" "${LOOKUP_ARR[@]}" >> "$TMP"
    else
        echo -e "\n# No data" >> "$TMP"
    fi

    mv "$TMP" "$OUT"
    scrub "$OUT"
done

echo "OK: files in ${OUT_DIR#"$REPO_ROOT"/}/*.sql"
