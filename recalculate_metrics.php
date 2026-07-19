<?php
require_once('../../config.php');
require_login();
require_sesskey();

$context = context_system::instance();
require_capability('local/pceinotifications:recalculatemetrics', $context);

$periodtype = optional_param('periodtype', 'monthly', PARAM_ALPHA);
$periodkey = optional_param('periodkey', date('Y-m'), PARAM_TEXT);

$service = new \local_pceinotifications\local\analytics\recalculation_service();
$service->run_period_recalculation($periodtype, $periodkey, [], $USER->id);
\local_pceinotifications\local\analytics\dashboard_data_service::clear_session_cache();
$_SESSION['local_pceinotif_recalc_ok'] = 1;
$url = new moodle_url('/local/pceinotifications/advanced_dashboard.php', ['periodtype' => $periodtype, 'periodkey' => $periodkey]);
header('Location: ' . $url->out(false));
exit;
