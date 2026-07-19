# Production promotion gate

Run these checks on a staging clone of production. Do not replace the production
plugin until every blocking item passes.

## Blocking reconciliation

1. Confirm that the institutional unique-student count equals a Moodle query of
   eligible student users for the same period.
2. Confirm that the student-course count equals the processed risk-record count.
3. Confirm `green + recovered + yellow + orange + red = unique students`.
4. Confirm `yellow + orange + red = students at risk` and `red = high risk`.
5. Confirm `students with follow-up / unique students = coverage`.
6. Confirm the tutor report includes an explicit `No tutor assigned` row when
   appropriate; do the same for cohort.
7. Recalculate a closed period twice and confirm identical inactivity and risk
   results.
8. Confirm that a failed notification changes delivery statistics only and does
   not create an open pedagogical case or mark a follow-up as pending.

## Access and privacy

1. A report viewer without the identified-report capability sees pseudonyms.
2. The same viewer cannot export identified critical cases or tutor names.
3. A designated manager can access the identified versions and the access is
   covered by institutional handling procedures.
4. Shared research evidence uses the pseudonymised export and an additional
   disclosure-risk review before release.

## Runtime

1. Upgrade from build `2026051601` on a database backup.
2. Run the dashboard recalculation task and inspect `local_pceinotif_runs`.
3. Purge caches, verify dashboards, CSV files and the four-page print view.
4. Run Moodle PHPUnit, scheduled tasks and supported database combinations.
5. Keep the old plugin directory and database backup available for rollback.
