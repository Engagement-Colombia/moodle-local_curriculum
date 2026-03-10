[← Volver al índice](README.md)

# Programas

Un programa es el nivel más alto de la estructura curricular. Representa un plan de formación completo.

## Gestión

Se accede desde **Administración del sitio > Cursos > Currículo > Gestionar currículo**.

### Campos del formulario

| Campo | Descripción |
|---|---|
| Nombre | Nombre del programa (obligatorio) |
| Descripción | Descripción del programa |
| Estado | Habilitado o Deshabilitado |

Además, en el formulario aparecerán los [campos personalizados](custom-fields.md) que se hayan configurado para los programas.

### Crear un programa

1. Hacer clic en **Nuevo**.
2. Completar los campos del formulario.
3. Guardar.

### Editar o eliminar

Desde la lista de programas, usar las acciones disponibles en cada fila.
Un programa no podrá ser eliminado si tiene Versiones asociadas.

## Estructura jerárquica

```
Programa
 └── Versión
      └── Ciclo
           └── Elemento (cursos)
```

Un programa puede tener varias versiones, pero solo una estará activa en un momento dado.
