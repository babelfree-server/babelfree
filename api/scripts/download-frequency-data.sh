#!/bin/bash
# Download FrequencyWords 50k files from Hermit Dave's GitHub
# Usage: bash download-frequency-data.sh
#
# Source: https://github.com/hermitdave/FrequencyWords
# Tries each language and reports which ones are available.

DATA_DIR="$(dirname "$0")/data"
BASE_URL="https://raw.githubusercontent.com/hermitdave/FrequencyWords/master/content/2018"

# All language codes from dict_languages (excluding ar which uses CAMeL)
# Map our codes to Hermit Dave's codes where they differ
declare -A CODE_MAP=(
    ["no"]="nb"        # Norwegian Bokmål
    ["zh-tw"]="zh_tw"  # Traditional Chinese (may not exist)
)

LANGS=(
    af am az be bg bn bo bs ca cs cy da el eo et eu
    fa fi fj ga gd gl gu ha haw he hi hr hu hy
    id ig is ka kk km kn ku ky
    la lb ln lo lt lv
    mg mi mk ml mn mr ms mt my
    ne no or pa pl ps ro rw
    sd si sk sl sm sn so sq sr st sv sw
    ta te tg th ti tk tl tn tr
    ug uk ur uz vi wo xh yo zh zh-tw zu
)

# Languages we already have (skip downloading)
EXISTING=(es en fr de pt it zh ja ko ru nl ar)

echo "=== FrequencyWords 50k Downloader ==="
echo "Target directory: ${DATA_DIR}"
echo ""

found=0
skipped=0
missing=0

for lang in "${LANGS[@]}"; do
    outfile="${DATA_DIR}/frequency_${lang}_50k.txt"

    # Skip if already exists
    if [ -f "$outfile" ]; then
        echo "  SKIP  ${lang} — already exists"
        ((skipped++))
        continue
    fi

    # Use mapped code for download URL
    dl_code="${CODE_MAP[$lang]:-$lang}"
    url="${BASE_URL}/${dl_code}/${dl_code}_50k.txt"

    # Try downloading
    http_code=$(curl -s -o "$outfile" -w "%{http_code}" -L --max-time 30 "$url")

    if [ "$http_code" = "200" ] && [ -s "$outfile" ]; then
        lines=$(wc -l < "$outfile")
        echo "  OK    ${lang} — ${lines} entries (from ${dl_code})"
        ((found++))
    else
        rm -f "$outfile"
        echo "  MISS  ${lang} — not available (HTTP ${http_code})"
        ((missing++))
    fi

    # Small delay to be polite
    sleep 0.2
done

echo ""
echo "=== Summary ==="
echo "  Downloaded: ${found}"
echo "  Already existed: ${skipped}"
echo "  Not available: ${missing}"
echo "Done."
