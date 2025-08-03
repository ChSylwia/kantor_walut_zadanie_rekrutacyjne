@echo off
set PROJECT_DIR=%~dp0
echo Ustawianie zadan cron dla Windows...

REM Tworzymy zadanie - co 5 minut miedzy 11:50-12:30 (codziennie, czas trwania 40 minut)
schtasks /create /tn "UpdateRates_Peak" /tr "\"%PROJECT_DIR%run-update-rates.bat\"" /sc daily /st 11:50 /ri 5 /du 00:40 /f

REM Tworzymy zadanie - co godzine
schtasks /create /tn "UpdateRates_Hourly" /tr "\"%PROJECT_DIR%run-update-rates.bat\"" /sc hourly /f

echo Zadania utworzone pomyslnie!
echo.
echo Aby sprawdzic zadania: schtasks /query | findstr UpdateRates
echo Aby usunac zadania: remove-cron.bat
