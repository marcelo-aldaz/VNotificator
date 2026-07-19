<?php
defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'VNotificator';

$string['pceinotifications'] = 'VNotificator';
$string['settings_general'] = 'Configuración general';
$string['settings_general_desc'] = 'Parámetros institucionales para notificaciones, bloques, calendario y seguimiento académico.';

$string['cfg_enabled'] = 'Habilitar motor de notificaciones';
$string['cfg_enabled_help'] = 'Si está deshabilitado, no se enviarán notificaciones automáticas.';

$string['cfg_daysbefore'] = 'Días de aviso previo';
$string['cfg_daysbefore_help'] = 'Número de días antes del inicio del bloque para enviar el recordatorio previo (por defecto 2).';

$string['cfg_sendhour'] = 'Hora institucional de envío (0-23)';
$string['cfg_sendhour_help'] = 'La tarea corre cada hora; solo envía cuando la hora del servidor coincide con este valor.';

$string['cfg_enableemail'] = 'Enviar por email';
$string['cfg_enableemail_help'] = 'Habilita el envío por correo electrónico.';

$string['cfg_enablepopup'] = 'Enviar notificación emergente';
$string['cfg_enablepopup_help'] = 'Habilita el envío por mensajería interna/Popup.';

$string['cfg_keywords_atpa'] = 'Palabras clave ATPA';
$string['cfg_keywords_atpa_help'] = 'Una palabra clave por línea o separadas por comas. Si el nombre o la descripción de la sección coincide, el bloque puede clasificarse como ATPA.';

$string['cfg_keywords_tei'] = 'Palabras clave TEI';
$string['cfg_keywords_tei_help'] = 'Una palabra clave por línea o separadas por comas. Si el nombre o la descripción de la sección coincide, el bloque puede clasificarse como TEI.';

$string['task_send_notifications'] = 'Enviar notificaciones ATPA/TEI (PCEI)';
$string['task_sync_blocks'] = 'Sincronizar bloques ATPA/TEI desde secciones (PCEI)';

$string['sync_blocks'] = 'Sincronizar desde secciones';
$string['sync_blocks_done'] = 'Sincronización completada.';

$string['blocks_title'] = 'Bloques ATPA/TEI del curso';
$string['no_blocks'] = 'No se detectaron bloques. Verifique que los nombres o descripciones de las secciones contengan palabras clave ATPA/TEI, o agregue una actividad BigBlueButton en secciones ATPA (detección heurística).';

$string['col_section'] = 'Sección';
$string['col_type'] = 'Tipo';
$string['col_start'] = 'Inicio';
$string['col_end'] = 'Fin';
$string['col_actions'] = 'Acciones';

$string['type_atpa'] = 'ATPA (Sincrónica)';
$string['type_tei'] = 'TEI (Asincrónica)';
$string['type_other'] = 'Otro';

$string['edit_block'] = 'Editar';
$string['save_changes'] = 'Guardar cambios';
$string['block_saved'] = 'Bloque actualizado.';

$string['confirm_resync'] = '¿Sincronizar ahora desde las secciones del curso? Se actualizarán nombres y clasificación.';

$string['privacy:metadata'] = 'Este plugin almacena trazas mínimas de notificaciones enviadas.';
$string['privacy:metadata:userid'] = 'ID del usuario notificado.';
$string['privacy:metadata:courseid'] = 'ID del curso.';
$string['privacy:metadata:blockid'] = 'ID del bloque.';
$string['privacy:metadata:notiftype'] = 'Tipo de notificación.';
$string['privacy:metadata:timesent'] = 'Momento del envío.';
$string['privacy:metadata:success'] = 'Si el envío fue exitoso.';
$string['privacy:metadata:errormsg'] = 'Mensaje de error si falló.';

$string['notifications_center'] = 'Centro de Notificaciones';
$string['my_notifications'] = 'Mis notificaciones';
$string['course_notifications'] = 'Notificaciones del curso';
$string['manage_blocks'] = 'Gestionar bloques';
$string['filter_blocktype'] = 'Tipo de bloque';
$string['filter_notiftype'] = 'Tipo de notificación';
$string['filter_status'] = 'Estado';
$string['filter_search'] = 'Buscar (asunto)';
$string['filter_search_ph'] = 'Escriba para buscar…';
$string['filter_apply'] = 'Aplicar';
$string['filter_all'] = 'Todos';
$string['notif_pre'] = 'Aviso previo';
$string['notif_today'] = 'Hoy';
$string['notif_late'] = 'Rezago';
$string['notif_manual'] = 'Manual';
$string['status_sent'] = 'Enviada';
$string['status_failed'] = 'Fallida';
$string['notifications_table_caption'] = 'Listado de notificaciones (más recientes primero)';
$string['no_notifications'] = 'No se encontraron notificaciones con los filtros seleccionados.';
$string['notifications_limit_note'] = 'Se muestran hasta 200 registros más recientes. Use filtros para acotar resultados.';
$string['col_time'] = 'Fecha/Hora';
$string['col_block'] = 'Bloque';
$string['col_notiftype'] = 'Tipo';
$string['col_subject'] = 'Asunto';
$string['col_status'] = 'Estado';
$string['col_user'] = 'Usuario';

