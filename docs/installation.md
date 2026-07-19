# Installation and Upgrade

## Requirements

- Moodle 4.1 or later, subject to the tested compatibility matrix.
- A PHP version supported by the selected Moodle release.
- A Moodle-supported database.
- Working Moodle cron and messaging configuration.

## Clean installation

Deploy the plugin as `<moodle-root>/local/pceinotifications`, run the Moodle
upgrade process, configure thresholds and notification delivery, and verify all
scheduled tasks. Do not enable production delivery until the staging checklist
passes with synthetic users.

## Upgrade from 1.3.9.12

Back up code and database, replace only the plugin directory, run the Moodle
upgrade process, purge caches, run cron manually once, and test privacy export
and deletion on synthetic accounts. Roll back both code and database together
if the upgrade fails.

