<?php
// Spanish (Latin America) language pack for VNotificator.
// This file largely mirrors the es language strings but exists to
// explicitly support the es_419 locale used in many Moodle sites. It
// maintains a consistent Latin American tone and terminology.

defined('MOODLE_INTERNAL') || die();

// Include base Spanish strings.
$string = array();
// Base language strings are loaded from the 'es' pack. We copy them
// explicitly here so that es_419 remains self contained.
$string = array_merge($string, [
    'pluginname' => 'VNotificator',
    'pceinotifications' => 'VNotificator',
    'settings_general' => 'Configuración general',
    'settings_general_desc' => 'Parámetros institucionales para notificaciones, bloques, calendario y seguimiento académico.',
    'cfg_enabled' => 'Habilitar motor de notificaciones',
    'cfg_enabled_help' => 'Si está deshabilitado, no se enviarán notificaciones automáticas.',
    'cfg_daysbefore' => 'Días de aviso previo',
    'cfg_daysbefore_help' => 'Número de días antes del inicio del bloque para enviar el recordatorio previo (por defecto 2).',
    'cfg_sendhour' => 'Hora institucional de envío (0-23)',
    'cfg_sendhour_help' => 'La tarea corre cada hora; solo envía cuando la hora del servidor coincide con este valor.',
    'cfg_enableemail' => 'Enviar por email',
    'cfg_enableemail_help' => 'Habilita el envío por correo electrónico.',
    'cfg_enablepopup' => 'Enviar notificación emergente',
    'cfg_enablepopup_help' => 'Habilita el envío por mensajería interna/Popup.',
    'cfg_keywords_atpa' => 'Palabras clave ATPA',
    'cfg_keywords_atpa_help' => 'Una palabra clave por línea o separadas por comas. Si el nombre o la descripción de la sección coincide, el bloque puede clasificarse como ATPA.',
    'cfg_keywords_tei' => 'Palabras clave TEI',
    'cfg_keywords_tei_help' => 'Una palabra clave por línea o separadas por comas. Si el nombre o la descripción de la sección coincide, el bloque puede clasificarse como TEI.',
]);

// Global resend notifications (mirror of es strings with same translations).
$string['globalresend'] = 'Reenvío global de notificaciones';
$string['globalresenddesc'] = 'Motor manual de reactivación institucional para reenviar recordatorios consolidados a docentes y estudiantes.';
$string['globalresendheading'] = 'Ejecución del reenvío institucional';
$string['choosecourse'] = 'Curso';
$string['choosecourse_desc'] = 'Puede reenviar para todos los cursos o limitar la acción a un solo curso.';
$string['recipient'] = 'Destinatarios';
$string['recipient_desc'] = 'Seleccione los destinatarios del reenvío.';
$string['recipients_students'] = 'Estudiantes';
$string['recipients_teachers'] = 'Docentes';
$string['recipients_both'] = 'Docentes y estudiantes';
$string['content'] = 'Contenido a reenviar';
$string['content_pending'] = 'Pendientes';
$string['content_progress'] = 'Avances';
$string['content_followup'] = 'Seguimiento';
$string['content_risk'] = 'Riesgo';
$string['resend'] = 'Reenviar notificaciones';
$string['globalresenddone'] = 'Se enviaron {$a} notificaciones.';
$string['resend_message_subject'] = 'Recordatorio del curso {$a}';
$string['resend_message_small'] = 'Tiene un nuevo recordatorio.';
$string['resend_message_greeting'] = 'Hola {$a}, a continuación un resumen de tu actividad en el curso:';
$string['resend_message_pending_student'] = '{$a} caso(s) pendiente(s) requieren tu atención.';
$string['resend_message_pending_teacher'] = '{$a} caso(s) pendiente(s) necesitan tu revisión.';
$string['resend_message_progress'] = 'Progreso: {$a->done}/{$a->total} completadas, {$a->pending} pendientes.';
$string['resend_message_followup'] = 'Estado del último seguimiento: {$a}.';
$string['resend_message_risk'] = 'Nivel de riesgo actual: {$a}.';
$string['resend_message_signature'] = 'Este es un recordatorio automático de VNotificator.';

