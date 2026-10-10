# AeroBook automated tests

Run all existing and new suites from the project root:

```powershell
powershell -ExecutionPolicy Bypass -File tests/run.ps1
```

Override executable paths if PHP or Node are not on PATH:

```powershell
./tests/run.ps1 -Php 'C:/path/to/php.exe' -Node 'C:/path/to/node.exe'
```

Requirements: PHP 8.3+ with PDO MySQL and fileinfo, Node 22+, and running MySQL using the credentials in `.env`. No Composer, npm packages, or database schema changes are needed. The new PHP suites create randomly named `aerobook_test_<hex>` databases using the real schema and drop only those databases on completion or shutdown. The configured database account therefore needs CREATE and DROP DATABASE permissions. The HTTP server binds a temporary loopback port and explicitly refuses a non-test database. Application data is preserved. Existing dashboard tests use read-only queries and session-local temporary tables on the configured database.

| Suite | Coverage |
| --- | --- |
| `setup.php` | Disposable first-time MySQL/HTTP install, token/HTTPS/CSRF gates, credential secrecy, literal dotenv passwords, existing-data preservation, exclusive config writes, permanent locks and failed connection recovery. Also creates/removes a temporary MySQL user, so this development-only suite needs CREATE USER and GRANT privileges. |
| `admin-workspace.php`, `admin-workspace.test.cjs` | Seven admin sections, guided collapsible forms, deliberate route choices, contextual help, valid row form ownership and CSRF, payment decision controls, dashboard priorities, search/status filters, natural sorting and pagination |
| `application.integration.php` | CSRF, sessions, authentication, hash upgrades, customer profile privacy; Admin airline/airport/flight validation and CRUD; seat generation/status; booking totals, stale fare checks, transaction rollback; seat ownership/conflicts; payment validation, rejection/resubmission, verification; ticket creation, cancellation; foreign keys and unique constraints |
| `application.functional.php` | Actual HTTP routing, guest/customer/admin access across every protected page, CSRF on every POST form, registration and login validation, session invalidation, security headers, escaping, passenger validation; complete flight-to-ticket and cancellation flow; IDOR checks; live seat/chart JSON; admin actions, proof traversal, 404/405 responses |
| `database.seed.php` | Real seed and additional-flight import, counts and cabin layouts, repeated imports, preservation of bookings/reservations/blocked seats, empty model/chart states |
| Existing PHP suites | Public Home access, shared UI markup/layout, MySQL flight availability and chart aggregation/date boundaries |
| Node suites | Chart rendering/filter/refresh behavior, mobile toasts, navigation, live flight availability, seat selection and individual ticket printing; per-tab Back history, loop prevention, POST safety, expired booking steps, role changes and storage fallback |

The runner also lints PHP application, configuration, test and front-controller files. Each suite returns a nonzero exit code on failure; the runner continues through suites and reports all failures. Run any PHP suite separately with `php tests/<file>.php`, or JavaScript tests with `node --test tests/*.test.cjs`.

Test session files and HTTP logs are retained under ignored `tests/.runtime/<test-database>/` for diagnosis. These contain synthetic fixtures only. The new tests use future-relative flight dates so booking tests do not expire. Chart tests respect the existing 8 October 2026 start date.

These are automated behavioral checks, not a claim of 100% code coverage or a complete security audit. JavaScript uses mocked DOM/browser APIs; rendered mobile layouts, real browser printing/PDF output, and concurrent multi-process load still need browser/load testing. No test sends mail, contacts payment providers, or changes production business records.
