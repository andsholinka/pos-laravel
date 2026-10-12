# Setup notes (local toolchain, Windows)

PHP, Composer and MySQL are not installed system-wide. Everything uses portable tools under `.tools`, called by full path. System PATH was not modified.

## Paths

| Tool | Version | Path |
|---|---|---|
| PHP (NTS x64, VS17) | 8.4.26 | `c:\Users\andsholinka\Documents\Kiro Aja\.tools\php\php.exe` |
| php.ini | from php.ini-development | `c:\Users\andsholinka\Documents\Kiro Aja\.tools\php\php.ini` |
| Composer | 2.10.3 | `c:\Users\andsholinka\Documents\Kiro Aja\.tools\composer\composer.phar` |
| Node / npm | 24.18.0 / 11.16.0 | on PATH (`node`, `npm`) |
| Project | Laravel 13.35.0 | `c:\Users\andsholinka\Documents\Kiro Aja\pos-umkm` |

I picked PHP 8.4 over 8.5.11 because more packages support it.

php.ini changes: `extension_dir = "c:\Users\andsholinka\Documents\Kiro Aja\.tools\php\ext"`, `memory_limit = 512M`, `date.timezone = Asia/Jakarta`.
Enabled extensions: openssl, mbstring, fileinfo, curl, pdo_sqlite, sqlite3, zip, intl, gd, pdo_mysql (all confirmed with `php -m`).

## Invocation commands (PowerShell)

Raw full paths:

```powershell
& "c:\Users\andsholinka\Documents\Kiro Aja\.tools\php\php.exe" -v
& "c:\Users\andsholinka\Documents\Kiro Aja\.tools\php\php.exe" "c:\Users\andsholinka\Documents\Kiro Aja\.tools\composer\composer.phar" install
# artisan (run from the project dir)
& "c:\Users\andsholinka\Documents\Kiro Aja\.tools\php\php.exe" "c:\Users\andsholinka\Documents\Kiro Aja\pos-umkm\artisan" migrate
# npm (run from the project dir)
npm install; npm run build
```

Helper wrappers (they forward args and the exit code; artisan.ps1 switches into pos-umkm by itself):

```powershell
& "c:\Users\andsholinka\Documents\Kiro Aja\.tools\php.ps1" -v
& "c:\Users\andsholinka\Documents\Kiro Aja\.tools\composer.ps1" require vendor/pkg:1.2.3
& "c:\Users\andsholinka\Documents\Kiro Aja\.tools\artisan.ps1" migrate
```

Dev server (long-running, start it in the background or in its own terminal):
`& "c:\Users\andsholinka\Documents\Kiro Aja\.tools\artisan.ps1" serve`

## Installed packages

Composer (exact pins for the packages I added):
- laravel/framework 13.35.0
- laravel/breeze 2.4.2 (dev, Blade stack installed with `breeze:install blade`)
- barryvdh/laravel-dompdf 3.1.2 (pulls in dompdf/dompdf 3.1.6)

npm: tailwindcss 3.4.19 (PostCSS, `tailwind.config.js`), @tailwindcss/forms 0.5.11, alpinejs 3.17.4, chart.js 4.5.1 (exact pin), vite 8.3.4, laravel-vite-plugin 3.2.0.
- I removed the skeleton's unused `@tailwindcss/vite` (v4) because Breeze runs Tailwind v3 through PostCSS.
- `resources/js/app.js` imports `chart.js/auto` and exposes `window.Chart` and `window.Alpine`.

## Config

- Database: SQLite at `pos-umkm\database\database.sqlite`, with `DB_CONNECTION=sqlite`.
- `.env` and `.env.example` set `APP_NAME="POS UMKM"`, `APP_LOCALE=id`, `APP_FAKER_LOCALE=id_ID` and `APP_TIMEZONE=Asia/Jakarta`.
- `config/app.php` timezone changed to `env('APP_TIMEZONE', 'UTC')`, because Laravel 13 hardcodes UTC there.
- `.env.example` has a commented-out MySQL block (`DB_DATABASE=pos_umkm`) for switching later.
- APP_KEY is generated in `.env`.

## Verification run

1. `composer create-project laravel/laravel`: OK. It ran key:generate, created database.sqlite and ran the initial migrations. It was built in a staging dir and then moved into pos-umkm, because `.agents` already existed there.
2. `composer require laravel/breeze:2.4.2 --dev` and `barryvdh/laravel-dompdf:3.1.2`: OK.
3. `artisan breeze:install blade`: OK. It also ran npm install and a build.
4. `npm install`: OK.
5. `artisan storage:link`: OK.
6. `artisan migrate`: exit 0. Users, cache and jobs tables are all at batch 1.
7. `npm run build`: exit 0. The CSS is 35.47 kB and the JS is 256.83 kB with Chart.js included.
8. Tinker check: `Asia/Jakarta id sqlite`.
9. `artisan test`: 25 passed (the Breeze auth tests).

Nothing has been seeded yet.
