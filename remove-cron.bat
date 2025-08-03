@echo off
echo Usuwanie zadan cron...

schtasks /delete /tn "UpdateRates_Peak" /f 2>nul
schtasks /delete /tn "UpdateRates_Hourly" /f 2>nul

echo Zadania usuniete!
