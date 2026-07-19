# Quality Control

## Automated checks

Run Moodle PHP linting and coding-style checks, PHPUnit, Behat, XMLDB validation,
and Privacy API tests in every supported Moodle/PHP/database combination.

## Minimum staging checklist

1. Install and upgrade from 1.3.9.12 without XMLDB errors.
2. Purge caches and load student, teacher, and administrator dashboards.
3. Run every scheduled task manually and confirm idempotency.
4. Test green, yellow, orange, red, and recovered scenarios with synthetic data.
5. Confirm notifications are not duplicated and failed delivery is logged.
6. Confirm access denial for unauthorised roles.
7. Export and delete Privacy API data for synthetic student and staff accounts.
8. Verify report exports and optional VTutor-disabled operation.
9. Record Moodle, PHP, database, operating system, and browser versions.

The included source was statically inspected in this stabilisation workflow,
but no claim of runtime validation is made until this checklist is executed in
a real Moodle staging environment.

