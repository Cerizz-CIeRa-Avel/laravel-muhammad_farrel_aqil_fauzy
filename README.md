Authentication Enterprise dan GitHub Project

Sistem API Enterprise yang dibangun dengan Laravel 11, dilengkapi dengan sistem autentikasi, role-based access control, dan audit logging.

## Instalasi
1. Clone repositori ini: `git clone (https://github.com/Cerizz-CIeRa-Avel/laravel-muhammad_farrel_aqil_fauzy)`
2. Instal dependensi: `composer install`
3. Salin .env: `cp .env.example .env`
4. Generate key: `php artisan key:generate`
5. Jalankan migrasi & seeder: `php artisan migrate --seed`

## Fitur Utama
- [x] Autentikasi (Sanctum)
- [x] Rate Limiter & Account Lockout
- [x] Role-Based Access Control (Spatie)
- [x] Audit Logging (laravel-auditing)
- [x] Email Verification
