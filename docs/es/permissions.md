[← Volver al índice](README.md)

# Permisos

El componente define las siguientes capacidades:

| Capacidad | Tipo | Descripción | Rol por defecto |
|---|---|---|---|
| `local/curriculum:manage` | Escritura | Gestionar programas, versiones, ciclos y elementos | Manager |
| `local/curriculum:configurecustomfields` | Escritura | Configurar campos personalizados de programas | Manager |
| `local/curriculum:viewreport` | Lectura | Ver el reporte de usuarios por ciclo | Manager |
| `local/curriculum:view` | Lectura | Ver su propio currículo (dashboard) | Usuario autenticado |
| `local/curriculum:viewother` | Lectura | Ver el currículo de otros usuarios | Manager |

Todas operan a nivel de **contexto de sistema**.
