#!/bin/bash
set -euo pipefail

YII_BIN="/var/www/html/yii"
MAM_HOME="/srv/mam-analyzer"

# Activate Python virtualenv
source "$MAM_HOME/venv/bin/activate"

# Call Yii to list and assemble reports
php "$YII_BIN" flight-report/list-and-assemble | while IFS=":" read -r report_id full_gz; do
    echo "🔎 Processing report $report_id"

    report_dir=$(dirname "$full_gz")

    # 1. Decompress the .gz into full_acars.json
    full_json="$report_dir/full_acars.json"
    if ! gzip -cd "$full_gz" > "$full_json"; then
        echo "❌ ERROR: Failed to decompress $full_gz" >&2
        exit 1
    fi

    # 2. Run mam-analyzer
    report_json="$report_dir/report.json"
    if ! python3 "$MAM_HOME/scripts/run.py" "$full_json" > "$report_json"; then
        echo "❌ ERROR: mam-analyzer failed for $report_id" >&2
        exit 1
    fi

    # 3. Import analysis into Yii
    if ! php "$YII_BIN" flight-report/import-report-analysis --id="$report_id"; then
        echo "❌ ERROR: Import failed for $report_id" >&2
        exit 1
    fi

    echo "✅ Report $report_id successfully processed"
done
