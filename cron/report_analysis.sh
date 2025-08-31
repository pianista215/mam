#!/bin/bash
set -euo pipefail

YII_BIN="/home/pianista/mios/mam/basic/yii"
MAM_ANALYZER_HOME="/home/pianista/mios/mam-analyzer"

# Activate Python virtualenv
source "$MAM_ANALYZER_HOME/venv/bin/activate"

# Call Yii to list and assemble acars chunks, then iterate over each report.json
while read -r report_json; do
    report_dir="$(dirname "$report_json")"

    # Execute mam-analyzer over each report.json
    python3 "$MAM_ANALYZER_HOME/scripts/run.py" "$report_json" "$report_dir/analysis.json"

    echo "Generated $report_dir/analysis.json"
done < <(php "$YII_BIN" flight-report/assemble-pending-acars)

# Call Yii to process all generated analysis.json
php "$YII_BIN" flight-report/import-pending-acars-analysis
