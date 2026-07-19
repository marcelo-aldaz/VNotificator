# Stabilisation Report — 1.3.9.14 V9.4.2 Stable

## Baseline

This candidate extends `1.3.9.13-V9.4.1-stable`, which was built from the
maintainer-supplied production package `1.3.9.12-V9.4.0-candidate-stable`. The
production package was not modified.

## Changes

- corrected the analytical population and reporting cut-off;
- separated technical delivery from pedagogical follow-up and open cases;
- changed institutional aggregation to unique students;
- exposed student-course observations separately;
- added explicit unassigned tutor/cohort buckets;
- restricted identified reports and added pseudonymised exports;
- corrected institutional trend and consolidation timestamp handling;
- restored the teacher-facing resolution form for open and reviewed cases;
- validated case ownership before accepting resolution updates;
- limited trend comparison to prior aggregates created by V9.4.2;
- prevented closed novelties without legacy closure timestamps from being
  counted as open and timestamped novelties created directly as closed;
- added an Apply and recalculate action that uses the period currently entered
  in the dashboard filter;
- made same-semaphore trends sensitive to changes of at least five percentage
  points in the unique high-risk student rate;
- replaced temporal curve wording with explicit categorical-profile wording;
- added regression tests and production acceptance criteria.

## Production UI evidence inherited from RC2

The maintainer completed a controlled Moodle web-interface test without cPanel
or direct database access. One human follow-up and one open novelty changed the
July 2026 institutional aggregate from 20 to 21 monitored students, from 0 to
1 open case, and from 0% to 4.8% human follow-up coverage. The test also exposed
the missing resolution form corrected in RC3. A subsequent closure test exposed
the legacy timestamp inconsistency corrected in this RC4 candidate.

## Validation required before promotion

- Run the checks in `docs/production-validation.md` on a staging clone.
- Recalculate at least two closed periods to validate trends.
- Reconcile unique students and student-course observations with Moodle source
  enrolments.
- Confirm that delivery failures do not appear as pedagogical cases or follow-up.
- Confirm access denial for identified reports outside the authorised manager
  role.

## Static validation completed

- all 52 PHP files parsed successfully with an independent PHP parser;
- XMLDB `install.xml` parsed successfully as XML;
- `CITATION.cff` parsed successfully as YAML;
- static language keys referenced by PHP were present in English and Spanish;
- duplicate language keys were not found in `en`, `es`, or `es_419`;
- semantic assertions confirmed period filters, unique-student aggregation,
  human follow-up sourcing, case sourcing, and identified-report capability;
- obvious embedded credential and private-key patterns were not found.

## Automated runtime validation not completed locally

This environment did not include a runnable Moodle/PHP stack. Installation,
upgrade, cron, message delivery, capabilities, PHPUnit, Behat, database
portability, and Privacy API behaviour remain recommended for a reproducible
staging compatibility matrix.

## Production web-interface acceptance completed

The maintainer installed successive candidates through Moodle without cPanel,
validated a complete human-follow-up and novelty lifecycle, reconciled open-case
and coverage aggregates, recalculated consecutive V9.4.2 periods, confirmed a
percentage-sensitive improving trend, and verified the Spanish dashboard. This
stable promotion changes release metadata only from the accepted RC5 logic.

## Known publication gaps

Before a JORS submission, publish the source repository, add a stable repository
URL and security contact, archive the exact release with a DOI, execute and
publish the compatibility matrix, and cite the archived DOI in `CITATION.cff`.
