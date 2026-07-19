# Institutional analytics data dictionary

## Units of analysis

- **Unique monitored students:** distinct Moodle users with a student role in
  at least one visible course whose course and enrolment overlap the selected
  period.
- **Student-course records:** distinct pairs of student and course processed by
  the risk layer for the period.
- **Course summary:** unique students inside that course; its student-course
  count normally equals its unique-student count.
- **Tutor/cohort summary:** unique students within the scope plus the number of
  contributing student-course records. Totals across scopes need not equal the
  institutional unique count when a student belongs to multiple scopes.

## Indicators

- **Currently not at risk:** unique students whose worst course-level state is
  `green` or `recovered`.
- **At risk:** unique students whose worst course-level state is `yellow`,
  `orange`, or `red`.
- **High risk:** unique students whose worst state is `red`.
- **Recovered:** unique students whose worst state is `recovered` after a
  previous yellow/orange/red period.
- **Open cases:** open or reviewed records in `local_pceinotif_novelty`; these
  are not notification delivery failures.
- **Human follow-up coverage:** percentage of unique students having at least
  one record in `local_pceinotif_followup` during the selected period.
- **Successful/failed notifications:** technical delivery outcomes stored in
  `local_pceinotif_log`; they are not case resolution or human follow-up.
- **Institutional trend:** comparison of the institutional semaphore with the
  previous chronological period of the same type.

## Period cut-off

Closed periods use their end timestamp as the analytical cut-off. The current
open period uses the execution time. Activity, notification delivery and human
follow-up are restricted to the selected period. The stored risk and aggregate
records are the reproducible evidence for a completed run.

## Identified information

Names in critical-case and tutor exports require
`local/pceinotifications:viewidentifiedreports`. Other report viewers receive
pseudonymous case identifiers. Pseudonyms are for operational minimisation and
must not be treated as irreversible anonymisation.
