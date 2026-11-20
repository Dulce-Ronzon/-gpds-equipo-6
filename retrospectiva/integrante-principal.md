integrante-principal.md
# Retrospectiva Individual — Sprint 1 (Taquería Novillero)

* **Sprint:** Sprint 1
* **Claves de Jira / Historias:** `HU03 / ATN-3` (Auditoría de cambios de precios) y `HU01 / ATN-16` (Control de asistencia)
* **Pull Request:** Vinculado con clave de Jira hacia `main`

## Mi experiencia en el sprint
En este sprint trabajé en la implementación y corrección del módulo de cambio y auditoría de precios (`cambiar_precios.php`) con registro en `historial_precios` y restricción para administrador, además de ajustar `control_asistencia.php` para que funcionara sin errores con la estructura de la base de datos. Al integrar los cambios descargados del repositorio tuve que corregir errores de columnas y probar varias veces en XAMPP, lo que me dejó un aprendizaje muy práctico sobre integración y depuración.

## Mi idea para mejorar en el siguiente sprint
Subir y mantener un archivo SQL actualizado en el repositorio cada vez que cambie la estructura de la base de datos, para que todos tengamos las mismas tablas y columnas sin perder tiempo corrigiendo errores de esquema.