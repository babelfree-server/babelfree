#!/bin/bash
# Enrich all 12 new languages with Wiktionary definitions.
# Run after the Spanish enrichment completes.
#
# Usage: nohup bash enrich-queue.sh > /tmp/enrich-queue.log 2>&1 &

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$SCRIPT_DIR/../.."

LANGS=(el tr sv pl fi cs id vi uk th hi he)

echo "=== Wiktionary Enrichment Queue ==="
echo "  Languages: ${LANGS[*]}"
echo "  Started: $(date)"
echo ""

for lang in "${LANGS[@]}"; do
    echo "=========================================="
    echo "  Starting: $lang ($(date))"
    echo "=========================================="

    # Run enrichment with batch of 500, process all un-enriched words
    php -d memory_limit=1G api/scripts/enrich-wiktionary.php --lang=$lang --all --batch=500 2>&1

    echo ""
    echo "  Completed: $lang ($(date))"
    echo ""

    # Brief pause between languages
    sleep 10
done

echo "=== All enrichments complete ==="
echo "  Finished: $(date)"
