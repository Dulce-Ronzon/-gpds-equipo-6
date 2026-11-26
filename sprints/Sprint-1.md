Sprint-1.md
# Sprint 1 — Sistema Taquería Novillero

## 1. Meta del Sprint
* **Meta:** Automatizar y consolidar los procesos clave de operación interna de la Taquería Novillero, integrando el control de asistencia del personal y la gestión y auditoría de precios del menú con validación estricta de roles.
* **Cumplimiento:** Sí, la meta se cumplió al 100%, logrando entregar los módulos funcionales validados en entorno local y sincronizados en el repositorio del equipo.

## 2. Roles del Sprint 1
* **Product Owner:** Raul Guemes Mosqueda
* **Scrum Master:** Dulce Dayana Ronzón Beltrán
* **Development Team:** Integración de módulos PHP, estructura de base de datos MySQL, control de versiones en GitHub y pruebas locales en XAMPP.
  * Angel Zaith, Alan Benjamin

## 3. Planeación y Revisión
* **Planeación:** Se seleccionaron las historias de usuario del Sprint 1 para estructurar las tablas de MySQL y desarrollar las funcionalidades operativas en PHP con control de sesión por rol.
* **Revisión (Review):** Se realizó la demostración en vivo de las historias terminadas ante el grupo, mostrando el Control de Asistencia (`HU01 / ATN-16`) y la Auditoría de Cambios de Precios (`HU03 / ATN-3`), comprobando la persistencia en la base de datos y registrando la aceptación con comentarios del Product Owner en Jira.

## 4. Métricas y «Análisis del sprint»
* **Pronóstico (martes 17):** Registrado en el work item *Análisis del sprint* en Jira.
* **Puntos comprometidos:** Registrados al inicio del Sprint 1 en Jira.
* **Puntos terminados:** `[X]` story points (y registrado al cierre del Sprint 1 en Jira).
* **Velocidad del sprint:** Calculada al cerrar el Sprint 1 con el botón *Complete sprint* en Jira.

## 5. Ideas del Sprint (Evaluación)

| Idea | Descripción | Aceptada / No aceptada | Comentario |
| :--- | :--- | :--- | :--- |
| Bitácora automática de precios (`ATN-3`) | Registrar precio anterior, precio nuevo, usuario que modificó y fecha/hora en `historial_precios` al editar un producto. | Aceptada | Implementada en `cambiar_precios.php` con restricción exclusiva para rol de administrador. |
| Registro de entradas y salidas (`ATN-16`) | Permitir marcar asistencia del personal y consultar el historial reciente en base de datos. | Aceptada | Integrada y corregida en `control_asistencia.php` para evitar errores de columnas. |
| Validación centralizada de sesiones | Verificar sesión y rol (`administrador` / `trabajador`) antes de ejecutar acciones sensibles. | Aceptada | Mejora la seguridad y evita accesos indebidos entre paneles. |
| Exportación directa de reportes a PDF | Descargar reportes en PDF desde el panel administrativo en el Sprint 1. | No aceptada | Se pospuso para el siguiente sprint para priorizar la estabilidad de los módulos base. |

## 6. Propuestas para el Siguiente Sprint y Acuerdo de Retrospectiva
* **Propuesta de Product Owner:** Alan Benjamin Crisanto Diaz.
* **Propuesta de Scrum Master:** Angel Zaith Arriola Montalvo.
* **Acuerdo de retrospectiva:** Mantener actualizado el script SQL en el repositorio cada vez que cambie la estructura de tablas, revisar los *Pull Requests* con anticipación y completar las mejoras pendientes de interfaz y reportes en el siguiente sprint (creado en Jira y dejado sin iniciar).

## 7. Cierre
* **Release:** `v1.0` publicado con notas en GitHub.
* **Sprint en Jira:** Cerrado con *Complete sprint* durante la sesión de revisión en clase.
