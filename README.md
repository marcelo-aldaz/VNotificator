# VNotificator

[![DOI](https://zenodo.org/badge/DOI/10.5281/zenodo.21436708.svg)](https://doi.org/10.5281/zenodo.21436708)

VNotificator (`local_pceinotifications`) is a Moodle local plugin for academic
analytics, configurable risk indicators, notifications, and human-reviewed
pedagogical follow-up. Risk colours are temporary rule outputs: they support
review and prioritisation but do not make autonomous educational decisions.

## Stable release

- Release: `1.3.9.14-V9.4.2-stable`
- Moodle build: `2026071807`
- Minimum Moodle: 4.1 (`2022112800`)
- License: GNU GPL v3 or later
- Version DOI: <https://doi.org/10.5281/zenodo.21436708>
- Concept DOI: <https://doi.org/10.5281/zenodo.21436707>
- Optional integration: VTutor through a configurable URL template

Repository: <https://github.com/marcelo-aldaz/VNotificator>

This release was stabilised from the production baseline
`1.3.9.12-V9.4.0-candidate-stable` and accepted through controlled Moodle
web-interface validation. See `STABILIZATION_REPORT.md` for scope and remaining
automated compatibility work.

## Installation

1. Back up the Moodle database and code directory.
2. Extract the plugin contents to `<moodle-root>/local/pceinotifications`.
3. Confirm that `version.php` is directly inside that directory.
4. Run `php admin/cli/upgrade.php --non-interactive` or visit Site
   administration > Notifications.
5. Configure the plugin under Site administration > Plugins > Local plugins.
6. Confirm that Moodle cron runs regularly.
7. Execute the staging checks in `docs/quality-control.md`.
8. Complete the blocking promotion gate in `docs/production-validation.md`.

The distribution ZIP retains the historical root name
`local_pceinotifications`; the deployed Moodle directory is
`local/pceinotifications` and the Frankenstyle component is
`local_pceinotifications`.

## Main functions

- synchronises ATPA/TEI blocks from course sections;
- creates course calendar entries;
- prioritises overdue and upcoming activities;
- calculates configurable, auditable risk indicators;
- sends notifications using Moodle's messaging API;
- provides student, teacher, and institutional dashboards;
- records human follow-up, commitments, evidence, and case validation;
- exports reports and implements Moodle's Privacy API.

## Human-in-the-Loop

VNotificator does not determine sanctions, grades, enrolment, or exclusion.
Teachers and authorised staff review alerts, add context, validate cases, and
decide any intervention. See `docs/human-in-the-loop.md`.

## Documentation

- `docs/installation.md`
- `docs/architecture.md`
- `docs/quality-control.md`
- `docs/production-validation.md`
- `docs/data-dictionary.md`
- `docs/privacy.md`
- `docs/human-in-the-loop.md`
- `docs/reuse.md`

## Support and security

See `SECURITY.md`. Use the
[public issue tracker](https://github.com/marcelo-aldaz/VNotificator/issues) for
reproducible non-sensitive defects. Never include student data in an issue.

## Citation

Citation metadata is provided in `CITATION.cff`. Cite this release using
<https://doi.org/10.5281/zenodo.21436708>. Use the concept DOI
<https://doi.org/10.5281/zenodo.21436707> when referring to the software project
across all versions.
