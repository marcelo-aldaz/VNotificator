<?php
namespace local_pceinotifications;

defined('MOODLE_INTERNAL') || die();

class util {

    public static function cfg_int(string $name, int $default, int $min = 0, int $max = 1000): int {
        $value = (int)get_config('local_pceinotifications', $name);
        if ($value < $min || $value > $max) {
            return $default;
        }
        return $value;
    }

    public static function is_debug_enabled(): bool {
        return !empty((int)get_config('local_pceinotifications', 'debugmode'));
    }

    public static function log_debug(string $message): void {
        if (self::is_debug_enabled()) {
            mtrace('[VNotificator] ' . $message);
        }
    }


    public static function get_keywords(string $configname): array {
        $raw = (string)get_config('local_pceinotifications', $configname);
        if ($raw === '') {
            return [];
        }
        $parts = preg_split('/\R/', $raw);
        $out = [];
        foreach ($parts as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            foreach (explode(',', $line) as $p) {
                $p = trim($p);
                if ($p !== '') {
                    $out[] = $p;
                }
            }
        }
        return $out;
    }

    public static function normalize(string $s): string {
        $s = \core_text::strtolower($s);
        if (method_exists('\\core_text', 'remove_accents')) {
            $s = \core_text::remove_accents($s);
        }
        return $s;
    }

    public static function classify_text(string $text): string {
        $n = self::normalize($text);

        foreach (self::get_keywords('keywords_atpa') as $k) {
            $k = self::normalize($k);
            if ($k !== '' && \core_text::strpos($n, $k) !== false) {
                return 'atpa';
            }
        }
        foreach (self::get_keywords('keywords_tei') as $k) {
            $k = self::normalize($k);
            if ($k !== '' && \core_text::strpos($n, $k) !== false) {
                return 'tei';
            }
        }
        return 'other';
    }

    public static function classify_section_name(string $name): string {
        return self::classify_text($name);
    }



    public static function vtutor_is_url_valid(string $url): bool {
        if ($url === '') {
            return false;
        }
        // Allow relative Moodle URLs and absolute http/https URLs.
        if (strpos($url, '/') === 0) {
            return true;
        }
        return (bool)preg_match('/^https?:\/\//i', $url);
    }

    public static function is_vtutor_enabled(): bool {
        $enabled = (int)get_config('local_pceinotifications', 'vtutor_enabled');
        $template = trim((string)get_config('local_pceinotifications', 'vtutor_urltemplate'));
        return !empty($enabled) && self::vtutor_is_url_valid($template);
    }

    public static function get_vtutor_label(): string {
        $label = trim((string)get_config('local_pceinotifications', 'vtutor_label'));
        if ($label === '') {
            $label = get_string('vtutor_open', 'local_pceinotifications');
        }
        return $label;
    }

    public static function build_vtutor_url(int $courseid, int $userid = 0, int $blockid = 0, int $sectionid = 0): ?\moodle_url {
        $template = trim((string)get_config('local_pceinotifications', 'vtutor_urltemplate'));
        if (!self::vtutor_is_url_valid($template)) {
            return null;
        }

        $replacements = [
            '{courseid}' => (string)$courseid,
            '{userid}' => (string)$userid,
            '{blockid}' => (string)$blockid,
            '{sectionid}' => (string)$sectionid,
        ];
        $url = strtr($template, $replacements);
        return new \moodle_url($url);
    }

    public static function get_vtutor_link_html(int $courseid, int $userid = 0, int $blockid = 0, int $sectionid = 0, string $variant = 'primary'): string {
        if (!self::is_vtutor_enabled()) {
            return '';
        }
        $url = self::build_vtutor_url($courseid, $userid, $blockid, $sectionid);
        if (!$url) {
            return '';
        }
        $class = 'btn btn-outline-primary';
        if ($variant === 'success') {
            $class = 'btn btn-outline-success';
        } else if ($variant === 'secondary') {
            $class = 'btn btn-outline-secondary';
        }
        return \html_writer::link($url, self::get_vtutor_label(), [
            'class' => $class,
            'target' => '_blank',
            'rel' => 'noopener'
        ]);
    }




    public static function sanitize_inactivity_days($value): ?int {
        if ($value === null || $value === '') {
            return null;
        }
        $days = (int)$value;
        if ($days < 0 || $days === 999 || $days > 365) {
            return null;
        }
        return $days;
    }