$string['progress_title'] = 'Progreso del curso';
$string['progress_completion_disabled'] = 'La finalización (completion) está desactivada en este curso. Actívela para calcular pendientes/realizadas.';
$string['progress_my_course'] = 'Mi progreso en este curso';
$string['progress_course_summary'] = 'Resumen del curso';
$string['progress_students'] = 'Estudiantes: {$a}';
$string['progress_total'] = 'Actividades evaluables: {$a}';
$string['progress_done'] = 'Realizadas: {$a}';
$string['progress_todo'] = 'Pendientes: {$a}';
$string['progress_avg_total'] = 'Prom. total: {$a}';

// Reenvío global de notificaciones.
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
$string['progress_avg_done'] = 'Prom. realizadas: {$a}';
$string['progress_avg_todo'] = 'Prom. pendientes: {$a}';
$string['progress_top_caption'] = 'Top 10 estudiantes con más pendientes';
$string['progress_todo_short'] = 'Pendientes';
$string['progress_done_short'] = 'Realizadas';
$string['progress_total_short'] = 'Total';
$string['progress_chart_title'] = 'Cumplimiento {$a}%';
$string['progress_chart_desc'] = 'Realizadas {$a->done}, pendientes {$a->todo}, total {$a->total}.';

$string['col_state'] = 'Estado';
$string['col_source'] = 'Fuente sync';
$string['state_detected'] = 'Detectado';
$string['state_configured'] = 'Configurado';
$string['state_notification_ready'] = 'Listo para notificar';
$string['state_notified'] = 'Notificado';
$string['state_error'] = 'Error';
$string['sync_blocks_done_stats'] = 'Sincronización completada. Analizadas: {$a->analysed}; creadas: {$a->created}; actualizadas: {$a->updated}; eliminadas: {$a->removed}; ignoradas: {$a->ignored}.';

$string['col_calendar'] = 'Calendario';
$string['sync_calendar'] = 'Sincronizar calendario';
$string['confirm_calendar_sync'] = '¿Sincronizar eventos del calendario del curso para los bloques configurados?';
$string['sync_calendar_done_stats'] = 'Sincronización de calendario completada. Procesados: {$a->processed}; creados: {$a->created}; actualizados: {$a->updated}; eliminados: {$a->removed}; omitidos: {$a->skipped}; errores: {$a->errors}.';
$string['calendarstatus_pending'] = 'Pendiente';
$string['calendarstatus_synced'] = 'Sincronizado';
$string['calendarstatus_error'] = 'Error';
$string['task_sync_calendar'] = 'Sincronizar calendario de VNotificator';

$string['col_activity'] = 'Actividad';
$string['col_module'] = 'Módulo';
$string['col_duedate'] = 'Fecha límite';
$string['col_taskstatus'] = 'Estado académico';
$string['col_completed_count'] = 'Cumplidas';
$string['col_pending_count'] = 'Pendientes';
$string['task_duedate_none'] = 'Sin fecha';
$string['task_table_empty'] = 'No se encontraron actividades evaluables en este curso.';
$string['task_table_mine_caption'] = 'Mis actividades del curso con seguimiento de cumplimiento y fechas';
$string['task_table_course_caption'] = 'Actividades del curso con seguimiento de cumplimiento y vencimiento';
$string['taskstatus_completed'] = 'Cumplida';
$string['taskstatus_pending_nodate'] = 'Pendiente sin fecha';
$string['taskstatus_pending_ontime'] = 'Pendiente en tiempo';
$string['taskstatus_pending_soon'] = 'Próxima a vencer';
$string['taskstatus_pending_overdue'] = 'Vencida';
$string['taskcount_nodate'] = 'Pendientes sin fecha: {$a}';
$string['taskcount_ontime'] = 'Pendientes en tiempo: {$a}';
$string['taskcount_soon'] = 'Próximas a vencer: {$a}';
$string['taskcount_overdue'] = 'Vencidas: {$a}';

