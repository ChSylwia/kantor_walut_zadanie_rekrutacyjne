# Instrukcja konfiguracji CRON

Ten dokument opisuje jak skonfigurować automatyczne pobieranie kursów walut z API NBP oraz jak uruchomić aktualizację ręcznie.

## Automatyczne uruchamianie (CRON)

### Windows

1. **Uruchom skrypt konfiguracyjny jako administrator:**

   ```powershell
   ./setup-cron.bat
   ```

2. **Sprawdź czy zadania zostały utworzone:**

   ```powershell
   schtasks /query | findstr UpdateRates
   ```

3. **Usunięcie zadań (jeśli potrzebne):**
   ```powershell
   ./remove-cron.bat
   ```

**Utworzone zadania:**

- `UpdateRates_Peak` - co 5 minut między 11:50-12:30 (40 minut dziennie)
- `UpdateRates_Hourly` - co godzinę

### Linux/Mac

1. **Uruchom skrypt konfiguracyjny:**

   ```bash
   chmod +x setup-cron.sh
   ./setup-cron.sh
   ```

2. **Sprawdź czy zadania zostały utworzone:**

   ```bash
   crontab -l
   ```

3. **Usunięcie zadań (jeśli potrzebne):**
   ```bash
   chmod +x remove-cron.sh
   ./remove-cron.sh
   ```

**Utworzone zadania:**

- Co 5 minut między 11:50-12:30 każdego dnia
- Co godzinę o pełnej godzinie

## Ręczne uruchamianie

### Przez konsolę Symfony

```bash
php bin/console app:update-rates
```

## Harmonogram działania

- **Godziny szczytu:** 11:50-12:30 (co 5 minut) - 9 wykonań dziennie
- **Tryb normalny:** Co godzinę - 24 wykonania dziennie
- **Łącznie:** ~33 aktualizacje dziennie
