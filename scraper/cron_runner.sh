#!/bin/bash
# CircuLens Cron Runner
# Runs the GTU scraper and notifier.
# Add to crontab: 0 */6 * * * /path/to/CircuLens/scraper/cron_runner.sh

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
LOG_FILE="$SCRIPT_DIR/cron.log"
LOCK_FILE="$SCRIPT_DIR/.scraper.lock"
PYTHON="${PYTHON:-python3}"

# Prevent overlapping runs
if [ -f "$LOCK_FILE" ]; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Scraper already running (lock file exists). Exiting." >> "$LOG_FILE"
    exit 0
fi

touch "$LOCK_FILE"
trap 'rm -f "$LOCK_FILE"' EXIT

cd "$SCRIPT_DIR"

echo "[$(date '+%Y-%m-%d %H:%M:%S')] Starting CircuLens scraper..." >> "$LOG_FILE"

# Use virtualenv if available
if [ -f "$SCRIPT_DIR/venv/bin/python" ]; then
    PYTHON="$SCRIPT_DIR/venv/bin/python"
fi

$PYTHON scraper.py >> "$LOG_FILE" 2>&1

echo "[$(date '+%Y-%m-%d %H:%M:%S')] Scraper finished." >> "$LOG_FILE"
