[← Volver al índice](README.md)

# Elementos de ciclo

Un elemento vincula cursos a un ciclo. Define qué cursos debe completar el usuario en esa etapa.

## Campos principales

| Campo | Descripción |
|---|---|
| Código del curso (`coursecode`) | Código identificador del curso en Moodle (`idnumber`) |
| Plantilla de grupo (`grouptemplate`) | Nombre del grupo al que se asignará el usuario en el curso (opcional) |

## Código de curso

El campo `coursecode` busca cursos por su campo `idnumber` en Moodle.

- **Exacto:** `CURSO-001` → busca el curso con ese idnumber exacto.
- **Con comodín:** `CURSO-%` → busca todos los cursos cuyo idnumber empiece con `CURSO-`.

Esto permite que un solo elemento vincule varios cursos.

## Plantilla de grupo

Si se define `grouptemplate`, al matricular al usuario en el curso se le asigna a un grupo con ese nombre.

### Placeholders disponibles

Se reemplazan con datos del perfil del usuario:

| Placeholder | Valor |
|---|---|
| `{institution}` | Institución del usuario |
| `{department}` | Departamento |
| `{city}` | Ciudad |
| `{country}` | País |
| `{lang}` | Idioma |
| `{profile_field_X}` | Campo personalizado de perfil (donde X es el nombre corto del campo) |

### Ejemplo

Si `grouptemplate` es `Grupo-{department}` y el departamento del usuario es "Ventas", se asignará al grupo `Grupo-Ventas`.

Si el grupo no existe y la creación automática está activada, se crea automáticamente.
