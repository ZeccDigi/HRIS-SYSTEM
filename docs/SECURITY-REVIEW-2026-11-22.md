# Hosting security fixes — 22 November 2026

Completed locally:
- Kiosk settings and connection-test/update routes require administrator authentication. Administration is disabled when credentials are absent. The saved database password is never displayed and a blank replacement preserves it.
- Locator scans accept only the current 128-bit LSQR token. Record IDs, control numbers, JSON and unsigned URLs are rejected. Legacy signed links are read-only.
- Locator writes require the main HRIS connection. The lookup and state transition run under a database transaction and row lock, with a brief duplicate-scan guard. Wrong-date, unapproved and weekend slips do not record time.
- Chiefs and RD cannot change another employee's signature. Owners and HR/Admin retain the permitted actions. Existing signature files were copied to private storage, verified byte-for-byte and removed from public storage; print/export paths remain valid.
- OTP verification has account-based attempt limits in addition to route limits. Guest resend has an account cooldown and delivery failures report an error. OTP fields are excluded from user serialization.
- PDS PDF generation supports a configured browser and standard Linux Chromium paths.
- Compatible security updates were installed for both applications. The outdated spreadsheet import bridge was replaced with direct PhpSpreadsheet import; the abandoned FPDI integration package was replaced with its maintained dependencies. The unused Electron packaging dependency was removed.
- Added production configuration template, deployment:check command, and HOSTING.md instructions.

Verification:
- HRIS: 214 tests passed, 1,607 assertions.
- Kiosk: 14 tests passed, 78 assertions.
- Both front-end production builds passed.
- Both Composer audits and both npm audits reported zero known vulnerabilities; Composer audit also reported no abandoned packages.
- PHP platform requirements passed. Actual attendance import and readable PDS workbook export were tested.
- Generated views and routes were checked. Old regression fixtures were updated to the existing user-approved policies; production validation was not weakened.

Pending on the selected hosting server:
- HTTPS/domain, production environment, debug disabled, secure cookies and proxy configuration.
- Private kiosk-to-database connection with a dedicated restricted account; no public MySQL port.
- Configure kiosk administrator credentials and verify them on the office device.
- Chromium, storage permissions, queue worker and scheduler operation.
- Mail delivery to an agreed test recipient.
- Encrypted off-server backups and restoration into an isolated staging system.
- Final role-based, physical USB-camera and document-print acceptance checks in the deployed environment.

No employee records or presentation samples were deleted. Local development remains local; passing local tests is not proof of the unconfigured hosting environment.
