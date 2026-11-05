# Library Management System (Laravel port of the Odoo `LMS` module)

A full-stack Laravel 11/12 + Blade application. No extra Composer packages. Tailwind, Chart.js and PDF.js load from CDNs.

> **Status:** written by reading the Odoo module's source. It has **not been run or tested yet**. The environment it was built in had no PHP. Expect to fix small typos on first boot.

## Install

```bash
composer create-project laravel/laravel lms
cd lms
# copy everything from this folder over the new project (overwrite bootstrap/app.php and routes/console.php)
cp .env.example .env   # configure DB + MAIL_* ; set QUEUE_CONNECTION=sync (or run a worker)
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

Scheduler (cron): `* * * * * cd /path/to/lms && php artisan schedule:run >> /dev/null 2>&1`
Queue (if not `sync`): `php artisan queue:work`

Demo logins (password `password`): `admin@lms.test`, `manager@lms.test`, `qa@lms.test`, `user@lms.test`, `reader@lms.test`

## Odoo -> Laravel mapping

| Odoo | Laravel |
|---|---|
| `lms.book` (+ states, write() locks, ISBN sequence, versioning wizard) | `Book` model, `BookController`, `books` table |
| `lms.loan` (issue/return/lost, fines) | `Loan` model, `LoanController` |
| `lms.member`, `lms.author`, `lms.category`, `lms.department` (hierarchical) | models + `Admin\*Controller` |
| `lms.viewer`, `lms.book.read.history` | `Viewer`, `ReadHistory`, `EbookController` (PDF.js reader) |
| `lms.report` SQL view + QWeb report | `ReportController` (filters, CSV, printable summary) |
| Dashboard OWL + Chart.js | `admin/dashboard` + JSON endpoints |
| `res.users.log` login report | `login_logs` table |
| Chatter | `activity_logs` (`LogsActivity` trait) |
| Security groups (User/QA/Manager/Admin) | `users.role` + `role:` middleware (hierarchical) |
| `ir.cron` jobs | `routes/console.php` + `app/Console/Commands` |
| Website catalog / feedback / PDF viewer controllers | `CatalogController`, `HomeController`, `EbookController` |

## Access rights (per-user CRUD)
Admin -> **Access rights** (sidebar). Pick a user, change their role/status, and tick Read / Create / Update / Delete per module (books, loans, members, authors, categories, departments, reports, feedback).
* No custom row = the role defaults in `config/lms.php` (`permissions`). *Reset to role defaults* removes the override.
* Admins can't be restricted; you can't demote or deactivate yourself.
* Routes are guarded by `perm:<module>,<action>` (see `routes/web.php`); buttons are hidden with `$user->canDo('books','delete')`.
* Book workflow buttons (submit/approve/release/obsolete) also need the QA / Manager role.

## Dashboard
Gradient KPI cards link to the filtered lists. **Snapshot** downloads the whole dashboard (cards + charts, timestamped) as a PNG; **Refresh** reloads the numbers.

## Roles
`reader` (portal, e-books only) < `user` < `qa` < `manager` < `admin`. Access matrix is documented at the top of the back-office block in `routes/web.php`.

## Deliberate differences from the Odoo code
* State changes are enforced in code (e.g. only *Approved* can be released). In Odoo this was only done by button visibility.
* In Odoo several `create()`/`write()` overrides returned before their audit/chatter code ran. Here the audit trail works.
* The "mark lost" guard in Odoo was effectively a no-op. Here only *Issued* loans can be marked lost.
* The viewer enforces release state, expiry and secure-ebook login *before* counting a read.
* Staff can preview non-released e-books (QA must read them before approving). Readers/guests cannot.
* The Odoo digest was hard-coded to `@gmail.com` users; here it goes to all staff with an e-mail.
* E-book PDFs are stored on the private disk and streamed through the app (never publicly addressable).

## Not ported
Odoo chatter followers / scheduled activities, pivot/graph/kanban back-end views, Odoo website theme, QWeb PDF engine (use the browser "Save as PDF" on the printable report).
