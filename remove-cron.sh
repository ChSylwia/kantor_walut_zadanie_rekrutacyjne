#!/bin/bash

echo "Usuwanie cron dla Linux/Mac..."

# Backup aktualnego crontab
crontab -l > /tmp/crontab_backup 2>/dev/null || touch /tmp/crontab_backup

# Usuwamy wpisy z app:update-rates
grep -v "app:update-rates" /tmp/crontab_backup > /tmp/new_crontab

# Wgrywamy nowy crontab
crontab /tmp/new_crontab

# Sprzątamy
rm /tmp/crontab_backup /tmp/new_crontab

echo "Wpisy cron usunięte!"
echo "Aktualny crontab: crontab -l"
