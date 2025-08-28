#!/bin/bash
set -euo pipefail

#Configuration
YII_BIN="/home/pianista/mios/mam/basic/yii"
MAM_ANALYZER_HOME="/home/pianista/mios/mam-analyzer"

#Process pending reports
for dir in $("$YII_BIN" flight-report/list-pending); do
    echo ">>> Processing pending report $dir"

    cd "$dir"

    # Clean previous inconsistent data
    rm -rf results
    mkdir -p results

    # Concat and decompress
    cat $(ls * | sort -V) > results/full.gz
    gzip -d results/full.gz

    # Execute mam-analyzer with venv
    (
        cd "$MAM_ANALYZER_HOME"
        source venv/bin/activate
        python3 scripts/run.py "$dir/results/"* > "$dir/results/analysis.json"
        deactivate
    )

    # Importar análisis
    "$YII_BIN" flight-report/import-report-analysis --file="$dir/results/analysis.json"

    # Clean analysis data
    #rm -rf results

    echo ">>> Done $dir"
done