$string['bbbcmid_label'] = 'BBB cmid (opcional)';

$string['settings_vtutor'] = 'Integración con VTutor';
$string['settings_vtutor_desc'] = 'Configuración opcional para abrir VTutor desde VNotificator sin dependencia obligatoria.';
$string['cfg_vtutor_enabled'] = 'Habilitar integración con VTutor';
$string['cfg_vtutor_enabled_help'] = 'Si está habilitada y existe una URL plantilla válida, VNotificator mostrará botones para abrir VTutor.';
$string['cfg_vtutor_urltemplate'] = 'URL plantilla de VTutor';
$string['cfg_vtutor_urltemplate_help'] = 'Use placeholders: {courseid}, {userid}, {blockid}, {sectionid}. Ejemplo: /blocks/ai_tutor/view.php?id={courseid}&userid={userid}';
$string['cfg_vtutor_label'] = 'Texto del botón VTutor';
$string['cfg_vtutor_label_help'] = 'Texto visible del botón para abrir VTutor.';
$string['vtutor_open'] = 'Abrir VTutor';
$string['vtutor_support_title'] = 'Apoyo con VTutor';
$string['vtutor_support_desc'] = 'Puede abrir VTutor para recibir orientación sobre este curso, sus bloques y sus actividades pendientes.';
$string['vtutor_notifications_desc'] = 'Desde aquí puede pasar a VTutor para revisar dudas, pendientes y apoyo sobre sus notificaciones.';
$string['col_action'] = 'Acción';


$string['col_priority'] = 'Prioridad';
$string['progress_risk'] = 'Riesgo académico';
$string['risk_low'] = 'En buen estado';
$string['risk_medium'] = 'Atención';
$string['risk_high'] = 'Riesgo académico alto';
$string['alert_overdue'] = 'Tiene {$a} actividad(es) vencida(s).';
$string['alert_due_soon'] = 'Tiene {$a} actividad(es) próxima(s) a vencer.';
$string['alert_no_pending'] = 'No tiene actividades pendientes.';

$string['messageprovider:pcei_notice'] = 'Avisos de VNotificator';

$string['cfg_warningdays'] = 'Días para alerta amarilla';
$string['cfg_warningdays_help'] = 'Número de días antes del vencimiento para marcar una actividad como próxima a vencer.';
$string['cfg_topstudentslimit'] = 'Límite de estudiantes en ranking';
$string['cfg_topstudentslimit_help'] = 'Número máximo de estudiantes a mostrar en el ranking de pendientes.';
$string['cfg_debugmode'] = 'Modo depuración controlada';
$string['cfg_debugmode_help'] = 'Si se activa, VNotificator registrará trazas resumidas en cron para diagnóstico técnico.';
$string['error_end_before_start'] = 'La fecha de fin no puede ser anterior a la fecha de inicio.';
$string['error_bbbcmid_invalid'] = 'El BBB cmid no puede ser negativo.';

$string['student_dashboard'] = 'Panel del estudiante';
$string['student_dashboard_desc'] = 'Aquí puede revisar sus prioridades académicas y el estado de sus actividades.';
$string['student_dashboard_go_progress'] = 'Ir a progreso del curso';
$string['teacher_dashboard'] = 'Panel docente';
$string['teacher_dashboard_desc'] = 'Aquí puede revisar el seguimiento del curso, las actividades críticas y las notificaciones recientes.';
$string['teacher_dashboard_go_progress'] = 'Ir a progreso del curso';
$string['teacher_dashboard_go_notifications'] = 'Ir a notificaciones del curso';
$string['admin_dashboard'] = 'Panel institucional';
$string['teacher_recent_notifications'] = 'Notificaciones recientes del curso';
$string['admin_recent_notifications'] = 'Notificaciones recientes del sistema';
$string['admin_courses_with_failures'] = 'Cursos con fallos de notificación';
$string['admin_kpi_courses'] = 'Cursos monitoreados: {$a}';
$string['admin_kpi_sent'] = 'Notificaciones enviadas: {$a}';
$string['admin_kpi_failed'] = 'Errores de envío: {$a}';
$string['col_course'] = 'Curso';
$string['task_evaluate_rules'] = 'Evaluar reglas de notificación por perfiles';
$string['task_profile_summaries'] = 'Enviar resúmenes por perfiles';

