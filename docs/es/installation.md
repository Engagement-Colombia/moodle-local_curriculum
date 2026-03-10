[← Volver al índice](README.md)

# Instalación y configuración

## Requisitos

- Moodle 4.5 o superior.
- Componente de matrícula [`enrol_curriculum`](https://github.com/Engagement-Colombia/moodle-enrol_curriculum) instalado (se usa para matricular usuarios automáticamente en los cursos del programa según el ciclo y sus elementos).
- Componente de campo de perfil de usuario [`profilefield_curriculums`](https://github.com/Engagement-Colombia/moodle-profilefield_curriculum/) instalado (se usa para asociar a los usuarios con un programa del currículo).

## Instalación

1. Copiar la carpeta del componente en `/local/curriculum/`.
2. Ir a **Administración del sitio > Notificaciones** para completar la instalación.

## Ajustes generales

Se encuentran en **Administración del sitio > Cursos > Currículo > Configuración**.

### Crear grupos automáticamente

- **Opción:** `autocreategroups`
- Si está activada, cuando un usuario es asignado a un curso que tiene plantilla de grupo en el elemento de ciclo, el grupo se crea automáticamente en el curso si no existe.
- Si está desactivada, se envía una notificación por correo indicando que el grupo debe crearse manualmente.

### Correos de notificación de nuevo grupo

- **Opción:** `newgroupnotifyemails`
- Lista de direcciones de correo (una por línea) que recibirán una notificación cuando se cree un grupo nuevo o cuando se requiera crear uno manualmente.

## Campo de perfil de usuario

Para vincular usuarios a programas se utiliza un campo de perfil personalizado de tipo `curriculum`.

Cuando se crea o actualiza un usuario y su campo de perfil tiene un programa asignado, el sistema automáticamente:

1. Inicia el primer ciclo del programa para ese usuario.
2. Lo matricula en los cursos correspondientes.

Si el programa cambia en el perfil, los ciclos del programa anterior se cierran automáticamente.
