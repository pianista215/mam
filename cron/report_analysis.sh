#!/bin/bash
set -euo pipefail

# List pending reports
for dir in $(php /var/www/html/yii flight-report/list-pending); do
    echo "Processing pending report $dir"

    cd "$dir"

    # Clean previous inconsistent data
    rm -rf results
    mkdir results

    # Concat and gzip
    cat $(ls * | sort -V) > results/full.gz
    gzip -d results/full.gz

    # Execute mam-analyzer
    python3 /srv/mam-analyzer/scripts/run.py results/* > results/analysis.json

    # Import analysis
    php /var/www/html/yii flight-report/import-report-analysis --file=results/analysis.json

    # Remove temporal data
    rm -rf results

done