    public static function page_styles(): string {
        return <<<CSS
.vtn-shell{display:flex;flex-direction:column;gap:1rem}
.vtn-hero{background:linear-gradient(135deg,#0f4c81 0%,#1d6fd8 55%,#0b88c9 100%);color:#fff;border-radius:20px;padding:1.4rem 1.5rem;box-shadow:0 14px 34px rgba(15,76,129,.18)}
.vtn-hero__title{font-size:1.45rem;font-weight:700;margin:0 0 .35rem 0}
.vtn-hero__text{margin:0;opacity:.94;max-width:920px}
.vtn-toolbar{display:flex;flex-wrap:wrap;gap:.75rem}
.vtn-kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:1rem}
.vtn-card{background:#fff;border:1px solid #e6edf5;border-radius:18px;box-shadow:0 8px 26px rgba(31,66,115,.08)}
.vtn-card__body{padding:1.1rem 1.15rem}
.vtn-kpi{position:relative;overflow:hidden}
.vtn-kpi:before{content:"";position:absolute;inset:auto -30px -30px auto;width:120px;height:120px;border-radius:50%;background:rgba(255,255,255,.15)}
.vtn-kpi--blue{background:linear-gradient(135deg,#0f4c81,#2680eb);color:#fff}
.vtn-kpi--green{background:linear-gradient(135deg,#18794e,#28a36a);color:#fff}
.vtn-kpi--orange{background:linear-gradient(135deg,#d97706,#f59e0b);color:#fff}
.vtn-kpi--red{background:linear-gradient(135deg,#b42318,#ef4444);color:#fff}
.vtn-kpi--slate{background:linear-gradient(135deg,#475467,#667085);color:#fff}
.vtn-kpi__value{font-size:1.65rem;font-weight:700;line-height:1.1}
.vtn-kpi__label{margin-top:.35rem;font-size:.95rem;opacity:.96}
.vtn-section-title{font-size:1.05rem;font-weight:700;margin:0 0 .2rem 0;color:#12344d}
.vtn-section-subtitle{font-size:.92rem;color:#5b7083;margin:0 0 1rem 0}
.vtn-filters{background:#f7fbff;border:1px solid #d8e8fb;border-radius:18px;padding:1rem 1rem .25rem}
.vtn-grid{display:grid;grid-template-columns:2fr 1fr;gap:1rem}
.vtn-grid--equal{grid-template-columns:1fr 1fr}
.vtn-meta-list{display:grid;gap:.7rem}
.vtn-meta-item{padding:.8rem 1rem;border:1px solid #e8eef5;border-radius:14px;background:#fcfdff}
.vtn-badge{display:inline-flex;align-items:center;gap:.35rem;padding:.34rem .68rem;border-radius:999px;font-size:.84rem;font-weight:600;line-height:1.1;border:1px solid transparent}
.vtn-badge--blue{background:#e7f0ff;border-color:#bfd7ff;color:#0f4c81}
.vtn-badge--green{background:#eaf7ef;border-color:#b7e0c2;color:#18794e}
.vtn-badge--orange{background:#fff2df;border-color:#ffd7a4;color:#b76600}
.vtn-badge--red{background:#fdeceb;border-color:#f3b7b3;color:#b42318}
.vtn-badge--slate{background:#f2f4f7;border-color:#d0d5dd;color:#475467}
.vtn-table{margin-bottom:0}
.vtn-table thead th{background:#eff6ff;color:#12344d;border-bottom:1px solid #d8e8fb;font-weight:700}
.vtn-table tbody tr:nth-child(even){background:#fbfdff}
.vtn-table tbody td{vertical-align:middle}
.vtn-empty{border:1px dashed #bfd7ff;background:#f7fbff;border-radius:18px;padding:1rem;color:#49657d}
.vtn-panel-note{background:#fff8e8;border:1px solid #f6d38a;color:#7a5400;border-radius:16px;padding:.85rem 1rem}
.vtn-timeline{display:grid;gap:.9rem}
.vtn-timeline__item{position:relative;padding:0 0 .9rem 1rem;border-left:3px solid #d5e6fb}
.vtn-timeline__item:last-child{padding-bottom:0}
.vtn-timeline__item:before{content:"";position:absolute;left:-7px;top:.2rem;width:11px;height:11px;border-radius:50%;background:#1d6fd8;box-shadow:0 0 0 3px #e8f1ff}
.vtn-quick-actions{display:flex;flex-wrap:wrap;gap:.5rem}
.vtn-split{display:grid;grid-template-columns:1.2fr .8fr;gap:1rem}
.vtn-highlight{background:linear-gradient(135deg,#eff6ff,#f7fbff);border:1px solid #d8e8fb;border-radius:16px;padding:1rem}
.vtn-card{transition:transform .18s ease, box-shadow .18s ease,border-color .18s ease}
.vtn-card:hover{transform:translateY(-1px);box-shadow:0 14px 34px rgba(31,66,115,.12);border-color:#cfe0f6}
.vtn-card__body{position:relative}
.vtn-shell .form-control,.vtn-shell .form-select,.teacher-profile-shell .form-control,.teacher-profile-shell .form-select{border-radius:14px;border:1px solid #d8e2f0;padding:.8rem .95rem;box-shadow:inset 0 1px 2px rgba(15,23,42,.03)}
.vtn-shell .form-control:focus,.vtn-shell .form-select:focus,.teacher-profile-shell .form-control:focus,.teacher-profile-shell .form-select:focus{border-color:#9ec5fe;box-shadow:0 0 0 .22rem rgba(29,111,216,.12)}
.vtn-shell textarea.form-control,.teacher-profile-shell textarea.form-control{min-height:126px;border-radius:18px}
.vtn-table{border-collapse:separate;border-spacing:0}
.vtn-table thead th:first-child{border-top-left-radius:14px}.vtn-table thead th:last-child{border-top-right-radius:14px}
.vtn-table tbody tr:hover{background:#f4f9ff}
.vtn-soft-panel{background:linear-gradient(135deg,#ffffff 0%,#f8fbff 100%);border:1px solid #d9e8fb;border-radius:20px;padding:1rem 1.1rem;box-shadow:0 10px 26px rgba(31,66,115,.08)}
.vtn-soft-panel--red{background:linear-gradient(135deg,#fff4f3 0%,#fff 100%);border-color:#f3c1bd}
.vtn-soft-panel--orange{background:linear-gradient(135deg,#fff7ed 0%,#fff 100%);border-color:#f7d0a4}
.vtn-soft-panel--green{background:linear-gradient(135deg,#effaf4 0%,#fff 100%);border-color:#bfe3cb}
.vtn-soft-panel--blue{background:linear-gradient(135deg,#eef5ff 0%,#fff 100%);border-color:#c8dbff}
.vtn-soft-panel__title{font-size:1rem;font-weight:700;color:#12344d;margin:0 0 .35rem 0}
.vtn-soft-panel__text{margin:0;color:#516a80}
.vtn-metric-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem}
.vtn-metric-panel{border-radius:20px;padding:1rem 1.05rem;box-shadow:0 12px 28px rgba(31,66,115,.09);border:1px solid rgba(255,255,255,.28);position:relative;overflow:hidden}
.vtn-metric-panel:before{content:"";position:absolute;right:-22px;top:-16px;width:92px;height:92px;border-radius:50%;background:rgba(255,255,255,.18)}
.vtn-metric-panel--blue{background:linear-gradient(135deg,#0f4c81,#2d77da);color:#fff}
.vtn-metric-panel--green{background:linear-gradient(135deg,#18794e,#28a36a);color:#fff}
.vtn-metric-panel--orange{background:linear-gradient(135deg,#c96b06,#f59e0b);color:#fff}
.vtn-metric-panel--red{background:linear-gradient(135deg,#b42318,#ef4444);color:#fff}
.vtn-metric-panel--slate{background:linear-gradient(135deg,#475467,#667085);color:#fff}
.vtn-metric-panel__label{font-size:.88rem;opacity:.95;margin-bottom:.35rem}
.vtn-metric-panel__value{font-size:1.55rem;font-weight:700;line-height:1.08}
.vtn-metric-panel__hint{margin-top:.35rem;font-size:.84rem;opacity:.92}
.vtn-linechart{width:100%;height:auto;display:block}
.vtn-linechart__grid{stroke:#dce7f4;stroke-width:1}
.vtn-linechart__label{font-size:11px;fill:#5b7083}
@media (max-width: 991px){.vtn-grid,.vtn-grid--equal,.vtn-split{grid-template-columns:1fr}.vtn-hero{padding:1.1rem}.vtn-card__body{padding:1rem}.vtn-metric-grid{grid-template-columns:1fr}}
CSS;
    }

    public static function badge(string $label, string $tone = 'blue'): string {
        $allowed = ['blue', 'green', 'orange', 'red', 'slate'];
        if (!in_array($tone, $allowed, true)) {
            $tone = 'blue';
        }
        return \html_writer::span(s($label), 'vtn-badge vtn-badge--' . $tone);
    }

    public static function tone_from_risk(string $risklevel): string {
        switch ($risklevel) {
            case 'red':
                return 'red';
            case 'orange':
                return 'orange';
            case 'yellow':
                return 'orange';
            case 'recovered':
                return 'blue';
            default:
                return 'green';
        }
    }

    public static function tone_from_priority(string $priority): string {
        switch ($priority) {
            case 'high':
            case 'alta':
                return 'red';
            case 'medium':
            case 'media':
                return 'orange';
            case 'low':
            case 'baja':
                return 'green';
            default:
                return 'slate';
        }
    }

    public static function tone_from_followup(string $status): string {
        switch ($status) {
            case 'pending':
            case 'none':
                return 'red';
            case 'inprogress':
                return 'orange';
            case 'attended':
                return 'green';
            default:
                return 'slate';
        }
    }

    public static function metric_panel(string $label, string $value, string $tone = 'blue', string $hint = ''): string {
        $allowed = ['blue', 'green', 'orange', 'red', 'slate'];
        if (!in_array($tone, $allowed, true)) {
            $tone = 'blue';
        }
        $out = \html_writer::start_div('vtn-metric-panel vtn-metric-panel--' . $tone);
        $out .= \html_writer::tag('div', s($label), ['class' => 'vtn-metric-panel__label']);
        $out .= \html_writer::tag('div', s($value), ['class' => 'vtn-metric-panel__value']);
        if ($hint !== '') {
            $out .= \html_writer::tag('div', s($hint), ['class' => 'vtn-metric-panel__hint']);
        }
        $out .= \html_writer::end_div();
        return $out;
    }

    public static function simple_line_chart(array $points, array $labels = [], string $stroke = '#1d6fd8', string $fill = 'rgba(29,111,216,0.12)'): string {
        $count = count($points);
        if ($count === 0) {
            return '';
        }
        $width = 420;
        $height = 170;
        $padx = 24;
        $pady = 24;
        $max = max(1, max(array_map(static function($p){ return (float)$p; }, $points)));
        $stepx = $count > 1 ? (($width - (2 * $padx)) / ($count - 1)) : 0;
        $coords = [];
        foreach ($points as $i => $point) {
            $x = $padx + ($stepx * $i);
            $y = $height - $pady - (((float)$point / $max) * ($height - (2 * $pady)));
            $coords[] = [round($x, 2), round($y, 2)];
        }
        $polyline = implode(' ', array_map(static function($c){ return $c[0] . ',' . $c[1]; }, $coords));
        $area = $polyline . ' ' . ($width - $padx) . ',' . ($height - $pady) . ' ' . $padx . ',' . ($height - $pady);
        $svg = '<svg viewBox="0 0 ' . $width . ' ' . $height . '" class="vtn-linechart" role="img" aria-hidden="true">';
        for ($i = 0; $i < 4; $i++) {
            $gy = $pady + (($height - (2 * $pady)) / 3) * $i;
            $svg .= '<line x1="' . $padx . '" y1="' . round($gy, 2) . '" x2="' . ($width - $padx) . '" y2="' . round($gy, 2) . '" class="vtn-linechart__grid" />';
        }
        $svg .= '<polygon points="' . $area . '" fill="' . $fill . '" />';
        $svg .= '<polyline points="' . $polyline . '" fill="none" stroke="' . $stroke . '" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" />';
        foreach ($coords as $idx => $c) {
            $label = $labels[$idx] ?? '';
            $svg .= '<circle cx="' . $c[0] . '" cy="' . $c[1] . '" r="5" fill="#ffffff" stroke="' . $stroke . '" stroke-width="3" />';
            if ($label !== '') {
                $svg .= '<text x="' . $c[0] . '" y="' . ($height - 6) . '" text-anchor="middle" class="vtn-linechart__label">' . s($label) . '</text>';
            }
        }
        $svg .= '</svg>';
        return $svg;
    }

    public static function simple_bar_chart(array $points, array $labels = [], string $fill = '#1d6fd8'): string {
        $count = count($points);
        if ($count === 0) {
            return '';
        }
        $width = 420;
        $height = 180;
        $padx = 28;
        $top = 22;
        $bottom = 34;
        $plotheight = $height - $top - $bottom;
        $max = max(1, max(array_map(static fn($point): float => (float)$point, $points)));
        $slot = ($width - (2 * $padx)) / $count;
        $barwidth = max(12, $slot * 0.58);
        $svg = '<svg viewBox="0 0 ' . $width . ' ' . $height . '" class="vtn-linechart vtn-barchart" role="img" aria-hidden="true">';
        for ($i = 0; $i < 4; $i++) {
            $gy = $top + ($plotheight / 3) * $i;
            $svg .= '<line x1="' . $padx . '" y1="' . round($gy, 2) . '" x2="' . ($width - $padx) . '" y2="' . round($gy, 2) . '" class="vtn-linechart__grid" />';
        }
        foreach ($points as $index => $point) {
            $value = (float)$point;
            $barheight = ($value / $max) * $plotheight;
            $x = $padx + ($slot * $index) + (($slot - $barwidth) / 2);
            $y = $top + $plotheight - $barheight;
            $label = $labels[$index] ?? '';
            $svg .= '<rect x="' . round($x, 2) . '" y="' . round($y, 2) . '" width="' . round($barwidth, 2) . '" height="' . round($barheight, 2) . '" rx="5" fill="' . s($fill) . '" />';
            $svg .= '<text x="' . round($x + ($barwidth / 2), 2) . '" y="' . max(13, round($y - 5, 2)) . '" text-anchor="middle" class="vtn-linechart__label">' . s((string)$point) . '</text>';
            if ($label !== '') {
                $svg .= '<text x="' . round($x + ($barwidth / 2), 2) . '" y="' . ($height - 8) . '" text-anchor="middle" class="vtn-linechart__label">' . s($label) . '</text>';
            }
        }
        $svg .= '</svg>';
        return $svg;
    }

    public static function get_type_label(string $type): string {
        if ($type === 'atpa') {
            return get_string('type_atpa', 'local_pceinotifications');
        }
        if ($type === 'tei') {
            return get_string('type_tei', 'local_pceinotifications');
        }
        return get_string('type_other', 'local_pceinotifications');
    }

    public static function get_state_label(string $state): string {
        switch ($state) {
            case 'configured':
                return get_string('state_configured', 'local_pceinotifications');
            case 'notification_ready':
                return get_string('state_notification_ready', 'local_pceinotifications');
            case 'notified':
                return get_string('state_notified', 'local_pceinotifications');
            case 'error':
                return get_string('state_error', 'local_pceinotifications');
            default:
                return get_string('state_detected', 'local_pceinotifications');
        }
    }

    public static function day_start(int $ts): int {
        return (int)usergetmidnight($ts);
    }

    public static function day_end(int $ts): int {
        return (int)(usergetmidnight($ts) + DAYSECS - 1);
    }

    public static function within_sendhour(): bool {
        $sendhour = (int)get_config('local_pceinotifications', 'sendhour');
        if ($sendhour < 0 || $sendhour > 23) {
            $sendhour = 7;
        }
        return (int)date('G') === $sendhour;
    }

    public static function get_calendar_status_label(string $status): string {
        switch ($status) {
            case 'synced':
                return get_string('calendarstatus_synced', 'local_pceinotifications');
            case 'error':
                return get_string('calendarstatus_error', 'local_pceinotifications');
            default:
                return get_string('calendarstatus_pending', 'local_pceinotifications');
        }
    }

    public static function determine_block_state($record): string {
        if (!empty($record->syncnote) && \core_text::strpos((string)$record->syncnote, 'Error:') === 0) {
            return 'error';
        }
        if (!empty($record->startdate)) {
            return 'notification_ready';
        }
        return 'detected';
    }

    public static function sync_course_blocks(\stdClass $course): array {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/course/lib.php');

        $stats = ['analysed' => 0, 'created' => 0, 'updated' => 0, 'removed' => 0, 'ignored' => 0, 'errors' => 0];
        $now = time();

        $sections = $DB->get_records('course_sections', ['course' => $course->id], 'section ASC', 'id,section,name,summary');
        $modinfo = get_fast_modinfo($course);

        $cmsbysection = [];
        foreach ($modinfo->get_cms() as $cm) {
            $sectionnum = (int)$cm->sectionnum;
            if (!isset($cmsbysection[$sectionnum])) {
                $cmsbysection[$sectionnum] = [];
            }
            $cmsbysection[$sectionnum][] = $cm;
        }

        $seensectionids = [];
        foreach ($sections as $sec) {
            $stats['analysed']++;
            $displayname = trim((string)$sec->name);
            if ($displayname === '') {
                $displayname = trim((string)get_section_name($course, $sec->section));
            }
            $summarytext = trim((string)$sec->summary);
            $fulltext = $displayname . ' ' . strip_tags($summarytext);

            $type = 'other';
            $source = '';
            $note = '';
            $sectioncms = $cmsbysection[(int)$sec->section] ?? [];

            foreach ($sectioncms as $cm) {
                if ($cm->modname === 'bigbluebuttonbn') {
                    $type = 'atpa';
                    $source = 'bbb';
                    $note = 'Detectado por actividad BigBlueButton.';
                    break;
                }
            }

            if ($type === 'other') {
                $type = self::classify_text($fulltext);
                if ($type !== 'other') {
                    $source = 'keywords';
                    $note = 'Detectado por palabras clave en nombre o descripción.';
                }
            }

            if ($type === 'other') {
                foreach ($sectioncms as $cm) {
                    if (!empty($cm->completion) && (int)$cm->completion > 0) {
                        $type = 'tei';
                        $source = 'completion';
                        $note = 'Detectado por actividades con finalización.';
                        break;
                    }
                }
            }

            if ($type === 'other') {
                $stats['ignored']++;
                continue;
            }

            $seensectionids[] = (int)$sec->id;
            $rec = $DB->get_record('local_pceinotif_blocks', ['courseid' => $course->id, 'sectionid' => $sec->id]);

            if ($rec) {
                $rec->sectionname = $displayname;
                $rec->blocktype = $type;
                $rec->sequenceindex = (int)$sec->section;
                $rec->syncsource = $source;
                $rec->syncnote = $note;
                $rec->lastsync = $now;
                if (empty($rec->blockstate) || in_array($rec->blockstate, ['detected', 'error', 'notification_ready'])) {
                    $rec->blockstate = self::determine_block_state($rec);
                }
                $rec->timemodified = $now;
                $DB->update_record('local_pceinotif_blocks', $rec);
                $stats['updated']++;
            } else {
                $DB->insert_record('local_pceinotif_blocks', (object)[
                    'courseid' => $course->id,
                    'sectionid' => $sec->id,
                    'sectionname' => $displayname,
                    'blocktype' => $type,
                    'sequenceindex' => (int)$sec->section,
                    'startdate' => 0,
                    'enddate' => 0,
                    'bbbcmid' => 0,
                    'blockstate' => 'detected',
                    'syncsource' => $source,
                    'syncnote' => $note,
                    'lastsync' => $now,
                    'timecreated' => $now,
                    'timemodified' => $now,
                ]);
                $stats['created']++;
            }
        }

        list($insql, $params) = $DB->get_in_or_equal($seensectionids ?: [-1], SQL_PARAMS_NAMED, 'sid', false);
        $stale = $DB->get_records_select('local_pceinotif_blocks', 'courseid = :courseid AND sectionid ' . $insql,
            ['courseid' => $course->id] + $params);
        foreach ($stale as $old) {
            $DB->delete_records('local_pceinotif_blocks', ['id' => $old->id]);
            $stats['removed']++;
        }

        return $stats;
    }
    /**
     * Synchronize Moodle course calendar from configured blocks.
     *
     * @param \stdClass $course
     * @return array
     */
    public static function sync_course_calendar(\stdClass $course): array {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/calendar/lib.php');

        $stats = ['processed' => 0, 'created' => 0, 'updated' => 0, 'removed' => 0, 'skipped' => 0, 'errors' => 0];
        $now = time();

        $blocks = $DB->get_records('local_pceinotif_blocks', ['courseid' => $course->id], 'sequenceindex ASC');
        foreach ($blocks as $block) {
            $stats['processed']++;

            if (empty($block->startdate)) {
                $block->calendarstatus = 'pending';
                $block->calendarnote = 'Falta fecha de inicio para crear evento.';
                $block->calendarupdated = $now;
                $DB->update_record('local_pceinotif_blocks', $block);

                if ($rel = $DB->get_record('local_pceinotif_events', ['blockid' => $block->id])) {
                    if (!empty($rel->eventid)) {
                        try {
                            \calendar_event::load($rel->eventid)->delete();
                        } catch (\Throwable $e) {
                        }
                    }
                    $DB->delete_records('local_pceinotif_events', ['id' => $rel->id]);
                    $stats['removed']++;
                }

                $stats['skipped']++;
                continue;
            }

            $timestart = (int)$block->startdate;
            $timeduration = 0;
            $eventname = '';
            $description = '';

            if ($block->blocktype === 'atpa') {
                $eventname = 'ATPA: ' . $block->sectionname;
                $timeduration = 45 * MINSECS;
                $description = 'Evento sincronizado por VNotificator para bloque ATPA.';
            } else if ($block->blocktype === 'tei') {
                $eventname = 'TEI: ' . $block->sectionname;
                if (!empty($block->enddate) && (int)$block->enddate > (int)$block->startdate) {
                    $timeduration = (int)$block->enddate - (int)$block->startdate;
                }
                $description = 'Evento sincronizado por VNotificator para bloque TEI.';
            } else {
                $stats['skipped']++;
                continue;
            }

            $eventdata = [
                'name' => $eventname,
                'description' => $description,
                'format' => FORMAT_HTML,
                'courseid' => $course->id,
                'groupid' => 0,
                'userid' => 0,
                'modulename' => '',
                'instance' => 0,
                'type' => CALENDAR_EVENT_TYPE_ACTION,
                'timestart' => $timestart,
                'timeduration' => $timeduration,
                'visible' => 1,
            ];

            try {
                $rel = $DB->get_record('local_pceinotif_events', ['blockid' => $block->id]);
                if ($rel && !empty($rel->eventid)) {
                    try {
                        $event = \calendar_event::load($rel->eventid);
                        $event->update($eventdata, false);
                        $rel->status = 'updated';
                        $rel->errormsg = null;
                        $rel->timemodified = $now;
                        $DB->update_record('local_pceinotif_events', $rel);
                        $stats['updated']++;
                    } catch (\Throwable $e) {
                        $event = \calendar_event::create($eventdata, false);
                        $rel->eventid = $event->id;
                        $rel->status = 'created';
                        $rel->errormsg = null;
                        $rel->timemodified = $now;
                        $DB->update_record('local_pceinotif_events', $rel);
                        $stats['created']++;
                    }
                } else {
                    $event = \calendar_event::create($eventdata, false);
                    $DB->insert_record('local_pceinotif_events', (object)[
                        'courseid' => $course->id,
                        'blockid' => $block->id,
                        'eventid' => $event->id,
                        'eventtype' => 'course',
                        'status' => 'created',
                        'errormsg' => null,
                        'timecreated' => $now,
                        'timemodified' => $now,
                    ]);
                    $stats['created']++;
                }

                $block->calendarstatus = 'synced';
                $block->calendarnote = 'Evento del curso sincronizado correctamente.';
                $block->calendarupdated = $now;
                $DB->update_record('local_pceinotif_blocks', $block);
            } catch (\Throwable $e) {
                $block->calendarstatus = 'error';
                $block->calendarnote = 'Error de calendario: ' . $e->getMessage();
                $block->calendarupdated = $now;
                $DB->update_record('local_pceinotif_blocks', $block);

                $rel = $DB->get_record('local_pceinotif_events', ['blockid' => $block->id]);
                if ($rel) {
                    $rel->status = 'error';
                    $rel->errormsg = $e->getMessage();
                    $rel->timemodified = $now;
                    $DB->update_record('local_pceinotif_events', $rel);
                } else {
                    $DB->insert_record('local_pceinotif_events', (object)[
                        'courseid' => $course->id,
                        'blockid' => $block->id,
                        'eventid' => 0,
                        'eventtype' => 'course',
                        'status' => 'error',
                        'errormsg' => $e->getMessage(),
                        'timecreated' => $now,
                        'timemodified' => $now,
                    ]);
                }

                $stats['errors']++;
            }
        }

        return $stats;
    }

}
