#!/bin/bash

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

echo "Ustawianie cron dla Linux/Mac..."

# Tworzymy backup aktualnego crontab
crontab -l > /tmp/crontab_backup 2>/dev/null || touch /tmp/crontab_backup

# Remove old entries if they exist
grep -v "app:update-rates" /tmp/crontab_backup > /tmp/new_crontab

# Add new entries
cat << EOF >> /tmp/new_crontab
# Update rates every 5 minutes between 11:50 and 12:30
50-59/5 11 * * * cd "$PROJECT_DIR" && php bin/console app:update-rates >> update-rates.log 2>&1
*/5 12 * * * [ \$(date +\%M) -le 30 ] && cd "$PROJECT_DIR" && php bin/console app:update-rates >> update-rates.log 2>&1

# Update rates every hour
0 * * * * cd "$PROJECT_DIR" && php bin/console app:update-rates >> update-rates.log 2>&1
EOF

# Upload new crontab
crontab /tmp/new_crontab

# Clean up
rm /tmp/crontab_backup /tmp/new_crontab

echo "Cron skonfigurowany pomyślnie!"
echo "Aby sprawdzić: crontab -l"
echo "Aby usunąć: ./remove-cron.sh"
