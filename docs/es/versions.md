[← Volver al índice](README.md)

# Versiones

Una versión representa una edición o iteración de un programa. Permite modificar la estructura de ciclos sin afectar a usuarios que ya están en una versión anterior.

## Campos principales

| Campo | Descripción |
|---|---|
| Nombre | Nombre de la versión |
| Fecha de inicio | Desde cuándo está disponible |
| Fecha de fin | Hasta cuándo está disponible (opcional) |

## Versión activa

El sistema considera activa la versión que cumple:

- Su fecha de inicio ya pasó o es hoy.
- No tiene fecha de fin, o su fecha de fin aún no llega.

Si hay varias que cumplen estas condiciones, se usa la de fecha de inicio más reciente.

## Gestión

Desde la vista de gestión de un programa, se pueden agregar, editar y eliminar versiones.
Una versión no puede ser eliminada si tiene ciclos asociados.
