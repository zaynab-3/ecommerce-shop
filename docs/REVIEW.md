# Repository review

Reviewed the custom controllers, services, models, middleware, database schema/seeding, routes, Blade forms, JavaScript interactions, setup scripts, Composer metadata, and GitHub Actions workflow. This is an implementation and setup review, not an independent penetration test or a production capacity certification.

## Confirmed issues fixed

- **Fresh checkout / red GitHub check:** `phpunit.xml` referenced an empty `tests/Unit` directory that existed only in the development workspace. Git does not track empty directories, so PHPUnit exited before running tests on GitHub and on a clean download. Removed the stale empty suite.
- **Second purchase in the same browser:** the completed checkout key was reused, causing the previous order to be returned for a new cart. The next checkout now gets a new key while an immediate duplicate submission remains idempotent.
- **Cart Remove button:** browser forms submit `"0"` as a string. A strict comparison with integer zero left an invalid zero-quantity cart line. Quantity is now normalized after validation, and checkout separately rejects zero/invalid quantities from existing carts.
- **Changed color options:** checkout now asks the customer to select a valid color when options were introduced after their item was added to the bag.
- **Literal search characters:** percent signs and underscores are escaped consistently for SQL LIKE searches, including SQLite.
- **Duplicate cart reads:** cart and checkout screens compute their line items once and derive the subtotal from those same rows.
- **Windows setup gap:** added the portable `php scripts/setup.php` entry point and a detailed PowerShell installation/manual testing guide. The cloud shell wrapper uses the same PHP setup implementation.
- **CI reproducibility:** the workflow runs the actual setup entry point on Ubuntu 24.04 and checks PHP 8.2, 8.3, and 8.4 independently.

- **PHP 8.2/8.3 SQLite contention:** Laravel uses deferred SQLite transactions on these versions. Concurrent readers could fail to upgrade to writers, exhausting immediate retries. Checkout and fulfillment now use bounded, jittered full-transaction retries after rollback. The stock race releases all ten processes together and reports underlying worker errors with nonzero exit status.

## Verification and boundaries

See [validation results](VALIDATION.md) for the executed checks, and [Windows setup](WINDOWS-SETUP.md) for download, startup, and manual tests. Credentials, local SQLite data, caches, vendor files, and local owner passwords remain excluded from Git.

The production boundaries remain documented: configure the real business details and policies, use an appropriate production PHP/database/cache deployment, and test against representative catalog/traffic sizes. Stock display uses polling; checkout performs immediate transactional validation. WhatsApp opens a prefilled message and the customer taps Send; the site cannot confirm that the customer sent it. Payments are cash on delivery and stock is tracked at product level, shared across colors.

## MySQL and phpMyAdmin correction

MySQL is now the environment/example default, and the portable setup requires `pdo_mysql`. The bundled local stack supplies MySQL 8.4 and phpMyAdmin; the cloud override retains database files inside the workspace. Generated local passwords stay in ignored configuration. The feature suite, deadlock rollback checks, and synchronized stock race run against isolated MySQL test databases. CI uses MySQL across PHP 8.2, 8.3, and 8.4. Tests reject an unsafe database name before resetting tables. The Windows guide covers the bundled services and an existing MySQL/phpMyAdmin server. Earlier SQLite notes above describe the previous setup, not the current database choice.
