[← Volver al índice](README.md)

# Dashboard del usuario

El dashboard muestra al usuario su avance en el programa curricular asignado.

## Acceso

- Desde el perfil del usuario: enlace **Mi currículo**.
- URL directa: `/local/curriculum/index.php`
- Para ver el currículo de otro usuario (requiere permiso `viewother`): `/local/curriculum/index.php?userid=ID`

## Contenido del dashboard

### Programa actual

Si el usuario tiene ciclos activos (sin fecha de fin), se muestra:

- **Nombre del programa y versión.**
- **Línea de tiempo:** barra visual con la proporción de cada ciclo y el progreso en tiempo.
- **Días transcurridos y restantes.**
- **Ciclos:** se muestran como pasos numerados. Cada uno indica su estado:
  - **Completado** — se reemplaza el número por un ícono de check.
  - **En progreso** — muestra el número del paso y el porcentaje de avance.
  - **Bloqueado** — muestra solo el número del paso.
- **Cursos por ciclo:** cada ciclo muestra sus cursos con:
  - Enlace al curso.
  - Barra de progreso (si el curso tiene completitud habilitada).
  - Estado: completado, en progreso, no iniciado o bloqueado.

### Historial

Si el usuario tuvo programas anteriores (ciclos con fecha de fin), se muestran en una sección de historial con la razón de finalización:

| Razón | Significado |
|---|---|
| Completado | Completó todos los cursos |
| Cambio de programa | Se cambió el programa en su perfil |
| Usuario eliminado | La cuenta fue eliminada |
| Homologado | El ciclo fue homologado |
