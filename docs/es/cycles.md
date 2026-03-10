[← Volver al índice](README.md)

# Ciclos

Un ciclo es una etapa dentro de una versión. Los usuarios avanzan por los ciclos de forma secuencial.

## Campos principales

| Campo | Descripción |
|---|---|
| Nombre | Nombre del ciclo |
| Descripción | Descripción opcional |
| Duración | Duración en días del ciclo |
| Etapa (`stage`) | Orden del ciclo dentro de la versión (menor = primero) |

## Activación de ciclos

Cuando un usuario es asignado al primer ciclo de un programa, los siguientes ciclos se activan automáticamente según el tiempo acumulado:

```
Tiempo de activación del ciclo N = fecha de inicio + suma de duraciones de los ciclos anteriores
```

Una tarea programada (`activate_user_cycles`) se ejecuta diariamente a la 1:00 AM y se encarga de crear las asignaciones de los ciclos que ya deben estar activos.

## Estados de un ciclo para el usuario

| Estado | Significado |
|---|---|
| **Activo** | El usuario está cursando este ciclo |
| **Completado** | El usuario terminó todos los cursos del ciclo |
| **Bloqueado** | Aún no se alcanzó la fecha de activación |

## Gestión

Desde la vista de una versión, se pueden agregar, editar y eliminar ciclos.
Los ciclos no se pueden eliminar si tienen elementos asociados.
