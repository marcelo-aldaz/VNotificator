# VNotificator 1.3.9.14 — V9.4.2 stable

Release date: 2026-07-18  
Moodle component: `local_pceinotifications`  
Moodle build: `2026071806`  
License: GPL-3.0-or-later

## Highlights

- Institutional aggregation based on unique monitored students, with separate
  student-course observations.
- Deterministic period-based risk calculations.
- Human follow-up coverage separated from technical notification delivery.
- Open and closed pedagogical cases sourced from teacher novelties.
- Teacher-facing follow-up, novelty, and case-resolution workflow.
- Identified reports restricted to authorised managers and pseudonymised
  critical-case exports for research use.
- Comparable-engine trend calculation with percentage-sensitive directionality.
- Spanish, Latin American Spanish, and English interface resources.
- Dashboard action to apply a period and recalculate it in one operation.

## Production acceptance evidence

The maintainer validated installation and upgrades through the Moodle web
interface without cPanel or direct database access. The accepted workflow
covered human follow-up registration, novelty creation, institutional case
counting, case closure, preservation of history, consecutive-period
recalculation, improving trend detection, and Spanish dashboard presentation.

## Installation asset

Use the attached `VNotificator_1.3.9.14_V9.4.2_stable.zip` asset for Moodle web
installation. Do not install GitHub's automatically generated source archive as
a Moodle plugin because the repository root does not add the required
`local_pceinotifications` wrapper directory.

SHA-256:

```text
2b8f7a706eab050284f7b02ffea431e50289793024e71fa86e85d2f95cd0460e
```

## Remaining reproducibility work

Publish a compatibility matrix covering supported Moodle, PHP, database, cron,
capability, PHPUnit, Behat, and Privacy API configurations. After Zenodo assigns
the release DOI, add it to `CITATION.cff`, `codemeta.json`, and the README in a
subsequent metadata-only release.
