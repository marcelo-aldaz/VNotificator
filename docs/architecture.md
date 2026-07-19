# Architecture

VNotificator is a Moodle local plugin with four main layers:

1. Moodle pages and dashboards enforce login, course context, and capabilities.
2. Analytics services obtain source data, calculate indicators, and aggregate reports.
3. Notification services and scheduled tasks use Moodle messaging and cron APIs.
4. XMLDB tables retain configuration, delivery logs, calculated indicators, and human follow-up.

Calculated risk is a temporary, configurable indicator. The human follow-up
tables record contextual review separately from automated calculation. VTutor
integration is optional and URL-based; VNotificator must remain functional when
it is disabled.