$string['course_dashboard'] = 'Dashboard del curso';
$string['course_dashboard_desc'] = 'Resumen ejecutivo y accesos rápidos del curso.';
$string['course_dashboard_go_progress'] = 'Ir a progreso del curso';
$string['course_dashboard_go_notifications'] = 'Ir a notificaciones del curso';
$string['course_dashboard_recent_logs'] = 'Notificaciones recientes del curso';


$string['task_recalculate_dashboard_metrics'] = 'Recalcular métricas del dashboard avanzado';


$string['admin_dashboard_desc'] = 'Centro institucional de monitoreo y acceso a dashboards.';
$string['pluginsettings'] = 'Configuración del plugin';
$string['pluginsettings_desc'] = 'Abrir la configuración general de VNotificator.';
$string['recalculate_desc'] = 'Ejecutar el recálculo de métricas del dashboard avanzado para el periodo actual.';
$string['open'] = 'Abrir';

$string['analyticsnotready'] = 'Las tablas analíticas de 8C aún no están creadas. Actualice el plugin o ejecute la actualización de base de datos para continuar.';




$string['trenddirection'] = 'Tendencia';
$string['risklevel'] = 'Riesgo';
$string['inactivitydays'] = 'Días de inactividad';
$string['followupstatus'] = 'Seguimiento';
$string['dashboardadvanced'] = 'Dashboard institucional avanzado';
$string['dashboardadvancedsubtitle'] = 'Monitoreo analítico institucional del periodo seleccionado';
$string['institutionalkpis'] = 'Indicadores institucionales';
$string['institutionalsemaphore'] = 'Semáforo institucional';
$string['riskdistribution'] = 'Distribución del riesgo';
$string['criticalcases'] = 'Casos críticos';
$string['recalculatemetrics'] = 'Recalcular métricas';
$string['metricsrecalculated'] = 'Métricas recalculadas correctamente.';
$string['applyfilters'] = 'Aplicar filtros';
$string['periodtype'] = 'Tipo de periodo';
$string['periodkey'] = 'Periodo';
$string['monthly'] = 'Mensual';
$string['bimonthly'] = 'Bimestral';
$string['finalcycle'] = 'Fin de ciclo';
$string['totalstudents'] = 'Estudiantes únicos monitoreados';
$string['activestudents'] = 'Sin riesgo actual';
$string['studentsatrisk'] = 'En riesgo';
$string['highriskstudents'] = 'Riesgo alto';
$string['recoveredstudents'] = 'Recuperados';
$string['openalerts'] = 'Casos abiertos';
$string['coveragepercent'] = 'Cobertura de seguimiento humano';
$string['suggestedaction'] = 'Acción sugerida';
$string['student'] = 'Estudiante';
$string['currentstatus'] = 'Estado actual';
$string['notavailable'] = 'No disponible';
$string['nocriticalcases'] = 'No existen casos críticos para los filtros seleccionados.';
$string['green'] = 'Verde';
$string['yellow'] = 'Amarillo';
$string['orange'] = 'Naranja';
$string['red'] = 'Rojo';
$string['action_normal'] = 'Seguimiento normal';
$string['action_preventive'] = 'Contacto preventivo';
$string['action_priority'] = 'Intervención prioritaria';
$string['action_immediate'] = 'Intervención inmediata';
$string['risk_green'] = 'Normal';
$string['risk_yellow'] = 'Preventivo';
$string['risk_orange'] = 'Prioritario';
$string['risk_red'] = 'Crítico';
$string['improving'] = 'Mejorando';
$string['stable'] = 'Estable';
$string['worsening'] = 'Empeorando';
$string['category'] = 'Categoría';
$string['count'] = 'Cantidad';
$string['percentage'] = 'Porcentaje';


$string['followup_none'] = 'Sin seguimiento';
$string['followup_pending'] = 'Pendiente';
$string['followup_inprogress'] = 'En seguimiento';
$string['followup_attended'] = 'Atendido';

$string['criticalcaseshint'] = 'Para acelerar la carga inicial, los casos críticos detallados se cargan bajo demanda.';
$string['loadcriticalcases'] = 'Cargar casos críticos';
$string['hidecriticalcases'] = 'Ocultar casos críticos';

