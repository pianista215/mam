#!/bin/bash
set -euo pipefail

YII_BIN="/home/pianista/mios/mam/basic/yii"
MAM_ANALYZER_HOME="/home/pianista/mios/mam-analyzer"

# Call Yii to list and assemble acars chunks, then iterate over each report.json
while read -r report_json; do
    report_dir="$(dirname "$report_json")"

    # Execute mam-analyzer over each report.json
    uv run --project "$MAM_ANALYZER_HOME" python "$MAM_ANALYZER_HOME/scripts/run.py" "$report_json" "$report_dir/analysis.json" --context "$report_dir/context.json"

    echo "Generated $report_dir/analysis.json"
    rm "$report_json"
    rm -f "$report_dir/context.json"
done < <(php "$YII_BIN" flight-report/assemble-pending-acars)

# Call Yii to process all generated analysis.json
php "$YII_BIN" flight-report/import-pending-reports-analysis
