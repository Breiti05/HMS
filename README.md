# HMS – Handwerker-Management-System

Lager, Kanban-Nachschub, Baustellen mit Aufgaben und Material-QR-Etiketten
für Handwerksbetriebe. Mandantenfähig (mehrere Betriebe in einer Installation).
Laravel 13, PHP 8.3, Blade, Tailwind 4, Pest.

## Entwickeln

```bash
composer install
cp .env.example .env && php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed      # Demodaten: demo@hms.test / demo-passwort
npm install && npm run build
php artisan serve
```

Tests (testgetrieben: erst der Test, dann der Code):

```bash
php artisan test        # oder ./vendor/bin/pest
```

## Aufbau

- **Mandanten:** jede Tabelle hat `tenant_id`; ein globaler Scope filtert auf den
  Betrieb des angemeldeten Benutzers. Ohne Betriebskontext liefert jede Abfrage
  nichts (fail-closed). In Jobs/Befehlen: `TenantContext::run($tenantId, fn () => …)`.
  Fremdschlüssel laufen über `(tenant_id, id)`, die Datenbank verhindert also
  Verweise auf Datensätze anderer Betriebe.
- **Buchungen:** alles läuft über `App\Services\StockService`. Bewegungen sind
  unveränderlich (Korrektur nur per Gegenbuchung). Unter Mindestbestand entsteht
  automatisch eine Kanban-Karte.
- **QR-Etikett:** `HMS1|Baustellennummer|Materialnummer|Menge|Token`. Beim
  Verladescan (`/scan`) wird das Etikett gegen die Buchung geprüft, der Ausgang
  gebucht und das Material als verladen markiert. Jedes Etikett gilt nur einmal.

## Auf Hostinger bereitstellen (SSH)

1. MySQL-Datenbank in hPanel anlegen (Name, Benutzer, Kennwort notieren).
2. PHP 8.3 für die Domain wählen. Den Document Root der Domain auf `public/` des
   Projekts setzen (hPanel → Websites → Erweitert).
3. Per SSH: `git clone https://github.com/Breiti05/HMS.git` in das Domain-Verzeichnis.
4. `composer install --no-dev --optimize-autoloader`
5. `cp .env.example .env`, dann in `.env` eintragen: `APP_ENV=production`,
   `APP_DEBUG=false`, `APP_URL`, `DB_CONNECTION=mysql`, `DB_HOST`, `DB_DATABASE`,
   `DB_USERNAME`, `DB_PASSWORD`. Zugangsdaten nur dort, nie im Repository.
6. `php artisan key:generate && php artisan migrate --force`
7. Frontend: `npm ci && npm run build` (falls Node auf dem Server fehlt, lokal
   bauen und `public/build/` hochladen).
8. `php artisan config:cache && php artisan route:cache && php artisan view:cache`
9. HTTPS erzwingen (hPanel → Sicherheit → SSL). Erster Betrieb: `/registrieren`.

Update: `git pull`, `composer install --no-dev`, `php artisan migrate --force`,
`npm run build`, Caches neu bauen.

## Offene Punkte (aus dem Datenmodell-Review)

- Rollen und Rechte (Policies); `users.role` ist bisher nur gespeichert.
- Mengen als Decimal/Ganzzahl statt Float führen.
- Belegnummernkreise pro Betrieb, Kunden/Lieferanten, Preise (für Einkauf,
  Kalkulation, Verkauf).
- Reservierung von Material, Storno/Neudruck von Etiketten, Chargen/Seriennummern.
- Parallelitätstests gegen MariaDB (lokal läuft SQLite).