$string['institutionalreport'] = 'Reporte institucional';
$string['exportcsv'] = 'Exportar CSV';
$string['viewreport'] = 'Ver reporte institucional';
$string['reportgeneratedat'] = 'Reporte generado';
$string['reportsummary'] = 'Resumen institucional consolidado del periodo seleccionado.';
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
$string['riskdistributionreport'] = 'Reporte de distribución de riesgo';
$string['downloadreport'] = 'Descargar reporte';
$string['generatedon'] = 'Generado el: {$a}';
$string['reportobservations'] = 'Observaciones automáticas';
$string['backtodashboard'] = 'Volver al dashboard';
$string['exportsummarycsv'] = 'Exportar resumen CSV';
$string['exportcriticalcsv'] = 'Exportar críticos CSV';
$string['exportdistributioncsv'] = 'Exportar distribución CSV';
$string['reportperiodlabel'] = 'Periodo: {$a}';
$string['metric'] = 'Métrica';
$string['value'] = 'Valor';
$string['percent'] = 'Porcentaje';
$string['reportobs_red'] = 'Predomina una condición crítica institucional; se recomienda priorizar seguimiento inmediato.';
$string['reportobs_yellow'] = 'La institución se encuentra en seguimiento preventivo; conviene monitorear la evolución del riesgo.';
$string['reportobs_green'] = 'La situación general es estable en el periodo analizado.';
$string['reportobs_riskpercent'] = 'Porcentaje total de estudiantes en riesgo: {$a}';

$string['printreport'] = 'Imprimir / guardar PDF';
$string['printreporthint'] = 'Use esta vista para imprimir o guardar como PDF desde el navegador.';
$string['executivesummary'] = 'Resumen ejecutivo';
$string['latestconsolidatedlabel'] = 'Último consolidado: {$a}';
$string['printfooter'] = 'Reporte generado por VNotificator.';


$string['reportsummary_formal'] = 'Informe institucional de monitoreo analítico para seguimiento, revisión directiva y respaldo documental.';
$string['exportcoursecsv'] = 'Exportar resumen por curso';
$string['exporttutorcsv'] = 'Exportar resumen por tutor';
$string['exportcohortcsv'] = 'Exportar resumen por cohorte';
$string['reportobs_red_formal'] = 'Se identifica predominio de riesgo crítico en el periodo analizado. Se recomienda priorizar acciones inmediatas de seguimiento institucional.';
$string['reportobs_orange_formal'] = 'Se observa concentración relevante de casos con necesidad de intervención prioritaria. Conviene focalizar acciones de acompañamiento.';
$string['reportobs_yellow_formal'] = 'Se identifican señales preventivas que requieren monitoreo focalizado para evitar escalamiento del riesgo.';
$string['reportobs_green_formal'] = 'La institución presenta una condición general estable en el periodo analizado, con comportamiento mayoritariamente favorable.';
$string['reportobs_riskpercent_formal'] = 'El porcentaje total de estudiantes en riesgo durante el periodo analizado es de {$a}.';
$string['reportobs_coverage_formal'] = 'La cobertura de seguimiento registrada para el periodo corresponde a {$a}.';
$string['invalidreporttype'] = 'El tipo de reporte solicitado no es válido.';
$string['nodatatoreport'] = 'No existen datos disponibles para generar este reporte.';


$string['profile_tracking_desc'] = 'Seleccione la vista de seguimiento según el perfil de usuario.';
$string['teacher_view'] = 'Vista de docente';
$string['teacher_view_desc'] = 'Acceda al seguimiento por curso desde la perspectiva docente.';
$string['student_view'] = 'Vista de estudiante';
$string['student_view_desc'] = 'Acceda al seguimiento individual desde la perspectiva del estudiante.';
$string['open_teacher_tracking'] = 'Abrir seguimiento docente';
$string['open_student_tracking'] = 'Abrir seguimiento estudiantil';
$string['no_teacher_courses'] = 'No se encontraron cursos con acceso de seguimiento docente para este usuario.';
$string['no_student_courses'] = 'No se encontraron cursos con acceso de seguimiento estudiantil para este usuario.';


