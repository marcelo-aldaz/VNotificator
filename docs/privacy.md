# Privacy

VNotificator processes Moodle user, course, notification, activity, risk, and
human follow-up data. Access is controlled through Moodle contexts and
capabilities. This release expands the Privacy API provider to declare, locate,
export, and delete subject-related records in notification, risk, follow-up,
and novelty tables.

Institutions remain responsible for defining lawful purpose, retention,
authorised roles, transparency notices, and escalation procedures. Public test
data must be synthetic. Before production deployment, test privacy export and
deletion for student, teacher, tutor, and case-closer roles.

System-level audit fields such as threshold creator and analytics-run executor
require institutional retention rules and should be reviewed separately before
a claim of complete regulatory compliance.

