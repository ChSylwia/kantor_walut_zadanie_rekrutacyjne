# 💱 Zadanie Rekrutacyjne Kantor Walut

Projekt kantoru walut. Aplikacja pobiera kursy walut z API NBP, przechowuje je lokalnie, wyświetla aktualne dane na froncie oraz umożliwia ich automatyczne odświeżanie za pomocą zadań CRON.

---

## 🛠 Technologie

- PHP 8.2 (Symfony)

- JavaScript (frontend wbudowany w Symfony)

- Docker (opcjonalnie)

- CRON (Windows / Linux / Mac)

---

## 🚀 Uruchomienie aplikacji

### Lokalny serwer (np. XAMPP)

Skonfiguruj vHosta tak, aby wskazywał na katalog `public/`, np.:

    <VirtualHost *:80>

    DocumentRoot "C:/xampp/htdocs/currency-exchange/public/"

    ServerName zadanie.localhost

    </VirtualHost>

Jeśli Twoja domena to coś innego niż zadanie.localhost, zaktualizuj ją w assets/js/components/SetupCheck.js (metoda getBaseUrl()).

W katalogu głównym projektu uruchom:

    composer install

    npm install

    npm run watch --dev

Otwórz przeglądarkę i przejdź do http://zadanie.localhost.

## 💻 Instalacja CRON

**Windows**
Uruchom jako administrator:

    ./setup-cron.bat

Sprawdź zadania:

    schtasks /query | findstr UpdateRates

Usunięcie:

    ./remove-cron.bat

**Linux / Mac**

Nadaj uprawnienia i uruchom:

    chmod +x setup-cron.sh

    ./setup-cron.sh

Sprawdź zadania:

    crontab -l

Usunięcie:

    chmod +x remove-cron.sh

    ./remove-cron.sh

**Ręczne uruchomienie aktualizacji**

Możesz też pobrać kursy ręcznie:

    php bin/console app:update-rates

## ✅ Wymagania i założenia narzucone przez firmę

- Nie należy dodawać nowych zależności do composer.json / package.json
- Kod powinien być zgodny z PHP 8.2
- Mile widziane testy jednostkowe
- Ocenie podlega: architektura, czytelność, estetyka kodu i podejście
  do problemu