$string['student_dashboard_desc_v914'] = 'Aquí puede revisar su ficha individual de trazabilidad, su estado actual y las acciones sugeridas para dar continuidad a su proceso.';
$string['student_current_risk'] = 'Riesgo actual';
$string['student_current_priority'] = 'Prioridad actual';
$string['student_traceability_title'] = 'Ficha individual de trazabilidad';
$string['student_last_activity'] = 'Última actividad registrada';
$string['student_last_signal'] = 'Última señal registrada';
$string['student_notifications_title'] = 'Notificaciones registradas';
$string['student_notifications_summary'] = 'Enviadas: {$a->sent} / exitosas: {$a->success}';
$string['student_recommendation_title'] = 'Recomendación actual';
$string['student_evidence_level'] = 'Nivel de evidencia';
$string['student_progress_snapshot'] = 'Resumen de progreso';
$string['student_progress_percent'] = 'Cumplimiento estimado: {$a}';
$string['student_quick_actions'] = 'Acciones rápidas sugeridas';
$string['student_action_review_pending'] = 'Revise sus actividades pendientes';
$string['student_action_check_notifications'] = 'Revise sus notificaciones';
$string['student_action_contact_teacher'] = 'Comuníquese con su docente si requiere apoyo';
$string['evidence_high'] = 'Alta';
$string['evidence_medium'] = 'Media';
$string['evidence_low'] = 'Baja';
$string['recommendation_student_red'] = 'Se recomienda atender de inmediato las actividades pendientes y establecer contacto con su docente o tutor para recibir acompañamiento.';
$string['recommendation_student_orange'] = 'Se recomienda priorizar las actividades pendientes del curso y revisar sus notificaciones recientes.';
$string['recommendation_student_yellow'] = 'Se recomienda mantener continuidad en el curso y evitar acumulación de tareas pendientes.';
$string['recommendation_student_recovered'] = 'Su situación muestra mejoría. Mantenga el ritmo de trabajo y continúe el seguimiento de sostenimiento.';
$string['recommendation_student_green'] = 'Su estado actual es favorable. Continúe con su ritmo de trabajo y revise periódicamente su progreso.';
$string['print_save_pdf'] = 'Imprimir / guardar PDF';

$string['teacher_student_profile'] = 'Ficha individual del estudiante';
$string['teacher_student_profile_desc'] = 'Seguimiento individual del estudiante desde la perspectiva docente.';
$string['back_to_teacher_dashboard'] = 'Volver al panel docente';
$string['teacher_student_traceability'] = 'Trazabilidad individual del estudiante';
$string['teacher_recommended_actions'] = 'Acciones sugeridas para seguimiento docente';
$string['teacher_action_contact_student'] = 'Contactar al estudiante';
$string['teacher_action_review_pending'] = 'Revisar pendientes del curso';
$string['teacher_action_register_followup'] = 'Registrar seguimiento pedagógico';
$string['teacher_total_students'] = 'Estudiantes del curso';
$string['teacher_high_priority_students'] = 'Prioridad alta';
$string['teacher_student_tracking_title'] = 'Seguimiento de estudiantes';
$string['teacher_student_tracking_desc'] = 'Listado priorizado de estudiantes del curso con acceso a su ficha individual.';
$string['view_student_profile'] = 'Ver ficha';

$string['followup_register_title'] = 'Registrar seguimiento pedagógico';
$string['followup_register_desc'] = 'Registre una intervención breve para dejar trazabilidad del acompañamiento docente.';
$string['followup_contacttype'] = 'Tipo de contacto';
$string['followup_contact_message'] = 'Mensaje';
$string['followup_contact_call'] = 'Llamada';
$string['followup_contact_virtual_meeting'] = 'Reunión virtual';
$string['followup_contact_family'] = 'Contacto con representante';
$string['followup_contact_review_only'] = 'Revisión interna';
$string['followup_note'] = 'Observación de seguimiento';
$string['followup_note_help'] = 'Describa brevemente el hallazgo, el contacto o el acuerdo pedagógico alcanzado.';
$string['followup_note_required'] = 'Debe ingresar una observación de seguimiento.';
$string['followup_invalid_date'] = 'La fecha de próxima revisión no es válida.';
$string['followup_nextreview'] = 'Próxima revisión';
$string['followup_last_registered'] = 'Último seguimiento registrado';
$string['followup_nextreview_short'] = 'Próxima revisión: {$a}';
$string['followup_save_button'] = 'Guardar seguimiento';
$string['followup_saved'] = 'Seguimiento guardado correctamente.';
$string['followup_history_title'] = 'Historial de seguimiento';
$string['followup_registered_by'] = 'Registrado por {$a}';
$string['no_followup_records'] = 'Todavía no existen registros de seguimiento para este estudiante.';


$string['teacher_filters_title'] = 'Filtros del panel docente';
$string['filter_risk'] = 'Riesgo';
$string['filter_priority'] = 'Prioridad';
$string['filter_followup'] = 'Seguimiento';
$string['filter_clear'] = 'Limpiar filtros';
$string['filter_search_student'] = 'Buscar estudiante';
$string['filter_search_student_placeholder'] = 'Nombre del estudiante';
$string['filter_followup_overdue'] = 'Revisión vencida';
$string['filter_followup_pendingreview'] = 'Con próxima revisión';
$string['filter_followup_withoutfollowup'] = 'Sin seguimiento';
$string['teacher_filtered_results'] = 'Resultados filtrados: {$a}';
$string['teacher_no_filtered_students'] = 'No hay estudiantes que coincidan con los filtros aplicados.';
$string['teacher_followup_overdue'] = 'Revisiones vencidas';
$string['teacher_without_followup'] = 'Sin seguimiento';
$string['followup_review_overdue_short'] = 'revisión vencida';
$string['priority_high'] = 'Alta';
$string['priority_medium'] = 'Media';
$string['priority_preventive'] = 'Preventiva';
$string['priority_ordinary'] = 'Ordinaria';
$string['risk_recovered'] = 'Recuperado';