// Note: Additional strings should be copied from the main es file as needed.

$string['privacy:metadata:log'] = 'Registros de entrega y procesamiento de notificaciones.';
$string['privacy:metadata:risk'] = 'Indicadores temporales de riesgo académico calculados desde la actividad de Moodle.';
$string['privacy:metadata:followup'] = 'Registros de seguimiento pedagógico revisados por una persona.';
$string['privacy:metadata:novelty'] = 'Casos de seguimiento académico y su validación humana.';
$string['privacy:metadata:subject'] = 'Asunto de la notificación.';
$string['privacy:metadata:tutorid'] = 'Identificador del tutor asignado.';
$string['privacy:metadata:teacherid'] = 'Identificador del docente que registró el seguimiento.';
$string['privacy:metadata:lastactivity'] = 'Momento de la actividad más reciente usada en el cálculo.';
$string['privacy:metadata:inactivitydays'] = 'Número calculado de días sin actividad.';
$string['privacy:metadata:risklevel'] = 'Categoría temporal de riesgo generada por reglas configurables.';
$string['privacy:metadata:followupstatus'] = 'Estado actual del seguimiento revisado por una persona.';
$string['privacy:metadata:timecalculated'] = 'Momento en que se calculó el indicador.';
$string['privacy:metadata:contacttype'] = 'Tipo de contacto de seguimiento.';
$string['privacy:metadata:note'] = 'Nota de seguimiento.';
$string['privacy:metadata:commitment'] = 'Compromiso registrado durante el seguimiento.';
$string['privacy:metadata:evidence'] = 'Evidencia registrada durante el seguimiento.';
$string['privacy:metadata:noveltytitle'] = 'Título del caso de seguimiento académico.';
$string['privacy:metadata:noveltydetail'] = 'Detalle del caso de seguimiento académico.';
$string['privacy:metadata:studentresponse'] = 'Respuesta del estudiante registrada para el caso.';
$string['privacy:metadata:teachervalidation'] = 'Validación docente registrada para el caso.';
$string['totalstudents'] = 'Estudiantes únicos monitoreados';
$string['activestudents'] = 'Sin riesgo actual';
$string['openalerts'] = 'Casos abiertos';
$string['coveragepercent'] = 'Cobertura de seguimiento humano';
$string['criticalcasesreport'] = 'Registros críticos priorizados';
$string['studentcourseobservations'] = 'Registros estudiante-curso';
$string['unassignedtutor'] = 'Sin tutor asignado';
$string['unassignedcohort'] = 'Sin cohorte asignada';
$string['criticalrecordsnote'] = '{$a->records} registros priorizados correspondientes a {$a->students} estudiantes únicos. El total institucional de estudiantes en riesgo alto es {$a->total}.';
$string['reportunitnote'] = 'Unidad institucional: estudiantes únicos. Los resúmenes por curso también muestran registros estudiante-curso.';
$string['applyandrecalculate'] = 'Aplicar y recalcular';
$string['riskprofiletitle'] = 'Perfil de riesgo por categoría';
$string['riskprofilesubtitle'] = 'Comparación categórica del periodo seleccionado; no constituye una serie temporal.';
$string['riskprofilemeaning'] = 'Perfil institucional de riesgo: valores más altos en prioridad y criticidad indican mayor urgencia de intervención.';
$string['identifiedreportrestricted'] = 'La exportación identificada requiere autorización institucional específica.';
$string['exportcriticalanoncsv'] = 'Exportar casos críticos seudonimizados';
$string['pceinotifications:viewidentifiedreports'] = 'Ver y exportar reportes institucionales identificados';
$string['analyticsrecalculationrequired'] = 'Los datos visibles fueron calculados por una versión analítica anterior. Ejecute el recálculo V9.4.2 antes de utilizarlos.';

return $string;
