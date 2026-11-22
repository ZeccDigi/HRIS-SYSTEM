# Hosting preparation

Use a Linux server with PHP compatible with composer.lock, MySQL, Node for building assets, and Chromium for PDS PDF generation. The web root must be this application's public directory. Never serve the repository, .env, storage/app/private, database backups, or logs directly.

Copy .env.production.example to a separate server environment and supply the domain, database, application key and mail credentials. Preserve the existing application key when moving existing encrypted data. Do not replace the working development .env with this template.

On the server, run composer install --no-dev --prefer-dist --optimize-autoloader and composer check-platform-reqs. Run npm ci and npm run build. After a verified database backup, run php artisan migrate --force, php artisan security:secure-signatures, php artisan storage:link, and php artisan optimize. Only profile pictures belong in public storage; signatures and supporting documents remain private.

Run php artisan deployment:check. Configure HTTPS before signing in. Keep debug disabled. Configure the trusted reverse proxy only for the chosen hosting platform. Use a dedicated database account with the minimum permissions; do not expose MySQL to the internet.

Run php artisan queue:work --tries=3 under a service manager. Run php artisan schedule:run every minute using the server scheduler. Confirm a queued notification is delivered to an agreed test address, a monthly report job runs, and a failed job can be retried. Restart workers after deployments.

The kiosk must remain on the office computer/private network with the USB camera. Connect it to the HRIS database through a private VPN/network or SSH tunnel with a dedicated account. Do not open port 3306 publicly. Locator scans require the main HRIS connection and fail visibly when unavailable. Local attendance queuing remains a distinct existing feature.

Run php artisan kiosk:set-password on the kiosk computer to choose a password (8+ characters) or PIN (6–12 digits). It stores only a hash in the private environment. Settings opens an unlock screen and access expires after five minutes; Lock Settings ends it immediately. Administration is unavailable until configured. Use HTTPS if serving beyond localhost. Settings never displays the saved database password.

Before launch, create an encrypted off-server backup containing a consistent database dump and private/public uploaded documents. Restore both into an isolated staging instance and check employee records, PDS/SALN, credit balances and file access. Set automated backup retention and prove a later restore. Never test restoration over the live database.

Release verification: exercise Employee, HR, Chief and RD permissions; an approved QR OUT/IN and wrong-date rejection; leave/CTO approvals; HR credit adjustment and history; PDS/SALN official previews; monetization salary; a PDF export; OTP delivery; and archive restoration. Do not send test notifications to employees without their agreement.

Reference: https://laravel.com/framework/docs/12.x/deployment