$string['commitment_section_title'] = 'Compromiso y acuerdo de intervención';
$string['commitment_title'] = 'Compromiso acordado';
$string['commitment_title_help'] = 'Describa el acuerdo o la acción concreta que debe cumplirse después del seguimiento.';
$string['commitment_responsible'] = 'Responsable';
$string['commitment_responsible_placeholder'] = 'Ejemplo: estudiante, docente, representante';
$string['commitment_date'] = 'Fecha compromiso';
$string['commitment_date_short'] = 'Compromiso: {$a}';
$string['commitment_status'] = 'Estado de cumplimiento';
$string['commitment_status_notstarted'] = 'No iniciado';
$string['commitment_status_inprogress'] = 'En proceso';
$string['commitment_status_completed'] = 'Cumplido';
$string['commitment_evidence'] = 'Evidencia o verificación';
$string['commitment_evidence_help'] = 'Registre evidencia breve del acuerdo, respuesta o verificación de cumplimiento.';


$string['admin_novelties'] = 'Bitácora administrativa de novedades';
$string['admin_novelties_desc'] = 'Revise las novedades registradas por docentes para dar seguimiento institucional a casos priorizados.';
$string['admin_kpi_novelties'] = 'Novedades registradas: {$a}';
$string['novelty_section_title'] = 'Bitácora de novedades';
$string['novelty_section_desc'] = 'Registre una novedad breve vinculada a la alerta o al caso del estudiante. Esta bitácora puede ser revisada por administración.';
$string['novelty_title'] = 'Título de la novedad';
$string['novelty_title_placeholder'] = 'Ejemplo: estudiante no asistió a la tutoría programada';
$string['novelty_detail'] = 'Detalle de la novedad';
$string['novelty_detail_help'] = 'Describa de forma breve y clara la novedad observada entre docente y estudiante.';
$string['novelty_status'] = 'Estado de la novedad';
$string['novelty_status_open'] = 'Abierta';
$string['novelty_status_reviewed'] = 'Revisada';
$string['novelty_status_closed'] = 'Cerrada';
$string['novelty_visibility'] = 'Visibilidad';
$string['novelty_visibility_internal'] = 'Uso interno institucional';
$string['novelty_visibility_shared'] = 'Compartida con estudiante/docente';
$string['novelty_save_button'] = 'Guardar novedad';
$string['novelty_saved'] = 'La novedad fue registrada correctamente.';
$string['novelty_required'] = 'Debe ingresar el título y el detalle de la novedad.';
$string['novelty_history_title'] = 'Historial de novedades';
$string['novelty_none'] = 'No existen novedades registradas para este caso.';
$string['novelty_count'] = 'Novedades registradas';
$string['novelty_total'] = 'Total de novedades';
$string['novelty_quick_button'] = 'Registrar novedad';


$string['notification_type_overdue_student'] = 'Actividad vencida del estudiante';
$string['notification_type_overdue_teacher'] = 'Actividad vencida para seguimiento docente';
$string['notification_type_upcoming_student'] = 'Actividad próxima del estudiante';
$string['notification_type_upcoming_teacher'] = 'Actividad próxima para seguimiento docente';
$string['notification_type_summary_student'] = 'Resumen de notificación al estudiante';
$string['notification_type_summary_teacher'] = 'Resumen de notificación al docente';
$string['notification_type_generic'] = 'Notificación del curso';
$string['teacher_recent_notifications_desc'] = 'Revise las alertas recientes del curso y abra directamente la ficha del estudiante para registrar acciones de seguimiento.';
$string['open_case_record'] = 'Abrir ficha del caso';
$string['novelty_section_desc_v920'] = 'Registre una novedad breve vinculada a la alerta o al caso del estudiante. Puede dejarla como uso interno o compartirla para que también se visualice en el panel del estudiante.';
$string['admin_novelties_desc_v920'] = 'Revise las novedades registradas por docentes para dar seguimiento institucional a casos priorizados. Las novedades marcadas como compartidas también se visualizan en el panel del estudiante.';
$string['student_shared_actions_title'] = 'Acciones compartidas del caso';
$string['student_shared_actions_desc'] = 'Aquí puede revisar las acciones, orientaciones o novedades que su docente ha compartido para acompañar su caso en este curso.';
$string['student_shared_actions_teacher'] = 'Docente que registró la acción';
$string['student_shared_actions_date'] = 'Fecha del registro';
$string['student_shared_actions_none'] = 'Todavía no existen acciones compartidas visibles para este caso.';

