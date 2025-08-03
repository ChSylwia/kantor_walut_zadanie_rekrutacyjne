@echo off
cd /d "d:\Nowy folder\kantor\recruitment_task_fullstack"
php bin/console app:update-rates >> update-rates.log 2>&1