$string['case_resolution_title'] = 'Cierre y validación del caso';
$string['case_resolution_desc'] = 'Actualice el estado del caso, registre la respuesta observada del estudiante y deje la validación docente para cierre o seguimiento institucional.';
$string['case_resolution_case'] = 'Caso o novedad a actualizar';
$string['case_resolution_status'] = 'Estado actualizado del caso';
$string['case_resolution_student_response'] = 'Respuesta o avance del estudiante';
$string['case_resolution_teacher_validation'] = 'Validación docente';
$string['case_resolution_save_button'] = 'Guardar cierre o validación';
$string['case_resolution_saved'] = 'La actualización del caso fue registrada correctamente.';
$string['case_resolution_closed_on'] = 'Cierre registrado el {$a}';
$string['case_resolution_closed_on_label'] = 'Fecha de cierre';

$string['currentrisk'] = 'Riesgo actual';
$string['teacher_current_priority'] = 'Prioridad actual';
$string['teacher_last_followup_recorded'] = 'Último seguimiento registrado';
$string['teacher_next_review'] = 'Próxima revisión';

$string['admin_novelties_filters_title'] = 'Filtros de la bitácora administrativa';
$string['filter_course'] = 'Curso';


$string['institutionalreportprint'] = 'Reporte institucional para impresión';
$string['institutionalreportprint_eyebrow'] = 'Versión profesional para impresión y PDF';
$string['institutionalprintsummary'] = 'Documento ejecutivo preparado para impresión institucional, archivo PDF y respaldo documental con conservación visual de colores, tarjetas, gráficos y tablas.';
$string['institutionalprintdocumentlabel'] = 'Tipo de documento';
$string['institutionalprintdocumentvalue'] = 'Reporte institucional profesional listo para impresión';
$string['printreportprofessional'] = 'Vista profesional para imprimir';
$string['printreportnow'] = 'Imprimir reporte ahora';
$string['backtoreportscreen'] = 'Volver al reporte en pantalla';
$string['printreporthint_professional'] = 'Use esta vista para imprimir o guardar como PDF. El diseño conserva colores, tarjetas, gráficos y tablas en una estructura preparada para hoja A4.';


$string['student_print_view'] = 'Vista profesional para imprimir';
$string['student_report_print_title'] = 'Reporte individual del estudiante';
$string['student_report_print_eyebrow'] = 'Versión profesional para impresión y PDF';
$string['student_report_print_desc'] = 'Documento individual de seguimiento académico preparado para impresión, archivo PDF y respaldo documental del caso del estudiante.';
$string['student_report_document_label'] = 'Tipo de documento';
$string['student_report_document_value'] = 'Reporte individual de seguimiento del estudiante';
$string['student_print_hint'] = 'Use esta vista para imprimir o guardar como PDF el reporte individual del estudiante con una presentación profesional y sin elementos ajenos al documento.';
$string['back_to_student_panel'] = 'Volver al panel del estudiante';
$string['student_report_hint_risk'] = 'Lectura actual del riesgo académico del caso.';
$string['student_report_hint_priority'] = 'Nivel de atención sugerido para continuidad del proceso.';
$string['student_report_hint_followup'] = 'Estado actual del acompañamiento registrado.';
$string['student_report_hint_evidence'] = 'Consistencia de la evidencia disponible para la lectura del caso.';
$string['student_report_traceability_desc'] = 'Síntesis de la trazabilidad académica reciente del estudiante dentro del curso.';
$string['student_report_progress_desc'] = 'Resumen ejecutivo del avance registrado sobre actividades evaluables del curso.';
$string['student_report_recommendation_desc'] = 'Orientación principal para continuidad del proceso y priorización de acciones.';
$string['student_report_footer'] = 'Reporte individual generado por VNotificator.';

$string['analyticreading'] = 'Lectura analítica';
$string['prioritycases'] = 'Casos priorizados';
$string['institutionaltracking'] = 'Seguimiento institucional';

// Metadatos de privacidad añadidos en V9.4.1.
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
